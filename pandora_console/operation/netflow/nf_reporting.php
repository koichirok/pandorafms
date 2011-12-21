<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

include_once("include/functions_ui.php");
include_once("include/functions_db.php");
include_once("include/functions_netflow.php");
include_once("include/functions_html.php");

check_login ();

if (! check_acl ($config["id_user"], 0, "IR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}
		
//Header
ui_print_page_header (__('Netflow Reporting'), "images/networkmap/so_cisco_new.png", false, "", false);

$filter = array ();

$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];

$reports = db_get_all_rows_filter ('tnetflow_report', $filter);
if ($reports == false){
	$reports = array();
}

$table->width = '98%';
$table->head = array ();
$table->head[0] = __('Report name');
$table->head[1] = __('Description');

$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[2] = 'center';
$table->size = array ();
$table->size[0] = '50%';
$table->size[1] = '40%';
$table->data = array ();

$total_reports = db_get_all_rows_filter ('tnetflow_report', false, 'COUNT(*) AS total');
$total_reports = $total_reports[0]['total'];

//ui_pagination ($total_reports, $url);

foreach ($reports as $report) {
	$data = array ();

	$data[0] = '<a href="index.php?sec=netf&sec2=operation/netflow/nf_view&id='.$report['id_report'].'">'.$report['id_name'].'</a>';
	$data[1] = $report['description'];
	
	array_push ($table->data, $data);
}

html_print_table ($table);

echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_report_form">';
		echo '<div class="action-buttons" style="width: '.$table->width.'">';
		html_print_submit_button (__('Create report'), 'crt', false, 'class="sub wand"');
		echo "</div>";
		echo "</form>";

?>
