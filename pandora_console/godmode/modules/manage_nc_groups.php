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

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Group Management");
	require ("general/noaccess.php");
	return;
}

// Header
ui_print_page_header (__('Module management').' &raquo; '. __('Component group management'), "", false, "", true);


require_once ('include/functions_network_components.php');

$create = (bool) get_parameter ('create');
$update = (bool) get_parameter ('update');
$delete = (bool) get_parameter ('delete');
$new = (bool) get_parameter ('new');
$id = (int) get_parameter ('id');
	
if ($create) {
	$name = (string) get_parameter ('name');
	$parent = (int) get_parameter ('parent');
	
	$result = db_process_sql_insert ('tnetwork_component_group',
		array ('name' => $name,
			'parent' => $parent));
	ui_print_result_message ($result,
		__('Successfully created'),
		__('Could not be created'));
}

if ($update) {
	$name = (string) get_parameter ('name');
	$parent = (int) get_parameter ('parent');
	
	$result = db_process_sql_update ('tnetwork_component_group',
		array ('name' => $name,
			'parent' => $parent),
		array ('id_sg' => $id));
	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Not updated. Error updating data'));
}

if ($delete) {
	$result = db_process_sql_delete ('tnetwork_component_group',
		array ('id_sg' => $id));
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Not deleted. Error deleting data'));
}

if ($id || $new) {
	require_once ('manage_nc_groups_form.php');
	return;
}


$url = ui_get_url_refresh (array ('offset' => false,
	'create' => false,
	'update' => false,
	'delete' => false,
	'new' => false,
	'crt' => false,
	'upd' => false,
	'id_sg' => false));

$table->width = '90%';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Parent');
$table->head[2] = __('Action');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[2] = 'center';
$table->size = array ();
$table->size[0] = '50%';
$table->size[1] = '50%';
$table->size[2] = '40px';
$table->data = array ();

$total_groups = db_get_all_rows_filter ('tnetwork_component_group', false, 'COUNT(*) AS total');
$total_groups = $total_groups[0]['total'];

$filter = array ();
$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];

$groups = db_get_all_rows_filter ('tnetwork_component_group', $filter);
if ($groups === false)
	$groups = array ();

ui_pagination ($total_groups, $url);

foreach ($groups as $group) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&id='.$group['id_sg'].'">'.$group['name'].'</a>';
	
	$data[1] = network_components_get_group_name ($group['parent']);
	
	$data[2] = '<form method="post" onsubmit="if (! confirm (\''.__('Are you sure?').'\')) return false">';
	$data[2] .= html_print_input_hidden ('delete', 1, true);
	$data[2] .= html_print_input_hidden ('id', $group['id_sg'], true);
	$data[2] .= html_print_input_image ('del', 'images/cross.png', 1, '', true,
		array ('title' => __('Delete')));
	$data[2] .= '</form>';
	
	array_push ($table->data, $data);
}

html_print_table ($table);

echo '<form method="post">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('new', 1);
html_print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';
?>
