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

require_once ("include/functions_agents.php");

require_once ($config["homedir"] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_groups.php');

check_login ();

$id_agente = get_parameter_get ("id_agente", -1);

$agent = db_get_row ("tagente", "id_agente", $id_agente);

if ($agent === false) {
	echo '<h3 class="error">'.__('There was a problem loading agent').'</h3>';
	return;
}

if (! check_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
	db_pandora_audit("ACL Violation", 
			  "Trying to access Agent General Information");
	require_once ("general/noaccess.php");
	return;
}

// Blank space below title, DONT remove this, this
// Breaks the layout when Flash charts are enabled :-o
echo '<div style="height: 10px">&nbsp;</div>';	
	
//Floating div
echo '<div style="float:right; width:320px; padding-top:11px;">';
echo '<b>'.__('Agent access rate (24h)').'</b><br />';

graphic_agentaccess2($id_agente, 280, 110, 86400);

echo '<div style="height:25px">&nbsp;</div>';
echo '<b>'.__('Events generated -by module-').'</b><br />';
echo graph_event_module2 (290, 120, $id_agente);
if ($config['flash_charts']) {
	echo graphic_agentevents2 ($id_agente, 290, 60, 86400);
}
echo '</div>';
	
echo '<div width="450px">';
echo '<table cellspacing="4" cellpadding="4" border="0" class="databox">';
//Agent name
echo '<tr><td class="datos"><b>'.__('Agent name').'</b></td>';
if ($agent['disabled']) {
	$cellName = "<em>" . ui_print_agent_name ($agent["id_agente"], true, 35, "upper", true) . ui_print_help_tip(__('Disabled'), true) . "</em>";
}
else {
	$cellName = ui_print_agent_name ($agent["id_agente"], true, 35, "upper", true);
}
echo '<td class="datos"><b>'.$cellName.'</b></td>';
echo '<td class="datos" width="40"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;refr=60">' . print_image("images/refresh.png", true, array("border" => '0', "title" => __('Refresh data'), "alt" => "")) . '</a>&nbsp;';
echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;flag_agent=1&amp;id_agente='.$id_agente.'">' . print_image("images/target.png", true, array("border" => '0', "title" => __('Flag'), "alt" => "")) . '</a></td></tr>';

//Addresses
echo '<tr><td class="datos2"><b>'.__('IP Address').'</b></td>';
echo '<td class="datos2" colspan="2">';
$ips = array();
$addresses = get_agent_addresses ($id_agente);
$address = get_agent_address($id_agente);

if (!empty($addresses)) {
	$ips = $addresses;
}

if (!empty($address)) {
	$ips = array_merge((array)get_agent_address ($id_agente), $ips);
}

$ips = array_unique($ips);

print_select($ips, "not_used", get_agent_address ($id_agente));
echo '</td></tr>';

//OS
echo '<tr><td class="datos"><b>'.__('OS').'</b></td>';
echo '<td class="datos" colspan="2">' . ui_print_os_icon ($agent["id_os"], true, true);

// Want to print last 15 characters of OS version, or start from 0 if smaller
$osversion_offset = strlen($agent["os_version"]);
if ($osversion_offset > 15)
	$osversion_offset = $osversion_offset - 15;
else
	$osversion_offset = 0;


echo '&nbsp;<i><span title="'.$agent["os_version"].'">'.substr($agent["os_version"],$osversion_offset,15).' </span></i></td></tr>';

// Parent
echo '<tr><td class="datos2"><b>'.__('Parent').'</b></td>';
echo '<td class="datos2" colspan="2"><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$agent["id_parent"].'">'.get_agent_name ($agent["id_parent"]).'</a></td></tr>';

// Agent Interval
echo '<tr><td class="datos"><b>'.__('Interval').'</b></td>';
echo '<td class="datos" colspan="2">'.human_time_description_raw ($agent["intervalo"]).'</td></tr>';
	
// Comments
echo '<tr><td class="datos2"><b>'.__('Description').'</b></td>';
echo '<td class="datos2" colspan="2">'.$agent["comentarios"].'</td></tr>';

// Group
echo '<tr><td class="datos"><b>'.__('Group').'</b></td>';
echo '<td class="datos" colspan="2">';
echo ui_print_group_icon ($agent["id_grupo"], true);
echo '&nbsp;(<b>';
echo ui_print_truncate_text(get_group_name ($agent["id_grupo"]));
echo '</b>)</td></tr>';

// Agent version
echo '<tr><td class="datos2"><b>'.__('Agent Version'). '</b></td>';
echo '<td class="datos2" colspan="2">'.$agent["agent_version"].'</td></tr>';

// Position Information
if ($config['activate_gis']) {
	$dataPositionAgent = getDataLastPositionAgent($agent['id_agente']);
	
	echo '<tr><td class="datos2"><b>'.__('Position (Long, Lat)'). '</b></td>';
    echo '<td class="datos2" colspan="2">';

    if ($dataPositionAgent === false) {
        echo __('There is no GIS data.');
    }
    else {
        echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=gis&amp;id_agente='.$id_agente.'">';
        if ($dataPositionAgent['description'] != "")
                echo $dataPositionAgent['description'];
        else
                echo $dataPositionAgent['stored_longitude'].', '.$dataPositionAgent['stored_latitude'];
        echo "</a>";
    }

    echo '</td></tr>';
}
// Last contact
echo '<tr><td class="datos2"><b>'.__('Last contact')." / ".__('Remote').'</b></td><td class="datos2 f9" colspan="2">';
ui_print_timestamp ($agent["ultimo_contacto"]);

echo " / ";

if ($agent["ultimo_contacto_remoto"] == "0000-00-00 00:00:00") { 
	echo __('Never');
} else {
	echo $agent["ultimo_contacto_remoto"];
}
echo '</td></tr>';

// Timezone Offset
if ($agent['timezone_offset'] != 0) {
	echo '<tr><td class="datos2"><b>'.__('Timezone Offset'). '</b></td>';
	echo '<td class="datos2" colspan="2">'.$agent["timezone_offset"].'</td></tr>';
}
// Next contact (agent)
$progress = agents_get_next_contact($id_agente);

echo '<tr><td class="datos"><b>'.__('Next agent contact').'</b></td>';
echo '<td class="datos f9" colspan="2">' . progress_bar2($progress, 200, 20) . '</td></tr>';

// Custom fields
$fields = db_get_all_rows_filter('tagent_custom_fields', array('display_on_front' => 1));
if ($fields === false) {
	$fields = array ();
}
if ($fields)
foreach($fields as $field) {
	echo '<tr><td class="datos"><b>'.$field['name'] . ui_print_help_tip (__('Custom field'), true).'</b></td>';
	$custom_value = db_get_value_filter('description', 'tagent_custom_data', array('id_field' => $field['id_field'], 'id_agent' => $id_agente));
	if($custom_value === false || $custom_value == '') {
		$custom_value = '<i>-'.__('empty').'-</i>';
	}
	echo '<td class="datos f9" colspan="2">'.$custom_value.'</td></tr>';
}

//End of table
echo '</table></div>';
?>
