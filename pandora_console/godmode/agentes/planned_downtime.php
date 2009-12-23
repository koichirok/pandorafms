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
require_once ("include/config.php");

check_login();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access downtime scheduler");
	require ("general/noaccess.php");
	return;
}

//Initialize data
$id_agent = get_parameter ("id_agent");
$id_group = (int) get_parameter ("id_group", 1);
$name = '';
$description = '';
$date_from = (string) get_parameter ('date_from', date ('Y-m-j'));
$time_from = (string) get_parameter ('time_from', date ('h:iA'));
$date_to = (string) get_parameter ('date_to', date ('Y-m-j'));
$time_to = (string) get_parameter ('time_to', date ('h:iA'));

$first_create = (int) get_parameter ('first_create', 0);
$first_update = (int) get_parameter ('first_update', 0);

$create_downtime = (int) get_parameter ('create_downtime');
$delete_downtime = (int) get_parameter ('delete_downtime');
$edit_downtime = (int) get_parameter ('edit_downtime');
$update_downtime = (int) get_parameter ('update_downtime');
$id_downtime = (int) get_parameter ('id_downtime',0);

$insert_downtime_agent = (int) get_parameter ("insert_downtime_agent", 0);
$delete_downtime_agent = (int) get_parameter ("delete_downtime_agent", 0);

$groups = get_user_groups ();

// INSERT A NEW DOWNTIME_AGENT ASSOCIATION
if ($insert_downtime_agent == 1){
	$agents = $_POST["id_agent"];
	for ($a=0;$a <count($agents); $a++){ 
		$id_agente_dt = $agents[$a];
		$sql = "INSERT INTO tplanned_downtime_agents (id_downtime, id_agent) VALUES ($id_downtime, $id_agente_dt)";		
		$result = process_sql ($sql);
	}
}

// DELETE A DOWNTIME_AGENT ASSOCIATION
if ($delete_downtime_agent == 1){

	$id_da = get_parameter ("id_downtime_agent");
	
	$sql = "DELETE FROM tplanned_downtime_agents WHERE id = $id_da";
	$result = process_sql ($sql);
}

// DELETE WHOLE DOWNTIME!
if ($delete_downtime) {
	$sql = sprintf ("DELETE FROM tplanned_downtime WHERE id = %d", $id_downtime);
	$result = process_sql ($sql);
	$sql = sprintf ("DELETE FROM tplanned_downtime_agents WHERE id = %d", $id_downtime);
	$result2 = process_sql ($sql);

	if (($result === false) OR ($result2 === false)){
		echo '<h3 class="error">'.__('Not deleted. Error deleting data').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
	}
}

// UPDATE OR CREATE A DOWNTIME (MAIN DATA, NOT AGENT ASSOCIATION)

if ($create_downtime || $update_downtime) {
	$description = (string) get_parameter ('description');
	$name = (string) get_parameter ('name');
	$datetime_from = strtotime ($date_from.' '.$time_from);
	$datetime_to = strtotime ($date_to.' '.$time_to);
	
	if ($datetime_from > $datetime_to) {
		echo '<h3 class="error">'.__('Not created. Error inserting data').': START &gt; END</h3>';
	} else {
		$sql = '';
		if ($create_downtime) {
			$sql = sprintf ("INSERT INTO tplanned_downtime (`name`,
				`description`, `date_from`, `date_to`, `id_group`) 
				VALUES ('%s','%s',%d,%d, %d)",
				$name, $description, $datetime_from,
				$datetime_to, $id_group);
		} else if ($update_downtime) {
			$sql = sprintf ("UPDATE tplanned_downtime 
				SET `name`='%s', `description`='%s', `date_from`=%d,
				`date_to`=%d, `id_group`=%d
				WHERE `id` = '%d'",
				$name, $description, $datetime_from,
				$datetime_to, $id_group, $id_downtime);
		}
		
		$result = process_sql ($sql);
		if ($result === false) {
			echo '<h3 class="error">'.__('Could not be created').'</h3>';
		} else {
			echo '<h3 class="suc">'.__('Successfully created').'</h3>';
		}
	}
}
echo '<h2>'.__('Agent configuration').' &raquo; ';
echo __('Planned Downtime').'</h2>';
// Show create / update form
	
	if (($first_create != 0) OR ($first_update != 0)){
		// Have any data to show ?
		if ($id_downtime > 0) {
			$sql = sprintf ("SELECT `id`, `name`, `description`, `date_from`, `date_to`, `id_group`
					FROM `tplanned_downtime` WHERE `id` = %d",
					$id_downtime);
			
			$result = get_db_row_sql ($sql);
			$name = $result["name"];
			$description = $result["description"];
			$date_from = strftime ('%Y-%m-%d', $result["date_from"]);
			$date_to = strftime ('%Y-%m-%d', $result["date_to"]);
			
			if ($id_group == 1)
				$id_group = $result['id_group'];
		}
			
		$table->class = 'databox_color';
		$table->width = '90%';
		$table->data = array ();
		$table->data[0][0] = __('Name');
		$table->data[0][1] = print_input_text ('name', $name, '', 25, 40, true);
		$table->data[2][0] = __('Description');
		$table->data[2][1] = print_textarea ('description', 3, 35, $description, '', true);
		$table->data[3][0] = __('Timestamp from');
		$table->data[3][1] = print_input_text ('date_from', $date_from, '', 10, 10, true);
		$table->data[3][1] .= print_input_text ('time_from', $time_from, '', 7, 7, true);
		
		$table->data[4][0] = __('Timestamp to');
		$table->data[4][1] = print_input_text ('date_to', $date_to, '', 10, 10, true);
		$table->data[4][1] .= print_input_text ('time_to', $time_to, '', 7, 7, true);

		$table->data[5][0] = __('Group');
		$table->data[5][1] = print_select ($groups, 'id_group', $id_group, '', '', 0, true);
		echo '<form method="POST" action="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime">';

		if ($id_downtime > 0){
			echo "<table width=100% border=0 cellpadding=4 >";
			echo "<tr><td width=65% valign='top'>";
		}
	
		//Editor form
		echo '<h3>'.__('Planned Downtime Form').' '.print_help_icon ('planned_downtime', true).'</h3>';
		print_table ($table);
		
		print_input_hidden ('id_agent', $id_agent);
		echo '<div class="action-buttons" style="width: 90%">';
		if ($id_downtime) {
			print_input_hidden ('update_downtime', 1);
			print_input_hidden ('id_downtime', $id_downtime);
			print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
		} else {
			print_input_hidden ('create_downtime', 1);
			print_submit_button (__('Add'), 'crtbutton', false, 'class="sub wand"');
		}
		echo '</div>';
		echo '</form>';
		
	if ($id_downtime > 0) {

		echo "<td valign=top>";
		// Show available agents to include into downtime
		echo '<h3>'.__('Available agents').':</h3>';
	
		$filter_group = get_parameter("filter_group", $result['id_group']);
		$filter_cond = " AND id_grupo = $filter_group ";
		$sql = sprintf ("SELECT tagente.id_agente, tagente.nombre, tagente.id_grupo FROM tagente WHERE tagente.id_agente NOT IN (SELECT tagente.id_agente FROM tagente, tplanned_downtime_agents WHERE tplanned_downtime_agents.id_agent = tagente.id_agente AND tplanned_downtime_agents.id_downtime = %d) AND disabled = 0 $filter_cond ORDER by tagente.nombre", $id_downtime);
		$downtimes = get_db_all_rows_sql ($sql);
		$data = array ();
		if ($downtimes)
			foreach ($downtimes as $downtime) {		
				if (give_acl ($config["id_user"], $downtime['id_grupo'], "AR")) {
					$data[$downtime['id_agente']] = $downtime['nombre'];
				}
			}
	
		echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&first_update=1&id_downtime=$id_downtime'>";

		print_select ($groups, 'filter_group', $filter_group);	
		echo "<br /><br />";
		print_submit_button (__('Filter by group'), '', false, 'class="sub next"',false);
		echo "</form>";
	
		echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&first_update=1&insert_downtime_agent=1&id_downtime=$id_downtime'>";
	
		echo print_select ($data, "id_agent[]", '', '', '', 0, false, true);
		echo "<br /><br /><br />";
		print_submit_button (__('Add'), '', false, 'class="sub next"',false);
		echo "</form>";
		echo "</table>";
		
		//Start Overview of existing planned downtime
		echo '<h3>'.__('Agents planned for this downtime').':</h3>';
		$table->class = 'databox';
		$table->width = '80%';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Group');
		$table->head[2] = __('OS');
		$table->head[3] = __('Last contact');
		$table->head[4] = __('Remove');
		
		$sql = sprintf ("SELECT tagente.nombre, tplanned_downtime_agents.id, tagente.id_os, tagente.id_agente, tagente.id_grupo, tagente.ultimo_contacto FROM tagente, tplanned_downtime_agents WHERE tplanned_downtime_agents.id_agent = tagente.id_agente AND tplanned_downtime_agents.id_downtime = %d ",$id_downtime);
		
		$downtimes = get_db_all_rows_sql ($sql);
		if ($downtimes === false) {
			$table->colspan[0][0] = 5;
			$table->data[0][0] = __('There are no scheduled downtimes');
			$downtimes = array();
		}
		
		foreach ($downtimes as $downtime) {
			$data = array ();
			
			$data[0] = $downtime['nombre'];
	
			$data[1] = get_db_sql ("SELECT nombre FROM tgrupo WHERE id_grupo = ". $downtime["id_grupo"]);
	
	
			$data[2] = print_os_icon ($downtime["id_os"], true, true);
			
			$data[3] = $downtime["ultimo_contacto"];
	
			$data[4] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime&amp;id_agent='.
				$id_agent.'&amp;delete_downtime_agent=1&amp;first_update=1&amp;id_downtime_agent='.$downtime["id"].'&amp;id_downtime='.$id_downtime.'">
				<img src="images/cross.png" border="0" alt="'.__('Delete').'" /></a>';
	
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
} else {

	// View available downtimes present in database (if any of them)
		$table->class = 'databox';
		//Start Overview of existing planned downtime
		$table->width = '90%';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('Name #Ag.');
		$table->head[1] = __('Description');
		$table->head[2] = __('Group');
		$table->head[3] = __('From');
		$table->head[4] = __('To');
		$table->head[5] = __('Delete');
		$table->head[6] = __('Update');
		$table->head[7] = __('Running');

		$sql = "SELECT * FROM tplanned_downtime WHERE id_group IN (" . implode (",", array_keys ($groups)) . ")";
		$downtimes = get_db_all_rows_sql ($sql);
		if (!$downtimes) {
			echo '<div class="nf">'.__('No planned downtime').'</div>';
		} else {
			echo '<h3>'.__('Planned Downtime present on system').':</h3>';
			foreach ($downtimes as $downtime) {
				$data = array();
				$total  = get_db_sql ("SELECT COUNT(id_agent) FROM tplanned_downtime_agents WHERE id_downtime = ".$downtime["id"]);

				$data[0] = $downtime['name']. " ($total)";
				$data[1] = $downtime['description'];
				$data[2] = print_group_icon ($downtime['id_group'], true);
				$data[3] = date ("Y-m-d H:i", $downtime['date_from']);
				$data[4] = date ("Y-m-d H:i", $downtime['date_to']);
				if ($downtime["executed"] == 0){
					$data[5] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime&amp;id_agent='.
					$id_agent.'&amp;delete_downtime=1&amp;id_downtime='.$downtime['id'].'">
					<img src="images/cross.png" border="0" alt="'.__('Delete').'" /></a>';
					$data[6] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime&amp;edit_downtime=1&amp;first_update=1&amp;id_downtime='.$downtime['id'].'">
					<img src="images/config.png" border="0" alt="'.__('Update').'" /></a>';
				} else {
					$data[5]= "N/A";
					$data[6]= "N/A";

				}
				if ($downtime["executed"] == 0)
					$data[7] = print_image ("images/pixel_green.png", true, array ('width' => 20, 'height' => 20, 'alt' => __('Executed')));
				else
					$data[7] = print_image ("images/pixel_red.png", true, array ('width' => 20, 'height' => 20, 'alt' => __('Not executed')));

				array_push ($table->data, $data);
			}
			print_table ($table);
		}
	echo '<div class="action-buttons" style="width: '.$table->width.'">';

	echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime">';
	print_input_hidden ("first_create", 1);
	print_submit_button (__('Create'), 'create', false, 'class="sub next"');
	echo '</form>';
	echo '</div>';
}

require_css_file ('datepicker');
require_jquery_file ('ui.core');
require_jquery_file ('ui.datepicker');
require_jquery_file ('timeentry');

?>
<script language="javascript" type="text/javascript">

$(document).ready (function () {
	$("#text-time_from, #text-time_to").timeEntry ({
		spinnerImage: 'images/time-entry.png',
		spinnerSize: [20, 20, 0]
		});
	$("#text-date_from, #text-date_to").datepicker ();
	$.datepicker.regional["<?php echo $config['language']; ?>"];
});
</script>
