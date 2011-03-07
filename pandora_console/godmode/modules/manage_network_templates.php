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
	pandora_audit("ACL Violation",
		"Trying to access Network Profile Management");
	require ("general/noaccess.php");
	return;
}

// Header
print_page_header (__('Module management')." &raquo; ".__('Module template management'), "", false, "", true);


require_once ('include/functions_network_profiles.php');

$delete_profile = (bool) get_parameter ('delete_profile');
$export_profile = (bool) get_parameter ('export_profile');

if ($delete_profile) { // if delete
	$id = (int) get_parameter_post ('delete_profile');
	
	$result = delete_network_profile ($id);
	print_result_message ($result,
		__('Template successfully deleted'),
		__('Error deleting template'));
}

if ($export_profile) {
	$id = (int) get_parameter_post ("export_profile");
	$profile_info = get_network_profile ($id);
	
	if (empty ($profile_info)) {
		print_error_message (__('This template does not exist'));
		return;
	}
	
	//It's important to keep the structure and order in the same way for backwards compatibility.
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("
				SELECT components.name, components.description, components.type, components.max, components.min, components.module_interval, 
					components.tcp_port, components.tcp_send, components.tcp_rcv, components.snmp_community, components.snmp_oid, 
					components.id_module_group, components.id_modulo, components.plugin_user, components.plugin_pass, components.plugin_parameter,
					components.max_timeout, components.history_data, components.min_warning, components.max_warning, components.min_critical, 
					components.max_critical, components.min_ff_event, comp_group.name AS group_name
				FROM `tnetwork_component` AS components, tnetwork_profile_component AS tpc, tnetwork_component_group AS comp_group
				WHERE tpc.id_nc = components.id_nc
					AND components.id_group = comp_group.id_sg
					AND tpc.id_np = %d", $id);
			break;
		case "postgresql":
			$sql = sprintf ("
				SELECT components.name, components.description, components.type, components.max, components.min, components.module_interval, 
					components.tcp_port, components.tcp_send, components.tcp_rcv, components.snmp_community, components.snmp_oid, 
					components.id_module_group, components.id_modulo, components.plugin_user, components.plugin_pass, components.plugin_parameter,
					components.max_timeout, components.history_data, components.min_warning, components.max_warning, components.min_critical, 
					components.max_critical, components.min_ff_event, comp_group.name AS group_name
				FROM \"tnetwork_component\" AS components, tnetwork_profile_component AS tpc, tnetwork_component_group AS comp_group
				WHERE tpc.id_nc = components.id_nc
					AND components.id_group = comp_group.id_sg
					AND tpc.id_np = %d", $id);
			break;
	}
	
	$components = get_db_all_rows_sql ($sql);
	
	$row_names = array ();
	$inv_names = array ();
	//Find the names of the rows that we are getting and throw away the duplicate numeric keys
	foreach ($components[0] as $row_name => $detail) {
		if (is_numeric ($row_name)) {
			$inv_names[] = $row_name;
		} else {
			$row_names[] = $row_name;
		}
	}
	
	//Send headers to tell the browser we're sending a file	
	header ("Content-type: application/octet-stream");
	header ("Content-Disposition: attachment; filename=".preg_replace ('/\s/', '_', $profile_info["name"]).".csv");
	header ("Pragma: no-cache");
	header ("Expires: 0");
	
	//Clean up output buffering
	while (@ob_end_clean ());
	
	//Then print the first line (row names)
	echo '"'.implode ('","', $row_names).'"';
	echo "\n";
	
	//Then print the rest of the data. Encapsulate in quotes in case we have comma's in any of the descriptions
	
	foreach ($components as $row) {
		foreach ($inv_names as $bad_key) {
			unset ($row[$bad_key]);
		}
		echo '"'.implode ('","', $row).'"';
		echo "\n";
	}
	
	//We're done here. The original page will still be there
	exit;
}

$result = get_db_all_rows_in_table ("tnetwork_profile", "name");

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = "95%";
$table->class = "databox";

$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Description');
$table->head[2] = __('Action');

$table->align = array ();
$table->align[2] = "center";

$table->data = array ();

foreach ($result as $row) {
	$data = array ();
	$data[0] = '<a href="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates_form&amp;id_np='.$row["id_np"].'">'.safe_input ($row["name"]).'</a>';
	$data[1] = safe_input ($row["description"]);
	$data[2] = print_input_image ("delete_profile", "images/cross.png",
		$row["id_np"],'', true,
		array ('onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;'));
	$data[2] .= print_input_image ("export_profile", "images/lightning_go.png",
		$row["id_np"], '', true);
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	echo '<form method="post" action="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates">';
	print_table ($table);
	echo '</form>';
} else {
	echo '<div class="nf" style="width:'.$table->width.'">'.__('There are no defined network profiles').'</div>';	
}

echo '<form method="post" action="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates_form">';
echo '<div style="width: '.$table->width.'" class="action-buttons">';
print_submit_button (__('Create'), "crt", '', 'class="sub next"'); 
echo '</div></form>';

?>
