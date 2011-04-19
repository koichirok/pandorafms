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

require_once ("include/functions_gis.php");
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . "/include/functions_groups.php");
require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . '/include/functions_users.php');

ui_require_javascript_file('openlayers.pandora');

enterprise_include ('operation/agentes/ver_agente.php');

check_login ();

if (is_ajax ()) {
	$get_agent_json = (bool) get_parameter ('get_agent_json');
	$get_agent_modules_json = (bool) get_parameter ('get_agent_modules_json');
	$get_agent_status_tooltip = (bool) get_parameter ("get_agent_status_tooltip");
	$get_agents_group_json = (bool) get_parameter ("get_agents_group_json");
	$get_agent_modules_json_for_multiple_agents = (bool) get_parameter("get_agent_modules_json_for_multiple_agents");
	$get_agent_modules_alerts_json_for_multiple_agents = (bool) get_parameter("get_agent_modules_alerts_json_for_multiple_agents");
	$get_agents_json_for_multiple_modules = (bool) get_parameter("get_agents_json_for_multiple_modules");
	$get_agent_modules_json_for_multiple_agents_id = (bool) get_parameter("get_agent_modules_json_for_multiple_agents_id");
	$get_agentmodule_status_tooltip = (bool) get_parameter ("get_agentmodule_status_tooltip");
	$get_group_status_tooltip = (bool) get_parameter ("get_group_status_tooltip");
	
	if ($get_agents_group_json) {
		$id_group = (int) get_parameter('id_group');

		if($id_group > 0)
			$filter = sprintf(" WHERE id_grupo = %d", $id_group);
		else {
			$groups_orig = get_user_groups();

			$a = 0;
			$groups = array_keys($groups_orig);
			
			$filter = " WHERE id_grupo IN (". implode(',', $groups) .")";
		}

		$agents = db_get_all_rows_sql("SELECT id_agente, nombre FROM tagente". $filter);

		echo json_encode($agents);
		return;
	}

	if ($get_agent_json) {
		$id_agent = (int) get_parameter ('id_agent');
		
		$agent = db_get_row ('tagente', 'id_agente', $id_agent);
		
		echo json_encode ($agent);
		return;
	}
	
	if ($get_agent_modules_json_for_multiple_agents_id) {
		$idAgents = get_parameter('id_agent');
		
		$nameModules = db_get_all_rows_sql('SELECT nombre, id_agente_modulo
			FROM tagente_modulo WHERE id_agente IN (' . implode(',', $idAgents) . ')');
		
		echo json_encode($nameModules);
		return;
	}
	
	if ($get_agents_json_for_multiple_modules) {
		$nameModules = get_parameter('module_name');
		
		$nameAgents = db_get_all_rows_sql('SELECT DISTINCT(t1.nombre) as name
			FROM tagente t1, tagente_modulo t2
			WHERE t1.id_agente = t2.id_agente
				AND t2.nombre IN (\'' . implode('\',\'', $nameModules) . '\')
				AND (
					SELECT count(t3.nombre)
					FROM tagente t3, tagente_modulo t4
					WHERE t3.id_agente = t4.id_agente AND t1.nombre = t3.nombre
						AND t4.nombre IN (\'' . implode('\',\'', $nameModules) . '\')) = '.count($nameModules));
		
		foreach($nameAgents as $nameAgent) {
			$names[] = $nameAgent['name'];
		}
		
		echo json_encode($names);
		return;
	}
	
	if ($get_agent_modules_alerts_json_for_multiple_agents) {
		$idAgents = get_parameter('id_agent');
		$id_template = get_parameter('template');
		
		$nameModules = db_get_all_rows_sql('SELECT DISTINCT(nombre)
			FROM tagente_modulo t1, talert_template_modules t2
			WHERE t2.id_agent_module = t1.id_agente_modulo
				AND delete_pending = 0
				AND id_agente IN (' . implode(',', $idAgents) . ') AND (
					SELECT count(nombre)
					FROM tagente_modulo t3, talert_template_modules t4
					WHERE t4.id_agent_module = t3.id_agente_modulo
						AND delete_pending = 0 AND t1.nombre = t3.nombre
						AND id_agente IN (' . implode(',', $idAgents) . ')) = (' . count($idAgents) . ')');
		
		if ($nameModules == false) {
			$nameModules = array();
		}
		
		$result = array();
		foreach($nameModules as $nameModule) {
			$result[] = $nameModule['nombre'];
		}
		
		echo json_encode($result);
		return;
	}
	
	if ($get_agent_modules_json_for_multiple_agents) {
		$idAgents = get_parameter('id_agent');
		$all = (string)get_parameter('all', 'all');
		switch ($all) {
			default:
			case 'all':
				$enabled = '1 = 1';
				break;
			case 'enabled':
				$enabled = 'disabled = 0';
				break;
		}
		
		$nameModules = db_get_all_rows_sql('SELECT DISTINCT(nombre)
			FROM tagente_modulo t1
			WHERE ' . $enabled . '
				AND delete_pending = 0
				AND id_agente IN (' . implode(',', $idAgents) . ') AND (
					SELECT count(nombre)
					FROM tagente_modulo t2
					WHERE delete_pending = 0 AND t1.nombre = t2.nombre
						AND id_agente IN (' . implode(',', $idAgents) . ')) = (' . count($idAgents) . ')');
		
		if ($nameModules == false) {
			$nameModules = array();
		}
		
		$result = array();
		foreach($nameModules as $nameModule) {
			$result[] = safe_output($nameModule['nombre']);
		}
		
		echo json_encode($result);
		return;
	}

	if ($get_agent_modules_json) {
		$id_agent = (int) get_parameter ('id_agent');
		$filter = safe_output((string) get_parameter ('filter'));
		$fields = (string) get_parameter ('fields');
		$indexed = (bool) get_parameter ('indexed', true);
		$agentName = (string) get_parameter ('agent_name', null);
		
		if ($agentName != null) {
				$search = array();
				$search['name'] = safe_output($agentName);
		}
		else
			$search = false;
		
		/* Get all agents if no agent was given */
		if ($id_agent == 0)
			$id_agent = array_keys (get_group_agents (array_keys (get_user_groups ()), $search, "none"));
		
		$agent_modules = get_agent_modules ($id_agent,
			($fields != '' ? explode (',', $fields) : "*"),
			($filter != '' ? $filter : false), $indexed);
			
		foreach($agent_modules as $key => $module) {
			$agent_modules[$key]['nombre'] = safe_output($module['nombre']);
		}
			
		//Hack to translate text "any" in PHP to javascript
		//$agent_modules['any_text'] = __('Any');

		echo json_encode ($agent_modules);
		return;
	}
	
	if ($get_agent_status_tooltip) {
		$id_agent = (int) get_parameter ('id_agent');
		$agent = db_get_row ('tagente', 'id_agente', $id_agent);
		echo '<h3>'.$agent['nombre'].'</h3>';
		echo '<strong>'.__('Main IP').':</strong> '.$agent['direccion'].'<br />';
		echo '<strong>'.__('Group').':</strong> ';
		echo print_image('images/groups_small/'.get_group_icon ($agent['id_grupo']).'.png', true); 
		echo get_group_name ($agent['id_grupo']).'<br />';

		echo '<strong>'.__('Last contact').':</strong> '.human_time_comparation($agent['ultimo_contacto']).'<br />';
		echo '<strong>'.__('Last remote contact').':</strong> '.human_time_comparation($agent['ultimo_contacto_remoto']).'<br />';
		
		$sql = sprintf ('SELECT tagente_modulo.descripcion, tagente_modulo.nombre
			FROM tagente_estado, tagente_modulo 
			WHERE tagente_modulo.id_agente = %d
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.disabled = 0 
				AND tagente_estado.estado = 1', $id_agent);
		$bad_modules = db_get_all_rows_sql ($sql);
		$sql = sprintf ('SELECT COUNT(*)
			FROM tagente_modulo
			WHERE id_agente = %d
				AND disabled = 0', $id_agent);
		$total_modules = db_get_sql ($sql);
		
		if ($bad_modules === false)
			$size_bad_modules = 0;
		else
			$size_bad_modules = sizeof ($bad_modules);

		// Modules down
		if ($size_bad_modules > 0) {
			echo '<strong>'.__('Monitors down').':</strong> '.$size_bad_modules.' / '.$total_modules;
			echo '<ul>';
			foreach ($bad_modules as $module) {
				echo '<li>';
				echo ui_print_truncate_text($module['nombre']);
				echo '</li>';
			}
			echo '</ul>';
		}

		// Alerts (if present)
		$sql = sprintf ('SELECT COUNT(talert_template_modules.id)
				FROM talert_template_modules, tagente_modulo, tagente
				WHERE tagente.id_agente = %d
					AND tagente.disabled = 0
					AND tagente.id_agente = tagente_modulo.id_agente
					AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
					AND talert_template_modules.times_fired > 0 ',
				$id_agent);
		$alert_modules = db_get_sql ($sql);
		if ($alert_modules > 0){
			$sql = sprintf ('SELECT tagente_modulo.nombre, talert_template_modules.last_fired
				FROM talert_template_modules, tagente_modulo, tagente
				WHERE tagente.id_agente = %d
					AND tagente.disabled = 0
					AND tagente.id_agente = tagente_modulo.id_agente
					AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
					AND talert_template_modules.times_fired > 0 ',
				$id_agent);
			$alerts = db_get_all_rows_sql ($sql);
			echo '<strong>'.__('Alerts fired').':</strong>';
			echo "<ul>";
			foreach ($alerts as $alert_item) {
				echo '<li>';
				echo ui_print_truncate_text($alert_item['nombre']).' -> ';
				echo human_time_comparation($alert_item['last_fired']);
				echo '</li>';
			}
			echo '</ul>';
		}
		
		return;
	}
	
		if ($get_agentmodule_status_tooltip) {
		$id_module = (int) get_parameter ('id_module');
		$module = db_get_row ('tagente_modulo', 'id_agente_modulo', $id_module);
		echo '<h3>';
		echo print_image("images/brick.png", true) . '&nbsp;'; 
		echo ui_print_truncate_text($module['nombre'],25,false,true,false).'</h3>';
		echo '<strong>'.__('Type').':</strong> ';
		$agentmoduletype = get_agentmodule_type ($module['id_agente_modulo']);
		echo get_moduletype_name ($agentmoduletype).'&nbsp;';
		echo print_image("images/" . get_module_type_icon ($agentmoduletype), true) . '<br />';
		echo '<strong>'.__('Module group').':</strong> ';
		$modulegroup =  get_modulegroup_name (get_agentmodule_modulegroup ($module['id_agente_modulo']));
		if($modulegroup === false){
			echo __('None').'<br />';
		}
		else{
			echo $modulegroup.'<br />';
		}
		echo '<strong>'.__('Agent').':</strong> ';
		echo ui_print_truncate_text(get_agentmodule_agent_name ($module['id_agente_modulo']),25,false,true,false).'<br />';
		
		return;
	}

	if ($get_group_status_tooltip) {
		$id_group = (int) get_parameter ('id_group');
		$group = db_get_row ('tgrupo', 'id_grupo', $id_group);
		echo '<h3>' . print_image("images/groups_small/" . get_group_icon ($group['id_grupo']) . ".png", true);
		echo ui_print_truncate_text($group['nombre'],25,false,true,false).'</h3>';
		echo '<strong>'.__('Parent').':</strong> ';
		if($group['parent'] == 0) {
			echo __('None').'<br />';
		}
		else {
			$group_parent = db_get_row ('tgrupo', 'id_grupo', $group['parent']);
			echo print_image("images/groups_small/" . get_group_icon ($group['parent']) . ".png", true); 
			echo $group_parent['nombre'].'<br />';
		}
		echo '<strong>'.__('Sons').':</strong> ';
		$groups_sons = db_get_all_fields_in_table ('tgrupo', 'parent', $group['id_grupo']);
		if($groups_sons === false){ 
			echo __('None').'<br />';
		}
		else{
			echo '<br /><br />';
			foreach($groups_sons as $group_son) {
				echo print_image("images/groups_small/" . get_group_icon ($group_son['id_grupo']) . ".png", true);
				echo $group_son['nombre'].'<br />';
			}
		}
		
		return;
	}

	return;
}

$id_agente = (int) get_parameter ("id_agente", 0);
if (empty ($id_agente)) {
	return;
}

$agent = db_get_row ('tagente', 'id_agente', $id_agente);
// get group for this id_agente
$id_grupo = $agent['id_grupo'];
if (! check_acl ($config['id_user'], $id_grupo, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access (read) to agent ".get_agent_name($id_agente));
	include ("general/noaccess.php");
	return;
}

// Check for Network FLAG change request
$flag = get_parameter('flag', '');
if ($flag !== '') {
	if ($flag == 1 && check_acl ($config['id_user'], $id_grupo, "AW")) {
		$id_agent_module = get_parameter('id_agente_modulo');
		
		db_process_sql_update('tagente_modulo', array('flag' => 1), array('id_agente_modulo' => $id_agent_module));
	}
}
// Check for Network FLAG change request
$flag_agent = get_parameter('flag_agent','');
if ($flag_agent !== ''){
	if ($flag_agent == 1 && check_acl ($config['id_user'], $id_grupo, "AW")) {
		db_process_sql_update('tagente_modulo', array('flag' => 1), array('id_agente' =>$id_agente));
	}
}

if ($agent["icon_path"]) {
	$icon = get_agent_icon_map($agent["id_agente"], true);
}
else {
	$icon = 'images/bricks.png';
}


$tab = get_parameter ("tab", "main");

/* Manage tab */

$managetab = "";

if (check_acl ($config['id_user'],$id_grupo, "AW")) {
	$managetab['text'] ='<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'">'
		. print_image("images/setup.png", true, array ("title" => __('Manage')))
		. '</a>';

	if ($tab == 'manage')
		$managetab['active'] = true;
	else
		$managetab['active'] = false;
}

/* Main tab */
$maintab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'">'
		. print_image("images/monitor.png", true, array("title" => __('Main')))
		. '</a>';
		
if ($tab == 'main')
	$maintab['active'] = true;
else
	$maintab['active'] = false;
	
/* Data */
$datatab['text']= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&tab=data">'
	. print_image("images/lightbulb.png", true, array("title" => __('Data')))
	. '</a>';

if (($tab == 'data') OR ($tab == 'data_view'))
	$datatab['active'] = true;
else
	$datatab['active'] = false;

/* Alert tab */
$alerttab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&tab=alert">'
		. print_image("images/bell.png", true, array("title" => __('Alerts')))
		. '</a>';
		
if ($tab == 'alert')
	$alerttab['active'] = true;
else
	$alerttab['active'] = false;
	
/* SLA view */
$slatab['text']= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=sla&id_agente='.$id_agente.'">'
		. print_image("images/images.png", true, array("title" => __('S.L.A.')))
		. '</a>';

if ($tab == 'sla') {
	$slatab['active'] = true;
}
else {
	$slatab['active'] = false;
}

/* Inventory */
$inventorytab = enterprise_hook ('inventory_tab');

if ($inventorytab == -1)
	$inventorytab = "";

/* Collection */
$collectiontab = enterprise_hook('collection_tab');

if ($collectiontab == -1)
	$collectiontab = "";
	
/* Policy */
$policyTab = enterprise_hook('policy_tab');
if ($policyTab == -1)
	$policyTab = "";

/* Group tab */

$grouptab['text']= '<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id='.$id_grupo.'">'
	. print_image("images/agents_group.png", true, array( "title" =>  __('Group')))
	. '</a>';
	
$grouptab['active']=false;

/* GIS tab */
$gistab="";
if ($config['activate_gis']) {
	
	$gistab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=gis&id_agente='.$id_agente.'">'
		.print_image("images/world.png", true, array( "title" => __('GIS data')))
		.'</a>';
	
	if ($tab == 'gis')
		$gistab['active'] = true;
	else 
		$gistab['active'] = false;
}

$custom_fields['text']= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=custom_fields&id_agente='.$id_agente.'">'
		. print_image("images/note.png", true, array("title" => __('Custom fields')))
		. '</a>';

if ($tab == 'custom_fields') {
	$custom_fields['active'] = true;
}
else {
	$custom_fields['active'] = false;
}

$graphs['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=graphs&id_agente='.$id_agente.'">'
		. print_image("images/chart_curve.png", true, array("title" => __('Graphs')))
		. '</a>';
if ($tab == 'graphs') {
	$graphs['active'] = true;
}
else {
	$graphs['active'] = false;
}


$onheader = array('manage' => $managetab, 'separator' => "", 'main' => $maintab, 
				'data' => $datatab, 'alert' => $alerttab, 'sla' => $slatab, 
				'inventory' => $inventorytab, 'collection' => $collectiontab, 
				'group' => $grouptab, 'gis' => $gistab, 'custom' => $custom_fields, 'graphs' => $graphs, 'policy' => $policyTab);

foreach($config['extensions'] as $extension) {
	if (isset($extension['extension_ope_tab'])) {
		$image = $extension['extension_ope_tab']['icon'];
		$name = $extension['extension_ope_tab']['name'];
		$id = $extension['extension_ope_tab']['id'];
		
		$id_extension = get_parameter('id_extension', '');
		
		if ($id_extension == $id) {
			$active = true;
		}
		else {
			$active = false;
		}
		
		$url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=extension&id_agente='.$id_agente . '&id_extension=' . $id;
		
		$extension_tab = array('text' => '<a href="' . $url .'">' . print_image ($image, true, array ( "title" => $name)) . '</a>', 'active' => $active);
		
		$onheader = $onheader + array($id => $extension_tab);	
	}
}

ui_print_page_header (__('Agent').'&nbsp;-&nbsp;'.mb_substr(get_agent_name($id_agente),0,25), $icon, false, "", false, $onheader);


switch ($tab) {
	case "custom_fields":
		require ("custom_fields.php");
		break;
	case "gis":
		require ("gis_view.php");
		break;
	case "sla":
		require ("sla_view.php");
		break;
	case "manage":	
		require ("estado_generalagente.php");
		break;
	case "main":	
		require ("estado_generalagente.php");
		require ("estado_monitores.php");
		require ("alerts_status.php");
		require ("status_events.php");
		break;
	case "data_view":
		require ("datos_agente.php");
		break;
	case "data":
		require ("estado_ultimopaquete.php");
		break;
	case "alert":
		require ("alerts_status.php");
		break;
	case "inventory":
		enterprise_include ('operation/agentes/agent_inventory.php');
		break;
	case "collection":
		enterprise_include ('operation/agentes/collection_view.php');
		break;
	case 'policy':
		enterprise_include ('operation/agentes/policy_view.php');
		break;
	case "graphs";
		require("operation/agentes/graphs.php");
		break;
	case "extension":
		$found = false;
		foreach($config['extensions'] as $extension) {
			if (isset($extension['extension_ope_tab'])) {
				$id = $extension['extension_ope_tab']['id'];
				$function = $extension['extension_ope_tab']['function'];
				
				$id_extension = get_parameter('id_extension', '');
				
				if ($id_extension == $id) {
					call_user_func_array($function, array());
					$found = true;
				}
			}
		}
		if (!$found) {
			ui_print_error_message ("Invalid tab specified in ".__FILE__.":".__LINE__);
		}
		break;
}

?>
