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

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Recon Task Management");
	require ("general/noaccess.php");
	exit;
}

require_once($config['homedir'] . "/include/functions_network_profiles.php");

// Headers
ui_print_page_header (__('Manage recontask'), "", false, "", true);

// --------------------------------
// DELETE A RECON TASKs
// --------------------------------
if (isset ($_GET["delete"])) {
	$id = get_parameter_get ("delete");
	
	$result = db_process_sql_delete('trecon_task', array('id_rt' => $id));
	
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Successfully deleted recon task').'</h3>';
	}
	else {
		echo '<h3 class="error">'.__('Error deleting recon task').'</h3>';
	}
}

// --------------------------------
// GET PARAMETERS IF UPDATE OR CREATE
// --------------------------------
if ((isset ($_GET["update"])) OR ((isset ($_GET["create"])))) {
	$name = get_parameter_post ("name");
	$network = get_parameter_post ("network");
	$description = get_parameter_post ("description");
	$id_recon_server = get_parameter_post ("id_recon_server");
	$interval = get_parameter_post ("interval");
	$id_group = get_parameter_post ("id_group");
	$create_incident = get_parameter_post ("create_incident");
	$id_network_profile = get_parameter_post ("id_network_profile");
	$recon_ports = get_parameter_post ("recon_ports", "");
	$id_os = get_parameter_post ("id_os", 10);
    $snmp_community = get_parameter_post ("snmp_community", "public");
    $id_recon_script = get_parameter ("id_recon_script", 'NULL');
    $mode = get_parameter ("mode", "");
    $field1 = get_parameter ("field1", "");
    $field2 = get_parameter ("field2", "");
    $field3 = get_parameter ("field3", "");
    $field4 = get_parameter ("field4", "");
    if ($mode == "network_sweep")
		$id_recon_script = 'NULL';
	else
		$id_network_profile = 0;
		
}

// --------------------------------
// UPDATE A RECON TASK
// --------------------------------
if (isset($_GET["update"])) {
	$id = get_parameter_get ("update");
	
	$values = array(
		'snmp_community' => $snmp_community,
		'id_os' => $id_os,
		'name' => $name,
		'subnet' => $network,
		'description' => $description,
		'id_recon_server' => $id_recon_server,
		'create_incident' => $create_incident,
		'id_group' => $id_group,
		'interval_sweep' => $interval,
		'id_network_profile' => $id_network_profile,
		'recon_ports' => $recon_ports,
		'id_recon_script' => $id_recon_script,
		'field1' => $field1,
		'field2' => $field2,
		'field3' => $field3,
		'field4' => $field4,
		);
		
	$where = array('id_rt' => $id);
	
	if ($name != "") {
		if (($id_recon_script == 0) && preg_match("/[0-9]+.+[0-9]+.+[0-9]+.+[0-9]+\/+[0-9]/", $network))
			$result = db_process_sql_update('trecon_task', $values, $where);
		elseif ($id_recon_script != 0)
			$result = db_process_sql_update('trecon_task', $values, $where);
		else 
			$result = false;
	}
	else
		$result = false;
		
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Successfully updated recon task').'</h3>';
	}
	else {
		echo '<h3 class="error">'.__('Error updating recon task').'</h3>';
	}
}

// --------------------------------
// CREATE A RECON TASK
// --------------------------------
if (isset($_GET["create"])) {
	$values = array(
		'name' => $name,
		'subnet' => $network,
		'description' => $description,
		'id_recon_server' => $id_recon_server,
		'create_incident' => $create_incident,
		'id_group' => $id_group,
		'id_network_profile' => $id_network_profile,
		'interval_sweep' => $interval,
		'id_os' => $id_os,
		'recon_ports' => $recon_ports,
		'snmp_community' => $snmp_community,
		'id_recon_script' => $id_recon_script,
		'field1' => $field1,
		'field2' => $field2,
		'field3' => $field3,
		'field4' => $field4);

	if ($name != "") {
		if (($id_recon_script == 0) && preg_match("/[0-9]+.+[0-9]+.+[0-9]+.+[0-9]+\/+[0-9]/", $network))
		{
			$result = db_process_sql_insert('trecon_task', $values);
		}
		elseif ($id_recon_script != 0) {
			$result = db_process_sql_insert('trecon_task', $values);
		}
		else 
			$result = false;
	}
	else
		$result = false;
		
	
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Successfully created recon task').'</h3>';
	}
	else {
		echo '<h3 class="error">'.__('Error creating recon task').'</h3>';
	}
}

// --------------------------------
// SHOW TABLE WITH ALL RECON TASKs
// --------------------------------
//Pandora Admin must see all columns
if (! give_acl ($config['id_user'], 0, "PM")) {
	$sql = sprintf('SELECT * FROM trecon_task RT, tusuario_perfil UP WHERE 
					UP.id_usuario = "%s" AND UP.id_grupo = RT.id_group', 
					$config['id_user']);
					
	$result = db_get_db_all_rows_sql ($sql);
} else {
	$result = db_get_db_all_rows_in_table('trecon_task');
}
$color=1;
if ($result !== false) {
	$table->head = array  (__('Name'), __('Network'), __('Mode'), __('Group'), __('Incident'), __('OS'), __('Interval'), __('Ports'), __('Action'));
	$table->align = array ("","","","center","","","center","center");
	$table->width = "99%";
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	$table->data = array ();	
	
	foreach ($result as $row) {
		
		$data = array();
		$data[0] = '<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update='.$row["id_rt"].'"><b>'.$row["name"].'</b></a>';
		
		if ($row["id_recon_script"] == 0)
			$data[1] = $row["subnet"];
		else
			$data[1] =__("N/A");
			
			
		if ($row["id_recon_script"] == 0){
		// Network recon task
			$data[2] = html_print_image ("images/network.png", true, array ("title" => __('Network recon task')))."&nbsp;&nbsp;";
			$data[2] .= network_profiles_get_name ($row["id_network_profile"]);
		} else {
			// APP recon task
			$data[2] = html_print_image ("images/plugin.png", true). "&nbsp;&nbsp;";
			$data[2] .= db_get_sql (sprintf("SELECT name FROM trecon_script WHERE id_recon_script = %d", $row["id_recon_script"]));
		}	
			
		
		// GROUP
		if ($row["id_recon_script"] == 0){
			$data[3] = ui_print_group_icon ($row["id_group"], true);
		}  else {
			$data[3] = "-";
		}
		
		// INCIDENT
		$data[4] = (($row["create_incident"] == 1) ? __('Yes') : __('No'));
		
		// OS
		if ($row["id_recon_script"] == 0){
			$data[5] =(($row["id_os"] > 0) ? ui_print_os_icon ($row["id_os"], false, true) : __('Any'));
		} else {
			$data[5] = "-";
		}
		// INTERVAL
		if ($row["interval_sweep"]==0)
			$data[6] = __("Manual");
		else
			$data[6] =human_time_description_raw($row["interval_sweep"]);
		
		// PORTS
		if ($row["id_recon_script"] == 0){
			$data[7] =	substr($row["recon_ports"],0,15);
		} else {
			$data[7] = "-";
		}
		
		// ACTION
		$data[8] = "<a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=".$row["id_recon_server"]."'>" . html_print_image("images/eye.png", true) . "</a>&nbsp;".
			'<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&delete='.$row["id_rt"].'">' . html_print_image("images/cross.png", true, array("border" => '0')) . '</a>&nbsp;<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update='.$row["id_rt"].'">' .
			html_print_image("images/config.png", true) . '</a>';
		
		$table->data[] = $data;
	}
	
	html_print_table ($table);
	unset ($table);
} else {
	echo '<div class="nf">'.__('There are no recon task configured').'</div>';
}

echo '<div class="action-buttons" style="width: 700px">';
echo '<form method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&create">';
echo html_print_submit_button (__('Create'),"crt",false,'class="sub next"',true);
echo '</form>';
echo "</div>";

?>
