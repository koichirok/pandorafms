<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;
require_once ($config['homedir'] . '/include/functions_alerts.php');
require_once ($config['homedir'] . '/include/functions_users.php');
enterprise_include_once ('meta/include/functions_alerts_meta.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}


$duplicate_template = (bool) get_parameter ('duplicate_template');
$id = (int) get_parameter ('id');
$pure = get_parameter('pure', 0);

// If user tries to duplicate/edit a template with group=ALL then must have "PM" access privileges 
if ($duplicate_template) {
	$source_id = (int) get_parameter ('source_id');
	$a_template = alerts_get_alert_template($source_id);
}
else {
	$a_template = alerts_get_alert_template($id);
}

if (defined('METACONSOLE')) {

	$sec = 'advanced';
}
else {

	$sec = 'galertas';
}

if ($a_template !== false) {
	// If user tries to duplicate/edit a template with group=ALL
	if ($a_template['id_group'] == 0){
		// then must have "PM" access privileges
		if (! check_acl ($config['id_user'], 0, "PM")) {
			db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
			require ("general/noaccess.php");
			exit;
		}
		else {
			// Header
			if (defined('METACONSOLE')) {
				
				alerts_meta_print_header();

			}
			else {
				
				ui_print_page_header (__('Alerts').' &raquo; '.__('Configure alert template'), "", false, "", true);

			}
		}
		 
	} // If user tries to duplicate/edit a template of others groups
	else {
		$own_info = get_user_info ($config['id_user']);
		if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
			$own_groups = array_keys(users_get_groups($config['id_user'], "LM"));
		else
			$own_groups = array_keys(users_get_groups($config['id_user'], "LM", false));
		$is_in_group = in_array($a_template['id_group'], $own_groups);
		// Then template group have to be in his own groups
		if ($is_in_group) {
			// Header
			if (defined('METACONSOLE')) {
				
				alerts_meta_print_header();

			}
			else {
				
				ui_print_page_header (__('Alerts').' &raquo; '.__('Configure alert template'), "", false, "", true);

			}
		}
		else {
			db_pandora_audit("ACL Violation",
			"Trying to access Alert Management");
			require ("general/noaccess.php");
			exit;
		}
	}
// This prevents to duplicate the header in case duplicate/edit_template action is performed
}
else {
	// Header
	if (defined('METACONSOLE')) {
		
		alerts_meta_print_header();
		
	}
	else {
		
		ui_print_page_header (__('Alerts').' &raquo; '.__('Configure alert template'), "", false, "", true);
		
	}
}


if ($duplicate_template) {
	$source_id = (int) get_parameter ('source_id');
	
	$id = alerts_duplicate_alert_template ($source_id);
	
	if ($id) {
		db_pandora_audit("Template alert management", "Duplicate alert template " . $source_id . " clone to " . $id);
	}
	else {
		db_pandora_audit("Template alert management", "Fail try to duplicate alert template " . $source_id);
	}
	
	ui_print_result_message ($id,
		__('Successfully created from %s', alerts_get_alert_template_name ($source_id)),
		__('Could not be created'));
}


function print_alert_template_steps ($step, $id) {
	echo '<ol class="steps">';
	
	if (defined('METACONSOLE')) {
		
		$sec = 'advanced';
	}
	else {
		
		$sec = 'galertas';
	}
	
	$pure = get_parameter('pure', 0);
	
	/* Step 1 */
	if ($step == 1)
		echo '<li class="first current">';
	elseif ($step > 1)
		echo '<li class="visited">';
	else
		echo '<li class="first">';
	
	if ($id) {
		echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&pure='.$pure.'">';
		echo __('Step').' 1 &raquo; ';
		echo '<span>'.__('Conditions').'</span>';
		echo '</a>';
	}
	else {
		echo __('Step').' 1 &raquo; ';
		echo '<span>'.__('Conditions').'</span>';
	}
	echo '</li>';
	
	/* Step 2 */
	if ($step == 2)
		echo '<li class="current">';
	elseif ($step > 2)
		echo '<li class="visited">';
	else
		echo '<li>';
	
	if ($id) {
		echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=2&pure='.$pure.'">';
		echo __('Step').' 2 &raquo; ';
		echo '<span>'.__('Firing').'</span>';
		echo '</a>';
	}
	else {
		echo __('Step').' 2 &raquo; ';
		echo '<span>'.__('Firing').'</span>';
	}
	echo '</li>';
	
	/* Step 3 */
	if ($step == 3)
		echo '<li class="last current">';
	elseif ($step > 3)
		echo '<li class="last visited">';
	else
		echo '<li class="last">';
	
	if ($id) {
		echo '<a href="index.php?sec='.$sec.'&sec2=godmode/alerts/configure_alert_template&id='.$id.'&step=3&pure='.$pure.'">';
		echo __('Step').' 3 &raquo; ';
		echo '<span>'.__('Recovery').'</span>';
		echo '</a>';
	}
	else {
		echo __('Step').' 3 &raquo; ';
		echo '<span>'.__('Recovery').'</span>';
	}
	
	echo '</ol>';
	echo '<div id="steps_clean"> </div>';
}

function update_template ($step) {
	global $config;
	
	$id = (int) get_parameter ('id');
	
	if (empty ($id))
		return false;
	
	if (defined('METACONSOLE')) {
		
		$sec = 'advanced';
	}
	else {
		
		$sec = 'galertas';
	}
	
	if ($step == 1) {
		$name = (string) get_parameter ('name');
		$description = (string) get_parameter ('description');
		$type = (string) get_parameter ('type');
		$value = (string) html_entity_decode (get_parameter ('value'));
		$max = (float) get_parameter ('max');
		$min = (float) get_parameter ('min');
		$matches = (bool) get_parameter ('matches_value');
		$wizard_level = (string) get_parameter ('wizard_level');
		$priority = (int) get_parameter ('priority');
		$id_group = get_parameter ("id_group");
		$name_check = db_get_value ('name', 'talert_templates', 'name', $name);
		
		$values = array ('name' => $name,
			'type' => $type,
			'description' => $description,
			'value' => $value,
			'max_value' => $max,
			'min_value' => $min,
			'id_group' => $id_group,
			'matches_value' => $matches,
			'priority' => $priority,
			'wizard_level' => $wizard_level);
		
		$result = alerts_update_alert_template ($id,$values);
	}
	elseif ($step == 2) {
		$monday = (bool) get_parameter ('monday');
		$tuesday = (bool) get_parameter ('tuesday');
		$wednesday = (bool) get_parameter ('wednesday');
		$thursday = (bool) get_parameter ('thursday');
		$friday = (bool) get_parameter ('friday');
		$saturday = (bool) get_parameter ('saturday');
		$sunday = (bool) get_parameter ('sunday');
		$special_day = (bool) get_parameter ('special_day');
		$time_from = (string) get_parameter ('time_from');
		$time_from = date ("H:i:00", strtotime ($time_from));
		$time_to = (string) get_parameter ('time_to');
		$time_to = date ("H:i:00", strtotime ($time_to));
		$threshold = (int) get_parameter ('threshold');
		$max_alerts = (int) get_parameter ('max_alerts');
		$min_alerts = (int) get_parameter ('min_alerts');
		
		$default_action = (int) get_parameter ('default_action');
		if (empty ($default_action)) {
			$default_action = NULL;
		}
		
		$values = array (
			'monday' => $monday,
			'tuesday' => $tuesday,
			'wednesday' => $wednesday,
			'thursday' => $thursday,
			'friday' => $friday,
			'saturday' => $saturday,
			'sunday' => $sunday,
			'special_day' => $special_day,
			'time_threshold' => $threshold,
			'id_alert_action' => $default_action,
			'max_alerts' => $max_alerts,
			'min_alerts' => $min_alerts);
		
		$fields = array();
		for($i=1;$i<=10;$i++) {
			$values['field'.$i] = $fields[$i] = (string) get_parameter ('field'.$i);
		}
		
		// Different datetimes format for oracle
		switch ($config['dbtype']){
			case "mysql":
			case "postgresql":
				$values['time_from'] = $time_from;
				$values['time_to'] = $time_to;
				break;
			case "oracle":
				$values['time_from'] = "#to_date('" . $time_from . "','hh24:mi:ss')";
				$values['time_to'] = "#to_date('" . $time_to . "','hh24:mi:ss')";
				break;
		}
		
		$result = alerts_update_alert_template ($id, $values);
	}
	elseif ($step == 3) {
		$recovery_notify = (bool) get_parameter ('recovery_notify');
		$fields_recovery = array();
		for($i=2;$i<=10;$i++) {
			$fields_recovery['field'.$i] = (string) get_parameter ('field'.$i);
		}
		$values = $fields_recovery;
		$values['recovery_notify'] = $recovery_notify;
		
		$result = alerts_update_alert_template ($id, $values);
	}
	else {
		return false;
	}
	
	if ($result) {
		db_pandora_audit("Template alert management", "Update alert template #" . $id, false, false, json_encode($values));
	}
	else {
		db_pandora_audit("Template alert management", "Fail try to update alert template #" . $id, false, false, json_encode($values));
	}
	
	return $result;
}

/* We set here the number of steps */
define ('LAST_STEP', 3);

$step = (int) get_parameter ('step', 1);

$create_template = (bool) get_parameter ('create_template');
$update_template = (bool) get_parameter ('update_template');

$name = '';
$description = '';
$type = '';
$value = '';
$max = '';
$min = '';
$time_from = '12:00';
$time_to = '12:00';
$monday = true;
$tuesday = true;
$wednesday = true;
$thursday = true;
$friday = true;
$saturday = true;
$sunday = true;
$special_day = false;
$default_action = 0;
$fields = array();
for($i=1;$i<=10;$i++) {
	$fields[$i] = '';
}
$priority = 1;
$min_alerts = 0;
$max_alerts = 1;
$threshold = 86400;
$recovery_notify = false;
$field2_recovery = '';
$field3_recovery = '';
$matches = true;
$id_group = 0;
$wizard_level = -1;

if ($create_template) {
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$type = (string) get_parameter ('type');
	$value = (string) get_parameter ('value');
	$max = (float) get_parameter ('max');
	$min = (float) get_parameter ('min');
	$matches = (bool) get_parameter ('matches_value');
	$priority = (int) get_parameter ('priority');
	$wizard_level = (string) get_parameter ('wizard_level');
	$id_group = get_parameter ("id_group");
	$name_check = db_get_value ('name', 'talert_templates', 'name', $name);
	
	$values = array ('description' => $description,
		'value' => $value,
		'max_value' => $max,
		'min_value' => $min,
		'id_group' => $id_group,
		'matches_value' => $matches,
		'priority' => $priority,
		'wizard_level' => $wizard_level);
	
	if($config['dbtype'] == "oracle") {
		$values['field3'] = ' ';
		$values['field3_recovery'] = ' ';
	}
	
	if (!$name_check) {
		$result = alerts_create_alert_template ($name, $type, $values);
	}
	else {
		$result = '';
	}	
	if ($result) {
		//db_pandora_audit("Command management", "Create alert command " . $result, false, false, json_encode($values));
		db_pandora_audit("Template alert management", "Create alert template #" . $result, false, false, json_encode($values));
	}
	else {
		//db_pandora_audit("Command management", "Fail try to create alert command", false, false, json_encode($values));
		db_pandora_audit("Template alert management", "Fail try to create alert template", false, false, json_encode($values));
	}
	
	ui_print_result_message ($result,
		__('Successfully created'),
		__('Could not be created'));
	/* Go to previous step in case of error */
	if ($result === false)
		$step = $step - 1;
	else
		$id = $result;
}

if ($update_template) {
	$result = update_template ($step - 1);
	
	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
	/* Go to previous step in case of error */
	if ($result === false) {
		$step = $step - 1;
	}
}

if ($id && ! $create_template) {
	$template = alerts_get_alert_template ($id);
	$name = $template['name'];
	$description = $template['description'];
	$type = $template['type'];
	$value = $template['value'];
	$max = $template['max_value'];
	$min = $template['min_value'];
	$matches = $template['matches_value'];
	$time_from = $template['time_from'];
	$time_to = $template['time_to'];
	$monday = (bool) $template['monday'];
	$tuesday = (bool) $template['tuesday'];
	$wednesday = (bool) $template['wednesday'];
	$thursday = (bool) $template['thursday'];
	$friday = (bool) $template['friday'];
	$saturday = (bool) $template['saturday'];
	$sunday = (bool) $template['sunday'];
	$special_day = (bool) $template['special_day'];
	$max_alerts = $template['max_alerts'];
	$min_alerts = $template['min_alerts'];
	$threshold = $template['time_threshold'];
	$fields = array();
	for($i=1;$i<=10;$i++) {
		$fields[$i] = $template['field'.$i];
	}
	$recovery_notify = $template['recovery_notify'];
	
	$fields_recovery = array();
	for($i=2;$i<=10;$i++) {
		$fields_recovery[$i] = $template['field'.$i.'_recovery'];
	}

	$default_action = $template['id_alert_action'];
	$priority = $template['priority'];
	$id_group = $template['id_group'];
	$wizard_level = $template['wizard_level'];
}

print_alert_template_steps ($step, $id);

$table->id = 'template';
$table->width = '98%';
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align: top';
$table->style[2] = 'font-weight: bold; vertical-align: top';
$table->size = array ();
$table->size[0] = '20%';
$table->size[2] = '20%';

if ($step == 2) {
	/* Firing conditions and events */
	$table->colspan = array ();
	$table->colspan[4][1] = 3;
	
	$table->data[0][0] = __('Days of week');
	$table->data[0][1] = __('Mon');
	$table->data[0][1] .= html_print_checkbox ('monday', 1, $monday, true);
	$table->data[0][1] .= __('Tue');
	$table->data[0][1] .= html_print_checkbox ('tuesday', 1, $tuesday, true);
	$table->data[0][1] .= __('Wed');
	$table->data[0][1] .= html_print_checkbox ('wednesday', 1, $wednesday, true);
	$table->data[0][1] .= __('Thu');
	$table->data[0][1] .= html_print_checkbox ('thursday', 1, $thursday, true);
	$table->data[0][1] .= __('Fri');
	$table->data[0][1] .= html_print_checkbox ('friday', 1, $friday, true);
	$table->data[0][1] .= __('Sat');
	$table->data[0][1] .= html_print_checkbox ('saturday', 1, $saturday, true);
	$table->data[0][1] .= __('Sun');
	$table->data[0][1] .= html_print_checkbox ('sunday', 1, $sunday, true);

	$table->data[0][2] = __('Use special days list');
	$table->data[0][3] = html_print_checkbox ('special_day', 1, $special_day, true);
	
	$table->data[1][0] = __('Time from');
	$table->data[1][1] = html_print_input_text ('time_from', $time_from, '', 7, 7,
		true);
	$table->data[1][2] = __('Time to');
	$table->data[1][3] = html_print_input_text ('time_to', $time_to, '', 7, 7,
		true);
	
	$table->colspan['threshold'][1] = 3;
	$table->data['threshold'][0] = __('Time threshold');
	$table->data['threshold'][1] = html_print_extended_select_for_time ('threshold', $threshold, '', '',
	'', false, true);
	
	$table->data[3][0] = __('Min. number of alerts');
	$table->data[3][1] = html_print_input_text ('min_alerts', $min_alerts, '',
		5, 7, true);
	$table->data[3][2] = __('Max. number of alerts');
	$table->data[3][3] = html_print_input_text ('max_alerts', $max_alerts, '',
		5, 7, true);
	
	$table->colspan['fields_switch'][0] = 4;
	$table->data['fields_switch'][0] = '<a href="javascript:toggle_fields();">'.__('Advanced fields management').' '.html_print_image('images/down.png',true).'</a>';

	for($i=1;$i<=10;$i++) {
		if(isset($template[$name])) {
			$value = $template[$name];
		}
		else {
			$value = '';
		}
			
		$table->colspan['field'.$i][1] = 3;
		$table->rowclass['field'.$i] = 'row_field';

		$table->data['field'.$i][0] = sprintf(__('Field %s'), $i) . ui_print_help_icon ('alert_macros', true, ui_get_full_url(false, false, false, false));
		$table->data['field'.$i][1] = html_print_textarea ('field'.$i, 1, 1, isset($fields[$i]) ? $fields[$i] : '', 'style="min-height:40px;" class="fields"', true);
	}
	
	$table->data[4][0] = __('Default action');
	$usr_groups = implode(',', array_keys(users_get_groups($config['id_user'], 'LM', true)));
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			$sql_query = sprintf('SELECT id, name FROM talert_actions WHERE id_group IN (%s) ORDER BY name', $usr_groups);
			break;
		case "oracle":
			$sql_query = sprintf('SELECT id, dbms_lob.substr(name,4000,1) as nombre FROM talert_actions WHERE id_group IN (%s) ORDER BY dbms_lob.substr(name,4000,1)', $usr_groups);
			break;
	}
	$table->data[4][1] = html_print_select_from_sql ($sql_query,
		'default_action', $default_action, '', __('None'), 0,
		true, false, false).ui_print_help_tip (__('In case you fill any Field 1, Field 2 or Field 3 above, those will replace the corresponding fields of this associated "Default action".'), true);
}
else if ($step == 3) {
	/* Alert recover */
	if (! $recovery_notify) {
		$table->rowstyle = array ();
		$table->rowstyle['field2'] = 'display:none;';
		$table->rowstyle['field3'] = 'display:none';
		$table->rowstyle['field4'] = 'display:none';
		$table->rowstyle['field5'] = 'display:none';
		$table->rowstyle['field6'] = 'display:none';
		$table->rowstyle['field7'] = 'display:none';
		$table->rowstyle['field8'] = 'display:none';
		$table->rowstyle['field9'] = 'display:none';
		$table->rowstyle['field10'] = 'display:none';
	}
	$table->data[0][0] = __('Alert recovery');
	$values = array (false => __('Disabled'), true => __('Enabled'));
	$table->data[0][1] = html_print_select ($values,
		'recovery_notify', $recovery_notify, '', '', '', true, false,
		false);
	
	for($i=2;$i<=10;$i++) {
	$table->data['field'.$i][0] = sprintf(__('Field %s'), $i);
	$table->data['field'.$i][1] = html_print_textarea ('field'.$i, 1, 1, isset($fields_recovery[$i]) ? $fields_recovery[$i] : '', 'style="min-height:40px" class="fields"', true);
	}
}
else {
	/* Step 1 by default */
	$table->size = array ();
	$table->size[0] = '20%';
	$table->data = array ();
	$table->rowstyle = array ();
	$table->rowstyle['value'] = 'display: none';
	$table->rowstyle['max'] = 'display: none';
	$table->rowstyle['min'] = 'display: none';
	
	$show_matches = false;
	switch ($type) {
		case "equal":
		case "not_equal":
		case "regex":
			$show_matches = true;
			$table->rowstyle['value'] = '';
			break;
		case "max_min":
			$show_matches = true;
		case "max":
			$table->rowstyle['max'] = '';
			if ($type == 'max')
				break;
		case "min":
			$table->rowstyle['min'] = '';
			break;
		case "onchange":
			$show_matches = true;
			break;
	}
	
	$table->data[0][0] = __('Name');
	$table->data[0][1] = html_print_input_text ('name', $name, '', 35, 255, true);
	
	$table->data[0][1] .= "&nbsp;&nbsp;". __("Group");
	$groups = users_get_groups ();
	$own_info = get_user_info($config['id_user']);
	// Only display group "All" if user is administrator or has "PM" privileges
	if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
		$display_all_group = true;
	else
		$display_all_group = false;
	$table->data[0][1] .= "&nbsp;".html_print_select_groups(false, "AR", $display_all_group, 'id_group', $id_group, '', '', 0, true);
	
	$table->data[1][0] = __('Description');
	$table->data[1][1] =  html_print_textarea ('description', 10, 30,
		$description, '', true);
	
	$table->data[2][0] = __('Priority');
	$table->data[2][1] = html_print_select (get_priorities (), 'priority',
		$priority, '', 0, 0, true, false, false);
		
	$table->data[2][0] = __('Wizard level');
	$wizard_levels = array('nowizard' => __('No wizard'),
							'basic' => __('Basic'),
							'advanced' => __('Advanced'),
							//'custom' => __('Custom'),
							);
	$table->data[2][1] = html_print_select($wizard_levels,'wizard_level',$wizard_level,'','',-1,true, false, false);
	
	$table->data[3][0] = __('Condition type');
	$table->data[3][1] = html_print_select (alerts_get_alert_templates_types (), 'type',
		$type, '', __('Select'), 0, true, false, false);
	$table->data[3][1] .= '<span id="matches_value" '.($show_matches ? '' : 'style="display: none"').'>';
	$table->data[3][1] .= '&nbsp;'.html_print_checkbox ('matches_value', 1, $matches, true);
	$table->data[3][1] .= html_print_label (__('Trigger when matches the value'),
		'checkbox-matches_value', true);
	$table->data[3][1] .= '</span>';
	
	$table->data['value'][0] = __('Value');
	$table->data['value'][1] = html_print_input_text ('value', $value, '',
		35, 255, true);
	$table->data['value'][1] .= '&nbsp;<span id="regex_ok">';
	$table->data['value'][1] .= html_print_image ('images/suc.png', true,
		array ('style' => 'display:none',
			'id' => 'regex_good',
			'title' => __('The regular expression is valid')));
	$table->data['value'][1] .= html_print_image ('images/err.png', true,
		array ('style' => 'display:none',
			'id' => 'regex_bad',
			'title' => __('The regular expression is not valid')));
	$table->data['value'][1] .= '</span>';
	
	//Min first, then max, that's more logical
	$table->data['min'][0] = __('Min.');
	$table->data['min'][1] = html_print_input_text ('min', $min, '', 5, 255, true);

	$table->data['max'][0] = __('Max.');
	$table->data['max'][1] = html_print_input_text ('max', $max, '', 5, 255, true);
	
	$table->data['example'][1] = ui_print_alert_template_example ($id, true, false);
	$table->colspan['example'][1] = 2;
}

/* If it's the last step it will redirect to template lists */
if ($step >= LAST_STEP) {
	echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/alerts/alert_templates&pure='.$pure.'">';
}
else {
	echo '<form method="post">';
}
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('id', $id);
	html_print_input_hidden ('update_template', 1);
}
else {
	html_print_input_hidden ('create_template', 1);
}

if ($step >= LAST_STEP) {
	html_print_submit_button (__('Finish'), 'finish', false, 'class="sub upd"');
}
else {
	html_print_input_hidden ('step', $step + 1);
	html_print_submit_button (__('Next'), 'next', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';

ui_require_javascript_file ('pandora_alerts');
ui_require_jquery_file ("ui-timepicker-addon");
?>

<script type="text/javascript">
/* <![CDATA[ */
var matches = <?php echo "'" . __("The alert would fire when the value matches <span id=\"value\"></span>") . "'";?>;
var matches_not = <?php echo "'" . __("The alert would fire when the value doesn\'t match <span id=\"value\"></span>") . "'";?>;
var is = <?php echo "'" . __("The alert would fire when the value is <span id=\"value\"></span>") . "'";?>;
var is_not = <?php echo "'" . __("The alert would fire when the value is not <span id=\"value\"></span>") . "'";?>;
var between = <?php echo "'" . __("The alert would fire when the value is between <span id=\"min\"></span> and <span id=\"max\"></span>") . "'";?>;
var between_not = <?php echo "'" . __("The alert would fire when the value is not between <span id=\"min\"></span> and <span id=\"max\"></span>") . "'";?>;
var under = <?php echo "'" . __("The alert would fire when the value is below <span id=\"min\"></span>") . "'";?>;
var over = <?php echo "'" . __("The alert would fire when the value is above <span id=\"max\"></span>") . "'";?>;
var warning = <?php echo "'" . __("The alert would fire when the module is in warning status") . "'";?>;
var critical = <?php echo "'" . __("The alert would fire when the module is in critical status") . "'";?>;
var onchange_msg = <?php echo "'" . __("The alert would fire when the module value changes") . "'";?>;
var onchange_not = <?php echo "'" . __("The alert would fire when the module value does not change") . "'";?>;
var unknown = <?php echo "'" . __("The alert would fire when the module is in unknown status") . "'";?>;

function check_regex () {
	if ($("#type").attr ('value') != 'regex') {
		$("img#regex_good, img#regex_bad").hide ();
		return;
	}
	
	try {
		re = new RegExp ($("#text-value").attr ("value"));
	} catch (error) {
		$("img#regex_good").hide ();
		$("img#regex_bad").show ();
		return;
	}
	$("img#regex_bad").hide ();
	$("img#regex_good").show ();
}

function render_example () {
	/* Set max */
	vmax = parseInt ($("input#text-max").attr ("value"));
	if (isNaN (vmax) || vmax == "") {
		$("span#max").empty ().append ("0");
	}
	else {
		$("span#max").empty ().append (vmax);
	}
	
	/* Set min */
	vmin = parseInt ($("input#text-min").attr ("value"));
	if (isNaN (vmin) || vmin == "") {
		$("span#min").empty ().append ("0");
	}
	else {
		$("span#min").empty ().append (vmin);
	}
	
	/* Set value */
	vvalue = $("input#text-value").attr ("value");
	if (vvalue == "") {
		$("span#value").empty ().append ("<em><?php echo __('Empty');?></em>");
	}
	else {
		$("span#value").empty ().append (vvalue);
	}
}

// Fix for metaconsole toggle
$('.row_field').css("display", "none");
var hided = true;

function toggle_fields() {
	$('.row_field').toggle();
	if (hided) {
		$('.row_field').css("display", "");
		hided = false;
	}
	else {
		$('.row_field').css("display", "none");
		hided = true;
	}
}

//toggle_fields();
	
$(document).ready (function () {
<?php
if ($step == 1) {
?>
	$("input#text-value").keyup (render_example);
	$("input#text-max").keyup (render_example);
	$("input#text-min").keyup (render_example);
	
	$("#type").change (function () {
		switch (this.value) {
		case "equal":
		case "not_equal":
			$("img#regex_good, img#regex_bad, span#matches_value").hide ();
			$("#template-max, #template-min").hide ();
			$("#template-value, #template-example").show ();
			
			/* Show example */
			if (this.value == "equal")
				$("span#example").empty ().append (is);
			else
				$("span#example").empty ().append (is_not);
			
			break;
		case "regex":
			$("#template-max, #template-min").hide ();
			$("#template-value, #template-example, span#matches_value").show ();
			check_regex ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (matches);
			else
				$("span#example").empty ().append (matches_not);
			
			break;
		case "max_min":
			$("#template-value").hide ();
			$("#template-max, #template-min, #template-example, span#matches_value").show ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (between);
			else
				$("span#example").empty ().append (between_not);
			
			break;
		case "max":
			$("#template-value, #template-min, span#matches_value").hide ();
			$("#template-max, #template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (over);
			break;
		case "min":
			$("#template-value, #template-max, span#matches_value").hide ();
			$("#template-min, #template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (under);
			break;
		case "warning":
			$("#template-value, #template-max, span#matches_value, #template-min").hide ();
			$("#template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (warning);
			break;
		case "critical":
			$("#template-value, #template-max, span#matches_value, #template-min").hide ();
			$("#template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (critical);
			break;
		case "onchange":
			$("#template-value, #template-max, #template-min").hide ();
			$("#template-example, span#matches_value").show ();
			
			/* Show example */
			if ($("#checkbox-matches_value")[0].checked)
				$("span#example").empty ().append (onchange_msg);
			else
				$("span#example").empty ().append (onchange_not);
			break;
		case "unknown":
			$("#template-value, #template-max, span#matches_value, #template-min").hide ();
			$("#template-example").show ();
			
			/* Show example */
			$("span#example").empty ().append (unknown);
			break;
		default:
			$("#template-value, #template-max, #template-min, #template-example, span#matches_value").hide ();
			break;
		}
		
		render_example ();
	}).change ();
	
	$("#checkbox-matches_value").click (function () {
		enabled = this.checked;
		type = $("#type").attr ("value");
		if (type == "regex") {
			if (enabled) {
				$("span#example").empty ().append (matches);
			}
			else {
				$("span#example").empty ().append (matches_not);
			}
		}
		else if (type == "max_min") {
			if (enabled) {
				$("span#example").empty ().append (between);
			}
			else {
				$("span#example").empty ().append (between_not);
			}
		}
		else if (type == "onchange") {
			if (enabled) {
				$("span#example").empty ().append (onchange_msg);
			}
			else {
				$("span#example").empty ().append (onchange_not);
			}
		} 
		render_example ();
	});
	
	$("#text-value").keyup (check_regex);
<?php
}
elseif ($step == 2) {
?>
	$('#text-time_from, #text-time_to').timepicker({
		showSecond: true,
		timeFormat: 'hh:mm:ss',
		timeOnlyTitle: '<?php echo __('Choose time');?>',
		timeText: '<?php echo __('Time');?>',
		hourText: '<?php echo __('Hour');?>',
		minuteText: '<?php echo __('Minute');?>',
		secondText: '<?php echo __('Second');?>',
		currentText: '<?php echo __('Now');?>',
		closeText: '<?php echo __('Close');?>'});
	
	$("#threshold").change (function () {
		if (this.value == -1) {
			$("#text-other_threshold").attr ("value", "");
			$("#template-threshold-other_label").show ();
			$("#template-threshold-other_input").show ();
		}
		else {
			$("#template-threshold-other_label").hide ();
			$("#template-threshold-other_input").hide ();
		}
	});
<?php
}
elseif ($step == 3) {
?>
	$("#recovery_notify").change (function () {
		if (this.value == 1) {
			$("#template-field2, #template-field3, #template-field4, #template-field5, #template-field6, #template-field7, #template-field8, #template-field9, #template-field10").show ();
		}
		else {
			$("#template-field2, #template-field3, #template-field4, #template-field5, #template-field6, #template-field7, #template-field8, #template-field9, #template-field10").hide ();
		}
	});
<?php
}
?>
})
/* ]]> */
</script>
