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
global $config;

// Login check
check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'].'/include/functions_users.php');

$groups = users_get_groups ();

switch ($action) {
	case 'new':
		$actionButtonHtml = html_print_submit_button(__('Save'), 'add', false, 'class="sub wand"', true);
		$hiddenFieldAction = 'save'; 
		break;
	case 'update':
	case 'edit':
		$actionButtonHtml = html_print_submit_button(__('Update'), 'edit', false, 'class="sub upd"', true);
		$hiddenFieldAction = 'update'; 
		break;
}

$table->width = '98%';
$table->id = 'add_alert_table';
$table->class = 'databox';
$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->size = array ();
$table->size[0] = '10%';
$table->size[1] = '90%';
$table->style[0] = 'font-weight: bold; vertical-align: top;';

$table->data['name'][0] = __('Name');
$table->data['name'][1] = html_print_input_text('name', $reportName, __('Name'), 20, 40, true);

$table->data['group'][0] = __('Group');
$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$return_all_groups = true;
else	
	$return_all_groups = false;
$table->data['group'][1] = html_print_select_groups(false, "AR", $return_all_groups, 'id_group', $idGroupReport, false, '', '', true);

$table->data['description'][0] = __('Description');
$table->data['description'][1] = html_print_textarea('description', 5, 15, $description, '', true);

echo '<form class="" method="post">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo $actionButtonHtml;
html_print_input_hidden('action', $hiddenFieldAction);
html_print_input_hidden('id_report', $idReport);
echo '</div></form>';
?>
