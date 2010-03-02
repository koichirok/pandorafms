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

// Load globar vars
global $config;

if (!isset ($id_agente)) {
	//This page is included, $id_agente should be passed to it.
	audit_db ($config['id_user'], $config['remote_addr'], "HACK Attempt",
			  "Trying to get to monitor list without id_agent passed");
	include ("general/noaccess.php");
	exit;
}

// Get all module from agent
$sql = sprintf ("
	SELECT *
	FROM tagente_estado, tagente_modulo
		LEFT JOIN tmodule_group
		ON tmodule_group.id_mg = tagente_modulo.id_module_group
	WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND tagente_modulo.id_agente = %d 
		AND tagente_modulo.disabled = 0
		AND tagente_modulo.delete_pending = 0
		AND tagente_estado.utimestamp != 0 
	ORDER BY tagente_modulo.id_module_group , tagente_modulo.nombre
	", $id_agente);

$modules = get_db_all_rows_sql ($sql);
if (empty ($modules)) {
	$modules = array ();
}
$table->width = 750;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();

$table->head[0] = '';
$table->head[1] = __('Type');
$table->head[2] = __('Module name');
$table->head[3] = __('Description');
$table->head[4] = __('Status');
$table->head[5] = __('Data');
$table->head[6] = __('Graph');
$table->head[7] = __('Last contact');

$table->align = array("left","left","left","left","center");

$last_modulegroup = 0;
$rowIndex = 0;
foreach ($modules as $module) {
	
	//The code add the row of 1 cell with title of group for to be more organice the list.
	
	if ($module["id_module_group"] != $last_modulegroup)
	{
		$table->colspan[$rowIndex][0] = count($table->head);
		$table->rowclass[$rowIndex] = 'datos4';
		
		array_push ($table->data, array ('<b>'.$module['name'].'</b>'));
		
		$rowIndex++;
		$last_modulegroup = $module["id_module_group"];
	}
	//End of title of group
	
	$data = array ();
	if (($module["id_modulo"] != 1) && ($module["id_tipo_modulo"] != 100)) {
		if ($module["flag"] == 0) {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&id_agente_modulo='.$module["id_agente_modulo"].'&flag=1&refr=60"><img src="images/target.png" border="0" /></a>';
		} else {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&id_agente_modulo='.$module["id_agente_modulo"].'&refr=60"><img src="images/refresh.png" border="0"></a>';
		}
	} else {
		$data[0] = '';
	}

	$data[1] = show_server_type ($module['id_modulo']);

	if (give_acl ($config['id_user'], $id_grupo, "AW")) 
	  $data[1] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&id_agent_module='.$module["id_agente_modulo"].'&edit_module='.$module["id_modulo"].'"><img src="images/config.png"></a>';
	  
	$data[2] = print_string_substr ($module["nombre"], 25, true);
	$data[3] = print_string_substr ($module["descripcion"], 30, true);

	$status = STATUS_MODULE_WARNING;
	$title = "";

	if ($module["estado"] == 2) {
		$status = STATUS_MODULE_WARNING;
		$title = __('WARNING');
	} elseif ($module["estado"] == 1) {
		$status = STATUS_MODULE_CRITICAL;
		$title = __('CRITICAL');
	} else {
		$status = STATUS_MODULE_OK;
		$title = __('NORMAL');
	}
	
	if (is_numeric($module["datos"])) {
		$title .= " : " . format_for_graph($module["datos"]);
	} else {
		$title .= " : " . substr(safe_output($module["datos"]),0,42);
	}

	$data[4] = print_status_image($status, $title, true);

	if (is_numeric($module["datos"])){
		$salida = format_numeric($module["datos"]);
	} else {
		$salida = "<span title='".$module['datos']."' style='white-space: nowrap;'>".substr(safe_output($module["datos"]),0,12)."</span>";
	}

	$data[5] = $salida;
	$graph_type = return_graphtype ($module["id_tipo_modulo"]);

	$data[6] = " ";
	if ($module['history_data'] == 1){
		$nombre_tipo_modulo = get_moduletype_name ($module["id_tipo_modulo"]);
		$handle = "stat".$nombre_tipo_modulo."_".$module["id_agente_modulo"];
		$url = 'include/procesos.php?agente='.$module["id_agente_modulo"];
		$win_handle=dechex(crc32($module["id_agente_modulo"].$module["nombre"]));

		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$module["id_agente_modulo"]."&label=".$module["nombre"]."&refresh=600','day_".$win_handle."')";

		$data[6] = "";
		if ($nombre_tipo_modulo != "log4x")
			$data[6] .= '<a href="javascript:'.$link.'"><img src="images/chart_curve.png" border=0></a>';
		$data[6] .= "&nbsp;<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data_view&period=86400&id=".$module["id_agente_modulo"]."'><img border=0 src='images/binary.png'></a>";
	}
	
	$seconds = get_system_time () - $module["utimestamp"];
	if ($module['id_tipo_modulo'] < 21 && $module["module_interval"] > 0 && $module["utimestamp"] > 0 && $seconds >= ($module["module_interval"] * 2)) {
		$data[7] = '<span class="redb">';
	} else {
		$data[7] = '<span>';
	}
	$data[7] .= print_timestamp ($module["utimestamp"], true);
	$data[7] .= '</span>';
	
	array_push ($table->data, $data);
	$rowIndex++;
}

if (empty ($table->data)) {
	echo '<div class="nf">'.__('This agent doesn\'t have any active monitors').'</div>';
} else {
	echo "<h3>".__('Full list of monitors')."</h3>";
	print_table ($table);
}

unset ($table);
unset ($table_data);
?>
