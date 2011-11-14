#!/usr/bin/perl

###############################################################################
# Pandora FMS General Management Tool
###############################################################################
# Copyright (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License version 2
###############################################################################

# Includes list
use strict;
use Time::Local;		# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use POSIX;
use HTML::Entities;		# Encode or decode strings with HTML entities

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

# version: define current version
my $version = "4.0 PS110923";

# Parameter
my $param;

# Initialize MD5 variables
md5_init ();

# Pandora server configuration
my %conf;

# FLUSH in each IO
$| = 0;

# Init
pandora_manage_init(\%conf);

# Read config file
pandora_load_config (\%conf);

# Load enterprise module
if (enterprise_load (\%conf) == 0) {
	print "[*] Pandora FMS Enterprise module not available.\n\n";
} else {
	print "[*] Pandora FMS Enterprise module loaded.\n\n";
}

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, '3306', $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

my $conf = \%conf;

# Main
pandora_manage_main(\%conf, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);
exit;

###############################################################################
###############################################################################
# GENERAL FUNCTIONS
###############################################################################
###############################################################################

###############################################################################
# Disable a entire group
###############################################################################
sub pandora_disable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	if ($group == 0){
		db_do ($dbh, "UPDATE tagente SET disabled = 1");
	}
	else {
		db_do ($dbh, "UPDATE tagente SET disabled = 1 WHERE id_grupo = $group");
	}
    exit;
}

###############################################################################
# Enable a entire group
###############################################################################
sub pandora_enable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	if ($group == 0){
			db_do ($dbh, "UPDATE tagente SET disabled = 0");
	}
	else {  
			db_do ($dbh, "UPDATE tagente SET disabled = 0 WHERE id_grupo = $group");
	}
    exit;
}

##############################################################################
# Init screen
##############################################################################
sub pandora_manage_init ($) {
    my $conf = shift; 
    
    $conf->{"verbosity"}=0;	# Verbose 1 by default
	$conf->{"daemon"}=0;	# Daemon 0 by default
	$conf->{'PID'}="";	# PID file not exist by default
	$conf->{"quiet"}=0;	# Daemon 0 by default
   

	print "\nPandora FMS Manage tool $version Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 0);
   
        $conf->{'pandora_path'} = $ARGV[0];

	help_screen () if ($conf->{'pandora_path'} =~ m/--*h\w*\z/i );
}

##########################################################################
## Delete a template module.
##########################################################################
sub pandora_delete_template_module ($$) {
	my ($dbh, $template_module_id) = @_;

	# Delete the template module
	db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id = ?', $template_module_id);
	
	# 
	db_do ($dbh, 'DELETE FROM talert_template_module_actions WHERE id_alert_template_module = ?', $template_module_id);
}

##########################################################################
## Delete a policy template module action.
##########################################################################
sub pandora_delete_template_module_action ($$$) {
        my ($dbh, $template_module_id, $action_id) = @_;

        return db_do ($dbh, 'DELETE FROM talert_template_module_actions WHERE id_alert_template_module = ? AND id_alert_action = ?', $template_module_id, $action_id);
}

##########################################################################
## Create a user.
##########################################################################
sub pandora_create_user ($$$$$) {
my ($dbh, $name, $password, $is_admin, $comments) = @_;


return db_insert ($dbh, 'id_user', 'INSERT INTO tusuario (id_user, fullname, password, comments, is_admin)
                         VALUES (?, ?, ?, ?, ?)', $name, $name, $password, $comments, $is_admin);
}

##########################################################################
## Delete a user.
##########################################################################
sub pandora_delete_user ($$) {
my ($dbh, $name) = @_;

# Delete user profiles
db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario = ?', $name);

# Delete the user
my $return = db_do ($dbh, 'DELETE FROM tusuario WHERE id_user = ?', $name);

if($return eq '0E0') {
	return -1;
}
else {
	return 0;
}
}

##########################################################################
## Assign a profile to the given user/group.
##########################################################################
sub pandora_create_user_profile ($$$$) {
        my ($dbh, $user_id, $profile_id, $group_id) = @_;
        
        return db_insert ($dbh, 'id_up', 'INSERT INTO tusuario_perfil (id_usuario, id_perfil, id_grupo) VALUES (?, ?, ?)', $user_id, $profile_id, $group_id);
}

##########################################################################
## Delete a profile from the given user/group.
##########################################################################
sub pandora_delete_user_profile ($$$$) {
        my ($dbh, $user_id, $profile_id, $group_id) = @_;
        
        return db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario=? AND id_perfil=? AND id_grupo=?', $user_id, $profile_id, $group_id);
}

##########################################################################
## Delete all the data of module, agent's modules or group's agent's modules
##########################################################################
sub pandora_delete_data ($$$) {
        my ($dbh, $type, $id) = @_;
        
        if($type eq 'group') {
			my @delete_agents = get_db_rows ($dbh, 'SELECT id_agente FROM tagente WHERE id_grupo = ?', $id);
			foreach my $agent (@delete_agents) {
				my @delete_modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ?', $agent->{'id_agente'});
				foreach my $module (@delete_modules) {
					pandora_delete_module_data($dbh, $module->{'id_agente_modulo'});
				}
			}
		}
        elsif ($type eq 'agent') {
			my @delete_modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ?', $id);
			foreach my $module (@delete_modules) {
				pandora_delete_module_data($dbh, $module->{'id_agente_modulo'});
			}
		}
		elsif ($type eq 'module'){
			pandora_delete_module_data($dbh, $id);
		}
		else {
			return 0;
		}
}

##########################################################################
## Delete all the data of module
##########################################################################
sub pandora_delete_module_data ($$) {
        my ($dbh, $id_module) = @_;
        my $buffer = 1000;
        
		while(1) {
			my $nd = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			my $ndinc = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			my $ndlog4x = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			my $ndstring = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			
			my $ntot = $nd + $ndinc + $ndlog4x + $ndstring;

			if($ntot == 0) {
				last;
			}
			
			if($nd > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
			
			if($ndinc > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos_inc WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
		
			if($ndlog4x > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos_log4x WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
			
			if($ndstring > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos_string WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
		}
			
		return 1;
}

##########################################################################
# Validate event.
# This validates all events pending to ACK for the same id_agent_module
##########################################################################
sub pandora_validate_event_filter ($$$$$$$$$) {
	my ($pa_config, $id_agentmodule, $id_agent, $timestamp_min, $timestamp_max, $id_user, $id_alert_agent_module, $criticity, $dbh) = @_;
	my $filter = '';
		
	if ($id_agentmodule ne ''){
		$filter .= " AND id_agentmodule = $id_agentmodule";
	}
	if ($id_agent ne ''){
		$filter .= " AND id_agente = $id_agent";
	}
	if ($timestamp_min ne ''){
		$filter .= " AND timestamp >= '$timestamp_min'";
	}
	if ($timestamp_max ne ''){
		$filter .= " AND timestamp <= '$timestamp_max'";
	}
	if ($id_user ne ''){
		$filter .= " AND id_usuario = '$id_user'";
	}
	
	if ($id_alert_agent_module ne ''){
		$filter .= " AND id_alert_am = $id_alert_agent_module";
	}	
	
	if ($criticity ne ''){
		$filter .= " AND criticity = $criticity";
	}

	logger($pa_config, "Validating events", 10);
	db_do ($dbh, "UPDATE tevento SET estado = 1 WHERE estado = 0".$filter);
}

###############################################################################
###############################################################################
# PRINT HELP AND CHECK ERRORS FUNCTIONS
###############################################################################
###############################################################################

###############################################################################
# Print a parameter error and exit the program.
###############################################################################
sub param_error ($$) {
    print (STDERR "[ERROR] Parameters error: $_[1] received | $_[0] necessary.\n\n");
    
    help_screen ();
    exit 1;
}

###############################################################################
# Print a 'not exists' error and exit the program.
###############################################################################
sub notexists_error ($$) {
    print (STDERR "[ERROR] Error: The $_[0] '$_[1]' not exists.\n\n");
    exit 1;
}

###############################################################################
# Check the return of 'get id' and call the error if its equal to -1.
###############################################################################
sub exist_check ($$$) {
    if($_[0] == -1) {
		notexists_error($_[1],$_[2]);
	}
}

###############################################################################
# Check the parameters.
# Param 0: # of received parameters
# Param 1: # of necessary parameters
# Param 2: # of optional parameters
###############################################################################
sub param_check ($$;$) {
	my ($ltotal, $lneed, $lopt) = @_;
	$ltotal = $ltotal - 1;
	
	if(!defined($lopt)){
		$lopt = 0;
	}

	if( $ltotal < $lneed - $lopt || $ltotal > $lneed) {
		if( $lopt == 0 ) {
			param_error ($lneed, $ltotal);
		}
		else {
			param_error (($lneed-$lopt)."-".$lneed, $ltotal);
		}
	}
}

##############################################################################
# Print a help line.
##############################################################################
sub help_screen_line($$$){
	my ($option, $parameters, $help) = @_;
	print "\t$option $parameters\t$help.\n" unless ($param ne '' && $param ne $option);
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "Usage: $0 <path to pandora_server.conf> [options] \n\n" unless $param ne '';
	print "Available options:\n\n" unless $param ne '';
	print "Available options for $param:\n\n" unless $param eq '';
	help_screen_line('--disable_alerts', '', 'Disable alerts in all groups (system wide)');
	help_screen_line('--enable_alerts', '', 'Enable alerts in all groups (system wide)');
	help_screen_line('--disable_eacl', '', 'Disable enterprise ACL system');
	help_screen_line('--enable_eacl', '', 'Enable enterprise ACL system');
	help_screen_line('--disable_group', '<group_name>', 'Disable agents from an entire group');
   	help_screen_line('--enable_group', '<group_name>', 'Enable agents from an entire group');
   	help_screen_line('--create_agent', '<agent_name> <operating_system> <group> <server_name> [<address> <description> <interval>]', 'Create agent');
	help_screen_line('--delete_agent', '<agent_name>', 'Delete agent');
	help_screen_line('--create_data_module', '<module_name> <module_type> <agent_name> [<description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <definition_file>]', 'Add data server module to agent');
	help_screen_line('--create_network_module', '<module_name> <module_type> <agent_name> <module_address> [<module_port> <description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold>]', 'Add not snmp network module to agent');
	help_screen_line('--create_snmp_module', '<module_name> <module_type> <agent_name> <module_address> <module_port> <version> [<community> <oid> <description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <snmp3_priv_method> <snmp3_priv_pass> <snmp3_sec_level> <snmp3_auth_method> <snmp3_auth_user> <snmp3_priv_pass> <ff_threshold>]', 'Add snmp network module to agent');
	help_screen_line('--create_plugin_module', '<module_name> <module_type> <agent_name> <module_address> <module_port> <plugin_name> <user> <password> <parameters> [<description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold>]', 'Add plug-in module to agent');
    help_screen_line('--delete_module', 'Delete module from agent', '<module_name> <agent_name>');
    help_screen_line('--create_template_module', '<template_name> <module_name> <agent_name>', 'Add alert template to module');
    help_screen_line('--delete_template_module', '<template_name> <module_name> <agent_name>', 'Delete alert template from module');
    help_screen_line('--create_template_action', '<action_name> <template_name> <module_name> <agent_name> [<fires_min> <fires_max>]', 'Add alert action to module-template');
    help_screen_line('--delete_template_action', '<action_name> <template_name> <module_name> <agent_name>', 'Delete alert action from module-template');
    help_screen_line('--data_module', '<server_name> <agent_name> <module_name> <module_type> [<datetime>]', 'Insert data to module');
    help_screen_line('--create_user', '<user_name> <user_password> <is_admin> [<comments>]', 'Create user');
    help_screen_line('--delete_user', '<user_name>', 'Delete user');
    help_screen_line('--create_profile', '<user_name> <profile_name> <group_name>', 'Add perfil to user');
    help_screen_line('--delete_profile', '<user_name> <profile_name> <group_name>', 'Delete perfil from user');
    help_screen_line('--create_event', '<event> <event_type> <agent_name> <module_name> <group_name> [<event_status> <severity> <template_name>]', 'Add event');
    help_screen_line('--validate_event', '<agent_name> <module_name> <datetime_min> <datetime_max> <user_name> <criticity> <template_name>', 'Validate events');
    help_screen_line('--create_incident', '<title> <description> <origin> <status> <priority 0 for Informative, 1 for Low, 2 for Medium, 3 for Serious, 4 for Very serious or 5 for Maintenance> <group> [<owner>]', 'Create incidents');
    help_screen_line('--delete_data', '-m <module_name> <agent_name> | -a <agent_name> | -g <group_name>', 'Delete historic data of a module, the modules of an agent or the modules of the agents of a group');
    help_screen_line('--delete_not_policy_modules', '', 'Delete all modules without policy from configuration file');
    help_screen_line('--apply_policy', '<policy_name>', 'Force apply a policy');
    help_screen_line('--disable_policy_alerts', '<policy_name>', 'Disable all the alerts of a policy');
    print "\n";
	exit;
}

###############################################################################
###############################################################################
# CLI FUNCTIONS
###############################################################################
###############################################################################

##############################################################################
# Disable group
# Related option: --disable_group
##############################################################################

sub cli_disable_group() {
	my $group_name = @ARGV[2];
	my $id_group;
	
	if($group_name eq "All") {
		print "[INFO] Disabling all groups\n\n";
		$id_group = 0;
	}
	else {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Disabling group '$group_name'\n\n";
	}
	
	pandora_disable_group ($conf, $dbh, $id_group);
}

##############################################################################
# Enable group
# Related option: --enable_group
##############################################################################

sub cli_enable_group() {
	my $group_name = @ARGV[2];
	my $id_group;
	
	if($group_name eq "All") {
		$id_group = 0;
		print "[INFO] Enabling all groups\n\n";
	}
	else {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Enabling group '$group_name'\n\n";
	}
	
	pandora_enable_group ($conf, $dbh, $id_group);
}

##############################################################################
# Create an agent.
# Related option: --created_agent
##############################################################################

sub cli_create_agent() {
	my ($agent_name,$os_name,$group_name,$server_name,$address,$description,$interval) = @ARGV[2..8];
	
	print "[INFO] Creating agent '$agent_name'\n\n";
	
	$address = '' unless defined ($address);
	$description = (defined ($description) ? safe_input($description)  : '' );	# safe_input() might be better at pandora_create_agent() (when passing 'description' to db_insert())
	$interval = 300 unless defined ($interval);
	
	my $id_group = get_group_id($dbh,$group_name);
	exist_check($id_group,'group',$group_name);
	my $os_id = get_os_id($dbh,$os_name);
	exist_check($id_group,'operating system',$group_name);
	pandora_create_agent ($conf, $server_name, $agent_name, $address, $id_group, 0, $os_id, $description, $interval, $dbh);
}

##############################################################################
# Delete an agent.
# Related option: --delete_agent
##############################################################################

sub cli_delete_agent() {
	my $agent_name = @ARGV[2];
	
	$agent_name = decode_entities($agent_name);
	print "[INFO] Deleting agent '$agent_name'\n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	
	pandora_delete_agent($dbh,$id_agent,$conf);
}

##############################################################################
# Create data module.
# Related option: --create_data_module
##############################################################################

sub cli_create_data_module() {
	my ($module_name, $module_type, $agent_name, $description, $module_group, 
	$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
	$critical_max, $history_data, $definition_file) = @ARGV[2..16];
	
	my $module_name_def;
	my $module_type_def;

	print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
		
	# If the module is local, we add it to the conf file
	if(defined($definition_file) && (-e $definition_file) && (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf')){
		open (FILE, $definition_file);
		my @file = <FILE>;
		my $definition = join("", @file);
		close (FILE);

		# If the parameter name or type and the definition file name or type 
		# dont match will be set the file definitions
		open (FILE, $definition_file);
		while (<FILE>) {
			chomp;
			my ($key, $val) = split / /;
			if ($key eq 'module_name') {
				$module_name_def =  $val;
			}
			if ($key eq 'module_type') {
				$module_type_def =  $val;
			}
		}
		close (FILE);
		
		open (FILE, $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
		my @file = <FILE>;
		my $conf_file = join("", @file);
		close(FILE);
		
		open FILE, "> ".$conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf';
		print FILE "$conf_file\n$definition";
		close(FILE);
		
		enterprise_hook('pandora_update_md5_file', [$conf, $agent_name]);
	}
	
	if(defined($definition_file) && $module_type ne $module_type_def) {
		$module_type = $module_type_def;
		print "[INFO] The module type has been forced to '$module_type' by the definition file\n\n";
	}
	
	if(defined($definition_file) && $module_name ne $module_name_def) {
		$module_name = $module_name_def;
		print "[INFO] The module name has been forced to '$module_name' by the definition file\n\n";
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);

	if ($module_type !~ m/.?generic.?/ && $module_type !~ m/.?async.?/ && $module_type ne 'log4x' && $module_type ne 'keep_alive') {
			print "[ERROR] '$module_type' is not valid type for data modules. Try with generic, asyncronous, keep alive or log4x types\n\n";
			exit;
	}
	
	my $agent_id = get_agent_id($dbh,$agent_name);
	exist_check($agent_id,'agent',$agent_name);
	
	my $module_group_id = get_module_group_id($dbh,$module_group);
	exist_check($module_group_id,'module group',$module_group);
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	$parameters{'nombre'} = safe_input($module_name);
	$parameters{'id_agente'} = $agent_id;

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);	


	$parameters{'id_modulo'} = 1;	
	
	pandora_create_module_from_hash ($conf, \%parameters, $dbh);
}

##############################################################################
# Create network module.
# Related option: --create_network_module
##############################################################################

sub cli_create_network_module() {
	my ($module_name, $module_type, $agent_name, $module_address, $module_port, $description, 
	$module_group, $min, $max, $post_process, $interval, $warning_min, $warning_max, $critical_min,
	$critical_max, $history_data, $ff_threshold) = @ARGV[2..18];
	
	my $module_name_def;
	my $module_type_def;
	
	print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
	
	if ($module_type =~ m/.?snmp.?/) {
		print "[ERROR] '$module_type' is not a valid type. For snmp modules use --create_snmp_module parameter\n\n";
		$param = '--create_snmp_module';
		help_screen ();
		exit 1;
	}
	if ($module_type !~ m/.?icmp.?/ && $module_type !~ m/.?tcp.?/) {
			print "[ERROR] '$module_type' is not valid type for (not snmp) network modules. Try with icmp or tcp types\n\n";
			exit;
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	my $agent_id = get_agent_id($dbh,$agent_name);
	exist_check($agent_id,'agent',$agent_name);
	
	my $module_group_id = get_module_group_id($dbh,$module_group);
	exist_check($module_group_id,'module group',$module_group);
	
	if ($module_type !~ m/.?icmp.?/) {
		if (not defined($module_port)) {
			print "[ERROR] Port error. Agents of type distinct of icmp need port\n\n";
			exit;
		}
		if ($module_port > 65535 || $module_port < 1) {
			print "[ERROR] Port error. Port must into [1-65535]\n\n";
			exit;
		}
	}
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	$parameters{'nombre'} = safe_input($module_name);
	$parameters{'id_agente'} = $agent_id;
	$parameters{'ip_target'} = $module_address;

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	$parameters{'tcp_port'} = $module_port unless !defined ($module_port);
	$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);	
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);	

	$parameters{'id_modulo'} = 2;	
	
	pandora_create_module_from_hash ($conf, \%parameters, $dbh);
}

##############################################################################
# Create snmp module.
# Related option: --create_snmp_module
##############################################################################

sub cli_create_snmp_module() {
	my ($module_name, $module_type, $agent_name, $module_address, $module_port, $version, $community, 
	$oid, $description, $module_group, $min, $max, $post_process, $interval, $warning_min, 
	$warning_max, $critical_min, $critical_max, $history_data, $snmp3_priv_method, $snmp3_priv_pass,
	$snmp3_sec_level, $snmp3_auth_method, $snmp3_auth_user, $snmp3_priv_pass, $ff_threshold) = @ARGV[2..27];
	
	my $module_name_def;
	my $module_type_def;
	
	print "[INFO] Adding snmp module '$module_name' to agent '$agent_name'\n\n";
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);

	my $agent_id = get_agent_id($dbh,$agent_name);
	exist_check($agent_id,'agent',$agent_name);
	
	my $module_group_id = get_module_group_id($dbh,$module_group);
	exist_check($module_group_id,'module group',$module_group);
	
	if ($module_type !~ m/.?snmp.?/) {
		print "[ERROR] '$module_type' is not a valid snmp type\n\n";
		exit;
	}
	
	if ($module_port > 65535 || $module_port < 1) {
		print "[ERROR] Port error. Port must into [1-65535]\n\n";
		exit;
	}
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	$parameters{'nombre'} = safe_input($module_name);
	$parameters{'id_agente'} = $agent_id;
	$parameters{'ip_target'} = $module_address;
	$parameters{'tcp_port'} = $module_port;
	$parameters{'tcp_send'} = $version;

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);
	$parameters{'snmp_community'} = $community unless !defined ($community);
	$parameters{'snmp_oid'} = $oid unless !defined ($oid);
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);	
	
	if($version == 3) {
		$parameters{'custom_string_1'} = $snmp3_priv_method;
		$parameters{'custom_string_2'} = $snmp3_priv_pass;
		$parameters{'custom_string_3'} = $snmp3_sec_level;
		$parameters{'plugin_parameter'} = $snmp3_auth_method;
		$parameters{'plugin_user'} = $snmp3_auth_user; 
		$parameters{'plugin_pass'} = $snmp3_priv_pass;
	}

	# id_modulo = 2 for snmp modules
	$parameters{'id_modulo'} = 2;	
	
	pandora_create_module_from_hash ($conf, \%parameters, $dbh);
}

##############################################################################
# Create plugin module.
# Related option: --create_plugin_module
##############################################################################

sub cli_create_plugin_module() {
	my ($module_name, $module_type, $agent_name, $module_address, $module_port, $plugin_name,
	$user, $password, $parameters, $description, $module_group, $min, $max, $post_process, 
	$interval, $warning_min, $warning_max, $critical_min, $critical_max, $history_data, 
	$ff_threshold) = @ARGV[2..22];
	
	my $module_name_def;
	my $module_type_def;
	
	print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);

	if ($module_type !~ m/.?generic.?/ && $module_type ne 'log4x') {
			print "[ERROR] '$module_type' is not valid type for plugin modules. Try with generic or log4x types\n\n";
			exit;
	}
	
	my $agent_id = get_agent_id($dbh,$agent_name);
	exist_check($agent_id,'agent',$agent_name);
	
	my $module_group_id = get_module_group_id($dbh,$module_group);
	exist_check($module_group_id,'module group',$module_group);
			
	my $plugin_id = get_plugin_id($dbh,$plugin_name);
	exist_check($plugin_id,'plugin',$plugin_name);
	
	if ($module_port > 65535 || $module_port < 1) {
		print "[ERROR] Port error. Port must into [1-65535]\n\n";
		exit;
	}

	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	$parameters{'nombre'} = safe_input($module_name);
	$parameters{'id_agente'} = $agent_id;
	$parameters{'ip_target'} = $module_address;
	$parameters{'tcp_port'} = $module_port;
	$parameters{'id_plugin'} = $plugin_id;
	$parameters{'plugin_user'} = $user;
	$parameters{'plugin_pass'} = $password;
	$parameters{'plugin_parameter'} = $parameters;

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);	
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);	

	$parameters{'id_modulo'} = 4;	
	
	pandora_create_module_from_hash ($conf, \%parameters, $dbh);
}

##############################################################################
# Create delete module.
# Related option: --delete_module
##############################################################################

sub cli_delete_module() {
	my ($module_name,$agent_name) = @ARGV[2..3];
	
	print "[INFO] Deleting module '$module_name' from agent '$agent_name' \n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $id_module = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($id_module,'module',$module_name);
	
	pandora_delete_module($dbh,$id_module,$conf);
}

##############################################################################
# Create delete not policy modules.
# Related option: --delete_not_policy_modules
##############################################################################

sub cli_delete_not_policy_modules() {
	my $incomingdir;

	$incomingdir = $conf->{incomingdir}.'/conf/';

	# Open the folder
	opendir FOLDER, $incomingdir || die "[ERROR] Opening incoming directory";
	
	# Store the list of files
	my @files = readdir(FOLDER);
	my $file;
	
	print "[INFO] Deleting modules without policy from conf files \n\n";
	foreach $file (@files)
	{
		if($file ne '.' && $file ne '..') {
			my $ret = enterprise_hook('pandora_delete_not_policy_modules', [$incomingdir.$file]);
		}
	}
	
	my @local_modules_without_policies = get_db_rows ($dbh, 'SELECT * FROM tagente_modulo WHERE id_policy_module = 0 AND id_tipo_modulo = 1');
	
	
	foreach my $module (@local_modules_without_policies) {
		pandora_delete_module ($dbh, $module->{'id_agente_modulo'});
	}
}

##############################################################################
# Create template module.
# Related option: --create_template_module
##############################################################################

sub cli_create_template_module() {
	my ($template_name,$module_name,$agent_name) = @ARGV[2..4];
	
	print "[INFO] Adding template '$template_name' to module '$module_name' from agent '$agent_name' \n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);
	
	pandora_create_template_module ($conf, $module_id, $template_id, $dbh);
}

##############################################################################
# Delete template module.
# Related option: --delete_template_module
##############################################################################

sub cli_delete_template_module() {
	my ($template_name,$module_name,$agent_name) = @ARGV[2..4];

	print "[INFO] Delete template '$template_name' from module '$module_name' from agent '$agent_name' \n\n";

	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);

	my $template_module_id = get_template_module_id($dbh, $module_id, $template_id);
	exist_check($template_module_id,"template '$template_name' on module",$module_name);
		
	pandora_delete_template_module ($dbh, $template_module_id);
}

##############################################################################
# Create template action.
# Related option: --create_template_action
##############################################################################

sub cli_create_template_action() {
	my ($action_name,$template_name,$module_name,$agent_name,$fires_min,$fires_max) = @ARGV[2..7];
	
	print "[INFO] Adding action '$action_name' to template '$template_name' in module '$module_name' from agent '$agent_name' with $fires_min min. fires and $fires_max max. fires\n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);
	my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
	exist_check($template_module_id,'template module',$template_name);
	my $action_id = get_action_id($dbh,$action_name);
	exist_check($action_id,'action',$action_name);
	
	$fires_min = 0 unless defined ($fires_min);
	$fires_max = 0 unless defined ($fires_max);
	
	my %parameters;						
	
	$parameters{'id_alert_template_module'} = $template_module_id;
	$parameters{'id_alert_action'} = $action_id;
	$parameters{'fires_min'} = $fires_min;
	$parameters{'fires_max'} = $fires_max;
	
	pandora_create_template_module_action ($conf, \%parameters, $dbh);
}

##############################################################################
# Delete template action.
# Related option: --delete_template_action
##############################################################################

sub cli_delete_template_action() {
	my ($action_name,$template_name,$module_name,$agent_name) = @ARGV[2..5];
	
	print "[INFO] Deleting action '$action_name' from template '$template_name' in module '$module_name' from agent '$agent_name')\n\n";

	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);
	my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
	exist_check($template_module_id,'template module',$template_name);
	my $action_id = get_action_id($dbh,$action_name);
	exist_check($action_id,'action',$action_name);

	pandora_delete_template_module_action ($dbh, $template_module_id, $action_id);
}

##############################################################################
# Insert data to module.
# Related option: --data_module
##############################################################################

sub cli_data_module() {
	my ($server_name,$agent_name,$module_name,$module_type,$datetime) = @ARGV[2..6];
	my $utimestamp;
	
	if(defined($datetime)) {
		if ($datetime !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print "[ERROR] Invalid datetime $datetime. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime .= ":00";
		$utimestamp = dateTimeToTimestamp($datetime);
	}
	else {
		$utimestamp = time();
	}

	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	
	# Server_type 0 is dataserver
	my $server_id = get_server_id($dbh,$server_name,0);
	exist_check($server_id,'data server',$server_name);
	
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND id_tipo_modulo = ?', $id_agent, $module_type_id);

	if(not defined($module->{'module_interval'})) {
		print "[ERROR] No module data finded. \n\n";
		exit;
	}

	my %data = ('data' => 1);
	pandora_process_module ($conf, \%data, '', $module, $module_type, '', $utimestamp, $server_id, $dbh);
	
	print "[INFO] Inserting data to module '$module_name'\n\n";
}

##############################################################################
# Create a user.
# Related option: --create_user
##############################################################################

sub cli_create_user() {
	my ($user_name,$password,$is_admin,$comments) = @ARGV[2..5];
	
	$comments = (defined ($comments) ? safe_input($comments)  : '' );
	
	print "[INFO] Creating user '$user_name'\n\n";
	
	pandora_create_user ($dbh, $user_name, md5($password), $is_admin, $comments);
}

##############################################################################
# Delete a user.
# Related option: --delete_user
##############################################################################

sub cli_delete_user() {
	my $user_name = @ARGV[2];
	
	print "[INFO] Deleting user '$user_name' \n\n";
	
	my $result = pandora_delete_user($dbh,$user_name);
	exist_check($result,'user',$user_name);
}

##############################################################################
# Create profile.
# Related option: --create_profile
##############################################################################

sub cli_create_profile() {
	my ($user_name,$profile_name,$group_name) = @ARGV[2..4];
	
	my $id_profile = get_profile_id($dbh,$profile_name);
	exist_check($id_profile,'profile',$profile_name);
	
	my $id_group;
	
	if($group_name eq "All") {
		$id_group = 0;
		print "[INFO] Adding profile '$profile_name' to all groups for user '$user_name') \n\n";
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Adding profile '$profile_name' to group '$group_name' for user '$user_name') \n\n";
	}
	
	pandora_create_user_profile ($dbh, $user_name, $id_profile, $id_group);
}

##############################################################################
# Delete profile.
# Related option: --delete_profile
##############################################################################

sub cli_delete_profile() {
	my ($user_name,$profile_name,$group_name) = @ARGV[2..4];
	
	my $id_profile = get_profile_id($dbh,$profile_name);
	exist_check($id_profile,'profile',$profile_name);
	
	my $id_group;
	
	if($group_name eq "All") {
		$id_group = 0;
		print "[INFO] Deleting profile '$profile_name' from all groups for user '$user_name') \n\n";
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Deleting profile '$profile_name' from group '$group_name' for user '$user_name') \n\n";
	}
	
	pandora_delete_user_profile ($dbh, $user_name, $id_profile, $id_group);
}

##############################################################################
# Create event.
# Related option: --create_event
##############################################################################

sub cli_create_event() {
	my ($event,$event_type,$agent_name,$module_name,$group_name,$event_status,$severity,$template_name) = @ARGV[2..9];

	$event_status = 0 unless defined($event_status);
	$severity = 0 unless defined($severity);
	
	my $id_group;
	
	if (!defined($group_name) || $group_name eq "All") {
		$id_group = 0;
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
	}
	
	my $id_agent;
	
	if (defined($agent_name) && $agent_name ne "") {
		$id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		
	} else {
		$id_agent = 0;
	}
	
	my $id_agentmodule;
		
	if (defined($module_name) && $module_name ne "") {				
		$id_agentmodule = get_agent_module_id($dbh,$module_name,$id_agent);
		exist_check($id_agentmodule,'module',$module_name);

	} else {
		$id_agentmodule = 0;
	}
	
	my $id_alert_agent_module;
				
	if(defined($template_name)) {
		my $id_template = get_template_id($dbh,$template_name);
		exist_check($id_template,'template',$template_name);
		$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
		exist_check($id_alert_agent_module,'template module',$template_name);
	}
	else {
		$id_alert_agent_module = 0;
	}
	
	print "[INFO] Adding event '$event' for agent '$agent_name' \n\n";

	pandora_event ($conf, $event, $id_group, $id_agent, $severity,
		$id_alert_agent_module, $id_agentmodule, $event_type, $event_status, $dbh);
}

##############################################################################
# Validate event.
# Related option: --validate_event
##############################################################################

sub cli_validate_event() {
	my ($agent_name, $module_name, $datetime_min, $datetime_max, $user_name, $criticity, $template_name) = @ARGV[2..8];
	my $id_agent = '';
	my $id_agentmodule = '';

	if(defined($agent_name) && $agent_name ne '') {
		$id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		
		if($module_name ne '') {
			$id_agentmodule = get_agent_module_id($dbh, $module_name, $id_agent);
			exist_check($id_agentmodule,'module',$module_name);
		}
	}

	if(defined($datetime_min) && $datetime_min ne '') {
		if ($datetime_min !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print "[ERROR] Invalid datetime_min format. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime_min .= ":00";
	}
	
	if(defined($datetime_max) && $datetime_max ne '') {
		if ($datetime_max !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print "[ERROR] Invalid datetime_max $datetime_max. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime_max .= ":00";
	}

	my $id_alert_agent_module = '';
	
	if(defined($template_name) && $template_name ne '') {
		my $id_template = get_template_id($dbh,$template_name);
		exist_check($id_template,'template',$template_name);
		$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
		exist_check($id_alert_agent_module,'template module',$template_name);
	}
				
	pandora_validate_event_filter ($conf, $id_agentmodule, $id_agent, $datetime_min, $datetime_max, $user_name, $id_alert_agent_module, $criticity, $dbh);
	print "[INFO] Validating event for agent '$agent_name'\n\n";
}

##############################################################################
# Create incident.
# Related option: --create_incident
##############################################################################

sub cli_create_incident() {
	my ($title, $description, $origin, $status, $priority, $group_name, $owner) = @ARGV[2..8];
	
	my $id_group = get_group_id($dbh,$group_name);
	exist_check($id_group,'group',$group_name);
				
	pandora_create_incident ($conf, $dbh, $title, $description, $priority, $status, $origin, $id_group, $owner);
	print "[INFO] Creating incident '$title'\n\n";
}

##############################################################################
# Delete data.
# Related option: --delete_data
##############################################################################

sub cli_delete_data($) {
	my $ltotal = shift;
	my ($opt, $name, $name2) = @ARGV[2..4];

	if($opt eq '-m' || $opt eq '--m') {
		# Delete module data
		param_check($ltotal, 3) unless ($name2 ne '');
		my $id_agent = get_agent_id($dbh,$name2);
		exist_check($id_agent,'agent',$name2);
		
		my $id_module = get_agent_module_id($dbh,$name,$id_agent);
		exist_check($id_module,'module',$name);
	
		print "DELETING THE DATA OF THE MODULE $name OF THE AGENT $name2\n\n";
		
		pandora_delete_data($dbh, 'module', $id_module);
	}
	elsif($opt eq '-a' || $opt eq '--a') {
		# Delete agent's modules data
		my $id_agent = get_agent_id($dbh,$name);
		exist_check($id_agent,'agent',$name);
		
		print "DELETING THE DATA OF THE AGENT $name\n\n";
		
		pandora_delete_data($dbh, 'module', $id_agent);
	}
	elsif($opt eq '-g' || $opt eq '--g') {
		# Delete group's modules data
		my $id_group = get_group_id($dbh,$name);
		exist_check($id_group,'group',$name);
		
		print "DELETING THE DATA OF THE GROUP $name\n\n";
		
		pandora_delete_data($dbh, 'group', $id_group);
	}
	else {
		print "[ERROR] Invalid parameter '$opt'.\n\n";
		help_screen ();
		exit;
	}
}

##############################################################################
# Add policy to apply queue.
# Related option: --apply policy
##############################################################################

sub cli_apply_policy() {
	my $policy_name = @ARGV[2];
	
	my $configuration_data = "";

	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	my $ret = enterprise_hook('pandora_add_policy_queue', [$dbh, $conf, $policy_id, 'apply']);
	
	if($ret == -1) {
		print "[ERROR] Operation 'apply' cannot be added to policy '$policy_name' because is duplicated in queue or incompatible with others operations\n\n";
		exit;
	}
	
	print "[INFO] Added operation 'apply' to policy '$policy_name'\n\n";
}

##############################################################################
# Disable policy alerts.
# Related option: --disable_policy_alerts
##############################################################################

sub cli_disable_policy_alerts() {
	my $policy_name = @ARGV[2];
	
	my $configuration_data = "";

	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	# Flag as disabled the policy alerts
	my $array_pointer_ag = enterprise_hook('pandora_disable_policy_alerts',[$dbh, $policy_id]);
}

###############################################################################
# Disable alert system globally
# Related option: --disable_alerts
###############################################################################
sub cli_disable_alerts ($$) {
	my ($conf, $dbh) = @_;

	print "[INFO] Disabling all alerts \n\n";

	# This works by disabling alerts in each defined group
    # If you have previously a group with alert disabled, and you disable 
    # alerts globally, when enabled it again, it will enabled also !

	db_do ($dbh, "UPDATE tgrupo SET disabled = 1");

    exit;
}

###############################################################################
# Enable alert system globally
# Related option: --enable_alerts
###############################################################################
sub cli_enable_alerts ($$) {
	my ($conf, $dbh) = @_;

	print "[INFO] Enabling all alerts \n\n";

	db_do ($dbh, "UPDATE tgrupo SET disabled = 0");

    exit;
}

###############################################################################
# Disable enterprise ACL
# Related option: --disable_eacl
###############################################################################
sub cli_disable_eacl ($$) {
	my ($conf, $dbh) = @_;
			
	print "[INFO] Disabling Enterprise ACL system (system wide)\n\n";

	db_do ($dbh, "UPDATE tconfig SET `value` ='0' WHERE `token` = 'acl_enterprise'");

    exit;
}

###############################################################################
# Enable enterprise ACL
# Related option: --enable_eacl
###############################################################################
sub cli_enable_eacl ($$) {
	my ($conf, $dbh) = @_;

	print "[INFO] Enabling Enterprise ACL system (system wide)\n\n";

    db_do ($dbh, "UPDATE tconfig SET `value` ='1' WHERE `token` = 'acl_enterprise'");
    	
    exit;
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub pandora_manage_main ($$$) {
	my ($conf, $dbh, $history_dbh) = @_;

	my @args = @ARGV;
 	my $ltotal=$#args; 
	my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
		print "[ERROR] No valid arguments\n\n";
		help_screen();
		exit;
 	}
	else {
		$param = $args[1];

		# help!
		if ($param =~ m/--*h\w*\z/i ) {
			$param = '';
			help_screen () ;
			exit;
		}
		elsif ($param eq '--disable_alerts') {
			param_check($ltotal, 0);
	        cli_disable_alerts ($conf, $dbh);
	    }
		elsif ($param eq '--enable_alerts') {
			param_check($ltotal, 0);
	        cli_enable_alerts ($conf, $dbh);
		} 
		elsif ($param eq '--disable_eacl') {
			param_check($ltotal, 0);
	        cli_disable_eacl ($conf, $dbh);
		} 
		elsif ($param eq '--enable_eacl') {
			param_check($ltotal, 0);
            pandora_enable_eacl ($conf, $dbh);
		} 
        elsif ($param eq '--disable_group') {
			param_check($ltotal, 1);
			cli_disable_group();
		}
		elsif ($param eq '--enable_group') {
			param_check($ltotal, 1);
			cli_enable_group();
		}
		elsif ($param eq '--create_agent') {
			param_check($ltotal, 7, 3);
			cli_create_agent();
		}
		elsif ($param eq '--delete_agent') {
			param_check($ltotal, 1);
			cli_delete_agent();
		}
		elsif ($param eq '--create_data_module') {
			param_check($ltotal, 15, 12);
			cli_create_data_module();
		}
		elsif ($param eq '--create_network_module') {
			param_check($ltotal, 17, 13);
			cli_create_network_module();
		}
		elsif ($param eq '--create_snmp_module') {
			param_check($ltotal, 26, 20);
			cli_create_snmp_module();
		}
		elsif ($param eq '--create_plugin_module') {
			param_check($ltotal, 21, 12);
			cli_create_plugin_module();
		}
		elsif ($param eq '--delete_module') {
			param_check($ltotal, 2);
			cli_delete_module();
		}
		elsif ($param eq '--delete_not_policy_modules') {
			param_check($ltotal, 0);
			cli_delete_not_policy_modules();
		}
		elsif ($param eq '--create_template_module') {
			param_check($ltotal, 3);
			cli_create_template_module();
		}
		elsif ($param eq '--delete_template_module') {
			param_check($ltotal, 3);
			cli_delete_template_module();
		}
		elsif ($param eq '--create_template_action') {
			param_check($ltotal, 6, 2);
			cli_create_template_action();
		}
		elsif ($param eq '--delete_template_action') {
			param_check($ltotal, 4);
			cli_delete_template_action();
		}
		elsif ($param eq '--data_module') {
			param_check($ltotal, 5, 1);
			cli_data_module();
		}
		elsif ($param eq '--create_user') {
			param_check($ltotal, 4, 1);
			cli_create_user();
		}
		elsif ($param eq '--delete_user') {
			param_check($ltotal, 1);
			cli_delete_user();
		}
		elsif ($param eq '--create_profile') {
			param_check($ltotal, 3);
			cli_create_profile();
		}
		elsif ($param eq '--delete_profile') {
			param_check($ltotal, 3);
			cli_delete_profile();
		}
		elsif ($param eq '--create_event') {
			param_check($ltotal, 8, 3);
			cli_create_event();
		}
		elsif ($param eq '--validate_event') {
			param_check($ltotal, 7, 6);
			cli_validate_event();
		}
		elsif ($param eq '--create_incident') {
			param_check($ltotal, 7, 1);
			cli_create_incident();
		}
		elsif ($param eq '--delete_data') {
			param_check($ltotal, 3, 1);
			cli_delete_data($ltotal);
		}
		elsif ($param eq '--apply_policy') {
			param_check($ltotal, 1);
			cli_apply_policy();
		}
		elsif ($param eq '--disable_policy_alerts') {
			param_check($ltotal, 1);
			cli_disable_policy_alerts();
		}
		else {
			print "[ERROR] Invalid option '$param'.\n\n";
			$param = '';
			help_screen ();
			exit;
		}
	}

     print "\n[*] Successful execution. Exiting !\n\n";

    exit;
}
