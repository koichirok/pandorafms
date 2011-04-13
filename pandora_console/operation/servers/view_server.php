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



// Load global vars
global $config;

require_once ("include/functions_servers.php");
require_once ($config["homedir"] . '/include/functions_graph.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	pandora_audit("ACL Violation",
		"Trying to access Server view");
	require ("general/noaccess.php");
	return;
}


// Header
ui_print_page_header (__("Pandora servers"), "images/server.png");

$servers = get_server_info ();
if ($servers === false) {
	echo "<div class='nf'>".__('There are no servers configured into the database')."</div>";
	return;
}

$table->width = '98%';
$table->size = array ();

$table->style = array ();
$table->style[0] = 'font-weight: bold';

$table->align = array ();
$table->align[1] = 'center';

$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Status');
$table->head[2] = __('Type');
$table->head[3] = __('Load') . ui_print_help_tip (__("Modules running on this server / Total modules of this type"), true);
$table->head[4] = __('Modules');
$table->head[5] = __('Lag') . ui_print_help_tip (__("Modules delayed / Max. Delay (sec)"), true);
$table->head[6] = __('T/Q') . ui_print_help_tip (__("Threads / Queued modules currently"), true);
// This will have a column of data such as "6 hours"
$table->head[7] = __('Updated');
$table->data = array ();

foreach ($servers as $server) {
	$data = array ();
	$data[0] = '<span title="'.$server['version'].'">'.$server['name'].'</span>';
	
	if ($server['status'] == 0) {
		$data[1] = ui_print_status_image (STATUS_SERVER_DOWN, '', true);
	}
	else {
		$data[1] = ui_print_status_image (STATUS_SERVER_OK, '', true);
	}
	
	// Type
	$data[2] = '<span style="white-space:nowrap;">'.$server["img"].'</span> ('.ucfirst($server["type"]).")";
	if ($server["master"] == 1)
		$data[2] .= ui_print_help_tip (__("This is a master server"), true);

	// Load
	$data[3] =
		progress_bar2($server["load"], 60, 20, $server["lag_txt"], 0);
	$data[4] = $server["modules"] . " ".__('of')." ". $server["modules_total"];
	$data[5] = '<span style="white-space:nowrap;">'.$server["lag_txt"].'</span>';
	$data[6] = $server['threads'].' : '.$server['queued_modules'];
	$data[7] = ui_print_timestamp ($server['keepalive'], true);
	
	array_push ($table->data, $data);
}

print_table ($table);	
?>
