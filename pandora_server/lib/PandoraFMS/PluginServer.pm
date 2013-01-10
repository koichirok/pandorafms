package PandoraFMS::PluginServer;
##########################################################################
# Pandora FMS Plugin Server.
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

use POSIX qw(strftime);
use HTML::Entities;
use JSON qw(decode_json);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared = Thread::Semaphore->new;
my $TaskSem :shared = Thread::Semaphore->new (0);

########################################################################################
# Plugin Server class constructor.
########################################################################################
sub new ($$;$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'pluginserver'} == 1;

	# Check for plugin_exec
	if (! -x $config->{'plugin_exec'}) {
		logger ($config, ' [E] ' . $config->{'plugin_exec'} . ' not found. Plugin Server not started.', 1);
		print_message ($config, ' [E] ' . $config->{'plugin_exec'} . ' not found. Plugin Server not started.', 1);
		return undef;
	}
		
	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, 4, \&PandoraFMS::PluginServer::data_producer, \&PandoraFMS::PluginServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print_message ($pa_config, " [*] Starting Pandora FMS Plugin Server.", 1);
	$self->setNumThreads ($pa_config->{'plugin_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;
	my @rows;

	if ($pa_config->{'pandora_master'} != 1) {
		@rows = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.flag, tagente_estado.current_interval + tagente_estado.last_execution_try AS time_left, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado
			WHERE server_name = ?
			AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente.disabled = 0
			AND tagente_modulo.id_plugin != 0
			AND tagente_modulo.disabled = 0
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND (tagente_modulo.flag = 1 OR (tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())
			ORDER BY tagente_modulo.flag DESC, time_left ASC, last_execution_try ASC', $pa_config->{'servername'});
    } else {
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo), tagente_modulo.flag, tagente_estado.current_interval + tagente_estado.last_execution_try AS time_left, last_execution_try
			FROM tagente, tagente_modulo, tagente_estado
			WHERE ((server_name = ?) OR (server_name = ANY(SELECT name FROM tserver WHERE status = 0)))
			AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente.disabled = 0
			AND tagente_modulo.disabled = 0
			AND tagente_modulo.id_plugin != 0
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND (tagente_modulo.flag = 1 OR (tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())
			ORDER BY tagente_modulo.flag DESC, time_left ASC, last_execution_try ASC', $pa_config->{'servername'});
	}

	foreach my $row (@rows) {
		
		# Reset forced execution flag
		if ($row->{'flag'} == 1) {
			db_do ($dbh, 'UPDATE tagente_modulo SET flag = 0 WHERE id_agente_modulo = ?', $row->{'id_agente_modulo'});
		}

		push (@tasks, $row->{'id_agente_modulo'});
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $module_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	# Retrieve module data
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $module_id);
	return unless defined $module;

	# Retrieve plugin data
	my $plugin = get_db_single_row ($dbh, 'SELECT * FROM tplugin WHERE id = ?', $module->{'id_plugin'});
	return unless defined $plugin;

	# Use the smallest timeout
	my $timeout = ($plugin->{'max_timeout'} < $pa_config->{'plugin_timeout'}) ?
				   $plugin->{'max_timeout'} : $pa_config->{'plugin_timeout'};

	# Setting default timeout if is invalid
	if($timeout <= 0) {
		$timeout = 15;
	}
	
	# Build command to execute
	my $command = $plugin->{'execute'};
	
	my $parameters = $plugin->{'parameters'};
	
	# Macros
	eval {
		if ($module->{'macros'} ne '') {
			logger ($pa_config, "Decoding json macros from # $module_id plugin command '$command'", 10);
			my $macros = decode_json($module->{'macros'});
			my %macros = %{$macros};
			if(ref($macros) eq "HASH") {
				foreach my $macro_id (keys(%macros))
				{
					logger ($pa_config, "Replacing macro ".$macros{$macro_id}{'macro'}." by '".$macros{$macro_id}{'value'}."' in AM # $module_id ", 10);
					$parameters =~ s/$macros{$macro_id}{'macro'}/$macros{$macro_id}{'value'}/g;
				}
			}
		}
	};
	
	# If something went wrong with macros, we log it
	if ($@) {
		logger ($pa_config, "Error reading macros from module # $module_id. Probably malformed json", 10);
	}
	
	$command .= ' ' . $parameters;

	$command = decode_entities($command);

	logger ($pa_config, "Executing AM # $module_id plugin command '$command'", 9);

	# Execute command
	$command = $pa_config->{'plugin_exec'} . ' ' . $timeout . ' ' . $command;

	my $module_data;
	eval {
		$module_data = `$command`;
	};
	my $ReturnCode = ($? >> 8) & 0xff;

	if ($plugin->{'plugin_type'} == 1) {

		# Get the errorlevel if is a Nagios plugin type (parsing the errorlevel)
		# Nagios errorlevels: 	
		#('OK'=>0,'WARNING'=>1,'CRITICAL'=>2,'UNKNOWN'=>3,'DEPENDENT'=>4);

		# Numerical modules (Data) or Alphanumeric modules (String) 
		# get reported data as is, ignoring return code.
		# Boolean data, transform module data depending on returning error code.

		if ($module->{'id_tipo_modulo'} == 2){
			if ($ReturnCode == 0){
				$module_data = 1;
			} 
			elsif ($ReturnCode == 1){
				$module_data = -1;
			} 
			elsif ($ReturnCode == 2){
				$module_data = 0;
			} 
			elsif ($ReturnCode == 3 || $ReturnCode == 124){
				# 124 should be a exit code of the timeout command (command times out)
				$module_data = ''; # not defined = Uknown 
			} 
			elsif ($ReturnCode == 4){
				$module_data = 1;
			}
		}
	}

	if (! defined $module_data || $module_data eq '') {
		logger ($pa_config,"[ERROR] Undefined value returned by Plugin query. Is the server out of memory?" , 0);
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	my %data = ("data" => $module_data);
	pandora_process_module ($pa_config, \%data, '', $module, '', $timestamp, $utimestamp, $self->getServerID (), $dbh);

	my $agent_os_version = get_db_value ($dbh, 'SELECT os_version FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
	
	if ($agent_os_version eq ''){
		$agent_os_version = $pa_config->{'servername'}.'_Plugin';
	}

	pandora_update_agent ($pa_config, $timestamp, $module->{'id_agente'}, $agent_os_version, $pa_config->{'version'}, -1, $dbh);
}

1;
__END__
