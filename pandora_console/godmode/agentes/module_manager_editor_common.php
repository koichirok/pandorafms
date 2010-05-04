<?php


// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function prepend_table_simple ($row, $id = false) {
	global $table_simple;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_simple->data = array_merge ($data, $table_simple->data);
}

function push_table_simple ($row, $id = false) {
	global $table_simple;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_simple->data = array_merge ($table_simple->data, $data);
}

function prepend_table_advanced ($row, $id = false) {
	global $table_advanced;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_advanced->data = array_merge ($data, $table_advanced->data);
}

function push_table_advanced ($row, $id = false) {
	global $table_advanced;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table_advanced->data = array_merge ($table_advanced->data, $data);
}

function add_component_selection ($id_network_component_type) {
	global $table_simple;
	
	$data = array ();
	$data[0] = __('Using module component').' ';
	$data[0] .= print_help_icon ('network_component', true);
	
	$component_groups = get_network_component_groups ($id_network_component_type);
	$data[1] = '<span id="component_group" class="left">';
	$data[1] .= print_select ($component_groups,
		'network_component_group', '', '', '--'.__('Manual setup').'--', 0,
		true, false, false);
	$data[1] .= '</span>';
	$data[1] .= print_input_hidden ('id_module_component_type', $id_network_component_type, true);
	$data[1] .= '<span id="no_component" class="invisible error">';
	$data[1] .= __('No component was found');
	$data[1] .= '</span>';
	$data[1] .= '<span id="component" class="invisible right">';
	$data[1] .= print_select (array (), 'network_component', '', '',
		'---'.__('Manual setup').'---', 0, true);
	$data[1] .= '</span>';
	$data[1] .= ' <span id="component_loading" class="invisible">';
	$data[1] .= '<img src="images/spinner.png" />';
	$data[1] .= '</span>';
	
	$table_simple->colspan['module_component'][1] = 3;
	$table_simple->rowstyle['module_component'] = 'background-color: #D4DDC6';
	
	prepend_table_simple ($data, 'module_component');
}

require_once ('include/functions_network_components.php');
enterprise_include_once('include/functions_policies.php');


$disabledBecauseInPolicy = false;
$disabledTextBecauseInPolicy = '';

$page = get_parameter('page', '');
if (strstr($page, "policy_modules") === false) {
	if ($config['enterprise_installed'])
		$disabledBecauseInPolicy = isModuleInPolicy($id_agent_module);
	else
		$disabledBecauseInPolicy = false;
	if ($disabledBecauseInPolicy)
		$disabledTextBecauseInPolicy = 'disabled = "disabled"';
}

$update_module_id = (int) get_parameter_get ('update_module');

print_input_hidden ('moduletype', $moduletype);

$table_simple->id = 'simple';
$table_simple->width = '90%';
$table_simple->class = 'databox_color';
$table_simple->data = array ();
$table_simple->colspan = array ();
$table_simple->style = array ();
$table_simple->style[0] = 'font-weight: bold; vertical-align: top';
$table_simple->style[2] = 'font-weight: bold; vertical-align: top';

$table_simple->data[0][0] = __('Name');
$table_simple->data[0][1] = print_input_text ('name', $name, '', 20, 100, true, $disabledBecauseInPolicy);
$table_simple->data[0][2] = __('Disabled');
$table_simple->data[0][3] = print_checkbox ("disabled", 1, $disabled, true);

$table_simple->data[1][0] = __('Type').' '.print_help_icon ('module_type', true);
if ($id_agent_module) {
	$table_simple->data[1][1] = '<em>'.get_moduletype_description ($id_module_type).'</em>';
}
else {
	if (isset($id_module_type)) {
		$idModuleType = $id_module_type;
	}
	else {
		$idModuleType = '';
	}
	
	$sql = sprintf ('SELECT id_tipo, descripcion
		FROM ttipo_modulo
		WHERE categoria IN (%s)
		ORDER BY descripcion',
		implode (',', $categories));
	$table_simple->data[1][1] = print_select_from_sql ($sql, 'id_module_type',
		$idModuleType, '', '', '', true, false, false, $disabledBecauseInPolicy);
}

$table_simple->data[1][2] = __('Module group');
$table_simple->data[1][3] = print_select_from_sql ('SELECT id_mg, name FROM tmodule_group ORDER BY name',
	'id_module_group', $id_module_group, '', __('Not assigned'), '0',
	true, false, true, $disabledBecauseInPolicy);

$table_simple->data[2][0] = __('Warning status');
$table_simple->data[2][1] = '<em>'.__('Min.').'</em>';
$table_simple->data[2][1] .= print_input_text ('min_warning', $min_warning,
	'', 5, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][1] .= '<br /><em>'.__('Max.').'</em>';
$table_simple->data[2][1] .= print_input_text ('max_warning', $max_warning,
	'', 5, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][2] = __('Critical status');
$table_simple->data[2][3] = '<em>'.__('Min.').'</em>';
$table_simple->data[2][3] .= print_input_text ('min_critical', $min_critical,
	'', 5, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][3] .= '<br /><em>'.__('Max.').'</em>';
$table_simple->data[2][3] .= print_input_text ('max_critical', $max_critical,
	'', 5, 255, true, $disabledBecauseInPolicy);

/* FF stands for Flip-flop */
$table_simple->data[3][0] = __('FF threshold').' '.print_help_icon ('ff_threshold', true);
$table_simple->data[3][1] = print_input_text ('ff_event', $ff_event,
	'', 5, 15, true, $disabledBecauseInPolicy);
$table_simple->data[3][2] = __('Historical data');
$table_simple->data[3][3] = print_checkbox ("history_data", 1, $history_data, true, $disabledBecauseInPolicy);

/* Advanced form part */
$table_advanced->id = 'advanced';
$table_advanced->width = '90%';
$table_advanced->class = 'databox_color';
$table_advanced->data = array ();
$table_advanced->style = array ();
$table_advanced->style[0] = 'font-weight: bold; vertical-align: top';
$table_advanced->style[2] = 'font-weight: bold; vertical-align: top';
$table_advanced->colspan = array ();

$table_advanced->data[0][0] = __('Description');
$table_advanced->colspan[0][1] = 3;
$table_advanced->data[0][1] = print_textarea ('description', 2, 65,
	$description, $disabledTextBecauseInPolicy, true);

$table_advanced->data[1][0] = __('Custom ID');
$table_advanced->data[1][1] = print_input_text ('custom_id', $custom_id,
	'', 20, 65, true);

$table_advanced->data[2][0] = __('Interval');
$table_advanced->data[2][1] = print_input_text ('module_interval', $interval,
	'', 5, 10, true, $disabledBecauseInPolicy);
	
$table_advanced->data[2][2] = __('Post process').' '.print_help_icon ('postprocess', true);
$table_advanced->data[2][3] = print_input_text ('post_process',
	$post_process, '', 12, 25, true, $disabledBecauseInPolicy);

$table_advanced->data[3][0] = __('Min. Value');
$table_advanced->data[3][1] = print_input_text ('min', $min, '', 5, 15, true, $disabledBecauseInPolicy);
$table_advanced->data[3][2] = __('Max. Value');
$table_advanced->data[3][3] = print_input_text ('max', $max, '', 5, 15, true, $disabledBecauseInPolicy);

$table_advanced->data[4][0] = __('Export target');
$table_advanced->data[4][1] = print_select_from_sql ('SELECT id, name FROM tserver_export ORDER BY name',
	'id_export', $id_export, '',__('None'),'0', true, false, false, $disabledBecauseInPolicy);
$table_advanced->colspan[4][1] = 3;
?>
