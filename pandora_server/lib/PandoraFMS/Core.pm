package PandoraFMS::Core;
##########################################################################
# Core Pandora FMS functions.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2010 Artica Soluciones Tecnologicas S.L
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

=head1 NAME 

PandoraFMS::Core - Core functions of Pandora FMS

=head1 VERSION

Version 3.1

=head1 SYNOPSIS

 use PandoraFMS::Core;

=head1 DESCRIPTION

This module contains all the base functions of B<Pandora FMS>, the most basic operations of the system are done here.

=head2 Interface
Exported Functions:

=over

=item * C<pandora_audit>

=item * C<pandora_create_agent>

=item * C<pandora_create_incident>

=item * C<pandora_create_module>

=item * C<pandora_evaluate_alert>

=item * C<pandora_evaluate_compound_alert>

=item * C<pandora_evaluate_snmp_alerts>

=item * C<pandora_event>

=item * C<pandora_execute_alert>

=item * C<pandora_execute_action>

=item * C<pandora_exec_forced_alerts>

=item * C<pandora_generate_alerts>

=item * C<pandora_generate_compound_alerts>

=item * C<pandora_module_keep_alive>

=item * C<pandora_module_keep_alive_nd>

=item * C<pandora_ping>

=item * C<pandora_ping_latency>

=item * C<pandora_planned_downtime>

=item * C<pandora_process_alert>

=item * C<pandora_process_module>

=item * C<pandora_reset_server>

=item * C<pandora_server_keep_alive>

=item * C<pandora_update_agent>

=item * C<pandora_update_module_on_error>

=item * C<pandora_update_server>

=item * C<pandora_group_statistics>

=item * C<pandora_server_statistics>

=item * C<pandora_self_monitoring>

=back

=head1 METHODS

=cut

use strict;
use warnings;

use DBI;
use XML::Simple;
use HTML::Entities;
use Time::Local;
use POSIX qw(strftime);

use PandoraFMS::DB;
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::GIS qw(distance_moved);

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
	pandora_audit
	pandora_create_agent
	pandora_create_incident
	pandora_create_module
	pandora_evaluate_alert
	pandora_evaluate_compound_alert
	pandora_evaluate_snmp_alerts
	pandora_event
	pandora_execute_alert
	pandora_execute_action
	pandora_exec_forced_alerts
	pandora_generate_alerts
	pandora_generate_compound_alerts
	pandora_module_keep_alive
	pandora_module_keep_alive_nd
	pandora_ping
	pandora_ping_latency
	pandora_planned_downtime
	pandora_process_alert
	pandora_process_module
	pandora_reset_server
	pandora_server_keep_alive
	pandora_update_agent
	pandora_update_module_on_error
	pandora_update_server
	pandora_group_statistics
	pandora_server_statistics
	pandora_self_monitoring
	@ServerTypes
	);

# Some global variables
our @DayNames = qw(monday tuesday wednesday thursday friday saturday sunday);
our @ServerTypes = qw (dataserver networkserver snmpconsole reconserver pluginserver predictionserver wmiserver exportserver inventoryserver webserver);
our @AlertStatus = ('Execute the alert', 'Do not execute the alert', 'Do not execute the alert, but increment its internal counter', 'Cease the alert', 'Recover the alert', 'Reset internal counter');

##########################################################################
=head2 C<< pandora_generate_alerts (I<$pa_config> I<$data> I<$status> I<$agent> I<$module> I<$utimestamp> I<$dbh> I<$extraMacros> I<$last_data_value>) >>

Generate alerts for a given I<$module>.

=cut
##########################################################################
sub pandora_generate_alerts ($$$$$$$;$$) {
	my ($pa_config, $data, $status, $agent, $module, $utimestamp, $dbh, $extraMacros, $last_data_value) = @_;

	# Do not generate alerts for disabled groups
	if (is_group_disabled ($dbh, $agent->{'id_grupo'})) {
		return;
	}

	# Get enabled alerts associated with this module
	my @alerts = get_db_rows ($dbh, 'SELECT talert_template_modules.id as id_template_module,
					talert_template_modules.*, talert_templates.*
					FROM talert_template_modules, talert_templates
					WHERE talert_template_modules.id_alert_template = talert_templates.id
					AND id_agent_module = ? AND disabled = 0', $module->{'id_agente_modulo'});

	foreach my $alert (@alerts) {
		my $rc = pandora_evaluate_alert($pa_config, $agent, $data, $status, $alert,
				$utimestamp, $dbh,  $last_data_value);

		pandora_process_alert ($pa_config, $data, $agent, $module,
		                       $alert, $rc, $dbh, $extraMacros);

		# Evaluate compound alerts even if the alert status did not change in
		# case the compound alert does not recover
		pandora_generate_compound_alerts ($pa_config, $data, $status, $agent, $module,
						$alert, $utimestamp, $dbh)
	}
}

##########################################################################
=head2 C<< pandora_evaluate_alert (I<$pa_config>, I<$agent>, I<$data>, I<$last_status>, I<$alert>, I<$utimestamp>, I<$dbh>) >>

Evaluate trigger conditions for a given alert.

B<Returns>:
 0 Execute the alert.
 1 Do not execute the alert.
 2 Do not execute the alert, but increment its internal counter.
 3 Cease the alert.
 4 Recover the alert.
 5 Reset internal counter (alert not fired, interval elapsed).

=cut
##########################################################################
sub pandora_evaluate_alert ($$$$$$$) {
	my ($pa_config, $agent, $data, $last_status, $alert, $utimestamp, $dbh, $last_data_value) = @_;

	logger ($pa_config, "Evaluating alert '" . $alert->{'name'} . "' for agent '" . $agent->{'nombre'} . "'.", 10);

	# Value returned on valid data
	my $status = 1;

	# Get current time
	my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time());

	# Check weekday
	return 1 if ($alert->{$DayNames[$wday]} != 1);

	# Check time slot
	my $time = sprintf ("%.2d:%.2d:%.2d", $hour, $min, $sec);
	return 1 if (($alert->{'time_to'} ne $alert->{'time_from'}) &&
		(($time ge $alert->{'time_to'}) || ($time le $alert->{'time_from'})));

	# Check time threshold
	my $limit_utimestamp = $alert->{'last_reference'} + $alert->{'time_threshold'};

	if ($alert->{'times_fired'} > 0) {

		# Reset fired alerts
		if ($utimestamp > $limit_utimestamp) {

			# Cease on valid data
			$status = 3;

			# Always reset
			($alert->{'internal_counter'}, $alert->{'times_fired'}) = (0, 0);
		}

		# Recover takes precedence over cease
		$status = 4 if ($alert->{'recovery_notify'} == 1);

	} elsif ($utimestamp > $limit_utimestamp) {
		$status = 5;
	}

	# Check for valid data
	# Simple alert
	if (defined ($alert->{'id_template_module'})) {
		return $status if ($alert->{'type'} eq "min" && $data >= $alert->{'min_value'});
		return $status if ($alert->{'type'} eq "max" && $data <= $alert->{'max_value'});

		if ($alert->{'type'} eq "max_min") {
			if ($alert->{'matches_value'} == 1) {
				return $status if ($data <= $alert->{'min_value'} ||
						$data >= $alert->{'max_value'});
			} else {
				return $status if ($data >= $alert->{'min_value'} &&
						$data <= $alert->{'max_value'});
			}
		}

		if ($alert->{'type'} eq "onchange") {

			if (is_numeric($last_data_value)){
				if ($last_data_value == $data){
					return $status;
				}
			} else {
				if ($last_data_value eq $data){
					return $status;
				}
			}
		}
		
		return $status if ($alert->{'type'} eq "equal" && $data != $alert->{'value'});
		return $status if ($alert->{'type'} eq "not_equal" && $data == $alert->{'value'});
		if ($alert->{'type'} eq "regex") {
			if ($alert->{'matches_value'} == 1) {
				return $status if ($data !~ m/$alert->{'value'}/i);
			} else {
				return $status if ($data =~ m/$alert->{'value'}/i);
			}
		}

		return $status if ($last_status != 1 && $alert->{'type'} eq 'critical');
		return $status if ($last_status != 2 && $alert->{'type'} eq 'warning');
	}
	# Compound alert
	elsif (pandora_evaluate_compound_alert($pa_config, $alert->{'id'}, $alert->{'name'}, $dbh) == 0) {
		return $status
	}

	# Check min and max alert limits
	return 2 if (($alert->{'internal_counter'} < $alert->{'min_alerts'}) ||
			($alert->{'times_fired'} >= $alert->{'max_alerts'}));

	return 0; #Launch the alert
}

##########################################################################
=head2 C<< pandora_process_alert (I<$pa_config>, I<$data>, I<$agent>, I<$module>, I<$alert>, I<$rc>, I<$dbh>) >> 

Process an alert given the status returned by pandora_evaluate_alert.

=cut
##########################################################################
sub pandora_process_alert ($$$$$$$;$) {
	my ($pa_config, $data, $agent, $module, $alert, $rc, $dbh, $extraMacros) = @_;
	
	logger ($pa_config, "Processing alert '" . $alert->{'name'} . "' for agent '" . $agent->{'nombre'} . "': " . (defined ($AlertStatus[$rc]) ? $AlertStatus[$rc] : 'Unknown status') . ".", 10);

	# Simple or compound alert?
	my $id = defined ($alert->{'id_template_module'}) ? $alert->{'id_template_module'} : $alert->{'id'};
	my $table = defined ($alert->{'id_template_module'}) ? 'talert_template_modules' : 'talert_compound';

	# Do not execute
	return if ($rc == 1);

	# Cease
	if ($rc == 3) {

		# Update alert status
		db_do($dbh, 'UPDATE ' . $table . ' SET times_fired = 0,
				 internal_counter = 0 WHERE id = ?', $id);

		# Generate an event
		pandora_event ($pa_config, "Alert ceased (" .
					$alert->{'name'} . ")", $agent->{'id_grupo'},
					$agent->{'id_agente'}, $alert->{'priority'}, $id, $alert->{'id_agent_module'}, 
					"alert_ceased", 0, $dbh);

		return;
	}

	# Recover
	if ($rc == 4) {

		# Update alert status
		db_do($dbh, 'UPDATE ' . $table . ' SET times_fired = 0,
				 internal_counter = 0 WHERE id = ?', $id);

		pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 0, $dbh, $extraMacros);
		return;
	}

	# Reset internal counter
	if ($rc == 5) {
		db_do($dbh, 'UPDATE ' . $table . ' SET internal_counter = 0 WHERE id = ?', $id);
		return;
	}

	# Get current date
	my $utimestamp = time ();

	# Do we have to start a new interval?
	my $new_interval = ($alert->{'internal_counter'} == 0) ?
			', last_reference = ' . $utimestamp : '';

	# Increment internal counter
	if ($rc == 2) {

		# Update alert status
		$alert->{'internal_counter'} += 1;

		# Do not increment times_fired, but set it in case the alert was reset
		db_do($dbh, 'UPDATE ' . $table . ' SET times_fired = ?,
			internal_counter = ? ' . $new_interval . ' WHERE id = ?',
			$alert->{'times_fired'}, $alert->{'internal_counter'}, $id);

		return;
	}

	# Execute
	if ($rc == 0) {

		# Update alert status
		$alert->{'times_fired'} += 1;
		$alert->{'internal_counter'} += 1;

		db_do($dbh, 'UPDATE  ' . $table . '  SET times_fired = ?,
				last_fired = ?, internal_counter = ? ' . $new_interval . ' WHERE id = ?',
			$alert->{'times_fired'}, $utimestamp, $alert->{'internal_counter'}, $id);
		pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 1, $dbh, $extraMacros);
		return;
	}
}

##########################################################################
=head2 C<< pandora_evaluate_compound_alert (I<$pa_config>, I<$id>, I<$name>, I<$dbh>) >> 

Evaluate the given compound alert. Returns 1 if the alert should be
fired, 0 if not.

=cut
##########################################################################
sub pandora_evaluate_compound_alert ($$$$) {
	my ($pa_config, $id, $name, $dbh) = @_;

	logger ($pa_config, "Evaluating compound alert '$name'.", 10);

	# Return value
	my $status = 0;

	# Get all the alerts associated with this compound alert
	my @compound_alerts = get_db_rows ($dbh, 'SELECT id_alert_template_module, operation FROM talert_compound_elements
						 WHERE id_alert_compound = ? ORDER BY `order`', $id);

	foreach my $compound_alert (@compound_alerts) {

		# Get alert data if enabled
		my $times_fired = get_db_value ($dbh, "SELECT times_fired FROM talert_template_modules WHERE id = ?
						AND disabled = 0", $compound_alert->{'id_alert_template_module'});
		next unless defined ($times_fired);

		# Check whether the alert was fired
		my $fired = ($times_fired > 0) ? 1 : 0;
		my $operation = $compound_alert->{'operation'};

		# Operate...
		if ($operation eq "AND") {
			$status &= $fired;
		}
		elsif ($operation eq "OR") {
			$status |= $fired;
		}
		elsif ($operation eq "XOR") {
			$status ^= $fired;
		}
		elsif ($operation eq "NAND") {
			$status &= ! $fired;
		}
		elsif ($operation eq "NOR") {
			$status |= ! $fired;
		}
		elsif ($operation eq "NXOR") {
			$status ^= ! $fired;
		}
		elsif ($operation eq "NOP") {
			$status = $fired;
		} else {
			logger ($pa_config, "Unknown operation: $operation.", 3);
		}
	}

	return $status;
}

##########################################################################
=head2 C<< pandora_generate_compound_alerts (I<$pa_config>, I<$data>, I<$status>, I<$agent>, I<$module>, I<$alert>, I<$utimestamp>, I<$dbh>) >> 

Generate compound alerts that depend on a given alert.

=cut
##########################################################################
sub pandora_generate_compound_alerts ($$$$$$$$) {
	my ($pa_config, $data, $status, $agent, $module, $alert, $utimestamp, $dbh) = @_;

	# Get all compound alerts that depend on this alert
	my @elements = get_db_rows ($dbh, 'SELECT id_alert_compound FROM talert_compound_elements
				WHERE id_alert_template_module = ?',
						$alert->{'id_template_module'});

	foreach my $element (@elements) {

		# Get compound alert parameters
		my $compound_alert = get_db_single_row ($dbh, 'SELECT * FROM talert_compound WHERE id = ?', $element->{'id_alert_compound'});
		next unless defined ($compound_alert);

		# Evaluate the alert
		my $rc = pandora_evaluate_alert ($pa_config, $agent, $data, $status, $compound_alert,
						$utimestamp, $dbh);

		pandora_process_alert ($pa_config, $data, $agent, $module,
					$compound_alert, $rc, $dbh);
	}
}

##########################################################################
=head2 C<< pandora_execute_alert (I<$pa_config>, I<$data>, I<$agent>, I<$module>, I<$alert>, I<$alert_mode>, I<$dbh>) >> 

Execute the given alert.

=cut
##########################################################################
sub pandora_execute_alert ($$$$$$$;$) {
	my ($pa_config, $data, $agent, $module,
	    $alert, $alert_mode, $dbh, $extraMacros) = @_;
	
	logger ($pa_config, "Executing alert '" . $alert->{'name'} . "' for module '" . $module->{'nombre'} . "'.", 10);

	# Get active actions/commands
	my @actions;

	# Simple alert
	if (defined ($alert->{'id_template_module'})) {
		@actions = get_db_rows ($dbh, 'SELECT * FROM talert_template_module_actions, talert_actions, talert_commands
					WHERE talert_template_module_actions.id_alert_action = talert_actions.id
					AND talert_actions.id_alert_command = talert_commands.id
					AND talert_template_module_actions.id_alert_template_module = ?
					AND ((fires_min = 0 AND fires_max = 0)
					OR (? >= fires_min AND ? <= fires_max))', 
					$alert->{'id_template_module'}, $alert->{'times_fired'}, $alert->{'times_fired'});	

		# Get default action
		if ($#actions < 0) {
			@actions = get_db_rows ($dbh, 'SELECT * FROM talert_actions, talert_commands
						WHERE talert_actions.id = ?
						AND talert_actions.id_alert_command = talert_commands.id',
						$alert->{'id_alert_action'}, );
		}
	}
	# Compound alert
	else {
		@actions = get_db_rows ($dbh, 'SELECT * FROM talert_compound_actions, talert_actions, talert_commands
					WHERE talert_compound_actions.id_alert_action = talert_actions.id
					AND talert_actions.id_alert_command = talert_commands.id
					AND talert_compound_actions.id_alert_compound = ?
					AND ((fires_min = 0 AND fires_max = 0)
					OR (? >= fires_min AND ? <= fires_max))',
					$alert->{'id'}, $alert->{'times_fired'}, $alert->{'times_fired'});
	}

	# No actions defined
	if ($#actions < 0) {
		logger ($pa_config, "No actions defined for alert '" . $alert->{'name'} . "' module '" . $module->{'nombre'} . "'.", 10);
		return;
	}

	# Execute actions
	foreach my $action (@actions) {
		pandora_execute_action ($pa_config, $data, $agent, $alert, $alert_mode, $action, $module, $dbh, $extraMacros);
	}

	# Generate an event
	my ($text, $event) = ($alert_mode == 0) ? ('recovered', 'alert_recovered') : ('fired', 'alert_fired');

	pandora_event ($pa_config, "Alert $text (" . $alert->{'name'} . ") assigned to (". $module->{'nombre'} .")",
			$agent->{'id_grupo'}, $agent->{'id_agente'}, $alert->{'priority'}, (defined ($alert->{'id_template_module'})) ? $alert->{'id_template_module'} : 0,
			$alert->{'id_agent_module'}, $event, 0, $dbh);
}

##########################################################################
=head2 C<< pandora_execute_action (I<$pa_config>, I<$data>, I<$agent>, I<$alert>, I<$alert_mode>, I<$action>, I<$module>, I<$dbh>) >> 

Execute the given action.

=cut
##########################################################################
sub pandora_execute_action ($$$$$$$$;$) {
	my ($pa_config, $data, $agent, $alert,
	    $alert_mode, $action, $module, $dbh, $extraMacros) = @_;

	logger($pa_config, "Executing action '" . $action->{'name'} . "' for alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'nombre'} : 'N/A') . "'.", 10);

	my $field1 = $action->{'field1'} ne "" ? $action->{'field1'} : $alert->{'field1'};
	my $field2 = $action->{'field2'} ne "" ? $action->{'field2'} : $alert->{'field2'};
	my $field3 = $action->{'field3'} ne "" ? $action->{'field3'} : $alert->{'field3'};		

	# Recovery fields, thanks to Kato Atsushi
	if ($alert_mode == 0){
		$field2 = $alert->{'field2_recovery'} ne "" ? $alert->{'field2_recovery'} : "[RECOVER]" . $field2;
		$field3 = $alert->{'field3_recovery'} ne "" ? $alert->{'field3_recovery'} : "[RECOVER]" . $field3;
	}

	$field1 = decode_entities ($field1);
	$field2 = decode_entities ($field2);
	$field3 = decode_entities ($field3);

	# Thanks to people of Cordoba univ. for the patch for adding module and 
	# id_agent macros to the alert.
	
	# Alert macros
	my %macros = (_field1_ => $field1,
				_field2_ => $field2,
				_field3_ => $field3,
				_agent_ => (defined ($agent)) ? $agent->{'nombre'} : '',
				_agentdescription_ => (defined ($agent)) ? $agent->{'comentarios'} : '',
				_agentgroup_ => (defined ($agent)) ? get_group_name ($dbh, $agent->{'id_grupo'}) : '',
				_address_ => (defined ($agent)) ? $agent->{'direccion'} : '',
				_timestamp_ => strftime ("%Y-%m-%d %H:%M:%S", localtime()),
				_data_ => $data,
				_alert_name_ => $alert->{'name'},
				_alert_description_ => $alert->{'description'},
				_alert_threshold_ => $alert->{'time_threshold'},
				_alert_times_fired_ => $alert->{'times_fired'},
				_alert_priority_ => $alert->{'priority'},
				_module_ => (defined ($module)) ? $module->{'nombre'} : '',
				_moduledescription_ => (defined ($module)) ? $module->{'descripcion'} : '',
				_id_agent_ => (defined ($module)) ? $module->{'id_agente'} : '', 
				_id_alert_ => $alert->{'id'}
				 );
	
	if (defined ($extraMacros)){
		while ((my $macro, my $value) = each (%{$extraMacros})) {
			$macros{$macro} = $value;
		}
	}

	# User defined alerts
	if ($action->{'internal'} == 0) {
		my $command = subst_alert_macros (decode_entities ($action->{'command'}), \%macros);
		$command = subst_alert_macros ($command, \%macros);
		logger($pa_config, "Executing command '$command' for action '" . $action->{'name'} . "' alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'nombre'} : 'N/A') . "'.", 8);

		eval {
			system ($command);
			logger($pa_config, "Command '$command' for action '" . $action->{'name'} . "' alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'nombre'} : 'N/A') . "' returned with errorlevel " . ($? >> 8), 8);
		};

		if ($@){
			logger($pa_config, "Error $@ executing command '$command' for action '" . $action->{'name'} . "' alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'nombre'} : 'N/A') ."'.", 8);
		}

	# Internal Audit
	} elsif ($action->{'name'} eq "Internal Audit") {
		$field1 = subst_alert_macros ($field1, \%macros);
		pandora_audit ($pa_config, $field1, defined ($agent) ? $agent->{'nombre'} : 'N/A', 'Alert (' . $alert->{'description'} . ')', $dbh);

	# Email		
	} elsif ($action->{'name'} eq "eMail") {
		$field2 = subst_alert_macros ($field2, \%macros);
		$field3 = subst_alert_macros ($field3, \%macros);
		foreach my $address (split (',', $field1)) {
			# Remove blanks
			$address =~ s/ +//g;
			pandora_sendmail ($pa_config, $address, $field2, $field3);
		}

	# Internal event
	} elsif ($action->{'name'} eq "Pandora FMS Event") {

	# Unknown
	} else {
		logger($pa_config, "Unknown action '" . $action->{'name'} . "' for alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'nombre'} : 'N/A') . "'.", 3);
	}
}

##########################################################################
=head2 C<< pandora_access_update (I<$pa_config>, I<$agent_id>, I<$dbh>) >> 

Update agent access table.

=cut
##########################################################################
sub pandora_access_update ($$$) {
	my ($pa_config, $agent_id, $dbh) = @_;

	return if ($agent_id < 0);

	if ($pa_config->{"agentaccess"} == 0){
		return;
	}
	db_insert ($dbh, "INSERT INTO tagent_access (`id_agent`, `utimestamp`) VALUES (?, ?)", $agent_id, time ());
}

##########################################################################
=head2 C<< pandora_process_module (I<$pa_config>, I<$data>, I<$agent>, I<$module>, I<$module_type>, I<$timestamp>, I<$utimestamp>, I<$server_id>, I<$dbh>) >> 

Process Pandora module.

=cut
##########################################################################
sub pandora_process_module ($$$$$$$$$;$) {
	my ($pa_config, $dataObject, $agent, $module, $module_type,
	    $timestamp, $utimestamp, $server_id, $dbh, $extraMacros) = @_;
	
	logger($pa_config, "Processing module '" . $module->{'nombre'} . "' for agent " . (defined ($agent) && $agent ne '' ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 10);

	# Get module type
	if (! defined ($module_type) || $module_type eq '') {
		$module_type = get_db_value ($dbh, 'SELECT nombre FROM ttipo_modulo WHERE id_tipo = ?', $module->{'id_tipo_modulo'});
		if (! defined ($module_type)) {
			logger($pa_config, "Invalid module type ID " . $module->{'id_tipo_modulo'} . " module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 10);
			pandora_update_module_on_error ($pa_config, $module->{'id_agente_modulo'}, $dbh);
			return;
		}
	}

	# Get agent information
	if (! defined ($agent) || $agent eq '') {
		$agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
		if (! defined ($agent)) {
			logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found while processing module '" . $module->{'nombre'} . "'.", 3);
			pandora_update_module_on_error ($pa_config, $module->{'id_agente_modulo'}, $dbh);
			return;
		}
	}

	# Process data
 	my $processed_data = process_data ($dataObject, $module, $module_type, $utimestamp, $dbh);
 	if (! defined ($processed_data)) {
		logger($pa_config, "Received invalid data '" . $dataObject . "' from agent '" . $agent->{'nombre'} . "' module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 3);
		pandora_update_module_on_error ($pa_config, $module->{'id_agente_modulo'}, $dbh);
		return;
	}

	$timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp)) if (! defined ($timestamp) || $timestamp eq '');

	# Export data
	export_module_data ($pa_config, $processed_data, $agent, $module, $module_type, $timestamp, $dbh);

	# Get previous status
	my $agent_status = get_db_single_row ($dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});
	if (! defined ($agent_status)) {
		logger($pa_config, "Status for agent '" . $agent->{'nombre'} . "' not found while processing module " . $module->{'nombre'} . ".", 3);
		pandora_update_module_on_error ($pa_config, $module->{'id_agente_modulo'}, $dbh);
		return;
	}
	my $last_status = $agent_status->{'last_status'};
	my $status = $agent_status->{'estado'};
	my $status_changes = $agent_status->{'status_changes'};
	my $last_data_value = $agent_status->{'datos'};

	# Get new status
	my $new_status = get_module_status ($processed_data, $module, $module_type);

	#Update module status
	my $current_utimestamp = time ();
	if ($last_status == $new_status) {
		$status_changes++;
	} else {
		$status_changes = 0;
	}

	if ($status_changes == $module->{'min_ff_event'}) {
		generate_status_event ($pa_config, $processed_data, $agent, $module, $new_status, $status, $dbh);
		$status = $new_status;
	}

	$last_status = $new_status;

	# Generate alerts
	if (pandora_inhibit_alerts ($pa_config, $agent, $dbh) == 0) {
		pandora_generate_alerts ($pa_config, $processed_data, $status, $agent, $module, $utimestamp, $dbh, $extraMacros, $last_data_value);
	}
	
	# tagente_estado.last_try defaults to NULL, should default to '0000-00-00 00:00:00'
	$agent_status->{'last_try'} = '0000-00-00 00:00:00' unless defined ($agent_status->{'last_try'});

	# Do we have to save module data?
	if ($agent_status->{'last_try'} !~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/) {
		logger($pa_config, "Invalid last try timestamp '" . $agent_status->{'last_try'} . "' for agent '" . $agent->{'nombre'} . "' not found while processing module '" . $module->{'nombre'} . "'.", 3);
		pandora_update_module_on_error ($pa_config, $module->{'id_agente_modulo'}, $dbh);
		return;
	}

	my $last_try = ($1 == 0) ? 0 : timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);
	my $save = ($module->{'history_data'} == 1 && ($agent_status->{'datos'} ne $processed_data || $last_try < (time() - 86400))) ? 1 : 0;
		
	db_do ($dbh, 'UPDATE tagente_estado SET datos = ?, estado = ?, last_status = ?, status_changes = ?, utimestamp = ?, timestamp = ?,
		id_agente = ?, current_interval = ?, running_by = ?, last_execution_try = ?, last_try = ?
		WHERE id_agente_modulo = ?', $processed_data, $status, $last_status, $status_changes,
		$current_utimestamp, $timestamp, $module->{'id_agente'}, $module->{'module_interval'}, $server_id,
		$utimestamp, ($save == 1) ? $timestamp : $agent_status->{'last_try'}, $module->{'id_agente_modulo'});

	# Save module data
	if ($module_type eq "log4x" || $save == 1) {
		save_module_data ($dataObject, $module, $module_type, $utimestamp, $dbh);
	}
}

##########################################################################
=head2 C<< pandora_planned_downtime (I<$pa_config>, I<$dbh>) >> 

Update planned downtimes.

=cut
##########################################################################
sub pandora_planned_downtime ($$) {
	my ($pa_config, $dbh) = @_;
	my $utimestamp = time();

	# Start pending downtimes (disable agents)
	my @downtimes = get_db_rows($dbh, 'SELECT * FROM tplanned_downtime WHERE executed = 0 AND date_from <= ? AND date_to >= ?', $utimestamp, $utimestamp);

	foreach my $downtime (@downtimes) {

		logger($pa_config, "Starting planned downtime '" . $downtime->{'name'} . "'.", 10);

		db_do($dbh, 'UPDATE tplanned_downtime SET executed = 1 WHERE id = ?', 	$downtime->{'id'});
		pandora_event ($pa_config, "Server ".$pa_config->{'servername'}." started planned downtime: ".$downtime->{'description'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		my @downtime_agents = get_db_rows($dbh, 'SELECT * FROM tplanned_downtime_agents WHERE id_downtime = ' . $downtime->{'id'});
		
		foreach my $downtime_agent (@downtime_agents) {
			db_do ($dbh, 'UPDATE tagente SET disabled = 1 WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
	}

	# Stop executed downtimes (enable agents)
	@downtimes = get_db_rows($dbh, 'SELECT * FROM tplanned_downtime WHERE executed = 1 AND date_to <= ?', $utimestamp);
	foreach my $downtime (@downtimes) {

		logger($pa_config, "Ending planned downtime '" . $downtime->{'name'} . "'.", 10);

		db_do($dbh, 'UPDATE tplanned_downtime SET executed = 0 WHERE id = ?', $downtime->{'id'});

		pandora_event ($pa_config, 'Server ' . $pa_config->{'servername'} . ' stopped planned downtime: ' . $downtime->{'description'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);

		my @downtime_agents = get_db_rows($dbh, 'SELECT * FROM tplanned_downtime_agents WHERE id_downtime = ' . $downtime->{'id'});

		foreach my $downtime_agent (@downtime_agents) {
			db_do ($dbh, 'UPDATE tagente SET disabled = 0 WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
	}
}

##########################################################################
=head2 C<< pandora_reset_server (I<$pa_config>, I<$dbh>) >> 

Reset the status of all server types for the current server.

=cut
##########################################################################
sub pandora_reset_server ($$) {
	my ($pa_config, $dbh) = @_;

	db_do ($dbh, 'UPDATE tserver SET status = 0, threads = 0, queued_modules = 0 WHERE name = ?', $pa_config->{'servername'});
}

##########################################################################
=head2 C<< pandora_update_server (I<$pa_config>, I<$dbh>, I<$server_name>, I<$status>, I<$server_type>, I<$num_threads>, I<$queue_size>) >> 

Update server status: 
 0 dataserver
 1 network server
 2 snmp console, 
 3 recon
 4 plugin
 5 prediction
 6 wmi.

=cut
##########################################################################
sub pandora_update_server ($$$$$;$$) {
	my ($pa_config, $dbh, $server_name, $status,
		$server_type, $num_threads, $queue_size) = @_;

	$num_threads = 0 unless defined ($num_threads);
	$queue_size = 0 unless defined ($queue_size);

	my $server = get_db_single_row ($dbh, 'SELECT * FROM tserver WHERE name = ? AND server_type = ?', $server_name, $server_type);
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());

	# Create an entry in tserver
	if (! defined ($server)){ 
		my $server_id = db_insert ($dbh, 'INSERT INTO tserver (`name`, `server_type`, `description`, `version`, `threads`, `queued_modules`)
						VALUES (?, ?, ?, ?, ?, ?)', $server_name, $server_type,
						'Autocreated at startup', $pa_config->{'version'} . ' (P) ' . $pa_config->{'build'}, $num_threads, $queue_size);
		$server = get_db_single_row ($dbh, 'SELECT * FROM tserver
						WHERE id_server = ?',
						$server_id);
		if (! defined ($server)) {
			logger($pa_config, "Server '" . $pa_config->{'servername'} . "' not found.", 3);
			return;
		}
	}

	# Server going up
	if ($server->{'status'} == 0) {
		my $version = $pa_config->{'version'} . ' (P) ' . $pa_config->{'build'};

		db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, laststart = ?, version = ?, threads = ?, queued_modules = ?
				WHERE id_server = ?',
				$status, $timestamp, $pa_config->{'pandora_master'}, $timestamp, $version, $num_threads, $queue_size, $server->{'id_server'});
		return;
	}

	db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, threads = ?, queued_modules = ?
			WHERE id_server = ?', $status, $timestamp, $pa_config->{'pandora_master'}, $num_threads, $queue_size, $server->{'id_server'});
}

##########################################################################
=head2 C<< pandora_update_agent (I<$pa_config>, I<$agent_timestamp>, I<$agent_id>, I<$os_version>, I<$agent_version>, I<$agent_interval>, I<$dbh>, [I<$timezone_offset>], [I<$longitude>], [I<$latitude>], [I<$altitude>], [I<$position_description>]) [I<$parent_agent_name>]) >>

Update last contact, timezone fields in B<tagente> and current position (this
can affect B<tgis_data_status> and B<tgis_data_history>). If the I<$parent_agent_id> is 
defined also the parent is updated.

=cut
##########################################################################
sub pandora_update_agent ($$$$$$$;$$$$$$) {
    my ($pa_config, $agent_timestamp, $agent_id, $os_version,
        $agent_version, $agent_interval, $dbh, $timezone_offset,
		$longitude, $latitude, $altitude, $position_description, $parent_agent_id) = @_;

    my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());


	# No access update for data without interval.
	# Single modules from network server, for example. This could be very
	# Heavy for Pandora FMS
	if ($agent_interval != -1){
	    pandora_access_update ($pa_config, $agent_id, $dbh);
	}
	
    # No update for interval, timezone and position fields (some old agents don't support it)
    if ($agent_interval == -1){
        db_do($dbh, 'UPDATE tagente SET agent_version = ?, ultimo_contacto_remoto = ?, ultimo_contacto = ?, os_version = ? WHERE id_agente = ?',
        $agent_version, $agent_timestamp, $timestamp, $os_version, $agent_id);
        return;
    }
	
	if ( defined ($timezone_offset)) {
		if (defined($parent_agent_id)) {
			# Update the table tagente with all the new data and set the new parent
			db_do ($dbh, 'UPDATE tagente SET intervalo = ?, agent_version = ?, ultimo_contacto_remoto = ?, ultimo_contacto = ?, os_version = ?, 
			   	  timezone_offset = ?, id_parent = ? WHERE id_agente = ?', $agent_interval, $agent_version, $agent_timestamp, $timestamp, $os_version, 
				  $timezone_offset, $parent_agent_id, $agent_id);
		}
		else {
			# Update the table tagente with all the new data
			db_do ($dbh, 'UPDATE tagente SET intervalo = ?, agent_version = ?, ultimo_contacto_remoto = ?, ultimo_contacto = ?, os_version = ?, 
			   	  timezone_offset = ? WHERE id_agente = ?', $agent_interval, $agent_version, $agent_timestamp, $timestamp, $os_version, $timezone_offset, $agent_id);
		}
	}
	else {
		if (defined($parent_agent_id)) {
			# Update the table tagente with all the new data and set the new parent
			db_do ($dbh, 'UPDATE tagente SET intervalo = ?, agent_version = ?, ultimo_contacto_remoto = ?, ultimo_contacto = ?, os_version = ?, id_parent = ?
						  WHERE id_agente = ?', $agent_interval, $agent_version, $agent_timestamp, $timestamp, $os_version,  $parent_agent_id, $agent_id);
		}
		else {
			# Update the table tagente with all the new data
			db_do ($dbh, 'UPDATE tagente SET intervalo = ?, agent_version = ?, ultimo_contacto_remoto = ?, ultimo_contacto = ?, os_version = ? WHERE id_agente = ?',
		   		   $agent_interval, $agent_version, $agent_timestamp, $timestamp, $os_version, $agent_id);
		}
	}

	my $update_gis_data= get_db_value ($dbh, 'SELECT update_gis_data FROM tagente WHERE id_agente = ?', $agent_id);
	#Test if we have received the optional position parameters
	if (defined ($longitude) && defined ($latitude) && $pa_config->{'activate_gis'} == 1 && $update_gis_data == 1 ){
		# Get the last position to see if it has moved.
		my $last_agent_position= get_db_single_row ($dbh, 'SELECT * FROM tgis_data_status WHERE tagente_id_agente = ?', $agent_id);
		if(defined($last_agent_position)) {

			logger($pa_config, "Old Agent data: current_longitude=". $last_agent_position->{'current_longitude'}. " current_latitude="
			   .$last_agent_position->{'current_latitude'}. " current_altitude=". $last_agent_position->{'current_altitude'}. " ID: $agent_id ", 10);

			# If the agent has moved outside the range stablised as location error
	    	if (distance_moved($pa_config, $last_agent_position->{'stored_longitude'}, $last_agent_position->{'stored_latitude'}, 
						   $last_agent_position->{'stored_altitude'}, $longitude, $latitude, $altitude) > $pa_config->{'location_error'}) {
				#Archive the old position and save new one as status
				archive_agent_position($pa_config, $last_agent_position->{'start_timestamp'},$timestamp,$last_agent_position->{'stored_longitude'}, $last_agent_position->{'stored_latitude'}, 
                          		    $last_agent_position->{'stored_altitude'},$last_agent_position->{'description'}, $last_agent_position->{'number_of_packages'},$agent_id, $dbh);
				if(!defined($altitude) ) {
					$altitude = 0;
				}
				# Save the agent position in the tgis_data_status table
				update_agent_position($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh, $longitude, $latitude, $altitude, $timestamp, $position_description);
			}
			else { #the agent has not moved enougth so just update the status table
				update_agent_position ($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh);
			}
		}
		else {
			logger($pa_config, "There was not previous positional data, storing first positioal status",8);
			save_agent_position($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh, $timestamp, $position_description);
		}
	}
	else {
		logger($pa_config, "Agent id $agent_id positional data ignored (update_gis_data = $update_gis_data)",10);
	}

}

##########################################################################
=head2 C<< pandora_module_keep_alive (I<$pa_config>, I<$id_agent>, I<$agent_name>, I<$server_id>, I<$dbh>) >> 

Updates the keep_alive module for the given agent.

=cut
##########################################################################
sub pandora_module_keep_alive ($$$$$) {
	my ($pa_config, $id_agent, $agent_name, $server_id, $dbh) = @_;
	
	logger($pa_config, "Updating keep_alive module for agent '" . $agent_name . "'.", 10);

	# Update keepalive module 
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND id_tipo_modulo = 100', $id_agent);
	return unless defined ($module);

	my %data = ('data' => 1);
	pandora_process_module ($pa_config, \%data, '', $module, 'keep_alive', '', time(), $server_id, $dbh);
}

##########################################################################
=head2 C<< pandora_create_incident (I<$pa_config>, I<$dbh>, I<$title>, I<$text>, I<$priority>, I<$status>, I<$origin>, I<$id_group>) >> 

Create an internal Pandora incident.

=cut
##########################################################################
sub pandora_create_incident ($$$$$$$$) {
	my ($pa_config, $dbh, $title, $text,
		$priority, $status, $origin, $id_group) = @_;

	logger($pa_config, "Creating incident '$text' source '$origin'.", 8);

	db_do($dbh, 'INSERT INTO tincidencia (`inicio`, `titulo`, `descripcion`, `origen`, `estado`, `prioridad`, `id_grupo`)
			VALUES (NOW(), ?, ?, ?, ?, ?, ?)', $title, $text, $origin, $status, $priority, $id_group);
}


##########################################################################
=head2 C<< pandora_audit (I<$pa_config>, I<$description>, I<$name>, I<$action>, I<$dbh>) >> 

Create an internal audit entry.

=cut
##########################################################################
sub pandora_audit ($$$$$) {
	my ($pa_config, $description, $name, $action, $dbh) = @_;
	my $disconnect = 0;

	logger($pa_config, "Creating audit entry '$description' name '$name' action '$action'.", 10);

	my $utimestamp = time();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	db_insert($dbh, 'INSERT INTO tsesion (`ID_usuario`, `IP_origen`, `accion`, `fecha`, `descripcion`, `utimestamp`) 
			VALUES (?, ?, ?, ?, ?, ?)', 
			'SYSTEM', $name, $action , $timestamp , $description , $utimestamp);

	db_disconnect($dbh) if ($disconnect == 1);
}

##########################################################################
=head2 C<< pandora_create_module (I<$pa_config>, I<$agent_id>, I<$module_type_id>, I<$module_name>, I<$max>, I<$min>, I<$post_process>, I<$description>, I<$interval>, I<$dbh>) >> 

Create a new entry in tagente_modulo and the corresponding entry in B<tagente_estado>.

=cut
##########################################################################
sub pandora_create_module ($$$$$$$$$$) {
	my ($pa_config, $agent_id, $module_type_id, $module_name, $max,
		$min, $post_process, $description, $interval, $dbh) = @_;
 
 	logger($pa_config, "Creating module '$module_name' for agent ID $agent_id.", 10);
 
	# Provide some default values	
	$max = 0 if ($max eq '');
	$min = 0 if ($min eq '');
	$post_process = 0 if ($post_process eq '');
	$description = 'N/A' if ($description eq '');

	my $module_id = db_insert($dbh, 'INSERT INTO tagente_modulo (`id_agente`, `id_tipo_modulo`, `nombre`, `max`, `min`, `post_process`, `descripcion`, `module_interval`, `id_modulo`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)', $agent_id, $module_type_id, $module_name, $max, $min, $post_process, $description, $interval);
	db_do ($dbh, 'INSERT INTO tagente_estado (`id_agente_modulo`, `id_agente`, `last_try`) VALUES (?, ?, \'0000-00-00 00:00:00\')', $module_id, $agent_id);
	return $module_id;
}

##########################################################################
=head2 C<< pandora_create_agent (I<$pa_config>, I<$server_name>, I<$agent_name>, I<$address>, I<$address_id>, I<$group_id>, I<$parent_id>, I<$os_id>, I<$description>, I<$interval>, I<$dbh>, [I<$timezone_offset>], [I<$longitude>], [I<$latitude>], [I<$altitude>], [I<$position_description>]) >>

Create a new entry in B<tagente> optionaly with position information

=cut
##########################################################################
sub pandora_create_agent ($$$$$$$$$$$;$$$$$) {
	my ($pa_config, $server_name, $agent_name, $address,
		$address_id, $group_id, $parent_id, $os_id,
		$description, $interval, $dbh, $timezone_offset,
		$longitude, $latitude, $altitude, $position_description) = @_;


	if (!defined($group_id)) {
		$group_id = $pa_config->{'autocreate_group'};
	}
	logger ($pa_config, "Server '$server_name' creating agent '$agent_name' address '$address'.", 10);

	$description = "Created by $server_name" unless ($description ne '');
	my $agent_id;
	# Test if the optional positional parameters are defined or GIS is disabled
	if (!defined ($timezone_offset) ) {
		$agent_id = db_insert ($dbh, 'INSERT INTO tagente (`nombre`, `direccion`, `comentarios`, `id_grupo`, `id_os`, `server_name`, `intervalo`, `id_parent`, `modo`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)', $agent_name, $address, $description, $group_id, $os_id, $server_name, $interval, $parent_id);
	}
	else {
		 $agent_id = db_insert ($dbh, 'INSERT INTO tagente (`nombre`, `direccion`, `comentarios`, `id_grupo`, `id_os`, `server_name`, `intervalo`, `id_parent`, 
				`timezone_offset`, `modo` ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)', $agent_name, $address, 
				 $description, $group_id, $os_id, $server_name, $interval, $parent_id, $timezone_offset);	
	}
	if (defined ($longitude) && defined ($latitude ) && $pa_config->{'activate_gis'} == 1 ) {
		if (!defined($altitude)) {
			$altitude = 0;
		}
		# Save the first position
		my $utimestamp = time ();
  	 	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));
		
		save_agent_position($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh, $timestamp,$position_description) ;
	}

	logger ($pa_config, "Server '$server_name' CREATED agent '$agent_name' address '$address'.", 10);
	pandora_event ($pa_config, "Agent [$agent_name] created by $server_name", $group_id, $agent_id, 2, 0, 0, 'new_agent', 0, $dbh);
	return $agent_id;
}

##########################################################################
=head2 C<< pandora_event (I<$pa_config>, I<$evento>, I<$id_grupo>, I<$id_agente>, I<$severity>, I<$id_alert_am>, I<$id_agentmodule>, I<$event_type>, I<$event_status>, I<$dbh>) >> 

Generate an event.

=cut
##########################################################################
sub pandora_event ($$$$$$$$$$) {
	my ($pa_config, $evento, $id_grupo, $id_agente, $severity,
		$id_alert_am, $id_agentmodule, $event_type, $event_status, $dbh) = @_;

	logger($pa_config, "Generating event '$evento' for agent ID $id_agente module ID $id_agentmodule.", 10);

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));
	$id_agentmodule = 0 unless defined ($id_agentmodule);

	db_do ($dbh, 'INSERT INTO tevento (`id_agente`, `id_grupo`, `evento`, `timestamp`, `estado`, `utimestamp`, `event_type`, `id_agentmodule`, `id_alert_am`, `criticity`)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente, $id_grupo, $evento, $timestamp, $event_status, $utimestamp, $event_type, $id_agentmodule, $id_alert_am, $severity);
}

##########################################################################
=head2 C<< pandora_update_module_on_error (I<$pa_config>, I<$id_agent_module>, I<$dbh>) >> 

Update module status on error.

=cut
##########################################################################
sub pandora_update_module_on_error ($$$) {
	my ($pa_config, $id_agent_module, $dbh) = @_;

	logger($pa_config, "Updating module ID $id_agent_module on error.", 10);

	# Update last_execution_try
	db_do ($dbh, 'UPDATE tagente_estado SET last_execution_try = ?
		WHERE id_agente_modulo = ?', time (), $id_agent_module);
}

##########################################################################
=head2 C<< pandora_exec_forced_alerts (I<$pa_config>, I<$dbh>) >>

Execute forced alerts.

=cut
##########################################################################
sub pandora_exec_forced_alerts {
	my ($pa_config, $dbh) = @_;

	# Get alerts marked for forced execution (even disabled alerts)
	my @alerts = get_db_rows ($dbh, 'SELECT talert_template_modules.id as id_template_module,
				talert_template_modules.*, talert_templates.*
				FROM talert_template_modules, talert_templates
				WHERE talert_template_modules.id_alert_template = talert_templates.id
				AND force_execution = 1');
	foreach my $alert (@alerts) {
		
		# Get the agent and module associated with the alert
		my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
		if (! defined ($module)) {
			logger($pa_config, "Module ID " . $alert->{'id_agent_module'} . " not found for alert ID " . $alert->{'id_template_module'} . ".", 10);
			next;
		}
		my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
		if (! defined ($agent)) {
			logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found for module ID " . $module->{'id_agente_modulo'} . " alert ID " . $alert->{'id_template_module'} . ".", 10);
			next;
		}

		pandora_execute_alert ($pa_config, 'N/A', $agent, $module, $alert, 1, $dbh);

		# Reset the force_execution flag, even if the alert could not be executed
		db_do ($dbh, "UPDATE talert_template_modules SET force_execution = 0 WHERE id = " . $alert->{'id_template_module'});
	}
}

##########################################################################
=head2 C<< pandora_module_keep_alive_nd (I<$pa_config>, I<$dbh>) >> 

Update keep_alive modules for agents without data.

=cut
##########################################################################
sub pandora_module_keep_alive_nd {
	my ($pa_config, $dbh) = @_;

	my @modules = get_db_rows ($dbh, 'SELECT tagente_modulo.*
					FROM tagente_modulo, tagente_estado, tagente 
					WHERE tagente.id_agente = tagente_estado.id_agente 
					AND tagente.disabled = 0 
					AND tagente_modulo.id_tipo_modulo = 100 
					AND tagente_modulo.disabled = 0 
					AND tagente_estado.datos = 1 
					AND tagente_estado.estado = 0 
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
					AND ( tagente_estado.utimestamp + (tagente.intervalo * 2) < UNIX_TIMESTAMP())');

	my %data = ('data' => 0);
	foreach my $module (@modules) {
		logger($pa_config, "Updating keep_alive module for module '" . $module->{'nombre'} . "' agent ID " . $module->{'id_agente'} . " (agent without data).", 10);
		pandora_process_module ($pa_config, \%data, '', $module, 'keep_alive', '', time (), 0, $dbh);
	}
}

##########################################################################
=head2 C<< pandora_evaluate_snmp_alerts (I<$pa_config>, I<$trap_id>, I<$trap_agent>, I<$trap_oid>, I<$trap_oid_text>, I<$trap_custom_oid>, I<$trap_custom_value>, I<$dbh>) >> 

Execute alerts that apply to the given SNMP trap.

=cut
##########################################################################
sub pandora_evaluate_snmp_alerts ($$$$$$$$) {
	my ($pa_config, $trap_id, $trap_agent, $trap_oid,
		$trap_oid_text, $trap_custom_oid, $trap_custom_value, $dbh) = @_;

	# Get all SNMP alerts
	my @snmp_alerts = get_db_rows ($dbh, 'SELECT * FROM talert_snmp');

	# Find those that apply to the given SNMP trap
	foreach my $alert (@snmp_alerts) {

		logger($pa_config, "Evaluating SNMP alert ID " . $alert->{'id_as'} . ".", 10);

		my ($fire_alert, $alert_data) = (0, '');		
		my ($times_fired, $internal_counter, $alert_type) =
			($alert->{'times_fired'}, $alert->{'internal_counter'}, $alert->{'alert_type'});

		# OID
		my $oid = $alert->{'oid'};
		if ($oid ne '' && $trap_oid =~ m/$oid/i || $trap_oid_text =~ m/$oid/i) {
			$fire_alert = 1;
			$alert_data .= "OID: $oid ";
		}
		# Custom OID/value
		my $custom_oid = $alert->{'custom_oid'};
		if ($custom_oid ne '' && $trap_custom_value =~ m/$custom_oid/i || $trap_custom_oid =~ m/$custom_oid/i) {
			$fire_alert = 1;
			$alert_data .= "CUSTOM OID: $custom_oid ";
		}
		# Agent IP
		my $agent = $alert->{'agent'};
		if ($agent ne '' && $trap_agent =~ m/$agent/i ) {
			$fire_alert = 1;
			$alert_data .= "AGENT: $agent";
		}

		next unless ($fire_alert == 1);
		
		# Check time threshold
		$alert->{'last_fired'} = '0000-00-00 00:00:00' unless defined ($alert->{'last_fired'});
		return unless ($alert->{'last_fired'} =~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/);
		my $last_fired = ($1 > 0) ? timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900) : 0;

		my $utimestamp = time ();
		my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

		# Out of limits, start a new interval
		($times_fired, $internal_counter) = (0, 0) if ($utimestamp >= ($last_fired + $alert->{'time_threshold'}));

		# Execute the alert
		my ($min_alerts, $max_alerts) = ($alert->{'min_alerts'}, $alert->{'max_alerts'});
		if (($internal_counter + 1 >= $min_alerts) && ($times_fired + 1 <= $max_alerts)) {
			($times_fired++, $internal_counter++);

			my %alert = (
				'name' => '',
				'agent' => 'N/A',
				'alert_data' => 'N/A',
				'id_agent_module' => 0,
				'id_template_module' => 0,
				'field1' => $alert->{'al_field1'},
				'field2' => $alert->{'al_field2'},
				'field3' => $alert->{'al_field3'},
				'description' => $alert->{'description'},
				'times_fired' => $times_fired,
				'time_threshold' => 0,
				'id_alert_action' => $alert->{'id_alert'},
				'priority' => $alert->{'priority'},
			);

			my %agent = (
				'nombre' => $trap_agent,
				'direccion' => $trap_agent,
			);

			# Execute alert
			my $action = get_db_single_row ($dbh, 'SELECT *
							FROM talert_actions, talert_commands
							WHERE talert_actions.id_alert_command = talert_commands.id
							AND talert_actions.id = ?', $alert->{'id_alert'});

			my $trap_rcv_full = $trap_oid . " " . $trap_custom_oid . " " . $trap_custom_value;
			pandora_execute_action ($pa_config, $trap_rcv_full, \%agent, \%alert, 1, $action, undef, $dbh) if (defined ($action));

			# Generate an event
			pandora_event ($pa_config, "SNMP alert fired (" . $alert->{'description'} . ")",
					0, 0, $alert->{'priority'}, 0, 0, 'alert_fired', 0, $dbh);

			# Update alert status
			db_do ($dbh, 'UPDATE talert_snmp SET times_fired = ?, last_fired = ?, internal_counter = ? WHERE id_as = ?',
				$times_fired, $timestamp, $internal_counter, $alert->{'id_as'});

			db_do ($dbh, 'UPDATE ttrap SET alerted = 1, priority = ? WHERE id_trap = ?',
				$alert->{'priority'}, $trap_id);
		} else {
			$internal_counter++;
			if ($internal_counter < $min_alerts){
				# Now update the new value for times_fired & last_fired if we are below min limit for triggering this alert
				db_do ($dbh, 'UPDATE talert_snmp SET internal_counter = ?, times_fired = ?, last_fired = ? WHERE id_as = ?',
					$internal_counter, $times_fired, $timestamp, $alert->{'id_as'});
			} else {
				db_do ($dbh, 'UPDATE talert_snmp SET times_fired = ?, internal_counter = ? WHERE id_as = ?',
					$times_fired, $internal_counter, $alert->{'id_as'});
			}
		}
	}
}

##############################################################################
=head2 C<< pandora_ping (I<$pa_config>, I<$host>) >> 

Ping the given host. 
Returns:
 1 if the host is alive
 0 otherwise.

=cut
##############################################################################
sub pandora_ping ($$) { 
	my ($pa_config, $host) = @_;

	# Ping the host
	`ping -q -W $pa_config->{'networktimeout'} -n -c $pa_config->{'icmp_checks'} $host >/dev/null 2>&1`;

	return ($? == 0) ? 1 : 0;
}

##############################################################################
=head2 C<< pandora_ping_latency (I<$pa_config>, I<$host>) >> 

Ping the given host. Returns the average round-trip time.

=cut
##############################################################################
sub pandora_ping_latency ($$) {
	my ($pa_config, $host) = @_;

	# Ping the host
	my @output = `ping -q -W $pa_config->{'networktimeout'} -n -c $pa_config->{'icmp_checks'} $host 2>/dev/null`;
	
	# Something went wrong
	return 0 if ($? != 0);
	
	# Parse the output
	my $stats = pop (@output);
	return 0 unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
	return $2;
}

##########################################################################
# Utility functions, not to be exported.
##########################################################################

##########################################################################
# Search string for macros and substitutes them with their values.
##########################################################################
sub subst_alert_macros ($$) {
	my ($string, $macros) = @_;

	while ((my $macro, my $value) = each (%{$macros})) {
		$string =~ s/($macro)/$value/ig;
	}

	return $string;
}

##########################################################################
# Process module data.
##########################################################################
sub process_data ($$$$$) {
	my ($dataObject, $module, $module_type, $utimestamp, $dbh) = @_;

	if ($module_type eq "log4x") {
		return log4x_get_severity_num($dataObject);
	}
	
	my $data = $dataObject->{'data'};
	
	# String data
	if ($module_type =~ m/_string$/) {

		# Empty strings are not allowed
		return undef if ($data eq '');

		return $data;
	}

	# Not a number
	if (! is_numeric ($data)) {
		return undef;
	}

	# If is a number, we need to replace "," for "."
	$data =~ s/\,/\./;

	# Out of bounds
	return undef if (($module->{'max'} != $module->{'min'}) &&
			($data > $module->{'max'} || $data < $module->{'min'}));

	# Process INC modules
	if ($module_type =~ m/_inc$/) {
		$data = process_inc_data ($data, $module, $utimestamp, $dbh);
		
		# Not an error, no previous data
		if (!defined($data)){
			$dataObject->{'data'} = 0;
			return 0;
		}
		#return 0 unless defined ($data);
	}

	# Post process
	if (is_numeric ($module->{'post_process'}) && $module->{'post_process'} != 0) {
		$data = $data * $module->{'post_process'};
	}

	# TODO: Float precission should be adjusted here in the future with a global
	# config parameter
	# Format data
	$data = sprintf("%.2f", $data);

	$dataObject->{'data'} = $data;
	return $data;
}

##########################################################################
# Process data of type *_inc.
##########################################################################
sub process_inc_data ($$$$) {
	my ($data, $module, $utimestamp, $dbh) = @_;

	my $data_inc = get_db_single_row ($dbh, 'SELECT * FROM tagente_datos_inc WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});

	# No previous data
	if (! defined ($data_inc)) {
		db_insert ($dbh, 'INSERT INTO tagente_datos_inc
		              (`id_agente_modulo`, `datos`, `utimestamp`)
		              VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);
		return undef;
	}

	# Negative increment, reset inc data
	if ($data < $data_inc->{'datos'}) {
		db_do ($dbh, 'DELETE FROM tagente_datos_inc WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});		
		db_insert ($dbh, 'INSERT INTO tagente_datos_inc
		              (`id_agente_modulo`, `datos`, `utimestamp`)
		              VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);
		return undef;
	}

	# Should not happen
	return 0 if ($utimestamp == $data_inc->{'utimestamp'});

	# Update inc data
	db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});

	return ($data - $data_inc->{'datos'}) / ($utimestamp - $data_inc->{'utimestamp'});
}

sub log4x_get_severity_num($) {
	my ($dataObject) = @_;
	my $data = $dataObject->{'severity'};
		
	# The severity is a word, so we need to translate to numbers
		
	if ($data =~ m/^trace$/i) {
		$data = 10;
	} elsif ($data =~ m/^debug$/i) {
		$data = 20;
	} elsif ($data =~ m/^info$/i) {
		$data = 30;
	} elsif ($data =~ m/^warn$/i) {
		$data = 40;
	} elsif ($data =~ m/^error$/i) {
		$data = 50;
	} elsif ($data =~ m/^fatal$/i) {
		$data = 60;
	} else {
		$data = 10;
	}
	return $data;
}

##########################################################################
# Returns the status of the module: 0 (NORMAL), 1 (CRITICAL), 2 (WARNING).
##########################################################################
sub get_module_status ($$) {
	my ($data, $module, $module_type) = @_;
	my ($critical_min, $critical_max, $warning_min, $warning_max) =
	   ($module->{'min_critical'}, $module->{'max_critical'}, $module->{'min_warning'}, $module->{'max_warning'});

	# Set default critical max/min for *proc modules
	if ($module_type =~ m/_proc$/ && ($critical_min eq $critical_max)) {
		($critical_min, $critical_max) = (0, 1);
	}

	if ($module_type =~ m/keep_alive/ && ($critical_min eq $critical_max)) {
		($critical_min, $critical_max) = (0, 1);
	}

	if ($module_type eq "log4x") {
		if ($critical_min eq $critical_max) {
			($critical_min, $critical_max) = (50, 61); # ERROR - FATAL
		}
		if ($warning_min eq $warning_max) {
			($warning_min, $warning_max) = (40, 41); # WARN - WARN
		}
	}
	
	# Critical
	if ($critical_min ne $critical_max) {
		return 1 if ($data >= $critical_min && $data < $critical_max);
		return 1 if ($data >= $critical_min && $critical_max < $critical_min);
	}

	# Warning
	if ($warning_min ne $warning_max) {
		return 2 if ($data >= $warning_min && $data < $warning_max);
		return 2 if ($data >= $warning_min && $warning_max < $warning_min);
	}

	# Normal
	return 0;
}

##########################################################################
# Validate event.
# This validates all events pending to ACK for the same id_agent_module
##########################################################################
sub pandora_validate_event (%$$) {
	my ($pa_config, $id_agentmodule, $dbh) = @_;
	if (!defined($id_agentmodule)){
		return;
	}

	logger($pa_config, "Validating events for id_agentmodule #$id_agentmodule", 10);
	db_do ($dbh, 'UPDATE tevento SET estado = 1 WHERE estado = 0 AND id_agentmodule = '.$id_agentmodule);
}

##########################################################################
# Generates an event according to the change of status of a module.
##########################################################################
sub generate_status_event ($$$$$$$) {
	my ($pa_config, $data, $agent, $module, $status, $last_status, $dbh) = @_;
	my ($event_type, $severity);
	my $description = "Module " . $module->{'nombre'} . " ($data) is ";

	# Mark as "validated" any previous event for this module
	pandora_validate_event ($pa_config, $module->{'id_agente_modulo'}, $dbh);

	# Normal
	if ($status == 0) {
		($event_type, $severity) = ('going_down_normal', 2);
		$description .= "going to NORMAL";
		enterprise_hook('mcast_change_report', [$pa_config, $module->{'nombre'}, $module->{'custom_id'}, strftime ("%Y-%m-%d %H:%M:%S", localtime()), 'OK', $dbh]);
	# Critical
	} elsif ($status == 1) {
		($event_type, $severity) = ('going_up_critical', 4);
		$description .= "going to CRITICAL";
		enterprise_hook('mcast_change_report', [$pa_config, $module->{'nombre'}, $module->{'custom_id'}, strftime ("%Y-%m-%d %H:%M:%S", localtime()), 'ERR', $dbh]);
	# Warning
	} elsif ($status == 2) {
		
		# From normal
		if ($last_status == 0) {
			($event_type, $severity) = ('going_up_warning', 3);
			$description .= "going to WARNING";
			
		# From critical
		} elsif ($last_status == 1) {
			($event_type, $severity) = ('going_down_warning', 3);
			$description .= "going to WARNING";
		} else {
			# Unknown last_status
			return;
		}
		enterprise_hook('mcast_change_report', [$pa_config, $module->{'nombre'}, $module->{'custom_id'}, strftime ("%Y-%m-%d %H:%M:%S", localtime()), 'WARN', $dbh]);
	} else {
		# Unknown status
		logger($pa_config, "Unknown status $status for module '" . $module->{'nombre'} . "' agent '" . $agent->{'nombre'} . "'.", 10);
		return;
	}

	# Generate the event
	if ($status != 0){
		pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
	               $severity, 0, $module->{'id_agente_modulo'}, $event_type, 0, $dbh);
	} else { 
		# Self validate this event if has "normal" status
		pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
	               $severity, 0, $module->{'id_agente_modulo'}, $event_type, 1, $dbh);
	}

}

##########################################################################
# Saves module data to the DB.
##########################################################################
sub save_module_data ($$$$$) {
	my ($dataObject, $module, $module_type, $utimestamp, $dbh) = @_;

	if ($module_type eq "log4x") {
		#<module>
		#	<name></name>
		#	<type>log4x</type>
		#
		#	<severity></severity>
		#	<message></message>
		#	
		#	<stacktrace></stacktrace>
		#</module>

		print "saving log4x data: " . $dataObject->{'message'} . "\n";

		my $sql = "INSERT INTO tagente_datos_log4x(id_agente_modulo, utimestamp, severity, message, stacktrace) values (?, ?, ?, ?, ?)";

		db_do($dbh, $sql, 
			$module->{'id_agente_modulo'}, $utimestamp,
			$dataObject->{'severity'},
			$dataObject->{'message'},
			$dataObject->{'stacktrace'}
		);
	} else {
		my $data = $dataObject->{'data'};
		my $table = ($module_type =~ m/_string/) ? 'tagente_datos_string' : 'tagente_datos';

		db_do($dbh, 'INSERT INTO ' . $table . ' (id_agente_modulo, datos, utimestamp)
					 VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);	
	}
}

##########################################################################
# Export module data.
##########################################################################
sub export_module_data ($$$$$$$) {
	my ($pa_config, $data, $agent, $module, $module_type, $timestamp, $dbh) = @_;

	# TODO: If module is log4x we hope for the best :P
	#return if ($module_type == "log4x");
	
	# Data export is disabled
 	return if ($module->{'id_export'} < 1);

	logger($pa_config, "Exporting data for module '" . $module->{'nombre'} . "' agent '" . $agent->{'nombre'} . "'.", 10);
	db_do($dbh, 'INSERT INTO tserver_export_data 
	         (`id_export_server`, `agent_name` , `module_name`, `module_type`, `data`, `timestamp`) VALUES
	         (?, ?, ?, ?, ?, ?)', $module->{'id_export'}, $agent->{'nombre'}, $module->{'nombre'}, $module_type, $data, $timestamp);
}

##########################################################################
# Returns 1 if alerts for the given agent should be inhibited, 0 otherwise.
##########################################################################
sub pandora_inhibit_alerts ($$$) {
	my ($pa_config, $agent, $dbh) = @_;

	return 0 if ($agent->{'cascade_protection'} ne '1' || $agent->{'id_parent'} eq '0');

	# Are any of the parent's critical alerts fired?	
	my $count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_modulo, talert_template_modules, talert_templates
				WHERE tagente_modulo.id_agente = ?
				AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
				AND talert_template_modules.id_alert_template = talert_templates.id
				AND talert_template_modules.times_fired > 0
				AND talert_templates.priority = 4', $agent->{'id_parent'});
	return 1 if ($count > 0);

	# Are any of the parent's critical compound alerts fired?	
	$count = get_db_value ($dbh, 'SELECT COUNT(*) FROM talert_compound WHERE id_agent = ? AND times_fired > 0 AND priority = 4', $agent->{'id_parent'});
	return 1 if ($count > 0);

	return 0;
}
##########################################################################
=head2 C<< save_agent_position (I<$pa_config>, I<$current_longitude>, I<$current_latitude>, 
		  I<$current_altitude>, I<$agent_id>, I<$dbh>, [I<$start_timestamp>], [I<$description>]) >>

Saves a new agent GIS information record in B<tgis_data_status> table. 

=cut
##########################################################################
sub save_agent_position($$$$$$;$$) {
	my ($pa_config, $current_longitude, $current_latitude, $current_altitude, $agent_id, $dbh, $start_timestamp, $description) = @_;
	
	if (!defined($description)) {
		$description = '';
	}
    logger($pa_config, "Updating agent position: longitude=$current_longitude, latitude=$current_latitude, altitude=$current_altitude, start_timestamp=$start_timestamp agent_id=$agent_id", 10);
	
	if (defined($start_timestamp)) {
		# Upadate the timestap of the received agentk
		db_insert ($dbh, 'INSERT INTO tgis_data_status (tagente_id_agente, current_longitude , current_latitude, current_altitude, 
					 stored_longitude , stored_latitude, stored_altitude, start_timestamp, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
				  $agent_id, $current_longitude, $current_latitude, $current_altitude, $current_longitude, 
					$current_latitude, $current_altitude, $start_timestamp, $description);
	}
	else {
		# Upadate the data of the received agent using the default timestamp
		db_insert ($dbh, 'INSERT INTO tgis_data_status (tagente_id_agente, current_longitude , current_latitude, current_altitude, 
					 stored_longitude , stored_latitude, stored_altitude,  description) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ',
				  $agent_id, $current_longitude, $current_latitude, $current_altitude, $current_longitude, 
					$current_latitude, $current_altitude, , $description);
	}
}

##########################################################################
=head2 C<< update_agent_position (I<$pa_config>, I<$current_longitude>, I<$current_latitude>, I<$current_altitude>,
		I<$agent_id>, I<$dbh>, [I<$stored_longitude>], [I<$stored_latitude>], [I<$stored_altitude>], [I<$start_timestamp>], [I<$description>]) >>

Updates agent GIS information in B<tgis_data_status> table.

=cut
##########################################################################
sub update_agent_position($$$$$$;$$$$$) {
	my ($pa_config, $current_longitude, $current_latitude, $current_altitude,
		 $agent_id, $dbh, $stored_longitude, $stored_latitude, $stored_altitude, $start_timestamp, $description) = @_;
	if (defined($stored_longitude) && defined($stored_latitude) && defined($start_timestamp) ) {
		# Upadate all the position data of the agent
    	logger($pa_config, "Updating agent position: current_longitude=$current_longitude, current_latitude=$current_latitude,
						 current_altitude=$current_altitude, stored_longitude=$stored_longitude, stored_latitude=$stored_latitude,
						 stored_altitude=$stored_altitude, start_timestamp=$start_timestamp, agent_id=$agent_id", 10);
		db_do ($dbh, 'UPDATE tgis_data_status SET current_longitude = ?, current_latitude = ?, current_altitude = ?,
				  stored_longitude = ?,stored_latitude = ?,stored_altitude = ?, start_timestamp = ?, description = ?,
				  number_of_packages = 1 WHERE tagente_id_agente = ?', 
				  $current_longitude, $current_latitude, $current_altitude, $stored_longitude, $stored_latitude,
				  $stored_altitude, $start_timestamp, $description, $agent_id);
	}
	else {
    	logger($pa_config, "Updating agent position: longitude=$current_longitude, latitude=$current_latitude, altitude=$current_altitude, agent_id=$agent_id", 10);
		# Upadate the timestap of the received agent
		db_do ($dbh, 'UPDATE tgis_data_status SET current_longitude = ?, current_latitude = ?, current_altitude = ?,
				  number_of_packages = number_of_packages + 1 WHERE tagente_id_agente = ?', 
				  $current_longitude, $current_latitude, $current_altitude, $agent_id);
	}
}

##########################################################################
=head2 C<< archive_agent_position (I<$pa_config>, I<$start_timestamp>, I<$end_timestamp>, I<$longitude>, I<$latitude>, I<$altitude>, I<$description>, 
I<$number_packages>, I<$agent_id>, I<$dbh>) >>
 
Archives the last position of an agent in the B<tgis_data_history> table

=cut
##########################################################################
sub archive_agent_position($$$$$$$$$$) {
    my ($pa_config, $start_timestamp, $end_timestamp, $longitude, $latitude, 
		$altitude, $description, $number_packages, $agent_id, $dbh) = @_;

    logger($pa_config, "Saving new agent position: start_timestamp=$start_timestamp longitude=$longitude latitude=$latitude altitude=$altitude", 10);

	db_insert($dbh, 'INSERT INTO tgis_data_history (`longitude`, `latitude`, `altitude`, `tagente_id_agente`, `start_timestamp`,
					`end_timestamp`, `description`, `number_of_packages`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', 
		  			$longitude, $latitude, $altitude, $agent_id, $start_timestamp, $end_timestamp, $description, $number_packages);

}



##########################################################################
=head2 C<< pandora_server_statistics (I<$pa_config>, I<$dbh>) >>

Process server statistics for statistics table

=cut
##########################################################################
sub pandora_server_statistics ($$) {
	my ($pa_config, $dbh) = @_;

	my $lag_time= 0;
	my $lag_modules = 0;
	my $total_modules_running = 0;
	my $my_modules = 0;
	my $stat_utimestamp  = 0;
	my $lag_row;

	# Get all servers with my name (each server only refresh it's own stats)
	my @servers = get_db_rows ($dbh, 'SELECT * FROM tserver WHERE name = "'.$pa_config->{'servername'}.'"');

	# For each server, update stats: Simple.
	foreach my $server (@servers) {
		if ($server->{"server_type"} !=3) {

			# Get LAG
			$server->{"modules"} = get_db_value ($dbh, "SELECT count(tagente_estado.id_agente_modulo) FROM tagente_estado, tagente_modulo, tagente WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_estado.running_by = ".$server->{"id_server"});

			$server->{"modules_total"} = get_db_value ($dbh,"SELECT count(tagente_estado.id_agente_modulo) FROM tserver, tagente_estado, tagente_modulo, tagente WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_estado.running_by = tserver.id_server AND tserver.server_type = ".$server->{"server_type"});

			if ($server->{"server_type"} != 0){
				$lag_row = get_db_single_row ($dbh, "SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo
						WHERE utimestamp > 0
						AND tagente_modulo.disabled = 0
						AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
						AND current_interval > 0
						AND running_by = ".$server->{"id_server"}."
						AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
						AND (UNIX_TIMESTAMP() - utimestamp) > current_interval");
			} else {
				# Local/Dataserver server LAG calculation:
				$lag_row = get_db_single_row ($dbh, "SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS lag FROM tagente_estado, tagente_modulo
						WHERE utimestamp > 0
						AND tagente_modulo.disabled = 0
						AND tagente_modulo.id_tipo_modulo < 5 
						AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
						AND current_interval > 0
						AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
						AND running_by = ".$server->{"id_server"}."
						AND (UNIX_TIMESTAMP() - utimestamp) > (current_interval * 1.1)");
			}

			$server->{"module_lag"} = $lag_row->{'module_lag'};
			$server->{"lag"} = $lag_row->{'lag'};
	
		} else {
			# Recon server only

				# Total jobs running on this recon server
				$server->{"modules"} = get_db_value ($dbh, "SELECT COUNT(id_rt) FROM trecon_task WHERE id_recon_server = ".$server->{"id_server"});
		
				# Total recon jobs (all servers)
				$server->{"modules_total"} = get_db_value ($dbh, "SELECT COUNT(status) FROM trecon_task");
		
				# Lag (take average active time of all active tasks)			

				$server->{"lag"} = get_db_value ($dbh, "SELECT UNIX_TIMESTAMP() - utimestamp from trecon_task WHERE UNIX_TIMESTAMP()  > (utimestamp + interval_sweep) AND id_recon_server = ".$server->{"id_server"});

				$server->{"module_lag"} = get_db_value ($dbh, "SELECT COUNT(id_rt) FROM trecon_task WHERE UNIX_TIMESTAMP()  > (utimestamp + interval_sweep) AND id_recon_server = ".$server->{"id_server"});

		}

		# Check that all values are defined and set to 0 if not

		if (!defined($server->{"lag"})){
			$server->{"lag"} = 0;
		}

		if (!defined($server->{"module_lag"})){
			$server->{"module_lag"} = 0;
		}

		if (!defined($server->{"modules_total"})){
			$server->{"modules_total"} = 0;
		}

		if (!defined($server->{"modules"})){
			$server->{"modules"} = 0;
		}

		# Update server record
		db_do ($dbh, "UPDATE tserver SET lag_time = '".$server->{"lag"}."', lag_modules = '".$server->{"module_lag"}."', total_modules_running = '".$server->{"modules_total"}."', my_modules = '".$server->{"modules"}."' , stat_utimestamp =  UNIX_TIMESTAMP() WHERE id_server = " . $server->{"id_server"} );
	}
}


##########################################################################
=head2 C<< pandora_group_statistics (I<$pa_config>, I<$dbh>) >>

Process groups statistics for statistics table

=cut
##########################################################################
sub pandora_group_statistics ($$) {
	my ($pa_config, $dbh) = @_;
	
	# Variable init
	my $modules = 0;
	my $normal = 0;
	my $critical = 0;
	my $warning = 0;
	my $unknown = 0;
	my $non_init = 0;
	my $alerts = 0;
	my $alerts_fired = 0;
	my $agents = 0;
	my $agents_unknown = 0;
	my $utimestamp = 0;
	my $group = 0;

	# Get all groups
	my @groups = get_db_rows ($dbh, 'SELECT id_grupo FROM tgrupo WHERE disabled = 0 AND id_grupo > 1');

	# For each valid group get the stats:  Simple uh?
	foreach my $group_row (@groups) {

		$group = $group_row->{'id_grupo'};

		$agents_unknown = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE id_grupo = $group AND disabled = 0 AND ultimo_contacto < NOW() - (intervalo *2)");

		$agents = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE id_grupo = $group AND disabled = 0");

		$modules = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0");

		# Following threelines gets critical/warning modules, skipping the unknown. By default
		# we consider status (ok, warning, critical) as a separate status from unknown.

#		$normal = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 0 AND ((tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100) AND utimestamp > ( UNIX_TIMESTAMP() - (current_interval * 2))) OR (tagente_modulo.id_tipo_modulo IN (21,22,23,100)))");

#		$critical = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 1 AND((tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100) AND utimestamp > ( UNIX_TIMESTAMP() - (current_interval * 2))) OR (tagente_modulo.id_tipo_modulo IN (21,22,23,100)))");

#		$warning = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 2 AND ((tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100) AND utimestamp > ( UNIX_TIMESTAMP() - (current_interval * 2))) OR (tagente_modulo.id_tipo_modulo IN (21,22,23,100)))");

		$normal = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 0");

		$critical = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 1");

		$warning = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 2 ");

		$unknown = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente  AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,30,100) AND utimestamp < ( UNIX_TIMESTAMP() - (current_interval * 2))");

		$non_init = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0
 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,30,100) AND tagente_estado.utimestamp = 0");

		$alerts = get_db_value ($dbh, "SELECT COUNT(talert_template_modules.id) FROM talert_template_modules, tagente_modulo, tagente_estado, tagente WHERE tagente.id_grupo = $group AND tagente_modulo.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo");

		$alerts_fired = get_db_value ($dbh, "SELECT COUNT(talert_template_modules.id) FROM talert_template_modules, tagente_modulo, tagente_estado, tagente WHERE tagente.id_grupo = $group AND tagente_modulo.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo AND times_fired > 0");

		# Update the record.

		db_do ($dbh, "DELETE FROM tgroup_stat WHERE id_group = $group");

		db_do ($dbh, "INSERT INTO tgroup_stat (id_group, modules, normal, critical, warning, unknown, `non-init`, alerts, alerts_fired, agents, agents_unknown, utimestamp) VALUES ($group, $modules, $normal, $critical, $warning, $unknown, $non_init, $alerts, $alerts_fired, $agents, $agents_unknown, UNIX_TIMESTAMP())");

	}

}


##########################################################################
=head2 C<< pandora_self_monitoring (I<$pa_config>, I<$dbh>) >>

Pandora self monitoring process

=cut
##########################################################################

sub pandora_self_monitoring ($$) {
	my ($pa_config, $dbh) = @_;
	my $timezone_offset = 0; # PENDING (TODO) !
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());

    my $xml_output = "";
	
	$xml_output = "<agent_data os_name='Linux' os_version='".$pa_config->{'version'}."' agent_name='".$pa_config->{'servername'}."' interval='".$pa_config->{"stats_interval"}."' timestamp='".$timestamp."' >";
	$xml_output .=" <module>";
	$xml_output .=" <name>Status</name>";
	$xml_output .=" <type>generic_proc</type>";
	$xml_output .=" <data>1</data>";
	$xml_output .=" </module>";

	my $load_average = load_average();
	my $free_mem = free_mem();;
	my $free_disk_spool = disk_free ($pa_config->{"incomingdir"});
	my $my_data_server = get_db_value ($dbh, "SELECT id_server FROM tserver WHERE server_type = 0 AND name = '".$pa_config->{"servername"}."'");

	my $agents_unknown = get_db_value ($dbh, "SELECT * FROM tagente_estado, tagente WHERE tagente.disabled =0 AND tagente.id_agente = tagente_estado.id_agente AND running_by = $my_data_server AND utimestamp < NOW() - (current_interval * 2) limit 10;");

	my $queued_modules = get_db_value ($dbh, "SELECT SUM(queued_modules) FROM tserver WHERE name = '".$pa_config->{"servername"}."'");

	$xml_output .=" <module>";
	$xml_output .=" <name>Queued_Modules</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$queued_modules</data>";
	$xml_output .=" </module>";

	$xml_output .=" <module>";
	$xml_output .=" <name>Agents_Unknown</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$agents_unknown</data>";
	$xml_output .=" </module>";

	$xml_output .=" <module>";
	$xml_output .=" <name>System_Load_AVG</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$load_average</data>";
	$xml_output .=" </module>";

	$xml_output .=" <module>";
	$xml_output .=" <name>Free_RAM</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$free_mem</data>";
	$xml_output .=" </module>";

	$xml_output .=" <module>";
	$xml_output .=" <name>FreeDisk_SpoolDir</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$free_disk_spool</data>";
	$xml_output .=" </module>";

	$xml_output .= "</agent_data>";

	my $filename = $pa_config->{"incomingdir"}."/".$pa_config->{'servername'}.".".$utimestamp.".data";

	open (XMLFILE, ">> $filename") or die "[FATAL] Could not open internal monitoring XML file for deploying monitorization at '$filename'";
	print XMLFILE $xml_output;
	close (XMLFILE);
}


# End of function declaration
# End of defined Code

1;
__END__

=head1 DEPENDENCIES

L<DBI>, L<XML::Simple>, L<HTML::Entities>, L<Time::Local>, L<POSIX>, L<PandoraFMS::DB>, L<PandoraFMS::Config>, L<PandoraFMS::Tools>, L<PandoraFMS::GIS>

=head1 LICENSE

This is released under the GNU Lesser General Public License.

=head1 SEE ALSO

L<DBI>, L<XML::Simple>, L<HTML::Entities>, L<Time::Local>, L<POSIX>, L<PandoraFMS::DB>, L<PandoraFMS::Config>, L<PandoraFMS::Tools>, L<PandoraFMS::GIS>

=head1 COPYRIGHT

Copyright (c) 2005-2010 Artica Soluciones Tecnologicas S.L

=cut
