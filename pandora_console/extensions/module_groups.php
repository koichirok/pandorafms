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
// Load global vars

global $config;

if (is_ajax ()) {
	check_login ();
	
	require_once('include/functions_agents.php');
	
	$get_info_alert_module_group = (bool)get_parameter('get_info_alert_module_group');
	$module_group = (int)get_parameter('module_group');
	$id_agent_group = (int)get_parameter('id_agent_group');
	
	if ($get_info_alert_module_group) {
		$agents = get_group_agents($id_agent_group);
		if (!empty($agents)) {
			$alerts = get_agent_alerts_simple($agents);
			
			foreach ($alerts as $alert) {
				$module = get_db_row_filter('tagente_modulo', array('id_agente_modulo' => $alert['id_agent_module']));
				
				if ($module_group == $module['id_module_group']) {
					if ($alert["times_fired"] > 0) {
						echo '<strong>' . __('Number fired of alerts').': </strong> ' . $alert["times_fired"] . '<br />';
						$agent = get_db_row('tagente', 'id_agente', $module['id_agente']);
						echo '<strong>' . __('Agent').': </strong>';
						echo safe_output($agent['nombre']) . '<br />';
						echo '<strong>' . __('Module') . ': </strong>';
						echo safe_output($module['nombre']) . '<br />';
						$template = get_db_row('talert_templates', 'id' , $alert['id_alert_template']);
						echo '<strong>' . __('Alert template') . ': </strong>';
						echo safe_output($template['name']) . '<br />';
						
						$sql = 'SELECT *
							FROM talert_template_module_actions AS t1
								INNER JOIN talert_actions AS t2 ON t1.id_alert_action = t2.id
							WHERE t1.id_alert_template_module = ' . $template['id'] . '
								OR t2.id = ' . $template['id_alert_action'] . ';';
						
						$actions = get_db_all_rows_sql($sql);
						if ($actions === false) {
							$actions = array();
						}
						
						echo '<strong>' . __('Actions') . ': </strong>' . '<br />';
						echo '<ul style="margin-top: 0px; margin-left: 30px;">';
						foreach ($actions as $action) {
							echo '<li style="list-style: disc;">' . $action['name'] . '</li>';
						}
						echo '</ul>';
						if ($alert != end($alerts)) {
							echo '<hr />';
						}
					}
				}
			}
		}
	}
}
 
/**
 * Translate the array texts using gettext
 */
 function translate(&$item, $key) {
 	$item = __($item);
 }
 
/**
 * The main function of module groups and the enter point to
 * execute the code.
 */
function mainModuleGroups() {
	global $config; //the useful global var of Pandora Console, it has many data can you use

	require_once ('include/functions_reporting.php');

	//The big query
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = "SELECT COUNT(id_agente) AS count, estado
				FROM tagente_estado
				WHERE utimestamp != 0 AND id_agente IN
					(SELECT id_agente FROM tagente WHERE id_grupo = %d AND disabled IS FALSE)
					AND id_agente_modulo IN
					(SELECT id_agente_modulo 
						FROM tagente_modulo 
						WHERE id_module_group = %d AND disabled IS FALSE AND delete_pending IS FALSE)
				GROUP BY estado";
			break;
		case "oracle":	
			$sql = "SELECT COUNT(id_agente) AS count, estado
				FROM tagente_estado
				WHERE utimestamp != 0 AND id_agente IN
					(SELECT id_agente FROM tagente WHERE id_grupo = %d AND (disabled IS NOT NULL AND disabled <> 0))
					AND id_agente_modulo IN
					(SELECT id_agente_modulo 
						FROM tagente_modulo 
						WHERE id_module_group = %d AND (disabled IS NOT NULL AND disabled <> 0) AND (delete_pending IS NOT NULL AND delete_pending <> 0))
				GROUP BY estado";
			break;
	}
	
	print_page_header (__("Combined table of agent group and module group"));
	
	echo "<p>" . __("This table shows in columns the modules group and in rows agents group. The cell shows all modules") . "</p>";
	
	
	$agentGroups = get_user_groups ($config['id_user'], "AR", false);
	$modelGroups = get_all_model_groups();
	array_walk($modelGroups, 'translate'); //Translate all head titles to language is set

	foreach ($modelGroups as $i => $n) {
		$modelGroups[$i] = printTruncateText($n, 20);
	}

	$head = $modelGroups;
	array_unshift($head, '&nbsp;');
	
	//Metaobject use in print_table
	$table = null;
	$table->align[0] = 'right'; //Align to right the first column.
	$table->style[0] = 'color: #ffffff; background-color: #778866; font-weight: bolder;';
	$table->head = $head;
	$table->width = '95%';
	
	//The content of table
	$tableData = array();
	
	$fired = false;
	
	//Create rows and cells
	foreach ($agentGroups as $idAgentGroup => $name) {
		
		$row = array();
		
		array_push($row, printTruncateText($name, 20));
	
		foreach ($modelGroups as $idModelGroup => $modelGroup) {
			$query = sprintf($sql,$idAgentGroup, $idModelGroup);

			$rowsDB = get_db_all_rows_sql ($query);
			
			
			$agents = get_group_agents($idAgentGroup);
			if (!empty($agents)) {
				$alerts = get_agent_alerts_simple($agents);
				
				foreach ($alerts as $alert) {
					$module = get_db_row_filter('tagente_modulo', array('id_agente_modulo' => $alert['id_agent_module']));
					
					if ($idModelGroup == $module['id_module_group']) {
						if ($alert["times_fired"] > 0) {
							$fired = true;
						}
					}
				}
			}			
			
			$states = array();
			if ($rowsDB !== false) {
				foreach ($rowsDB as $rowDB) {
					$states[$rowDB['estado']] = $rowDB['count'];	
				}
			}
			
			$count = 0;
			foreach ($states as $idState => $state) {
				$count += $state;
			}
			
			$color = 'transparent'; //Defaut color for cell
			$font_color = '#000000'; //Default font color for cell
			if ($count == 0) {
				$color = '#eeeeee'; //Soft grey when the cell for this model group and agent group hasn't modules.
				$alinkStart = '';
				$alinkEnd = '';
			}
			else {
				
				if ($fired) {
						$color = '#ffa300'; //Orange when the cell for this model group and agent has at least one alert fired.
				}
				else if (array_key_exists(1,$states)) {
						$color = '#cc0000'; //Red when the cell for this model group and agent has at least one module in critical state and the rest in any state.
						$font_color = '#ffffff';
				}
				elseif (array_key_exists(2,$states)) {
						$color = '#fce94f'; //Yellow when the cell for this model group and agent has at least one in warning state and the rest in green state.
				}
				elseif (array_key_exists(3,$states)) {
					$color = '#babdb6'; //Grey when the cell for this model group and agent has at least one module in unknown state and the rest in any state.
				}
				elseif (array_key_exists(0,$states)) {
					$color = '#8ae234'; //Green when the cell for this model group and agent has OK state all modules.
				}
				
				
				$alinkStart = '<a class="info_cell" rel="ajax.php?page=extensions/module_groups&get_info_alert_module_group=1&module_group=' . $idModelGroup . '&id_agent_group=' . $idAgentGroup . '"
					href="index.php?sec=estado&sec2=operation/agentes/status_monitor&status=-1&ag_group=' . $idAgentGroup . 
					'&modulegroup=' . $idModelGroup . '" style="color: ' . $font_color . '; font-size: 18px;";>';
				$alinkEnd = '</a>';
			}
			
			array_push($row,
				'<div
					style="background: ' . $color . ';
					height: 25px;
					margin-left: auto; margin-right: auto;
					text-align: center; padding-top: 0px; font-size: 18px;">
					' . $alinkStart . $count . $alinkEnd . '</div>');
		}
		array_push($tableData,$row);
	}
	$table->data = $tableData;
	echo "<div style='width:98%; overflow-x:scroll;'>";
	print_table($table);
	echo "</div>";
	
	echo "<p>" . __("The colours meaning:") .
		"<ul style='float: left;'>" .
			'<li style="clear: both;">
				<div style="float: left; background: #ffa300; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
				__("Orange cell when the module group and agent have at least one alarm fired.") .
			'</li>' .
			'<li style="clear: both;">
				<div style="float: left; background: #cc0000; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
				__("Red cell when the module group and agent have at least one module in critical status and the others in any status") .
			'</li>' .
			'<li style="clear: both;">
				<div style="float: left; background: #fce94f; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
				__("Yellow cell when the module group and agent have at least one in warning status and the others in grey or green status") .
			'</li>' .
			'<li style="clear: both;">
				<div style="float: left; background: #8ae234; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
				__("Green cell when the module group and agent have all modules in OK status") .
			'</li>' .
			'<li style="clear: both;">
				<div style="float: left; background: #babdb6; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
				__("Grey cell when the module group and agent have at least one in unknown status and the others in green status") .
			'</li>' .
		"</ul>" .
	"</p>";
	
	require_css_file('cluetip');
	require_jquery_file('cluetip');
	?>
	<script>
		$(document).ready (function () {
			$("a.info_cell").cluetip ({
				arrows: true,
				attribute: 'rel',
				cluetipClass: 'default'
			});
		});
	</script>
	<?php
}
 
add_operation_menu_option(__("Modules groups"), 'estado', 'module_groups/icon_menu.png');
add_extension_main_function('mainModuleGroups');
?>
