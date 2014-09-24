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
require_once ("include/config.php");
require_once ("include/functions_reporting.php");
require_once ($config['homedir'] . "/include/functions_agents.php");
require_once ($config['homedir'] . '/include/functions_users.php');

check_login ();
// ACL Check
if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	exit;
}

// Update network modules for this group
// Check for Network FLAG change request
// Made it a subquery, much faster on both the database and server side
if (isset ($_GET["update_netgroup"])) {
	$group = get_parameter_get ("update_netgroup", 0);
	
	if (check_acl ($config['id_user'], $group, "AW")) {
		if ($group == 0) {
			db_process_sql_update('tagente_modulo', array('flag' => 1));
		}
		else {
			db_process_sql("UPDATE `tagente_modulo`
				SET `flag` = 1
				WHERE `id_agente` = ANY(SELECT id_agente
					FROM tagente
					WHERE id_grupo = " . $group . ")");
		}
	}
	else {
		db_pandora_audit("ACL Violation", "Trying to set flag for groups");
		require ("general/noaccess.php");
		exit;
	}
}

// Get group list that user has access
$groups_full = users_get_groups ($config['id_user'], "AR", true, true);

$groups = array();
foreach ($groups_full as $group) {
	$groups[$group['id_grupo']]['name'] = $group['nombre'];
	$groups[$group['id_grupo']]['parent'] = $group['parent'];
	
	if ($group['id_grupo'] != 0) {
		$groups[$group['parent']]['childs'][] = $group['id_grupo'];
		$groups[$group['id_grupo']]['prefix'] = $groups[$group['parent']]['prefix'].'&nbsp;&nbsp;&nbsp;';
	}
	else {
		$groups[$group['id_grupo']]['prefix'] = '';
	}
	
	if (!isset($groups[$group['id_grupo']]['childs'])) {
		$groups[$group['id_grupo']]['childs'] = array();
	}
}

if ($config["realtimestats"] == 0) {
	$updated_time = __('Last update') . " : " .
		ui_print_timestamp (db_get_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
}
else {
	$updated_time = __("Updated at realtime");
}

// Header
ui_print_page_header (__("Group view"), "images/group.png", false, "", false, $updated_time );

if (tags_has_user_acl_tags()) {
	ui_print_tags_warning();
}

// Init vars
$groups_info = array ();
$counter = 1;

$agents = agents_get_group_agents(array_keys($groups));

$offset = (int)get_parameter('offset', 0);

if (count($agents) > 0) {
	$groups_get_groups_with_agent = groups_get_groups_with_agent($config['id_user'], "AR", true, true);
	ui_pagination(count($groups_get_groups_with_agent));
	
	echo '<table cellpadding="0" cellspacing="0" style="margin-top:10px;" class="databox" border="0" width="98%">';
	echo "<tr>";
	echo "<th style='width: 26px;'>" . __("Force") . "</th>";
	//echo "<th style='width: 26px;'>" . __("Status") . "</th>";
	echo "<th width='30%' style='min-width: 60px;'>" . __("Group") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Agents") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Agent unknown") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Unknown") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Not Init") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Normal") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Warning") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Critical") . "</th>";
	echo "<th width='10%' style='min-width: 60px;'>" . __("Alert fired") . "</th>";
	
	$printed_groups = array();
	
	// For each valid group for this user, take data from agent and modules
	$table_rows = array();
	foreach ($groups as $id_group => $group) {
		$rows = groups_get_group_row($id_group, $groups, $group, $printed_groups);
		if (!is_array_empty($rows)) {
			$table_rows += $rows;
		}
	}
	
	$table_rows = array_slice($table_rows, $offset, $config['block_size']);
	foreach ($table_rows as $row) {
		echo $row;
	}
	
	echo "</table>";
	
	ui_pagination(count($groups_get_groups_with_agent));
}
else {
	echo "<div class='nf'>" . __('There are no defined agents') .
		"</div>";
}

?>

