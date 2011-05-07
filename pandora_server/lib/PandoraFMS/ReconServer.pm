package PandoraFMS::ReconServer;
##########################################################################
# Pandora FMS Recon Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2009 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use strict;
use warnings;

use threads;
use threads::shared;
use Thread::Semaphore;

use IO::Socket::INET;
use NetAddr::IP;
use POSIX qw(strftime ceil);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;
use PandoraFMS::GIS qw(get_reverse_geoip_sql get_reverse_geoip_file get_random_close_point);

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared = Thread::Semaphore->new;
my $TaskSem :shared = Thread::Semaphore->new (0);
my $TracerouteAvailable = (eval 'use PandoraFMS::Traceroute::PurePerl; 1') ? 1 : 0;

########################################################################################
# Recon Server class constructor.
########################################################################################
sub new ($$$$$$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'reconserver'} == 1;

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, 3, \&PandoraFMS::ReconServer::data_producer, \&PandoraFMS::ReconServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting Pandora FMS Recon Server.", 1);
	$self->setNumThreads ($pa_config->{'recon_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;

	my $server_id = get_server_id ($dbh, $pa_config->{'servername'}, $self->getServerType ());
	return @tasks unless defined ($server_id);

	# Manual tasks have interval_sweep = 0
	# Manual tasks are "forced" like the other, setting the utimestamp to 1
	# By default, after create a tasks it takes the utimestamp to 0
	# Status -1 means "done".

	my @rows = get_db_rows ($dbh, 'SELECT * FROM trecon_task 
                                   WHERE id_recon_server = ? 
                                   AND (utimestamp = 1) OR (status = -1 AND interval_sweep > 0 AND (utimestamp = 0 OR (utimestamp + interval_sweep) < UNIX_TIMESTAMP()))', $server_id);
	foreach my $row (@rows) {

		# Update task status
		update_recon_task ($dbh, $row->{'id_rt'}, 1);

		push (@tasks, $row->{'id_rt'});
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $task_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	# Get recon task data	
	my $task = get_db_single_row ($dbh, 'SELECT * FROM trecon_task WHERE id_rt = ?', $task_id);	
	return -1 unless defined ($task);

	# Fixed config here. SANCHO TODO
	$task->{'os_detect'} = 1;
	$task->{'resolve_names'} = 1;
	$task->{'parent_detection'} = 1;
	$task->{'parent_recursion'} = 5;

	# Is it a recon script?
	if ((defined ($task->{'id_recon_script'})) && ($task->{'id_recon_script'} != 0)) {
		exec_recon_script ($pa_config, $dbh, $task);
		return;
	}

	# Get a NetAddr::IP object for the target network
	my $net_addr = new NetAddr::IP ($task->{'subnet'});
	if (! defined ($net_addr)) {
		logger ($pa_config, "Invalid network " . $task->{'subnet'} . " for task '" . $task->{'name'} . "'.", 3);
		update_recon_task ($dbh, $task_id, -1);
		return -1;
	}

	# Scan the network for hosts
	my ($total_hosts, $hosts_found, $addr_found) = ($net_addr->num, 0, '');
	for (my $i = 1, $net_addr++; $net_addr < $net_addr->broadcast; $i++, $net_addr++) {

		my $addr = (split(/\//, $net_addr))[0];
		
		# Update the recon task or break if it does not exist anymore
		last if (update_recon_task ($dbh, $task_id, ceil ($i / ($total_hosts / 100))) eq '0E0');
       
		# Does the host already exist?
		my $current_agent_id = get_agent_from_addr ($dbh, $addr);

		if ($current_agent_id > 0) {
			my $current_agent = get_db_single_row ($dbh, 'SELECT * FROM tagente wHERE id_agente = ?', $current_agent_id);

			# Skip existing hosts with a known parent
			if ($current_agent->{'id_parent'} != 0){
				next;
			}
			
			# Skip existing hosts with learnmode disabled
			if ($current_agent->{'modo'} != 1){
				next;
			}
			

		} else {		
			my $alive = 0;
			if (pandora_ping ($pa_config, $addr) == 1) {
				$alive = 1;

				# TCP Port profiling 
				if ((defined ($task->{'recon_ports'})) && ($task->{'recon_ports'} ne "")) {
					$alive = tcp_scan ($pa_config, $addr, $task->{'recon_ports'});
				}
			}

			# Skip non-responsive hosts
			next unless ($alive > 0);
		}

		$hosts_found ++;

		# Implement OS detection (optional)
		my $id_os = 11; # By default now is a network host
		if ($task->{'os_detect'} == 1){
			# Guess the OS and filter
			$id_os = guess_os ($pa_config, $addr);
			if ($task->{'id_os'} > 0 && $task->{'id_os'} != $id_os) {
				logger($pa_config, "Skipping host $addr os ID $id_os.", 10);
				next;
			}
		}

		# This string will contain all addresses separated with blank spaces (for incident)
		$addr_found .= $addr . " ";

		# By default, hostname is the IP address
		my $host_name = $addr;

		# Get the parent host
		my $parent_id = 0;
		if ($task->{'parent_detection'} == 1){
			logger($pa_config, "Getting the parent for host $addr", 5);
			$parent_id = get_host_parent ($pa_config, $addr, $dbh, $task->{'id_group'}, $task->{'parent_recursion'}, $task->{'resolve_names'}, $task->{'os_detect'});
		}

		# Update parent only if agent already exist, and skip rest of iteration
		if ($current_agent_id > 0) {
			# Skip existing hosts with a known parent
			if ($parent_id > 0){
				db_do ($dbh, 'UPDATE tagente SET id_parent = ? WHERE id_agente = ?', $parent_id  ,$current_agent_id );
			}
			next;
		}

		# Resolve names is optional (performance killer!)
		if ($task->{'resolve_names'} == 1){
			# Resolve the address
			$host_name = gethostbyaddr (inet_aton($addr), AF_INET);
		}

		# Add the new address if it does not exist
		my $addr_id = get_addr_id ($dbh, $addr);
		$addr_id = add_address ($dbh, $addr) unless ($addr_id > 0);
		if ($addr_id <= 0) {
			logger($pa_config, "Could not add address '$addr' for host '$host_name'.", 3);
			next;
		}

		my $agent_id;

        # GIS Code -----------------------------

		# If GIS is activated try to geolocate the ip address of the agent 
        # and store also it's position.

		if($pa_config->{'activate_gis'} == 1 && $pa_config->{'recon_reverse_geolocation_mode'} !~ m/^disabled$/i) {

			# Try to get aproximated positional information for the Agent.
			my $region_info = undef;
			if ($pa_config->{'recon_reverse_geolocation_mode'} =~ m/^sql$/i) {
				logger($pa_config, "Trying to get gis data of $addr from the SQL database", 8);
				$region_info = get_reverse_geoip_sql($pa_config, $addr, $dbh);	
			}
			elsif ($pa_config->{'recon_reverse_geolocation_mode'} =~ m/^file$/i) {
				logger($pa_config, "Trying to get gis data of $addr from the file database", 8);
				$region_info = get_reverse_geoip_file($pa_config, $addr);	
			}
			else {
				logger($pa_config, "ERROR:Trying to get gis data of $addr. Unknown source", 5);
			}
			if (defined($region_info))  {
				my $location_description = '';
				if (defined($region_info->{'region'})) {
					$location_description .= "$region_info->{'region'}, ";
				}
				if (defined($region_info->{'city'})) {
					$location_description .= "$region_info->{'city'}, ";
				}
				if (defined($region_info->{'country_name'})) {
					$location_description .= "($region_info->{'country_name'})";
				}
				# We store a random offset in the coordinates to avoid all the agents apear on the same place.
				my ($longitude, $latitude) = get_random_close_point ($pa_config, $region_info->{'longitude'}, $region_info->{'latitude'});
				
				logger($pa_config, "Placing agent on random position (Lon,Lat)  =  ($longitude, $latitude)", 8);
				# Crate a new agent adding the positional info (as is unknown we set 0 time_offset, and 0 altitude)
				$agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'},
					                                  $host_name, $addr, $task->{'id_group'}, 
									  $parent_id, $id_os, '', 300, $dbh, 0, 
								          $longitude, $latitude, 0, $location_description);
			}
			else {
				logger($pa_config,"Id location of '$addr' for host '$host_name' NOT found", 3);
				# Create a new agent
				$agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'},
					                                  $host_name, $addr, $task->{'id_group'},
									  $parent_id, $id_os, '', 300, $dbh);
			}
		}

		# End of GIS code -----------------------------      

		else {	
			# Create a new agent
			$agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'},
					                                  $host_name, $addr, $task->{'id_group'},
									  $parent_id, $id_os, '', 300, $dbh);
		}

		# Assign the new address to the agent
		db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
		                  VALUES (?, ?)', $addr_id, $agent_id);

		# Create network profile modules for the agent
		create_network_profile_modules ($pa_config, $dbh, $agent_id, $task->{'id_network_profile'}, $addr, $task->{'snmp_community'});

		# Generate an event
        pandora_event ($pa_config, "[RECON] New host [$host_name] detected on network [" . $task->{'subnet'} . ']',
                       $task->{'id_group'}, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $dbh);
	}
	# End of recon sweep main loop


	# Create an incident with totals

	if ($hosts_found > 0 && $task->{'create_incident'} == 1){
		my $text = "At " . strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " ($hosts_found) new hosts were detected by Pandora FMS Recon Server running on [" . $pa_config->{'servername'} . "_Recon]. This incident has been automatically created following instructions for this recon task [" . $task->{'id_group'} . "].\n\n";
		if ($task->{'id_network_profile'} > 0) {
			$text .= "Aditionally, and following instruction for this task, agent(s) has been created, with modules assigned to network component profile [" . get_nc_profile_name ($dbh, $task->{'id_network_profile'}) . "]. Please check this agent as soon as possible to verify it.";
		}
		$text .= "\n\nThis is the list of IP addresses found: \n\n$addr_found";
		pandora_create_incident ($pa_config, $dbh, "[RECON] New hosts detected", $text, 0, 0, 'Pandora FMS Recon Server', $task->{'id_group'});
	}

	# Mark recon task as done
	update_recon_task ($dbh, $task_id, -1);
}


##########################################################################
# Returns the ID of the parent of the given host if available.
##########################################################################	
sub get_host_parent; # Need to define first, because is a recursive function
sub get_host_parent ($$$$$$){
	my ($pa_config, $host, $dbh, $group, $max_depth, $resolve, $os_detect) = @_;

	# Recursive exit condition
	if ($max_depth == 0){
		return 0;
	}

	if ($TracerouteAvailable == 0){
		logger($pa_config, "Traceroute is not available, skipping get_parent for $host", 10);
		return 0;
		# Traceroute not available
	}

	my $traceroutetimeout = $pa_config->{'networktimeout'};


	# TTL is not configurable, you can get problems with this if too low
	# Timeout is 20% more than normal network timeout

	my $tr = PandoraFMS::Traceroute::PurePerl->new (
		backend        => 'PurePerl',
		host           => $host,
		debug          => 0,
		max_ttl        => 25,   
		query_timeout  => ceil ($traceroutetimeout * 1.2),
		packetlen      => 150,
		protocol       => 'icmp', # udp or icmp
	);

	my $success = 0;

	# Do the traceroute	
	$success = $tr->traceroute();

	# Error or timeout
	if ($@){
		return 0;
	}

	# Traceroute was not successful
	if ($tr->hops < 2 || $success == 0){
		return 0;
	}

	my $hopstotal = $tr->hops;
	$hopstotal--;
	
	# Run all list of parents until find a known parent
	my $parent_addr;
	my $parent_addr_check = 0;

	for (my $ax = $hopstotal; $ax >= 0; $ax--){
		$parent_addr = $tr->hop_query_host($ax, 0);

		if ($host eq $parent_addr){
			last;
		}			

		if ($parent_addr ne $host){
			$parent_addr_check  = get_agent_from_addr ($dbh, $parent_addr);

			if ($parent_addr_check != -1){
				return $parent_addr_check;
			}
			else {
				last;
			}
		} else {
			# Parent cannot be host, that means 0 hops or no parents ... skipping
			return 0;
		}
	}


	# If no parent found, create the last parent. It will greate a new agent
	# for inmediate parentship, and call again the get_host_parent recursively 
	# until found the whole connection. For safety this have implemented
	# a max numbers of hops to build the chain (max_depth).

	# Create a new agent if we get a valid parent
	if ($parent_addr_check == -1){

		# Add the new address if it does not exist
		my $addr_id = get_addr_id ($dbh, $parent_addr);
		$addr_id = add_address ($dbh, $parent_addr) unless ($addr_id > 0);
		if ($addr_id <= 0) {
			# This should never happen ! :(
			logger($pa_config, "Could not add address '$parent_addr'", 1);
			return 0;
		}

		# Recursive call - Limiting with ($max_depth -1)
		my $parent_parent = get_host_parent ($pa_config, $parent_addr, $dbh, $group, $max_depth-1, $resolve, $os_detect);

		my $parent_name = $parent_addr;
		if ($resolve == 1){
			# Resolve the address
			$parent_name = gethostbyaddr(inet_aton($parent_addr), AF_INET);
		}

		# Implement OS detection (optional)
		my $id_os = 11;
		if ($os_detect == 1){
			# Guess the OS and filter
			$id_os = guess_os ($pa_config, $parent_addr);
		}

		my $agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'}, $parent_name, $parent_addr, $group, $parent_parent, $id_os, '', 300, $dbh);

		# Assign the new address to the agent
		db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
			          VALUES (?, ?)', $addr_id, $agent_id);

		return $agent_id;
	}

	return 0;	
}

##############################################################################
# TCP scan the given host/port. Returns 1 if successful, 0 otherwise.
##############################################################################
sub tcp_scan ($$$) {
	my ($pa_config, $host, $portlist) = @_;
	my $runcommand;
	
	my $nmap = $pa_config->{'nmap'};
    eval {
		$runcommand = `$nmap -p$portlist $host | grep open | wc -l`;
	};
	return 0 if ($@);
	return $runcommand;
}

##########################################################################
# Guess OS using xprobe2.
##########################################################################
sub guess_os {
    my ($pa_config, $host) = @_;
    my $nmap = $pa_config->{'nmap'};
	my $xprobe = $pa_config->{'xprobe2'};

    # if xprobe2 not available, use nmap, if not, not able to detect OS	
	if (! -e $xprobe){
	    return 10 if (! -e $nmap);
	}
	
	# Execute Nmap (4.x) or Xprobe2
    my $output = ''; 
    eval {
    	if (-e $xprobe){
    		$output = `$xprobe $host 2> /dev/null | grep 'Running OS' | head -1`;
    	} else {
			$output = `$nmap -F -O $host 2> /dev/null | grep 'Aggressive OS guesses'`;
    	}
    };

	# Check for errors
    return 10 if ($@);
	return pandora_get_os ($output);
}

##########################################################################
# Return the ID of the given address, -1 if it does not exist.
##########################################################################
sub get_addr_id ($$) {
	my ($dbh, $addr) = @_;

	my $addr_id = get_db_value ($dbh, 'SELECT id_a FROM taddress WHERE ip = ?', $addr);
	return (defined ($addr_id) ? $addr_id : -1);
}

##########################################################################
# Return the ID of the agent with the given IP.
##########################################################################
sub get_agent_from_addr ($$) {
	my ($dbh, $ip_address) = @_;

	return 0 if (! defined ($ip_address) || $ip_address eq '');

	my $agent_id = get_db_value ($dbh, 'SELECT id_agent FROM taddress, taddress_agent, tagente
	                                    WHERE tagente.id_agente = taddress_agent.id_agent
	                                    AND taddress_agent.id_a = taddress.id_a
	                                    AND ip = ?', $ip_address);
	return (defined ($agent_id)) ? $agent_id : -1;
}

##########################################################################
# Update recon task status.
##########################################################################
sub update_recon_task ($$$) {
	my ($dbh, $id_task, $status) = @_;

	db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $status, $id_task);
}

##########################################################################
# Add the given address to taddress.
##########################################################################
sub add_address ($$) {
	my ($dbh, $ip_address) = @_;

	return db_insert ($dbh, 'id_a', 'INSERT INTO taddress (ip) VALUES (?)', $ip_address);
}

##########################################################################
# Create network profile modules for the given agent.
##########################################################################
sub create_network_profile_modules {
	my ($pa_config, $dbh, $agent_id, $np_id, $addr, $snmp_community) = @_;
	
	return unless ($np_id > 0);

	# Get network components associated to the network profile
	my @np_components = get_db_rows ($dbh, 'SELECT * FROM tnetwork_profile_component WHERE id_np = ?', $np_id);

	foreach my $np_component (@np_components) {

		# Get network component data
		my $component = get_db_single_row ($dbh, 'SELECT * FROM tnetwork_component wHERE id_nc = ?', $np_component->{'id_nc'});
		if (! defined ($component)) {
			logger($pa_config, "Network component ID " . $np_component->{'id_nc'} . " for agent $addr not found.", 3);
			next;
		}

		logger($pa_config, "Processing network component '" . $component->{'name'} . "' for agent $addr.", 10);

        # Use snmp_community from network task instead the component snmp_community
        $component->{'snmp_community'} = $snmp_community;

		# Create the module
		my $module_id = db_insert ($dbh, 'id_agente_modulo', 'INSERT INTO tagente_modulo (id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, tcp_port, tcp_send, tcp_rcv, snmp_community, snmp_oid, ip_target, id_module_group, flag, disabled, plugin_user, plugin_pass, plugin_parameter, max_timeout, id_modulo )
		                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?, ?, ?)',
		                                             $agent_id, $component->{'type'}, $component->{'description'}, safe_input($component->{'name'}), $component->{'max'}, $component->{'min'}, $component->{'module_interval'}, $component->{'tcp_port'}, $component->{'tcp_send'}, $component->{'tcp_rcv'}, $component->{'snmp_community'},
		                                             $component->{'snmp_oid'}, $addr, $component->{'id_module_group'}, $component->{'plugin_user'}, $component->{'plugin_pass'}, $component->{'plugin_parameter'}, $component->{'max_timeout'}, $component->{'id_modulo'});

		# An entry in tagente_estado is necessary for the module to work
        	db_do ($dbh, 'INSERT INTO tagente_estado (`id_agente_modulo`, `id_agente`, `last_try`, current_interval) VALUES (?, ?, \'1970-01-01 00:00:00\', ?)', $module_id, $agent_id, $component->{'module_interval'});

		logger($pa_config, 'Creating module ' . $component->{'name'} . " for agent $addr from network component '" . $component->{'name'} . "'.", 10);
	}
}

##########################################################################
# Executes recon scripts
##########################################################################	
sub exec_recon_script ($$$) {
	my ($pa_config, $dbh, $task) = @_;
	
	# Get recon plugin data	
	my $script = get_db_single_row ($dbh, 'SELECT * FROM trecon_script WHERE id_recon_script = ?', $task->{'id_recon_script'});
	return -1 unless defined ($script);

	logger($pa_config, 'Executing recon script ' . safe_output($script->{'name'}), 10);

	my $command = safe_output($script->{'script'});
	my $field1 = safe_output($task->{'field1'}); 
	my $field2 = safe_output($task->{'field2'});
	my $field3 = safe_output($task->{'field3'}); 
	my $field4 = safe_output($task->{'field4'});

	`$command $task->{'id_rt'} $task->{'id_group'} $task->{'create_incident'} $field1 $field2 $field3 $field4`;

	# Notify this recon task is ended
	update_recon_task ($dbh, $task->{'id_rt'}, -1);

	return 0;
}

1;
__END__
