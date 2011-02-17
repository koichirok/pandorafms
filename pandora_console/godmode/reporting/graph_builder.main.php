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

if (is_ajax ()) {
	$search_agents = (bool) get_parameter ('search_agents');
	
	if ($search_agents) {
		
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		$id_group = (int) get_parameter('id_group');
		
		$filter = array ();
		$filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
		$filter['id_grupo'] = $id_group; 
		
		$agents = get_agents ($filter, array ('nombre', 'direccion'));
		if ($agents === false)
			return;
		
		foreach ($agents as $agent) {
			echo $agent['nombre']."|".$agent['direccion']."\n";
		}
		
		return;
 	}
 	
 	return;
}

if ($config['flash_charts']) {
	require_once ('include/fgraph.php');
}

check_login ();

if (! give_acl ($config['id_user'], 0, "IW")) {
	pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

if ($edit_graph) {
	$graphInTgraph = get_db_row_sql("SELECT * FROM tgraph WHERE id_graph = " . $id_graph);
	$stacked = $graphInTgraph['stacked'];
	$events = $graphInTgraph['events'];
	$period = $graphInTgraph['period'];
	$name = $graphInTgraph['name'];
	$description = $graphInTgraph['description'];
	$id_group = $graphInTgraph['id_group'];
	$width = $graphInTgraph['width'];
	$height = $graphInTgraph['height'];
}else {
	$id_agent = 0;
	$id_module = 0;
	$id_group = 0;
	$name = "Pandora FMS combined graph";
	$width = 550;
	$height = 210;
	$period = 86400;
	//$alerts= "";
	$events = 0;
	$factor = 1;
	$stacked = 0;
}



// -----------------------
// CREATE/EDIT GRAPH FORM
// -----------------------

echo "<table width='500' cellpadding=4 cellspacing=4 class='databox_color'>";

if ($edit_graph)
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&edit_graph=1&update_graph=1&id=" . $id_graph . "'>";
else
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&edit_graph=1&add_graph=1'>";

echo "<tr>";
echo "<td class='datos'><b>".__('Name')."</b></td>";
echo "<td class='datos'><input type='text' name='name' size='25' ";
if ($edit_graph) {
	echo "value='" . $graphInTgraph['name'] . "' ";
}
echo ">";

$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || give_acl ($config['id_user'], 0, "PM"))
	$return_all_groups = true;
else	
	$return_all_groups = false;
	
echo "<td><b>".__('Group')."</b></td><td>" .
	print_select_groups($config['id_user'], "AR", $return_all_groups, 'graph_id_group', $id_group, '', '', '', true) .
	"</td></tr>";
echo "<tr>";
echo "<td class='datos2'><b>".__('Description')."</b></td>";
echo "<td class='datos2' colspan=3><textarea name='description' style='height:45px;' cols=55 rows=2>";
if ($edit_graph) {
	echo $graphInTgraph['description'];
}
echo "</textarea>";
echo "</td></tr>";
echo "<tr>";
echo "<td class='datos'>";
echo "<b>".__('Width')."</b></td>";
echo "<td class='datos'>";
echo "<input type='text' name='width' value='$width' size=6></td>";
echo "<td class='datos2'>";
echo "<b>".__('Height')."</b></td>";
echo "<td class='datos2'>";
echo "<input type='text' name='height' value='$height' size=6></td></tr>";

$periods = array(3600 => "1 ".__('hour'), 7200 => "2 ".__('hours'), 10800 => "3 ".__('hours'),
					21600 => "6 ".__('hours'), 43200 => "12 ".__('hours'), 86400 => "1 ".__('day'),
					172800 => "2 ".__('days'), 345600 => "4 ".__('days'), 604800 => __('Last week'),
					1296000 => "15 ".__('days'), 2592000 => __('Last month'), 5184000 => "2 ".__('months'),
					15552000 => "6 ".__('months'), 31104000 => __('1 year'), 31104000 => __('1 year'));
					
$period_label = $periods[$period];

echo "<tr>";
echo "<td class='datos'>";
echo "<b>".__('Period')."</b></td>";
echo "<td class='datos'>";
print_select ($periods, 'period', $period);
echo "</td><td class='datos2'>";
echo "<b>".__('Stacked')."</b></td>";
echo "<td class='datos2'>";
$stackeds = array(__('Area'), __('Stacked area'), __('Line'), __('Stacked line'));
print_select ($stackeds, 'stacked', $stacked);
echo "</td>";

echo "<tr>";
echo "<td class='datos'>";
echo "<b>".__('View events')."</b></td>";
echo "<td class='datos'>";
print_checkbox('events', 1, $events);
echo "</td>";

echo "<td></td><td></td>";
/*echo "<td class='datos'>";
echo "<b>".__('View alerts')."</b></td>";
echo "<td class='datos'>";
print_checkbox('alerts', 1, $alerts);
echo "</td>";*/

echo "</tr>";
echo "<tr><td colspan='4' align='right'>";
if ($edit_graph) {
	echo "<input type=submit name='store' class='sub upd' value='".__('Update')."'>";
}
else {
	echo "<input type=submit name='store' class='sub next' value='".__('Create')."'>";
}
echo "</td></tr></table>";
echo "</form>";
