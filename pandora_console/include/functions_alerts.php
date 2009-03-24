<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

function clean_alert_command_values ($values, $set_empty = true) {
	$retvalues = array ();
	
	if ($set_empty) {
		$retvalues['description'] = '';
	}
	
	if (empty ($values))
		return $retvalues;
	
	if (isset ($values['description']))
		$retvalues['description'] = (string) $values['description'];
	
	return $retvalues;
}

function create_alert_command ($name, $command, $values = false) {
	if (empty ($name))
		return false;
	if (empty ($command))
		return false;
	
	$values = clean_alert_command_values ($values);
	
	$sql = sprintf ('INSERT talert_commands (name, command, description)
		VALUES ("%s", "%s", "%s")',
		$name, $command, $values['description']);
	return @process_sql ($sql, 'insert_id');
}

function update_alert_command ($id_alert_command, $name, $command, $description = '', $values = false) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	if (empty ($name))
		return false;
	if (empty ($command))
		return false;
	
	$values = clean_alert_command_values ($values);
	
	$sql = sprintf ('UPDATE talert_commands SET name = "%s", command = "%s",
		description = "%s" WHERE id = %d',
		$name, $command, $values['description'], $id_alert_command);
	return @process_sql ($sql) !== false;
}

function delete_alert_command ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	$sql = sprintf ('DELETE FROM talert_commands WHERE id = %d',
		$id_alert_command);
	return @process_sql ($sql);
}

function get_alert_command ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return get_db_row ('talert_commands', 'id', $id_alert_command);
}

function get_alert_command_name ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return get_db_value ('name', 'talert_commands', 'id', $id_alert_command);
}

function get_alert_command_command ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return get_db_value ('command', 'talert_commands', 'id', $id_alert_command);
}

function get_alert_command_internal ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return (bool) get_db_value ('internal', 'talert_commands', 'id', $id_alert_command);
}

function get_alert_command_description ($id_alert_command) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	
	return get_db_value ('description', 'talert_commands', 'id', $id_alert_command);
}

function clean_alert_action_values ($values, $set_empty = true) {
	$retvalues = array ();
	
	if ($set_empty) {
		$retvalues['field1'] = '';
		$retvalues['field2'] = '';
		$retvalues['field3'] = '';
	}
	
	if (empty ($values))
		return $retvalues;
	
	if (isset ($values['field1']))
		$retvalues['field1'] = (string) $values['field1'];
	if (isset ($values['field2']))
		$retvalues['field2'] = (string) $values['field2'];
	if (isset ($values['field3']))
		$retvalues['field3'] = (string) $values['field3'];
	
	return $retvalues;
}

function create_alert_action ($name, $id_alert_command, $values = false) {
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	if (empty ($name))
		return false;
	
	$values = clean_alert_action_values ($values);
	
	$sql = sprintf ('INSERT talert_actions (name, id_alert_command, field1, field2, field3)
		VALUES ("%s", %d, "%s", "%s", "%s")',
		$name, $id_alert_command, $values['field1'], $values['field2'],
		$values['field3']);
	
	return @process_sql ($sql, 'insert_id');
}

function update_alert_action ($id_alert_action, $id_alert_command, $name, $values = false) {
	$id_alert_action = safe_int ($id_alert_action, 1);
	if (empty ($id_alert_action))
		return false;
	$id_alert_command = safe_int ($id_alert_command, 1);
	if (empty ($id_alert_command))
		return false;
	if (empty ($name))
		return false;
	
	$values = clean_alert_action_values ($values);
	
	$sql = sprintf ('UPDATE talert_actions SET name = "%s",
		id_alert_command = %d, field1 = "%s",
		field2 = "%s", field3 = "%s" WHERE id = %d',
		$name, $id_alert_command, $values['field1'], $values['field2'],
		$values['field3'], $id_alert_action);
	
	return @process_sql ($sql) !== false;
}

function delete_alert_action ($id_alert_action) {
	$id_alert_action = safe_int ($id_alert_action, 1);
	if (empty ($id_alert_action))
		return false;
	
	$sql = sprintf ('DELETE FROM talert_actions WHERE id = %d',
		$id_alert_action);
	return @process_sql ($sql);
}

function get_alert_actions ($only_names = true) {
	$all_actions = get_db_all_rows_in_table ('talert_actions');
	
	if ($all_actions === false)
		return array ();
	
	if (! $only_names)
		return $all_actions;
	
	$actions = array ();
	foreach ($all_actions as $action) {
		$actions[$action['id']] = $action['name'];
	}
	
	return $actions;
}

function get_alert_action ($id_alert_action) {
	$id_alert_action = safe_int ($id_alert_action, 1);
	if (empty ($id_alert_action))
		return false;
	
	return get_db_row ('talert_actions', 'id', $id_alert_action);
}

function get_alert_action_alert_command_id ($id_alert_action) {
	return get_db_value ('id_alert_command', 'talert_actions', 'id', $id_alert_action);
}

function get_alert_action_alert_command ($id_alert_action) {
	$id_command = get_alert_action_alert_command_id ($id_alert_action);
	return get_alert_command ($id_command);
}

function get_alert_action_field1 ($id_alert_action) {
	return get_db_value ('field1', 'talert_actions', 'id', $id_alert_action);
}

function get_alert_action_field2 ($id_alert_action) {
	return get_db_value ('field2', 'talert_actions', 'id', $id_alert_action);
}

function get_alert_action_field3 ($id_alert_action) {
	return get_db_value ('field3', 'talert_actions', 'id', $id_alert_action);
}

function get_alert_action_name ($id_alert_action) {
	return get_db_value ('name', 'talert_actions', 'id', $id_alert_action);
}

function get_alert_templates_types () {
	$types = array ();
	
	$types['regex'] = __('Regular expression');
	$types['max_min'] = __('Max and min');
	$types['max'] = __('Max.');
	$types['min'] = __('Min.');
	$types['equal'] = __('Equal to');
	$types['not_equal'] = __('Not equal to');
	
	return $types;
}

function get_alert_templates_type_name ($type) {
	$types = get_alert_templates_types ();
	if (! isset ($type[$type]))
		return __('Unknown');
	return $types[$type];
}

function clean_alert_template_values ($values, $set_empty = true) {
	$retvalues = array ();
	
	if ($set_empty) {
		$retvalues['type'] = 'equal';
		$retvalues['description'] = '';
		$retvalues['id_alert_action'] = NULL;
		$retvalues['field1'] = '';
		$retvalues['field2'] = '';
		$retvalues['field3'] = '';
		$retvalues['value'] = '';
		$retvalues['max_value'] = 0;
		$retvalues['min_value'] = 0;
		$retvalues['time_threshold'] = 0;
		$retvalues['max_alerts'] = 0;
		$retvalues['min_alerts'] = 0;
		$retvalues['monday'] = 0;
		$retvalues['tuesday'] = 0;
		$retvalues['wednesday'] = 0;
		$retvalues['thursday'] = 0;
		$retvalues['friday'] = 0;
		$retvalues['saturday'] = 0;
		$retvalues['sunday'] = 0;
		$retvalues['time_from'] = '00:00';
		$retvalues['time_to'] = '00:00';
		$retvalues['time_threshold'] = '300';
		$retvalues['recovery_notify'] = '';
		$retvalues['field2_recovery'] = '';
		$retvalues['field2_recovery'] = '';
		$retvalues['matches_value'] = true;
	}
	
	if (empty ($values))
		return $retvalues;
	
	if (isset ($values['name']))
		$retvalues['name'] = (string) $values['name'];
	if (isset ($values['type']))
		$retvalues['type'] = (string) $values['type'];
	if (isset ($values['description']))
		$retvalues['description'] = (string) $values['description'];
	if (isset ($values['id_alert_action']))
		$retvalues['id_alert_action'] = (int) $values['id_alert_action'];
	if (isset ($values['field1']))
		$retvalues['field1'] = (string) $values['field1'];
	if (isset ($values['field2']))
		$retvalues['field2'] = (string) $values['field2'];
	if (isset ($values['field3']))
		$retvalues['field3'] = (string) $values['field3'];
	if (isset ($values['value']))
		$retvalues['value'] = (string) $values['value'];
	if (isset ($values['matches_value']))
		$retvalues['matches_value'] = (bool) $values['matches_value'];
	if (isset ($values['max_value']))
		$retvalues['max_value'] = (float) $values['max_value'];
	if (isset ($values['min_value']))
		$retvalues['min_value'] = (float) $values['min_value'];
	if (isset ($values['time_threshold']))
		$retvalues['time_threshold'] = (int) $values['time_threshold'];
	if (isset ($values['max_alerts']))
		$retvalues['max_alerts'] = (int) $values['max_alerts'];
	if (isset ($values['min_alerts']))
		$retvalues['min_alerts'] = (int) $values['min_alerts'];
	/* Ensure max an min orders */
	if (isset ($values['min_alerts']) && isset ($values['max_alerts'])) {
		$max = max ($retvalues['max_alerts'], $retvalues['min_alerts']);
		$min = min ($retvalues['max_alerts'], $retvalues['min_alerts']);
		$retvalues['max_alerts'] = $max;
		$retvalues['min_alerts'] = $min;
	}
	if (isset ($values['monday']))
		$retvalues['monday'] = (bool) $values['monday'];
	if (isset ($values['tuesday']))
		$retvalues['tuesday'] = (bool) $values['tuesday'];
	if (isset ($values['wednesday']))
		$retvalues['wednesday'] = (bool) $values['wednesday'];
	if (isset ($values['thursday']))
		$retvalues['thursday'] = (bool) $values['thursday'];
	if (isset ($values['friday']))
		$retvalues['friday'] = (bool) $values['friday'];
	if (isset ($values['saturday']))
		$retvalues['saturday'] = (bool) $values['saturday'];
	if (isset ($values['sunday']))
		$retvalues['sunday'] = (bool) $values['sunday'];
	if (isset ($values['time_from']))
		$retvalues['time_from'] = (string) $values['time_from'];
	if (isset ($values['time_to']))
		$retvalues['time_to'] = (string) $values['time_to'];
	if (isset ($values['time_threshold']))
		$retvalues['time_threshold'] = (int) $values['time_threshold'];
	if (isset ($values['recovery_notify']))
		$retvalues['recovery_notify'] = (bool) $values['recovery_notify'];
	if (isset ($values['field2_recovery']))
		$retvalues['field2_recovery'] = (string) $values['field2_recovery'];
	if (isset ($values['field3_recovery']))
		$retvalues['field3_recovery'] = (string) $values['field3_recovery'];
	
	return $retvalues;
}

function create_alert_template ($name, $type, $values = false) {
	if (empty ($name))
		return false;
	if (empty ($type))
		return false;
	
	$values = clean_alert_template_values ($values);
	$values['name'] = $name;
	$values['type'] = $type;
	
	switch ($type) {
	/* TODO: Check values based on type, return false if failure */
	}
	
	return @process_sql_insert ('talert_templates', $values);
}

function update_alert_template ($id_alert_template, $values = false) {
	$id_alert_template = safe_int ($id_alert_template, 1);
	if (empty ($id_alert_template))
		return false;
	
	$values = clean_alert_template_values ($values, false);
	
	return (@process_sql_update ('talert_templates',
		$values,
		array ('id' => $id_alert_template))) !== false;
}

function delete_alert_template ($id_alert_template) {
	$id_alert_template = safe_int ($id_alert_template, 1);
	if (empty ($id_alert_template))
		return false;
	
	return @process_sql_delete ('talert_templates', array ('id' => $id_alert_template));
}

function get_alert_templates ($only_names = true) {
	$all_templates = get_db_all_rows_in_table ('talert_templates');
	
	if ($all_templates === false)
		return array ();
	
	if (! $only_names)
		return $all_templates;
	
	$templates = array ();
	foreach ($all_templates as $template) {
		$templates[$template['id']] = $template['name'];
	}
	
	return $templates;
}

function get_alert_template ($id_alert_template) {
	$id_alert_template = safe_int ($id_alert_template, 1);
	if (empty ($id_alert_template))
		return false;
	
	return get_db_row ('talert_templates', 'id', $id_alert_template);
}

function get_alert_template_field1 ($id_alert_template) {
	return get_db_value ('field1', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_field2 ($id_alert_template) {
	return get_db_value ('field2', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_field3 ($id_alert_template) {
	return get_db_value ('field3', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_name ($id_alert_template) {
	return get_db_value ('name', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_description ($id_alert_template) {
	return get_db_value ('description', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_type ($id_alert_template) {
	return get_db_value ('type', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_type_name ($id_alert_template) {
	$type = get_alert_template_type ($id_alert_template);
	return get_alert_templates_type_name ($type);
}

function get_alert_template_value ($id_alert_template) {
	return get_db_value ('value', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_max_value ($id_alert_template) {
	return get_db_value ('max_value', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_min_value ($id_alert_template) {
	return get_db_value ('min_value', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_alert_text ($id_alert_template) {
	return get_db_value ('alert_text', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_time_from ($id_alert_template) {
	return get_db_value ('time_from', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_time_to ($id_alert_template) {
	return get_db_value ('time_from', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_weekdays ($id_alert_template) {
	$alert = get_alert_template ($id_alert_template);
	if ($alert === false)
		return false;
	$retval = array ();
	$days = array ('monday', 'tuesday', 'wednesday', 'thursday', 'friday',
		'saturday', 'sunday');
	foreach ($days as $day)
		$retval[$day] = (bool) $alert[$day];
	return $retval;
}

function get_alert_template_recovery_notify ($id_alert_template) {
	return get_db_value ('recovery_notify', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_field2_recovery ($id_alert_template) {
	return get_db_value ('field2_recovery', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_field3_recovery ($id_alert_template) {
	return get_db_value ('field3_recovery', 'talert_templates', 'id', $id_alert_template);
}

function get_alert_template_threshold_values () {
	$times = array ();
	
	$times['300'] = '5 '.__('minutes');
	$times['600'] = '10 '.__('minutes');
	$times['900'] = '15 '.__('minutes');
	$times['1800'] = '30 '.__('minutes');
	$times['3600'] = '1 '.__('hour');
	$times['7200'] = '2 '.__('hours');
	$times['18000'] = '5 '.__('hours');
	$times['43200'] = '12 '.__('hours');
	$times['86400'] = '1 '.__('day');
	$times['604800'] = '1 '.__('week');
	$times['-1'] = __('Other value');
	
	return $times;
}

function duplicate_alert_template ($id_alert_template) {
	$template = get_alert_template ($id_alert_template);
	if ($template === false)
		return false;
	$name = __('Copy of').' '.$template['name'];
	$type = $template['type'];
	
	$size = count ($template) / 2;
	for ($i = 0; $i < $size; $i++) {
		unset ($template[$i]);
	}
	unset ($template['name']);
	unset ($template['id']);
	unset ($template['type']);
	$template['value'] = safe_sql_string ($template['value']);
	
	return create_alert_template ($name, $type, $template);
}

function clean_alert_agent_module_values ($values, $set_empty = true) {
	$retvalues = array ();
	
	if ($set_empty) {
		$retvalues['internal_counter'] = 0;
		$retvalues['last_fired'] = 0;
		$retvalues['times_fired'] = 0;
		$retvalues['disabled'] = 0;
		$retvalues['priority'] = 0;
		$retvalues['force_execution'] = 0;
	}
	
	if (empty ($values))
		return $retvalues;
	
	if (isset ($values['internal_counter']))
		$retvalues['internal_counter'] = (int) $values['internal_counter'];
	if (isset ($values['last_fired']))
		$retvalues['last_fired'] = (int) $values['last_fired'];
	if (isset ($values['times_fired']))
		$retvalues['times_fired'] = (int) $values['times_fired'];
	if (isset ($values['disabled']))
		$retvalues['disabled'] = (int) $values['disabled'];
	if (isset ($values['priority']))
		$retvalues['priority'] = (int) $values['priority'];
	if (isset ($values['force_execution']))
		$retvalues['force_execution'] = (int) $values['force_execution'];
	
	return $retvalues;
}

function create_alert_agent_module ($id_agent_module, $id_alert_template, $values = false) {
	if (empty ($id_agent_module))
		return false;
	if (empty ($id_alert_template))
		return false;
	
	$values = clean_alert_agent_module_values ($values);
	$values['id_agent_module'] = $id_agent_module;
	$values['id_alert_template'] = $id_alert_template;
	return @process_sql_insert ('talert_template_modules',
		$values,
		array ('id' => $id_alert_template));
}

function update_alert_agent_module ($id_alert_agent_module, $values = false) {
	if (empty ($id_agent_module))
		return false;
	
	$values = clean_alert_agent_module_values ($values, false);
	if ($empty ($values))
		return true;
	
	return (@process_sql_update ('talert_template_modules',
		$values,
		array ('id' => $id_alert_template))) !== false;
}

function delete_alert_agent_module ($id_alert_agent_module) {
	if (empty ($id_alert_agent_module))
		return false;
	
	return (@process_sql_delete ('talert_template_modules',
		array ('id' => $id_alert_agent_module))) !== false;
}

function get_alert_agent_module ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	if (empty ($id_alert_agent_module))
		return false;
	
	return get_db_row ('talert_template_modules', 'id', $id_alert_agent_module);
}

function get_alerts_agent_module ($id_agent_module, $disabled = false, $filter = false, $fields = false) {
	$id_alert_agent_module = safe_int ($id_agent_module, 0);
	
	if (! is_array ($filter))
		$filter = array ();
	if (! $disabled)
		$filter['disabled'] = 0;
	$filter['id_agent_module'] = $id_agent_module;
	
	return get_db_all_rows_filter ('talert_template_modules',
		$filter, $fields);
}

function get_alert_agent_module_disabled ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	return get_db_value ('disabled', 'talert_template_modules', 'id',
		$id_alert_agent_module);
}

function set_alerts_agent_module_force_execution ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	return (@process_sql_update ('talert_template_modules',
		array ('force_execution' => 1),
		array ('id' => $id_alert_agent_module))) !== false;
}

function set_alerts_agent_module_disable ($id_alert_agent_module, $disabled) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 0);
	return (@process_sql_update ('talert_template_modules',
		array ('disabled' => (bool) $disabled),
		array ('id' => $id_alert_agent_module))) !== false;
}

function get_alerts_agent_module_last_fired ($id_alert_agent_module) {
	$id_alert_agent_module = safe_int ($id_alert_agent_module, 1);
	return get_db_value ('last_fired', 'talert_template_modules', 'id',
		$id_alert_agent_module);
}

function add_alert_agent_module_action ($id_alert_template_module, $id_alert_action, $options = false) {
	if (empty ($id_alert_template_module))
		return false;
	if (empty ($id_alert_action))
		return false;
	
	$values = array ();
	$values['id_alert_template_module'] = $id_alert_template_module;
	$values['id_alert_action'] = $id_alert_action;
	$values['fires_max'] = 0;
	$values['fires_min'] = 0;
	if ($options) {
		$max = 0;
		$min = 0;
		if (isset ($options['fires_max']))
			$max = (int) $options['fires_max'];
		if (isset ($options['fires_min']))
			$min = (int) $options['fires_min'];
		
		$values['fires_max'] = max ($max, $min);
		$values['fires_min'] = min ($max, $min);
	}
	
	return (@process_sql_insert ('talert_template_module_actions', $values)) !== false;
}

function delete_alert_agent_module_action ($id_alert_agent_module_action) {
	if (empty ($id_alert_agent_module_action))
		return false;
	
	return (@process_sql_delete ('talert_template_module_actions',
		array ('id' => $id_alert_agent_module_action))) !== false;
}

function get_alert_agent_module_actions ($id_alert_agent_module, $fields = false) {
	if (empty ($id_alert_agent_module))
		return false;
	
	$actions = get_db_all_rows_filter ('talert_template_module_actions',
		array ('id_alert_template_module' => $id_alert_agent_module),
		$fields);
	if ($actions === false)
		return array ();
	if ($fields !== false)
		return $actions;
	
	$retval = array ();
	foreach ($actions as $element) {
		$action = get_alert_action ($element['id_alert_action']);
		$action['fires_min'] = $element['fires_min'];
		$action['fires_max'] = $element['fires_max'];
		$retval[$element['id']] = $action;
	}
	
	return $retval;
}

/**
 *  Validates an alert id or an array of alert id's
 *
 * @param mixed Array of alerts ids or single id
 *
 * @return bool True if it was successful, false otherwise.
 */
function validate_alert_agent_module ($id_alert_agent_module) {
	global $config;
	require_once ("include/functions_events.php");
	
	$alerts = safe_int ($id_alert_agent_module, 1);
	
	if (empty ($alerts)) {
		return false;
	}
	
	$alerts = (array) $alerts;
	
	foreach ($alerts as $id) {
		$alert = get_alert_agent_module ($id);
		$agent_id = get_agentmodule_agent ($alert["id_agent_module"]);
		$group_id = get_agentmodule_group ($agent_id);
		
		if (! give_acl ($config['id_user'], $group_id, "AW")) {
			continue;
		}
		$result = process_sql_update ('talert_template_modules',
			array ('times_fired' => 0,
				'internal_counter' => 0),
			array ('id' => $id));
		
		if ($result > 0) {
			create_event ("Manual validation of alert for ".
				get_alert_template_description ($alert["id_alert_template"]),
				$group_id, $agent_id, 1, $config["id_user"],
				"alert_manual_validation", 1, $alert["id_agent_module"],
				$id);
		} elseif ($result === false) {
			return false;
		}
	}
	return true;
}

/**
 * Copy an alert defined in a module agent to other module agent.
 * 
 * This function avoid duplicated insertion.
 * 
 * @param int Source agent module id.
 * @param int Detiny agent module id.
 *
 * @return New alert id on success. Existing alert id if it already exists.
 * False on error.
 */
function copy_alert_agent_module_to_agent_module ($id_agent_alert, $id_destiny_module) {
	$alert = get_alert_agent_module ($id_agent_alert);
	if ($alert === false)
		return false;
	
	$alerts = get_alerts_agent_module ($id_destiny_module, false,
		array ('id_alert_template' => $alert['id_alert_template']));
	if (! empty ($alerts)) {
		return $alerts[0]['id'];
	}
	
	/* PHP copy arrays on assignment */
	$new_alert = array ();
	$new_alert['id_agent_module'] = $id_destiny_module;
	$new_alert['id_alert_template'] = $alert['id_alert_template'];
	
	$id_new_alert = @process_sql_insert ('talert_template_modules', $new_alert);
	if ($id_new_alert === false) {
		return false;
	}
	$actions = get_alert_agent_module_actions ($id_agent_alert);
	if (empty ($actions))
		return $id_new_alert;
	
	foreach ($actions as $action) {
		$result = add_alert_agent_module_action ($id_new_alert, $action['id'],
			array ('fires_min' => $action['fires_min'],
				'fires_max' => $action['fires_max']));
		if ($result === false)
			return false;
	}
	
	return $id_new_alert;
}

/* Compound alerts */

function get_alert_compound_threshold_values () {
	/* At this moment we don't need different threshold values */
	return get_alert_template_threshold_values ();
}

function get_alert_compound_operations () {
	$operations = array ();

	$operations['OR'] = 'OR';
	$operations['AND'] = 'AND';
	$operations['NOR'] = 'NOR';
	$operations['NAND'] = 'NAND';
	$operations['NXOR'] = 'NXOR';
	
	return $operations;
}

function clean_alert_compound_values ($values, $set_empty = true) {
	$retvalues = array ();
	
	if ($set_empty) {
		$retvalues['description'] = '';
		$retvalues['time_threshold'] = 0;
		$retvalues['max_alerts'] = 0;
		$retvalues['min_alerts'] = 0;
		$retvalues['monday'] = 0;
		$retvalues['tuesday'] = 0;
		$retvalues['wednesday'] = 0;
		$retvalues['thursday'] = 0;
		$retvalues['friday'] = 0;
		$retvalues['saturday'] = 0;
		$retvalues['sunday'] = 0;
		$retvalues['time_from'] = '00:00';
		$retvalues['time_to'] = '00:00';
		$retvalues['time_threshold'] = '300';
		$retvalues['recovery_notify'] = '';
		$retvalues['field2_recovery'] = '';
		$retvalues['field2_recovery'] = '';
	}
	
	if (empty ($values))
		return $retvalues;
	
	if (isset ($values['name']))
		$retvalues['name'] = (string) $values['name'];
	if (isset ($values['description']))
		$retvalues['description'] = (string) $values['description'];
	if (isset ($values['id_agent']))
		$retvalues['id_agent'] = (int) $values['id_agent'];
	if (isset ($values['time_threshold']))
		$retvalues['time_threshold'] = (int) $values['time_threshold'];
	if (isset ($values['max_alerts']))
		$retvalues['max_alerts'] = (int) $values['max_alerts'];
	if (isset ($values['min_alerts']))
		$retvalues['min_alerts'] = (int) $values['min_alerts'];
	/* Ensure max an min orders */
	if (isset ($values['min_alerts']) && isset ($values['max_alerts'])) {
		$max = max ($retvalues['max_alerts'], $retvalues['min_alerts']);
		$min = min ($retvalues['max_alerts'], $retvalues['min_alerts']);
		$retvalues['max_alerts'] = $max;
		$retvalues['min_alerts'] = $min;
	}
	if (isset ($values['monday']))
		$retvalues['monday'] = (bool) $values['monday'];
	if (isset ($values['tuesday']))
		$retvalues['tuesday'] = (bool) $values['tuesday'];
	if (isset ($values['wednesday']))
		$retvalues['wednesday'] = (bool) $values['wednesday'];
	if (isset ($values['thursday']))
		$retvalues['thursday'] = (bool) $values['thursday'];
	if (isset ($values['friday']))
		$retvalues['friday'] = (bool) $values['friday'];
	if (isset ($values['saturday']))
		$retvalues['saturday'] = (bool) $values['saturday'];
	if (isset ($values['sunday']))
		$retvalues['sunday'] = (bool) $values['sunday'];
	if (isset ($values['time_from']))
		$retvalues['time_from'] = (string) $values['time_from'];
	if (isset ($values['time_to']))
		$retvalues['time_to'] = (string) $values['time_to'];
	if (isset ($values['time_threshold']))
		$retvalues['time_threshold'] = (int) $values['time_threshold'];
	if (isset ($values['recovery_notify']))
		$retvalues['recovery_notify'] = (bool) $values['recovery_notify'];
	if (isset ($values['field2_recovery']))
		$retvalues['field2_recovery'] = (string) $values['field2_recovery'];
	if (isset ($values['field3_recovery']))
		$retvalues['field3_recovery'] = (string) $values['field3_recovery'];
	
	return $retvalues;
}

function create_alert_compound ($name, $id_agent, $values = false) {
	if (empty ($name))
		return false;
	
	$values = clean_alert_compound_values ($values);
	$values['name'] = $name;
	$values['id_agent'] = $id_agent;
	
	return @process_sql_insert ('talert_compound', $values);
}

function update_alert_compound ($id_alert_compound, $values = false) {
	$id_alert_compound = safe_int ($id_alert_compound);
	if (empty ($id_alert_compound))
		return false;
	$values = clean_alert_compound_values ($values, false);
	
	return (@process_sql_update ('talert_compound', $values,
		array ('id' => $id_alert_compound))) !== false;
}

function delete_alert_compound_elements ($id_alert_compound) {
	$id_alert_compound = safe_int ($id_alert_compound);
	if (empty ($id_alert_compound))
		return false;
	
	return (@process_sql_delete ('talert_compound_elements',
		array ('id_alert_compound' => $id_alert_compound))) !== false;
}

function add_alert_compound_element ($id_alert_compound, $id_alert_template_module, $operation) {
	$id_alert_compound = safe_int ($id_alert_compound);
	if (empty ($id_alert_compound))
		return false;
	if (empty ($id_alert_template_module))
		return false;
	if (empty ($operation))
		return false;
	
	$values = array ();
	$values['id_alert_compound'] = $id_alert_compound;
	$values['id_alert_template_module'] = $id_alert_template_module;
	$values['operation'] = $operation;
	
	return @process_sql_insert ('talert_compound_elements', $values);
}

function get_alert_compound ($id_alert_compound) {
	return get_db_row ('talert_compound', 'id', $id_alert_compound);
}

function get_alert_compound_actions ($id_alert_compound, $fields = false) {
	$id_alert_compound = safe_int ($id_alert_compound);
	if (empty ($id_alert_compound))
		return false;
	
	$actions = get_db_all_rows_filter ('talert_compound_actions',
		array ('id_alert_compound' => $id_alert_compound),
		$fields);
	if ($actions === false)
		return array ();
	if ($fields !== false)
		return $actions;
	
	$retval = array ();
	foreach ($actions as $element) {
		$action = get_alert_action ($element['id_alert_action']);
		$action['fires_min'] = $element['fires_min'];
		$action['fires_max'] = $element['fires_max'];
		$retval[$element['id']] = $action;
	}
	
	return $retval;
}

function get_alert_compound_name ($id_alert_compound) {
	return (string) get_db_value ('name', 'talert_compound', 'id', $id_alert_compound);
}

function get_alert_compound_elements ($id_alert_compound) {
	return get_db_all_rows_field_filter ('talert_compound_elements',
		'id_alert_compound', $id_alert_compound);
}

function add_alert_compound_action ($id_alert_compound, $id_alert_action, $options = false) {
	if (empty ($id_alert_compound))
		return false;
	if (empty ($id_alert_action))
		return false;
	
	$values = array ();
	$values['id_alert_compound'] = $id_alert_compound;
	$values['id_alert_action'] = $id_alert_action;
	$values['fires_max'] = 0;
	$values['fires_min'] = 0;
	if ($options) {
		$max = 0;
		$min = 0;
		if (isset ($options['fires_max']))
			$max = (int) $options['fires_max'];
		if (isset ($options['fires_min']))
			$min = (int) $options['fires_min'];
		
		$values['fires_max'] = max ($max, $min);
		$values['fires_min'] = min ($max, $min);
	}
	
	return (@process_sql_insert ('talert_compound_actions', $values)) !== false;
}

function set_alerts_compound_disable ($id_alert_compound, $disabled) {
	$id_alert_agent_module = safe_int ($id_alert_compound, 0);
	return (@process_sql_update ('talert_compound',
		array ('disabled' => (bool) $disabled),
		array ('id' => $id_alert_compound))) !== false;
}

/**
 *  Validates a compound alert id or an array of alert id's
 *
 * @param mixed Array of compound alert ids or single id
 *
 * @return bool True if it was successful, false otherwise.
 */
function validate_alert_compound ($id_alert_compound) {
	global $config;
	require_once ("include/functions_events.php");
	
	$alerts = safe_int ($id_alert_compound, 1);
	
	if (empty ($alerts)) {
		return false;
	}
	
	$alerts = (array) $alerts;
	
	foreach ($alerts as $id) {
		$alert = get_alert_compound ($id);
		
		$agent_id = $alert["id_agent"];
		$group_id = get_agent_group ($agent_id);
		
		if (! give_acl ($config['id_user'], $group_id, "AW")) {
			continue;
		}
		$result = process_sql_update ('talert_compound',
			array ('times_fired' => 0,
				'internal_counter' => 0),
			array ('id' => $id));
		
		if ($result > 0) {
			create_event ("Manual validation of compound alert for ".
				$alert["name"],
				$group_id, $agent_id, 1, $config["id_user"],
				"alert_manual_validation", 1, $alert["id"],
				$id);
		} elseif ($result === false) {
			return false;
		}
	}
	return true;
}

function delete_alert_compound ($id_alert_compound) {
	$id_alert_compound = safe_int ($id_alert_compound, 1);
	if (empty ($id_alert_compound))
		return false;
	return (@process_sql_delete ('talert_compound',
		array ('id' => $id_alert_compound))) !== false;
}
?>
