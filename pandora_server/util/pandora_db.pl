#!/usr/bin/perl

###############################################################################
# Pandora FMS DB Management
###############################################################################
# Copyright (c) 2005-2008 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation;  version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,USA
###############################################################################

# Includes list
use strict;
use Time::Local;			# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use PandoraFMS::Tools;
use PandoraFMS::DB;
use POSIX qw(strftime);

# version: define la version actual del programa
my $version = "3.0-dev PS090930";

# Setup variables
my $dirname="";
my $dbname = 'pandora';
my $dbhost ='';
my $dbuser ='';
my $verbosity =0;
my $onlypurge = 0;

my $dbpass='';
my $server_threshold='';
my $log_file="";
my $pandora_path="";
my $config_days_compact;
my $config_days_purge;
my $config_step_compact;# Step compact variable defines "how-fine" is thecompact algorithm. 1 Hour its very fine, 24 hours is bad value

# FLUSH in each IO
$| = 0;

pandora_init();

# Read config file for Global variables
pandora_loadconfig ($pandora_path);

# Begin pandora_server
pandoradb_main();

###############################################################################
###############################################################################
###############################################################################
###############################################################################
###############################################################################

###############################################################################
## SUB pandora_purgedb (days, dbname, dbuser, dbpass, dbhost)
###############################################################################
sub pandora_purgedb {

	# 1) Obtain last value for date limit
	# 2) Delete all elements below date limit
	# 3) Insert last value in date_limit position

	my $days = $_[0];
	my $dbname = $_[1];
	my $dbuser = $_[2];
	my $dbpass = $_[3];
	my $dbhost = $_[4];
 	my @query;
 	my $counter;
	my $buffer; my $buffer2; my $buffer3;
	my $err; # error code in datecalc function
 	my $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",$dbuser, $dbpass,{RaiseError => 1, AutoCommit => 1 });

 	# Calculate limit for deletion, today - $days
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());

	my $ulimit_access_timestamp =  time() - 86400;
	my $ulimit_timestamp = time() - (86400 * $days);
	my $limit_timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($ulimit_timestamp));

	print "[PURGE] Deleting old event data (More than $config_days_purge days)... \n";
	$dbh->do("DELETE FROM tevento WHERE utimestamp < '$ulimit_timestamp'");

	print "[PURGE] Deleting old data... \n";
	$dbh->do ("DELETE FROM tagente_datos WHERE utimestamp < '$ulimit_timestamp'");

	print "[PURGE] Delete old data (string) ... \n";
	$dbh->do ("DELETE FROM tagente_datos_string WHERE utimestamp < '$ulimit_timestamp'");

	print "[PURGE] Delete pending deleted modules (data table)...\n";
	$dbh->do ("DELETE FROM tagente_datos WHERE id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1)");

	print "[PURGE] Delete pending deleted modules (data string table)...\n";
	$dbh->do ("DELETE FROM tagente_datos_string WHERE id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1)");
	
	print "[PURGE] Delete pending deleted modules (data inc  table)...\n";
	$dbh->do ("DELETE FROM tagente_datos_inc WHERE id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1)");
	
	print "[PURGE] Delete pending deleted modules (status, module table)...\n";
	$dbh->do ("DELETE FROM tagente_estado WHERE id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1)");
	$dbh->do ("DELETE FROM tagente_modulo WHERE delete_pending = 1");
	
	print "[PURGE] Delete old session data \n";
	$dbh->do ("DELETE FROM tsesion WHERE utimestamp < $ulimit_timestamp");

	print "[PURGE] Delete old data from SNMP Traps \n"; 
	$dbh->do ("DELETE FROM ttrap WHERE timestamp < '$limit_timestamp'");

	print "[PURGE] Deleting old access data (More than 24hr) \n";
	$dbh->do("DELETE FROM tagent_access WHERE utimestamp < '$ulimit_access_timestamp'");

    $dbh->disconnect();
}

###############################################################################
## SUB pandora_compactdb (days, dbname, dbuser, dbpass, dbhost)
###############################################################################
sub pandora_compactdb {
	my $days = $_[0];
	my $dbname = $_[1];
	my $dbuser = $_[2];
	my $dbpass = $_[3];
	my $dbhost = $_[4];

	my %count_hash;
	my %id_agent_hash;
	my %value_hash;
	my $err;

	if ($days == 0){
		return;
	}

	# Connect to the database
	my $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",$dbuser, $dbpass,
	                       { RaiseError => 1, AutoCommit => 1 });

	if ($config_step_compact < 1) {
		return;
	}

	# Compact interval length in seconds
	# $config_step_compact varies between 1 (36 samples/day) and
	# 20 (1.8 samples/day)
	my $step = $config_step_compact * 2400;

	# The oldest timestamp will be the lower limit
	my $query = "SELECT min(utimestamp) as min FROM tagente_datos";
	my $query_st = $dbh->prepare($query);
	$query_st->execute();
	if ($query_st->rows == 0) {
		return;
	}

	my $data = $query_st->fetchrow_hashref();
	my $limit_utime = $data->{'min'};
	$query_st->finish();

	if ($limit_utime < 1) {
		return;
	}

	# Calculate the start date
	my $start_utime = time() - $days * 24 * 60 * 60;
	my $start_date = strftime ("%Y-%m-%d %H:%M:%S", localtime($start_utime));
	my $stop_date;
	my $stop_utime;

	print "[COMPACT] Compacting data until $start_date\n";

	# Prepare the query to retrieve data from an interval
	$query = "SELECT * FROM tagente_datos WHERE utimestamp < ? AND
	            utimestamp >= ?";
	$query_st = $dbh->prepare($query);

	while (1) {

			# Calculate the stop date for the interval
			$stop_utime = $start_utime - $step;

			# Out of limits
			if ($start_utime < $limit_utime) {
				return;
			}

			$query_st->execute($start_utime, $stop_utime);
			$query_st->rows;
			if ($query_st->rows == 0) {
				# No data, move to the next interval
				$start_utime = $stop_utime;
				next;
			}

			# Get interval data
			while ($data = $query_st->fetchrow_hashref()) {
				my $id_module = $data->{'id_agente_modulo'};


				if (! defined($value_hash{$id_module})) {
					$value_hash{$id_module} = 0;
					$count_hash{$id_module} = 0;

					if (! defined($id_agent_hash{$id_module})) {
						$id_agent_hash{$id_module} = $data->{'id_agente'};
					}
				}

				$value_hash{$id_module} += $data->{'datos'};
				$count_hash{$id_module}++;
			}

			# Delete interval from the database
			$dbh->do("DELETE FROM tagente_datos WHERE utimestamp < $start_utime
			         AND utimestamp >= $stop_utime");

			# Insert interval average value
			foreach my $key (keys(%value_hash)) {
				$value_hash{$key} /= $count_hash{$key};
				$stop_date = strftime ("%Y-%m-%d %H:%M:%S", localtime());
				$dbh->do("INSERT INTO tagente_datos (id_agente_modulo,
					  datos, utimestamp) VALUES
				         ($key, $value_hash{$key} ,
				         $stop_utime)");

				delete($value_hash{$key});
				delete($count_hash{$key});
			}

			# Move to the next interval
			$start_utime = $stop_utime;
	}

	$query_st->finish();
	$dbh->disconnect();
}

##############################################################################
# SUB pandora_init ()
# Makes the initial parameter parsing, initializing and error checking
##############################################################################

sub pandora_init {
	print "\nPandora FMS DB Tool $version Copyright (c) 2004-2008 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n";

	# Load config file from command line
	if ($#ARGV == -1 ){
		print "FATAL ERROR: I Need at least one parameter: Complete path to pandora_server.conf file !!\n\n";
		exit;
	}
   
	# If there are not valid parameters
	my $parametro;
	my $ltotal=$#ARGV; my $ax;
	for ($ax=0;$ax<=$ltotal;$ax++){
		$parametro = $ARGV[$ax];
		if ($parametro =~ m/-h\z/i ) { help_screen();  }
			elsif ($parametro =~ m/-help\z/i ) { help_screen();  }
			elsif ($parametro =~ m/--help\z/i ) { help_screen();  }
		elsif ($parametro =~ m/-v\z/i) { $verbosity=5; }
		elsif ($parametro =~ m/-d\z/i) { $verbosity=10; }
		elsif ($parametro =~ m/-d\z/i) { $verbosity=0; }
		elsif ($parametro =~ m/-p\z/i) { $onlypurge=1; }
		else { ($pandora_path = $parametro); }
	}
	if ($pandora_path eq ""){
		print "FATAL ERROR: I Need complete path to pandora_server.conf file !!\n\n";
		exit;
	}
}


##############################################################################
# Read external configuration file
##############################################################################

sub pandora_loadconfig {
	my $archivo_cfg = @_[0];
	my $buffer_line;
	my @command_line;
	# Check for file
	if ( ! -e $archivo_cfg ) {
		printf "[ERROR] Cannot open configuration file. Please specify a valid one in command line \n";
		exit 1;
	}

	# Collect items from config file and put in an array 
	open (CFG, "< $archivo_cfg");
	while (<CFG>){
		$buffer_line = $_;
		if ($buffer_line =~ m/([\w-_\.]+)\s([0-9\w-_\.\/\?\&\=\)\(\_\-\\*\@\#\%\$\~\"\']+)/){
			push @command_line,$1;
		push @command_line,$2;
		}
	}
 
 
 	close (CFG);
 	# Process this array with commandline like options 
 	# Process input parameters
 	my @args = @command_line;
 	my $parametro;
 	my $ltotal=$#args; my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
  		print "[ERROR] No valid setup tokens readed in $archivo_cfg ";
  		exit;
 	}
 
 	for ($ax=0;$ax<=$ltotal;$ax++){
  		$parametro = $args[$ax];
  		if ($parametro =~ m/dirname\z/) {  $dirname = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/dbuser\z/) { $dbuser  = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/dbpass\z/) { $dbpass = $args[$ax+1]; $ax++; }
  		elsif ($parametro =~ m/dbname\z/) { $dbname = $args[$ax+1]; $ax++; }
  		elsif ($parametro =~ m/dbhost\z/) { $dbhost  = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/log_file\z/) { $log_file = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/server_threshold\z/) { $server_threshold = $args[$ax+1]; $ax++; }
 	}
 
 	# Check for valid token token values
 	if (( $dbuser eq "" ) || ( $log_file eq "" ) || ( $dbhost eq "")  || ($dbpass eq "" ) ) {
  		print "[ERROR] Bad Config values. Be sure that $archivo_cfg is a valid setup file";
		print "\n\n";
  		exit;
 	}
	
	# Open database to get days_purge days_compact values
	my $query; my $query_ready; my @data; my $rows_selected;
	my $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",$dbuser, $dbpass, {RaiseError => 1, AutoCommit => 1 });
	$query = "select * from tconfig where token = 'days_purge'";
	$query_ready = $dbh->prepare($query);
	$query_ready ->execute();
	$rows_selected = $query_ready->rows;
	if ($query_ready->rows > 0) {
		@data = $query_ready->fetchrow_array();
		$config_days_purge = $data[2]; # value
	} else {
		print "[ERROR] I cannot find in database a config item (DAYS_PURGE)\n";
		exit(-1);
	}
	$query_ready->finish();
	
	$query = "select * from tconfig where token = 'days_compact'";
	$query_ready = $dbh->prepare($query);
	$query_ready ->execute();
	$rows_selected = $query_ready->rows;
	if ($query_ready->rows > 0) {
		@data = $query_ready->fetchrow_array();
		$config_days_compact = $data[2]; # value
	} else {
		print "[ERROR] I cannot find in database a config item (DAYS_COMPACT)\n";
		exit(-1);
	}
	$query_ready->finish();
	
	$query = "select * from tconfig where token = 'step_compact'";
	$query_ready = $dbh->prepare($query);
	$query_ready ->execute();
	$rows_selected = $query_ready->rows;
	if ($query_ready->rows > 0) {
		@data = $query_ready->fetchrow_array();
		$config_step_compact = $data[2]; # value
	} else {
		print "[ERROR] I cannot find in database a config item (CONFIG_STEP_COMPACT)\n";
		exit(-1);
	}
		
	$query_ready->finish();
	$dbh->disconnect;
	
  	printf "Pandora DB now initialized and running (PURGE=$config_days_purge days, COMPACT=$config_days_compact days, STEP=$config_step_compact) ... \n\n";
}
	
###############################################################################
## SUB pandora_checkdb_consistency (dbname, dbuser, dbpass, dbhost)
###############################################################################
sub pandora_checkdb_consistency {

	# 1. Check for modules that do not have tagente_estado but have tagente_module
	
	my $dbname = $_[0];
	my $dbuser = $_[1];
	my $dbpass = $_[2];
	my $dbhost = $_[3];

 	my @query;
 	my $counter;
	my $err; # error code in datecalc function
 	my $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",$dbuser, $dbpass,{RaiseError => 1, AutoCommit => 1 });

	print "[CHECKDB] Deleting non-init data... \n";
        my $query4 = "SELECT * FROM tagente_estado WHERE utimestamp = 0";
        my $prep4 = $dbh->prepare($query4);
        $prep4 ->execute;
        my @datarow4;
        if ($prep4->rows != 0) {
                # for each record in tagente_modulo
                while (@datarow4 = $prep4->fetchrow_array()) {
                        my $id_agente_modulo = $datarow4[1];
						
						# Skip policy modules
                        next if (is_policy_module ($dbh, $id_agente_modulo));

                        # Delete the module
                        my $query0 = "DELETE FROM tagente_modulo WHERE disabled = 0 AND id_agente_modulo = $id_agente_modulo";
                        my $prep0 = $dbh->prepare($query0);
                        $prep0 ->execute;
                        $prep0->finish();
                        
                        # Delete any alerts associated to the module
						$query0 = "DELETE FROM talert_template_modules WHERE id_agent_module = $id_agente_modulo";
                        $prep0 = $dbh->prepare($query0);
                        $prep0 ->execute;
                        $prep0->finish();
                }
        }
        $prep4->finish();

	print "[CHECKDB] Checking database consistency (Missing status)... \n";

	my $query1 = "SELECT * FROM tagente_modulo";
	my $prep1 = $dbh->prepare($query1);
	$prep1 ->execute;
	my @datarow1;
	if ($prep1->rows != 0) {
		# for each record in tagente_modulo
		while (@datarow1 = $prep1->fetchrow_array()) {
			my $id_agente_modulo = $datarow1[0];
			# check if exist in tagente_estado and create if not
			my $query2 = "SELECT * FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
			my $prep2 = $dbh->prepare($query2);
			$prep2->execute;
			# If have 0 items, we need to re-create tagente_estado record
			if ($prep2->rows == 0) {
				my $id_agente = $datarow1[1];
				my $query3 = "INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUE ($id_agente_modulo, 0, '0000-00-00 00:00:00', 1, $id_agente, '0000-00-00 00:00:00', 0, 0, 0, 0)";
				print "[CHECKDB] Inserting module $id_agente_modulo in state table \n";
				my $prep3 = $dbh->prepare($query3);
				$prep3->execute;
				$prep3->finish();
			}
			$prep2->finish();
		}
	}
	$prep1->finish();
	
	print "[CHECKDB] Checking database consistency (Missing module)... \n";
	# 2. Check for modules in tagente_estado that do not have tagente_modulo, if there is any, delete it

	my $query1 = "SELECT * FROM tagente_estado";
	my $prep1 = $dbh->prepare($query1);
	$prep1 ->execute;
	my @datarow1;
	if ($prep1->rows != 0) {
		# for each record in tagente_modulo
		while (@datarow1 = $prep1->fetchrow_array()) {
			my $id_agente_modulo = $datarow1[1];
			# check if exist in tagente_estado and create if not
			my $query2 = "SELECT * FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo";
			my $prep2 = $dbh->prepare($query2);
			$prep2->execute;
			# If have 0 items, we need to create tagente_estado record
			if ($prep2->rows == 0) {
				my $id_agente = $datarow1[1];
				my $query3 = "DELETE FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
				print "[CHECKDB] Deleting non-existing module $id_agente_modulo in state table \n";
				my $prep3 = $dbh->prepare($query3);
				$prep3->execute;
				$prep3->finish();
			}
			$prep2->finish();
		}
	}
	$prep1->finish();
	
}

###############################################################################
# Returns undef if the module is not a policy module.
###############################################################################
sub is_policy_module ($$) {
	my ($dbh, $module_id) = @_;
	my ($agent_id, $module_name, $policy_id) = (undef, undef, undef);

	# Get agent id
	my $sth = $dbh->prepare('SELECT id_agente FROM tagente_modulo WHERE id_agente_modulo = ?');
	$sth->execute ($module_id);
	while (my @row = $sth->fetchrow_array()) {
			$agent_id = $row[0];
			last;
	}
	$sth->finish();
	return unless defined ($agent_id);

	# Get module name
	$sth = $dbh->prepare('SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ?');
	$sth->execute ($module_id);
	while (my @row = $sth->fetchrow_array()) {
			$module_name = $row[0];
			last;
	}
	$sth->finish();
	return unless defined ($module_name);

	# Search policies
	$sth = $dbh->prepare('SELECT t3.id FROM tpolicy_agents AS t1
			INNER JOIN tpolicy_modules AS t2 ON t1.id_policy = t2.id_policy
			INNER JOIN tpolicies AS t3 ON t1.id_policy = t3.id
		WHERE t1.id_agent = ? AND t2.name LIKE ?');
	$sth->execute ($agent_id, $module_name);
	while (my @row = $sth->fetchrow_array()) {
			$policy_id = $row[0];
			last;
	}
	$sth->finish();
	
	# Not a policy module
	return undef unless defined ($policy_id);

	return $policy_id;
}

##############################################################################
# SUB help_screen()
#  Show a help screen an exits
##############################################################################

sub help_screen{
	printf "\n\nSintax: \n pandora_db.pl  fullpathname_to_pandora_server.conf \n\n";
	print "             -d   Debug output (very verbose) \n";
	print "             -v   Verbose output \n";
	print "             -q   Quiet output \n";
	print "             -p   Only purge and consistency check, skip compact \n";
	exit;
}

#
###############################################################################
# Program main begin 
#
###############################################################################
sub pandoradb_main {

	print "Starting at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n";
	pandora_purgedb ($config_days_purge, $dbname, $dbuser, $dbpass, $dbhost);
	pandora_checkdb_consistency ($dbname, $dbuser, $dbpass, $dbhost);

	if ($onlypurge == 0){
		pandora_compactdb ($config_days_compact, $dbname, $dbuser, $dbpass, $dbhost);
	}
	print "Ending at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n";
	exit;
}
