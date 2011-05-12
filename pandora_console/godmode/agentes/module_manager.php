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

/* You can redefine $url and unset $id_agente to reuse the form. Dirty (hope temporal) hack */
if (isset ($id_agente)) {
	$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente;
	echo "<h2>".__('Modules')."</h2>";
}

enterprise_include ('godmode/agentes/module_manager.php');
$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');
require_once ('include/functions_modules.php');
require_once ('include/functions_agents.php');
require_once ('include/functions_servers.php');

// Create module/type combo
echo '<table width="300" cellpadding="4" cellspacing="4" class="databox">';
echo '<form id="create_module_type" method="post" action="'.$url.'">';
echo "<tr><td class='datos'>";

// Check if there is at least one server of each type available to assign that
// kind of modules. If not, do not show server type in combo

$network_available = db_get_sql ("SELECT count(*) from tserver where server_type = 1"); //POSTGRESQL AND ORACLE COMPATIBLE
$wmi_available = db_get_sql ("SELECT count(*) from tserver where server_type = 6"); //POSTGRESQL AND ORACLE COMPATIBLE
$plugin_available = db_get_sql ("SELECT count(*) from tserver where server_type = 4"); //POSTGRESQL AND ORACLE COMPATIBLE
$prediction_available = db_get_sql ("SELECT count(*) from tserver where server_type = 5"); //POSTGRESQL AND ORACLE COMPATIBLE

// Development mode to use all servers
if ($develop_bypass) {
	$network_available = 1;
	$wmi_available = 1;
	$plugin_available = 1;
	$prediction_available = 1;
}

$modules = array ();
$modules['dataserver'] = __('Create a new data server module');
if ($network_available)
	$modules['networkserver'] = __('Create a new network server module');
if ($plugin_available)
	$modules['pluginserver'] = __('Create a new plugin server module');
if ($wmi_available)
	$modules['wmiserver'] = __('Create a new WMI server module');
if ($prediction_available)
	$modules['predictionserver'] = __('Create a new prediction server module');

enterprise_hook ('set_enterprise_module_types', array (&$modules));

$sec2 = get_parameter('sec2', '');
if (strstr($sec2, "enterprise/godmode/policies/policies") !== false) {
	unset($modules['predictionserver']);
}

html_print_select ($modules, 'moduletype', '', '', '', '', false, false, false);
html_print_input_hidden ('edit_module', 1);
echo '</td>';
echo '<td class="datos">';
echo '<input align="right" name="updbutton" type="submit" class="sub next" value="'.__('Create').'">';
echo '</td>';
echo '</tr>';
echo "</form>";
echo "</table>";

if (! isset ($id_agente))
	return;
	

$multiple_delete = (bool) get_parameter('multiple_delete');

if ($multiple_delete) {
	$id_agent_modules_delete = (array)get_parameter('id_delete');
	
	foreach($id_agent_modules_delete as $id_agent_module_del) {
		$id_grupo = (int) agents_get_agent_group($id_agente);
		
		if (! check_acl ($config["id_user"], $id_grupo, "AW")) {
			db_pandora_audit("ACL Violation",
			"Trying to delete a module without admin rights");
			require ("general/noaccess.php");
			exit;
		}
		
		if ($id_agent_module_del < 1) {
			db_pandora_audit("HACK Attempt",
			"Expected variable from form is not correct");
			die ("Nice try buddy");
			exit;
		}
		
		enterprise_include_once('include/functions_config_agents.php');
		enterprise_hook('config_agents_delete_module_in_conf', array(modules_get_agentmodule_agent($id_agent_module_del), modules_get_agentmodule_name($id_agent_module_del)));
		
		//Init transaction
		$error = 0;
		db_process_sql_begin ();
		
		// First delete from tagente_modulo -> if not successful, increment
		// error. NOTICE that we don't delete all data here, just marking for deletion
		// and delete some simple data.
		$status = '';
		if (db_process_sql("UPDATE tagente_modulo
			SET nombre = 'pendingdelete', disabled = 1, delete_pending = 1 WHERE id_agente_modulo = ".$id_agent_module_del, "affected_rows", '', true, $status, false) === false)
			$error++;
	
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":		
				$result = db_process_sql_delete('tagente_estado', array('id_agente_modulo' => $id_agent_module_del)); 
				if ($result === false)
					$error++;
		
				$result = db_process_sql_delete('tagente_datos_inc', array('id_agente_modulo' => $id_agent_module_del));
				if ($result === false)
					$error++;
				break;
			case "oracle":
				$result = db_process_delete_temp('tagente_estado', 'id_agente_modulo', $id_agent_module_del);
				if ($result === false)
					$error++;
				$result = db_process_delete_temp('tagente_datos_inc', 'id_agente_modulo', $id_agent_module_del);		
				if ($result === false)
					$error++;
				break;
				
		}	
		//Check for errors
		if ($error != 0) {
			db_process_sql_rollback ();
			ui_print_error_message (__('There was a problem deleting the module'));
		}
		else {
			db_process_sql_commit ();
			ui_print_success_message (__('Module deleted succesfully'));
		}
	}
}


// ==========================
// MODULE VISUALIZATION TABLE
// ==========================

echo "<h3>".__('Assigned modules')."</h3>";

$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente=' . $id_agente;
$selectNameUp = '';
$selectNameDown = '';
$selectServerUp = '';
$selectServerDown = '';
$selectTypeUp = '';
$selectTypeDown = '';
$selectIntervalUp = '';
$selectIntervalDown = '';
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = 'border: 1px solid black;';

$order[] = array('field' => 'id_module_group', 'order' => 'ASC');

switch ($sortField) {
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				switch ($config["dbtype"]) {
					case "mysql":
					case "postgresql":				
						$order[] = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
						break;
					case "oracle":
						$order[] = array('field' => 'dbms_lob.substr(tagente_modulo.nombre,4000,1)', 'order' => 'ASC');
						break;
				}
				break;
			case 'down':
				$selectNameDown = $selected;
				switch ($config["dbtype"]) {
					case "mysql":
					case "postgresql":
						$order[] = array('field' => 'tagente_modulo.nombre', 'order' => 'DESC');
						break;
					case "oracle":	
						$order[] = array('field' => 'dbms_lob.substr(tagente_modulo.nombre,4000,1)', 'order' => 'DESC');
						break;
				}		
				break;
		}
		break;
	case 'server':
		switch ($sort) {
			case 'up':
				$selectServerUp = $selected;
				$order[] = array('field' => 'id_modulo', 'order' => 'ASC');
				break;
			case 'down':
				$selectServerDown = $selected;
				$order[] = array('field' => 'id_modulo', 'order' => 'DESC');
				break;
		}
		break;
	case 'type':
		switch ($sort) {
			case 'up':
				$selectTypeUp = $selected;
				$order[] = array('field' => 'id_tipo_modulo', 'order' => 'ASC');
				break;
			case 'down':
				$selectTypeDown = $selected;
				$order[] = array('field' => 'id_tipo_modulo', 'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order[] = array('field' => 'module_interval', 'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order[] = array('field' => 'module_interval', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectNameUp = $selected;
		$selectNameDown = '';
		$selectServerUp = '';
		$selectServerDown = '';
		$selectTypeUp = '';
		$selectTypeDown = '';
		$selectIntervalUp = '';
		$selectIntervalDown = '';
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":
				$order[] = array('field' => 'nombre', 'order' => 'ASC');
				break;
			case "oracle":
				$order[] = array('field' => 'dbms_lob.substr(nombre,4000,1)', 'order' => 'ASC');
				break;
		}
		break;
}

$modules = db_get_all_rows_filter ('tagente_modulo',
	array ('delete_pending' => 0,
		'id_agente' => $id_agente,
		'order' => $order),
	array ('id_agente_modulo', 'id_tipo_modulo', 'descripcion', 'nombre',
		'max', 'min', 'module_interval', 'id_modulo', 'id_module_group',
		'disabled',));

if ($modules === false) {
	echo "<div class='nf'>".__('No available data to show')."</div>";
	return;
}

$table->width = '95%';
$table->head = array ();
$table->head[0] = __('Name') . ' ' .
	'<a href="' . $url . '&sort_field=name&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown)) . '</a>';
if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK)
	$table->head[1] = "<span title='" . __('Policy') . "'>" . __('P.') . "</span>";
$table->head[2] = "<span title='" . __('Server') . "'>" . __('S.') . "</span>" . ' ' .
	'<a href="' . $url . '&sort_field=server&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectServerUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=server&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectServerDown)) . '</a>';
$table->head[3] = __('Type') . ' ' .
	'<a href="' . $url . '&sort_field=type&sort=up">' .  html_print_image("images/sort_up.png", true, array("style" => $selectTypeUp)) .'</a>' .
	'<a href="' . $url . '&sort_field=type&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTypeDown)) . '</a>';
$table->head[4] = __('Interval') . ' ' .
	'<a href="' . $url . '&sort_field=interval&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectIntervalUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=interval&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectIntervalDown)) . '</a>';
$table->head[5] = __('Description');
$table->head[6] = __('Max/Min');
$table->head[7] = __('Action');

$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[2] = '55px';
$table->size[7] = '90px';
$table->align = array ();
$table->align[2] = 'center';
$table->align[7] = 'left';
$table->data = array ();

$agent_interval = agents_get_interval ($id_agente);
$last_modulegroup = "0";

//Extract the ids only numeric modules for after show the normalize link. 
$tempRows = db_get_all_rows_sql("SELECT *
	FROM ttipo_modulo
	WHERE nombre NOT LIKE '%string%' AND nombre NOT LIKE '%proc%'");
$numericModules = array();
foreach($tempRows as $row) {
	$numericModules[$row['id_tipo']] = true;
}

foreach ($modules as $module) {
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		if (!module_in_acl_enterprise($module['id_agente_modulo'])) continue;
	} 
	
	$type = $module["id_tipo_modulo"];
	$id_module = $module["id_modulo"];
	$nombre_modulo = $module["nombre"];
	$descripcion = $module["descripcion"];
	$module_max = $module["max"];
	$module_min = $module["min"];
	$module_interval2 = $module["module_interval"];
	$module_group2 = $module["id_module_group"];
	
	$data = array ();
	if ($module['id_module_group'] != $last_modulegroup) {
		$last_modulegroup = $module['id_module_group'];
		
		$data[0] = '<strong>'.modules_get_modulegroup_name ($last_modulegroup).'</strong>';
		$i = array_push ($table->data, $data);
		$table->rowclass[$i - 1] = 'datos3';
		if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK)
				$table->colspan[$i - 1][0] = 8;
		else
			$table->colspan[$i - 1][0] = 7;
		
		$data = array ();
	}

	$data[0] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&edit_module=1&id_agent_module='.$module['id_agente_modulo'].'">';
	if ($module["disabled"])
		$data[0] .= '<em class="disabled_module">' . ui_print_truncate_text($module['nombre'], 25, false).'</em>';
	else
		$data[0] .= ui_print_truncate_text($module['nombre'], 25, false);
	$data[0] .= '</a>';
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$policyInfo = policies_info_module_policy($module['id_agente_modulo']);
		if ($policyInfo === false)
			$data[1] = '';
		else {
			$linked = policies_is_module_linked($module['id_agente_modulo']);
			
			$adopt = false;
			if (policies_is_module_adopt($module['id_agente_modulo'])) {
				$adopt = true;
			}
			
			if ($linked) {
				if ($adopt) {
					$img = 'images/policies_brick.png';
					$title = __('(Adopt) ') . $policyInfo['name_policy'];
				}
				else {
					$img = 'images/policies.png';
					$title = $policyInfo['name_policy'];
				}
			}
			else {
				if ($adopt) {
					$img = 'images/policies_not_brick.png';
					$title = __('(Adopt) (Unlinked) ') . $policyInfo['name_policy'];
				}
				else {
					$img = 'images/unlinkpolicy.png';
					$title = __('(Unlinked) ') . $policyInfo['name_policy'];
				}
			}
				
			$data[1] = '<a href="?sec=gpolicies&sec2=enterprise/godmode/policies/policies&id=' . $policyInfo['id_policy'] . '">' . 
				html_print_image($img,true, array('title' => $title)) .
				'</a>';
		}
	}
	
	// Module type (by server type )
	$data[2] = '';
	if ($module['id_modulo'] > 0) {
		$data[2] = servers_show_type ($module['id_modulo']);
	}

	// This module is initialized ? (has real data)
	$module_init = db_get_value ('utimestamp', 'tagente_estado', 'id_agente_modulo', $module['id_agente_modulo']);
	if ($module_init == 0)
		$data[2] .= html_print_image ('images/error.png', true, array ('title' => __('Non initialized module')));
	
	// Module type (by data type)
	$data[3] = '';
	if ($type) {
		$data[3] = ui_print_moduletype_icon ($type, true);
	}

	// Module interval
	if ($module['module_interval']) {
		$data[4] = $module['module_interval'];
	} else {
		$data[4] = $agent_interval;
	}
	
	$data[5] = ui_print_truncate_text($module['descripcion'], 25, false);
	
	// MAX / MIN values
	$data[6] = $module["max"] ? $module["max"] : __('N/A');
	$data[6] .= ' / '.($module["min"] != $module['max']? $module["min"] : __('N/A'));

	// Delete module
	$data[7] = html_print_checkbox('id_delete[]', $module['id_agente_modulo'], false, true);
	$data[7] .= '<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&delete_module='.$module['id_agente_modulo'].'"
		onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
	$data[7] .= html_print_image ('images/cross.png', true,
		array ('title' => __('Delete')));
	$data[7] .= '</a> ';
	$data[7] .= '<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&duplicate_module='.$module['id_agente_modulo'].'"
		onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
	$data[7] .= html_print_image ('images/copy.png', true,
		array ('title' => __('Duplicate')));
	$data[7] .= '</a> ';
	
	// Make a data normalization

	if (isset($numericModules[$type])) {
		if ($numericModules[$type] === true) {
			$data[7] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&fix_module='.$module['id_agente_modulo'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
			$data[7] .= html_print_image ('images/chart_curve.png', true,
				array ('title' => __('Normalize')));
			$data[7] .= '</a>';
		}
	}

	array_push ($table->data, $data);
}

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module"
	onsubmit="if (! confirm (\''.__('Are you sure?').'\')) return false">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('multiple_delete', 1);
html_print_submit_button (__('Delete'), 'multiple_delete', false, 'class="sub delete"');
echo '</div>';
echo '</form>'
?>
