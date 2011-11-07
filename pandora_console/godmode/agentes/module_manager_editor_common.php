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

include_once("include/functions_modules.php");

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
	$data[0] .= ui_print_help_icon ('network_component', true);
	
	$component_groups = network_components_get_groups ($id_network_component_type);
	$data[1] = '<span id="component_group" class="left">';
	$data[1] .= html_print_select ($component_groups,
		'network_component_group', '', '', '--'.__('Manual setup').'--', 0,
		true, false, false);
	$data[1] .= '</span>';
	$data[1] .= html_print_input_hidden ('id_module_component_type', $id_network_component_type, true);
	$data[1] .= '<span id="no_component" class="invisible error">';
	$data[1] .= __('No component was found');
	$data[1] .= '</span>';
	$data[1] .= '<span id="component" class="invisible right">';
	$data[1] .= html_print_select (array (), 'network_component', '', '',
		'---'.__('Manual setup').'---', 0, true);
	$data[1] .= '</span>';
	$data[1] .= ' <span id="component_loading" class="invisible">';
	$data[1] .= html_print_image('images/spinner.png', true);
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
if (strstr($page, "policy_modules") === false && $id_agent_module) {
	if ($config['enterprise_installed'])
		$disabledBecauseInPolicy = policies_is_module_in_policy($id_agent_module) && policies_is_module_linked($id_agent_module);
	else
		$disabledBecauseInPolicy = false;
	if ($disabledBecauseInPolicy)
		$disabledTextBecauseInPolicy = 'disabled = "disabled"';
}

$update_module_id = (int) get_parameter_get ('update_module');

html_print_input_hidden ('moduletype', $moduletype);

$table_simple->id = 'simple';
$table_simple->width = '98%';
$table_simple->class = 'databox_color';
$table_simple->data = array ();
$table_simple->colspan = array ();
$table_simple->style = array ();
$table_simple->style[0] = 'font-weight: bold; vertical-align: top; width: 26%';
$table_simple->style[1] = 'width: 40%';
$table_simple->style[2] = 'font-weight: bold; vertical-align: top';

$table_simple->data[0][0] = __('Name');
$table_simple->data[0][1] = html_print_input_text ('name', $name, '', 50, 100, true, $disabledBecauseInPolicy);
$table_simple->data[0][2] = __('Disabled');
$table_simple->data[0][3] = html_print_checkbox ("disabled", 1, $disabled, true);

$table_simple->data[1][0] = __('Type').' ' . ui_print_help_icon ('module_type', true);
$table_simple->data[1][0] .= html_print_input_hidden ('id_module_type_hidden', $id_module_type, true);

if (isset($id_agent_module)) {
	if ($id_agent_module) {
		$edit = false;
	}
	else {
		$edit = true;
	}
}
else 
{
	//Run into a policy
	$edit = true;
}

if (!$edit) {
	$table_simple->data[1][1] = '<em>'.modules_get_moduletype_description ($id_module_type).'</em>';
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
	$table_simple->data[1][1] = html_print_select_from_sql ($sql, 'id_module_type',
		$idModuleType, '', '', '', true, false, false, $disabledBecauseInPolicy);
}

$table_simple->data[1][2] = __('Module group');
$table_simple->data[1][3] = html_print_select_from_sql ('SELECT id_mg, name FROM tmodule_group ORDER BY name',
	'id_module_group', $id_module_group, '', __('Not assigned'), '0',
	true, false, true, $disabledBecauseInPolicy);

$table_simple->data[2][0] = __('Warning status').' ' . ui_print_help_icon ('warning_status', true);
$table_simple->data[2][1] = '<em>'.__('Min. ').'</em></span>';
$table_simple->data[2][1] .= html_print_input_text ('min_warning', $min_warning,
	'', 10, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][1] .= '<br /><em>'.__('Max.').'</em>';
$table_simple->data[2][1] .= html_print_input_text ('max_warning', $max_warning,
	'', 10, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][1] .= '<br /><em>'.__('Str.').'</em>';
$table_simple->data[2][1] .= html_print_input_text ('str_warning', $str_warning,
	'', 10, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][2] = __('Critical status').' ' . ui_print_help_icon ('critical_status', true);
$table_simple->data[2][3] = '<em>'.__('Min. ').'</em>';
$table_simple->data[2][3] .= html_print_input_text ('min_critical', $min_critical,
	'', 10, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][3] .= '<br /><em>'.__('Max.').'</em>';
$table_simple->data[2][3] .= html_print_input_text ('max_critical', $max_critical,
	'', 10, 255, true, $disabledBecauseInPolicy);
$table_simple->data[2][3] .= '<br /><em>'.__('Str.').'</em>';
$table_simple->data[2][3] .= html_print_input_text ('str_critical', $str_critical,
	'', 10, 255, true, $disabledBecauseInPolicy);

/* FF stands for Flip-flop */
$table_simple->data[3][0] = __('FF threshold').' ' . ui_print_help_icon ('ff_threshold', true);
$table_simple->data[3][1] = html_print_input_text ('ff_event', $ff_event,
	'', 5, 15, true, $disabledBecauseInPolicy);
$table_simple->data[3][2] = __('Historical data');
$table_simple->data[3][3] = html_print_checkbox ("history_data", 1, $history_data, true, $disabledBecauseInPolicy);

/* Advanced form part */
$table_advanced->id = 'advanced';
$table_advanced->width = '98%';
$table_advanced->class = 'databox_color';
$table_advanced->data = array ();
$table_advanced->style = array ();
$table_advanced->style[0] = 'font-weight: bold; vertical-align: top';
$table_advanced->style[2] = 'font-weight: bold; vertical-align: top';
$table_advanced->colspan = array ();

$table_advanced->data[0][0] = __('Description');
$table_advanced->colspan[0][1] = 3;
$table_advanced->data[0][1] = html_print_textarea ('description', 2, 65,
	$description, $disabledTextBecauseInPolicy, true);

$table_advanced->data[1][0] = __('Custom ID');
$table_advanced->data[1][1] = html_print_input_text ('custom_id', $custom_id,
	'', 20, 65, true);

$table_advanced->data[2][0] = __('Interval');
$table_advanced->data[2][1] = html_print_input_text ('module_interval', $interval,
	'', 5, 10, true, $disabledBecauseInPolicy).ui_print_help_tip (__('Module execution time interval (in secs).'), true);
	
$table_advanced->data[2][2] = __('Post process').' ' . ui_print_help_icon ('postprocess', true);
$table_advanced->data[2][3] = html_print_input_text ('post_process',
	$post_process, '', 15, 25, true, $disabledBecauseInPolicy);

$table_advanced->data[3][0] = __('Min. Value');
$table_advanced->data[3][1] = html_print_input_text ('min', $min, '', 5, 15, true, $disabledBecauseInPolicy);
$table_advanced->data[3][2] = __('Max. Value');
$table_advanced->data[3][3] = html_print_input_text ('max', $max, '', 5, 15, true, $disabledBecauseInPolicy);

$table_advanced->data[4][0] = __('Export target');
$table_advanced->data[4][1] = html_print_select_from_sql ('SELECT id, name FROM tserver_export ORDER BY name',
	'id_export', $id_export, '',__('None'),'0', true, false, false, $disabledBecauseInPolicy).ui_print_help_tip (__('In case you use an Export server you can link this module and export data to one these.'), true);
$table_advanced->colspan[4][1] = 3;
$table_advanced->data[5][0] = __('Unit');
$table_advanced->data[5][1] = html_print_input_text ('unit', $unit,
	'', 20, 65, true);
/* Tags */
// This var comes from module_manager_editor.php or policy_modules.php
global $__code_from;
$table_advanced->data[6][0] =  __('Tags available');
// Code comes from module_editor
if ($__code_from == 'modules') {
	$__table_modules = 'ttag_module';
	$__id_where = 'b.id_agente_modulo';
	$__id = $id_agent_module;
// Code comes from policy module editor
}else {
	global $__id_pol_mod;
	$__table_modules= 'ttag_policy_module';
	$__id_where = 'b.id_policy_module';
	$__id = $__id_pol_mod;
}
$table_advanced->data[6][1] = html_print_select_from_sql ("SELECT id_tag, name
										FROM ttag 
										WHERE id_tag NOT IN (
											SELECT a.id_tag
											FROM ttag a, $__table_modules b 
											WHERE a.id_tag = b.id_tag AND $__id_where = $__id )
											ORDER BY name",
	'id_tag_available[]', $id_tag, '',__('None'),'0', true, true, false, false, 'width: 200px', '5');
$table_advanced->data[6][2] =  html_print_image('images/darrowright.png', true, array('id' => 'right', 'title' => __('Add tags to module'))); //html_print_input_image ('add', 'images/darrowright.png', 1, '', true, array ('title' => __('Add tags to module')));
$table_advanced->data[6][2] .= '<br><br><br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'left', 'title' => __('Delete tags to module'))); //html_print_input_image ('add', 'images/darrowleft.png', 1, '', true, array ('title' => __('Delete tags to module')));
	
$table_advanced->data[6][3] = '<b>' . __('Tags selected') . '</b>';
$table_advanced->data[6][4] =  html_print_select_from_sql ("SELECT a.id_tag, name 
										FROM ttag a, $__table_modules b
										WHERE a.id_tag = b.id_tag AND $__id_where = $__id
										ORDER BY name",
	'id_tag_selected[]', $id_tag, '',__('None'),'0', true, true, false, false, 'width: 200px', '5');
//$table_advanced->data[6][4] .= html_print_input_hidden('id_tag_serialize', '');

?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#right").click (function () {
		jQuery.each($("select[name='id_tag_available[]'] option:selected"), function (key, value) {
			tag_name = $(value).html();
			if (tag_name != 'None'){
				id_tag = $(value).attr('value');
				$("select[name='id_tag_selected[]']").append($("<option selected='selected'>").val(id_tag).html('<i>' + tag_name + '</i>'));
				$("#id_tag_available").find("option[value='" + id_tag + "']").remove();
			}
		});			
	});
	$("#left").click (function () {
		jQuery.each($("select[name='id_tag_selected[]'] option:selected"), function (key, value) {
				tag_name = $(value).html();
				if (tag_name != 'None'){
					id_tag = $(value).attr('value');
					$("select[name='id_tag_available[]']").append($("<option>").val(id_tag).html('<i>' + tag_name + '</i>'));
					$("#id_tag_selected").find("option[value='" + id_tag + "']").remove();
				}
		});			
	});
});
/* ]]> */
</script>
