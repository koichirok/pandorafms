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


if (! isset ($config['id_user'])) {
	return;
}

require_once ('include/functions_menu.php');

enterprise_include ('operation/menu.php');

$menu = array ();
$menu['class'] = 'operation';

// Agent read, Server read
if (give_acl ($config['id_user'], 0, "AR")) {

	enterprise_hook ('metaconsole_menu');

	enterprise_hook ('dashboard_menu');

	enterprise_hook ('services_menu');

	//View agents
	$menu["estado"]["text"] = __('View agents');
	$menu["estado"]["sec2"] = "operation/agentes/tactical";
	$menu["estado"]["refr"] = 60;
	$menu["estado"]["id"] = "oper-agents";
	
	$sub = array ();
	$sub["operation/agentes/tactical"]["text"] = __('Tactical view');
	$sub["operation/agentes/tactical"]["refr"] = 60;
	
	$sub["operation/agentes/group_view"]["text"] = __('Group view');
	$sub["operation/agentes/group_view"]["refr"] = 60;
	
	$sub["operation/agentes/networkmap"]["text"] = __('Network map');
	
	$sub["operation/agentes/estado_agente"]["text"] = __('Agent detail');
	$sub["operation/agentes/estado_agente"]["refr"] = 60;
				
	$sub["operation/agentes/alerts_status"]["text"] = __('Alert detail');
	$sub["operation/agentes/alerts_status"]["refr"] = 60;
	
	$sub["operation/agentes/status_monitor"]["text"] = __('Monitor detail');
	$sub["operation/agentes/status_monitor"]["refr"] = 60;
	
	$sub["operation/agentes/exportdata"]["text"] = __('Export data');
	$sub["operation/agentes/exportdata"]["refr"] = 60;

	$menu["estado"]["sub"] = $sub;
	//End of view agents
	
	//INI GIS Maps
	if ($config['activate_gis']) {
		$menu["gismaps"]["text"] = __('GIS Maps');
		$menu["gismaps"]["sec2"] = "operation/gis_maps/index";
		$menu["gismaps"]["refr"] = 60;
		$menu["gismaps"]["id"] = "oper-gismaps";
		
		$sub = array ();
		
		$gisMaps = get_db_all_rows_in_table ('tgis_map', 'map_name');
		if ($gisMaps === false) {
			$gisMaps = array ();
		}
		$id = (int) get_parameter ('id', -1);
		
		foreach ($gisMaps as $gisMap) {
			if (! check_acl ($config["id_user"], $gisMap["group_id"], "IR")) {
				continue;
			}
			$sub["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["text"] = mb_substr ($gisMap["map_name"], 0, 15);
			$sub["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["refr"] = 0;
		}
		
		$menu["gismaps"]["sub"] = $sub;
	}
	//END GIS Maps
	
	//Visual console
	$menu["visualc"]["text"] = __('Visual console');
	$menu["visualc"]["sec2"] = "operation/visual_console/index";
	$menu["visualc"]["refr"] = 60;
	$menu["visualc"]["id"] = "oper-visualc";
	
	$sub = array ();
	
	$layouts = get_db_all_rows_in_table ('tlayout', 'name');
	if ($layouts === false) {
		$layouts = array ();
	}
	$id = (int) get_parameter ('id', -1);
	
	foreach ($layouts as $layout) {
		if (! give_acl ($config["id_user"], $layout["id_group"], "AR")) {
			continue;
		}
		$sub["operation/visual_console/render_view&amp;id=".$layout["id"]]["text"] = mb_substr ($layout["name"], 0, 15);
		$sub["operation/visual_console/render_view&amp;id=".$layout["id"]]["refr"] = 0;
	}
	
	$menu["visualc"]["sub"] = $sub;
	//End of visual console
}

// Agent read, Server read
if (give_acl ($config['id_user'], 0, "PM")) {

	// Server view
	$menu["estado_server"]["text"] = __('Pandora servers');
	$menu["estado_server"]["sec2"] = "operation/servers/view_server";
	$menu["estado_server"]["id"] = "oper-servers";

	$sub = array ();		
	// Show all recon servers, and generate menu for details

	$servers = get_db_all_rows_sql ('SELECT * FROM tserver WHERE server_type = 3');
	if ($servers === false) {
		$servers = array ();
	}

	foreach ($servers as $serverItem) {
		$sub["operation/servers/view_server_detail&server_id=".$serverItem["id_server"]]["text"] = $serverItem["name"];
	}

	$menu["estado_server"]["sub"] = $sub;
	//End of server view

	//End of server view
}

enterprise_hook ('inventory_menu');

//Incidents
if (give_acl ($config['id_user'], 0, "IR") == 1) {
	$menu["incidencias"]["text"] = __('Manage incidents');
	$menu["incidencias"]["sec2"] = "operation/incidents/incident";
	$menu["incidencias"]["refr"] = 60;
	$menu["incidencias"]["id"] = "oper-incidents";
	
	$sub = array ();	
	$sub["operation/incidents/incident_statistics"]["text"] = __('Statistics');
	
	$menu["incidencias"]["sub"] = $sub;
}

// Rest of options, all with AR privilege (or should events be with incidents?)
if (give_acl ($config['id_user'], 0, "AR")) {
	// Events
	$menu["eventos"]["text"] = __('View events'); 
	$menu["eventos"]["refr"] = 60;
	$menu["eventos"]["sec2"] = "operation/events/events";
	$menu["eventos"]["id"] = "oper-events";
	
	$sub = array ();
	$sub["operation/events/event_statistics"]["text"] = __('Statistics');
	
	//RSS
	$sub["operation/events/events_rss.php"]["text"] = __('RSS');
	$sub["operation/events/events_rss.php"]["type"] = "direct";
	
	//CSV
	$sub["operation/events/export_csv.php"]["text"] = __('CSV File');
	$sub["operation/events/export_csv.php"]["type"] = "direct";
	
	//Marquee
	$sub["operation/events/events_marquee.php"]["text"] = __('Marquee');
	$sub["operation/events/events_marquee.php"]["type"] = "direct";
	
	$menu["eventos"]["sub"] = $sub;
}

// ANY user can view itself !
    // Users
    $menu["usuarios"]["text"] = __('Edit my user');
    $menu["usuarios"]["sec2"] = "operation/users/user_edit";
    $menu["usuarios"]["id"] = "oper-users";

//End of Users

// Rest of options, all with AR privilege (or should events be with incidents?)
if (give_acl ($config['id_user'], 0, "AR")) {

	//SNMP Console
	$menu["snmpconsole"]["text"] = __('SNMP console');
	$menu["snmpconsole"]["refr"] = 60;
	$menu["snmpconsole"]["sec2"] = "operation/snmpconsole/snmp_view";
	$menu["snmpconsole"]["id"] = "oper-snmpc";
	
	// Messages
	$menu["messages"]["text"] = __('Messages');
	$menu["messages"]["refr"] = 60;
	$menu["messages"]["sec2"] = "operation/messages/message";
	$menu["messages"]["id"] = "oper-messages";
	
	$sub = array ();
	$sub["operation/messages/message&amp;new_msg=1"]["text"] = __('New message');
	
	$menu["messages"]["sub"] = $sub;
	
	// Reporting
	$menu["reporting"]["text"] = __('Reporting');
	$menu["reporting"]["sec2"] = "operation/reporting/custom_reporting";
	$menu["reporting"]["id"] = "oper-reporting";
	
	$sub = array ();
	$sub["operation/reporting/custom_reporting"]["text"] = __('Custom reporting');

	$sub["operation/reporting/graph_viewer"]["text"] = __('Custom graphs');	
	
	$menu["reporting"]["sub"] = $sub;
	
	// Extensions menu additions
	if (is_array ($config['extensions'])) {
		$menu["extensions"]["text"] = __('Extensions');
		$menu["extensions"]["sec2"] = "operation/extensions";
		$menu["extensions"]["id"] = "oper-extensions";
		
		$sub = array ();
		foreach ($config["extensions"] as $extension) {
			if ($extension["operation_menu"] == '') {
				continue;
			}
			$extension_menu = $extension["operation_menu"];
			$sub[$extension_menu["sec2"]]["text"] = $extension_menu["name"];
			$sub[$extension_menu["sec2"]]["refr"] = 0;
		}
		
		$menu["extensions"]["sub"] = $sub;

		/**
		 * Add the extensions
		 */
		 foreach($config['extensions'] as $extension) {
			$operationModeMenu = $extension['operation_menu'];
			if ($operationModeMenu == null)
				continue;
			
			if (array_key_exists('fatherId',$operationModeMenu)) {
				if (strlen($operationModeMenu['fatherId']) > 0) {
					$menu[$operationModeMenu['fatherId']]['sub'][$operationModeMenu['sec2']]["text"] = __($operationModeMenu['name']);
					$menu[$operationModeMenu['fatherId']]['sub'][$operationModeMenu['sec2']]["refr"] = 60;
					$menu[$operationModeMenu['fatherId']]['sub'][$operationModeMenu['sec2']]["icon"] = $operationModeMenu['icon'];
					$menu[$operationModeMenu['fatherId']]['sub'][$operationModeMenu['sec2']]["sec"] = 'extensions';
					$menu[$operationModeMenu['fatherId']]['sub'][$operationModeMenu['sec2']]["extension"] = true;
					$menu[$operationModeMenu['fatherId']]['hasExtensions'] = true;
				}
			}
		}
	}
}


print_menu ($menu);
?>
