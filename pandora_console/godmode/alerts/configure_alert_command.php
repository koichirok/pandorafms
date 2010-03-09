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
require_once ("include/functions_alerts.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

$name = '';
$command = '';
$description = '';
if ($id) {
	$alert = get_alert_command ($id);
	$name = $alert['name'];
	$command = $alert['command'];
	$description = $alert['description'];
}

// Header
print_page_header (__('Alerts').' &raquo; '.__('Configure alert command'), "images/god2.png", false, "", true);

$table->width = '90%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 35, 255, true);
$table->data[1][0] = __('Command');
$table->data[1][0] .= print_help_icon ('alert_macros', true);
$table->data[1][1] = print_input_text ('command', $command, '', 80, 255, true);

$table->data[2][0] = __('Description');
$table->data[2][1] = print_textarea ('description', 10, 30, $description, '', true);

echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_commands">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	print_input_hidden ('id', $id);
	print_input_hidden ('update_command', 1);
	print_submit_button (__('Update'), 'create', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_command', 1);
	print_submit_button (__('Create'), 'create', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';
?>
