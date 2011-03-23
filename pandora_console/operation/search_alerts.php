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

include_once('include/functions_alerts.php');

$searchAlerts = check_acl($config['id_user'], 0, "AR");

$selectDisabledUp = '';
$selectDisabledDown = '';
$selectAgentUp = '';
$selectAgentDown = '';
$selectModuleUp = '';
$selectModuleDown = '';
$selectTemplateUp = '';
$selectTemplateDown = '';

switch ($sortField) {
	case 'disabled':
		switch ($sort) {
			case 'up':
				$selectAgentUp = $selected;
				$order = array('field' => 'disabled', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentDown = $selected;
				$order = array('field' => 'disabled', 'order' => 'DESC');
				break;
		}
		break;
	case 'agent':
		switch ($sort) {
			case 'up':
				$selectAgentUp = $selected;
				$order = array('field' => 'agent_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentDown = $selected;
				$order = array('field' => 'agent_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'module':
		switch ($sort) {
			case 'up':
				$selectModuleUp = $selected;
				$order = array('field' => 'module_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleDown = $selected;
				$order = array('field' => 'module_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'template':
		switch ($sort) {
			case 'up':
				$selectTemplateUp = $selected;
				$order = array('field' => 'template_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectTemplateDown = $selected;
				$order = array('field' => 'template_name', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectDisabledUp = '';
		$selectDisabledDown = '';
		$selectAgentUp = $selected;
		$selectAgentDown = '';
		$selectModuleUp = '';
		$selectModuleDown = '';
		$selectTemplateUp = '';
		$selectTemplateDown = '';
		
		$order = array('field' => 'agent_name', 'order' => 'ASC');
		break;
}

$alerts = false;

if($searchAlerts) {
	$agents = array_keys(get_group_agents(array_keys(get_user_groups($config["id_user"], 'AR', false))));
	
	switch ($config["dbtype"]) {
		case "mysql":
			$whereAlerts = 'AND (
				id_alert_template IN (SELECT id FROM talert_templates WHERE name LIKE "%' . $stringSearchSQL . '%") OR
				id_alert_template IN (
					SELECT id
					FROM talert_templates
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE "%' . $stringSearchSQL . '%")) OR
				talert_template_modules.id IN (
					SELECT id_alert_template_module
					FROM talert_template_module_actions
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE "%' . $stringSearchSQL . '%")) OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE nombre LIKE "%' . $stringSearchSQL . '%") OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE id_agente IN (
						SELECT id_agente
						FROM tagente
						WHERE nombre LIKE "%' . $stringSearchSQL . '%"))
			)';
			break;
		case "postgresql":
			$whereAlerts = 'AND (
				id_alert_template IN (SELECT id FROM talert_templates WHERE name LIKE \'%' . $stringSearchSQL . '%\') OR
				id_alert_template IN (
					SELECT id
					FROM talert_templates
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE \'%' . $stringSearchSQL . '%\')) OR
				talert_template_modules.id IN (
					SELECT id_alert_template_module
					FROM talert_template_module_actions
					WHERE id_alert_action IN (
						SELECT id
						FROM talert_actions
						WHERE name LIKE \'%' . $stringSearchSQL . '%\')) OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE nombre LIKE \'%' . $stringSearchSQL . '%\') OR
				id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE id_agente IN (
						SELECT id_agente
						FROM tagente
						WHERE nombre LIKE \'%' . $stringSearchSQL . '%\'))
			)';
			break;
	}
		
	$alertsraw = get_agent_alerts_simple ($agents, "all_enabled", array('offset' => get_parameter ('offset',0), 'limit' => $config['block_size'], 'order' => $order['field'] . " " . $order['order']), $whereAlerts);

	$stringSearchPHP = substr($stringSearchSQL,1,strlen($stringSearchSQL)-2);

	$alerts = array();
	foreach($alertsraw as $key => $alert){
		$finded = false;
		$alerts[$key]['disabled'] = $alert['disabled'];
		$alerts[$key]['id_agente'] = get_agentmodule_agent($alert['id_agent_module']);
		$alerts[$key]['agent_name'] = $alert['agent_name'];
		$alerts[$key]['module_name'] = $alert['agent_module_name'];
		$alerts[$key]['template_name'] = $alert['template_name'];
		$actions = get_alert_agent_module_actions($alert['id']);
		
		$actions_name = array();
		foreach($actions as $action) {
			$actions_name[] = $action['name'];
		}
		
		$alerts[$key]['actions'] = implode(',',$actions_name);
	}

	$totalAlerts = count($alerts);
}

if ($alerts === false || $totalAlerts == 0) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = '' . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=disabled&sort=up">' . print_image("images/sort_up.png", true, array("style" => $selectDisabledUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=disabled&sort=down">' . print_image("images/sort_down.png", true, array("style" => $selectDisabledDown)) . '</a>';
	$table->head[1] = __('Agent') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=agent&sort=up">' . print_image("images/sort_up.png", true, array("style" => $selectAgentUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=agent&sort=down">' . print_image("images/sort_down.png", true, array("style" => $selectAgentDown)) . '</a>';
	$table->head[2] = __('Module') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=module&sort=up">' . print_image("images/sort_up.png", true, array("style" => $selectModuleUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=module&sort=down">' . print_image("images/sort_down.png", true, array("style" => $selectModuleDown)) . '</a>';
	$table->head[3] = __('Template') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=template&sort=up">' . print_image("images/sort_up.png", true, array("style" => $selectTemplateUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=template&sort=down">' . print_image("images/sort_down.png", true, array("style" => $selectTemplateDown)) . '</a>';
	$table->head[4] = __('Action');
	
	$table->align = array ();
	$table->align[0] = "center";
	$table->align[1] = "left";
	$table->align[2] = "left";
	$table->align[3] = "left";
	$table->align[4] = "left";
	
	$table->valign = array ();
	$table->valign[0] = "top";
	$table->valign[1] = "top";
	$table->valign[2] = "top";
	$table->valign[3] = "top";
	$table->valign[4] = "top";
	
	$table->data = array ();
	foreach ($alerts as $alert) {
		if ($alert['disabled'])
			$disabledCell = print_image ('images/lightbulb_off.png', true, array('title' => 'disable', 'alt' => 'disable'));
		else
			$disabledCell = print_image ('images/lightbulb.png', true, array('alt' => 'enable', 'title' => 'enable'));
		
		$actionCell = '';
		if (strlen($alert["actions"]) > 0) {
			$arrayActions = explode(',', $alert["actions"]);
			$actionCell = '<ul class="action_list">';
			foreach ($arrayActions as $action)
				$actionCell .= '<li><div><span class="action_name">' . $action . '</span></div><br /></li>';
			$actionCell .= '</ul>';
		}
		
		
		array_push($table->data, array(
		$disabledCell,
		print_agent_name ($alert["id_agente"], true, "upper"),
		$alert["module_name"],
		$alert["template_name"],$actionCell
		));
	}
	
	echo "<br />";pagination ($totalAlerts);
	print_table ($table); unset($table);
	pagination ($totalAlerts);
}
?>
