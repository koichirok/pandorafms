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
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

$id_agent = 0;
$id_module = 0;
$name = "Pandora FMS combined graph";
$width = 550;
$height = 210;
$period = 86401;
//$alerts= "";
$events = "";
$factor = 1;
$render=1; // by default
$stacked = 0;

$add_module = (bool) get_parameter ('add_module');
$editGraph = (bool) get_parameter('edit_graph');

if (isset ($_GET["store_graph"])) {
	$name = get_parameter_post ("name");
	$description = get_parameter_post ("description");
	$module_number = get_parameter_post ("module_number");
	//$private = get_parameter_post ("private");
	$idGroup = get_parameter_post ('graph_id_group');
	$width = get_parameter_post ("width");
	$height = get_parameter_post ("height");
	$events = get_parameter_post ("events");
	$stacked = get_parameter ("stacked", 0);
	if ($events == "") // Temporal workaround
		$events = 0;
	$period = get_parameter_post ("period");
	// Create graph
	$sql = "INSERT INTO tgraph
		(id_user, name, description, period, width, height, private, id_group, events, stacked) VALUES
		('".$config['id_user']."',
		'$name',
		'$description',
		$period,
		$width,
		$height,
		0,
		$idGroup,
		$events,
		$stacked)";
		//echo "DEBUG $sql<br>";
	$res = mysql_query($sql);
	if ($res){
		$id_graph = mysql_insert_id();
		if ($id_graph){
			for ($a=0; $a < $module_number; $a++){
				$id_agentemodulo = get_parameter_post ("module_".$a);
				$id_agentemodulo_w = get_parameter_post ("module_weight_".$a);
				$sql = "INSERT INTO tgraph_source (id_graph, id_agent_module, weight) VALUES
					($id_graph, $id_agentemodulo, $id_agentemodulo_w)";
				//echo "DEBUG $sql<br>";
				mysql_query($sql);
			}
			echo "<h3 class='suc'>".__('Graph stored successfully')."</h3>";
		} else
			echo "<h3 class='error'>".__('There was a problem storing Graph')."</h3>";
	} else 
		echo "<h3 class='error'>".__('There was a problem storing Graph')."</h3>";
}

if (isset($_GET['change_graph'])) {
	$id = get_parameter('id');
	$name = get_parameter('name');
    $id_group = get_parameter('graph_id_group');
    $description = get_parameter('description');
    
    $success = process_sql_update('tgraph', 
    	array('name' => $name, 'id_group' => $id_group, 'description' => $description), 
    	array('id_graph' => $id));
    
    print_result_message($success, __("Update the graph"), __("Bad update the graph"));
}

if (isset ($_GET["get_agent"])) {
	$id_agent = $_POST["id_agent"];
	if (isset($_POST["chunk"]))
		$chunkdata = $_POST["chunk"];
}

if (isset ($_GET["delete_module"] )) {
	if ($editGraph) {
		$deleteGraphs = get_parameter('delete');
		foreach ($deleteGraphs as $deleteGraph) {
			process_sql_delete('tgraph_source', array('id_gs' => $deleteGraph));
		}
	}
	else
	{
		$chunkdata = $_POST["chunk"];
		if (isset($chunkdata)) {
			$chunk1 = array();
			$chunk1 = split ("\|", $chunkdata);
			$modules="";$weights="";
			for ($a = 0; $a < count ($chunk1); $a++) {
				if (isset ($_POST["delete_$a"])) {
					$id_module = $_POST["delete_$a"];
					$deleted_id[]=$id_module;
				}	
			}
			$chunkdata2 = "";
			$module_array = array ();
			$weight_array = array ();
			$agent_array = array ();
			for ($a = 0; $a < count ($chunk1); $a++) {
				$chunk2[$a] = array();
				$chunk2[$a] = split (",", $chunk1[$a]);
				$skip_module =0;
				for ($b = 0; $b < count ($deleted_id); $b++) {
					if ($deleted_id[$b] == $chunk2[$a][1]) {
						$skip_module = 1;
					}
				}
				if (($skip_module == 0) && (strpos ($modules, $chunk2[$a][1]) == 0)) {  // Skip
					$module_array[] = $chunk2[$a][1];
					$agent_array[] = $chunk2[$a][0];
					$weight_array[] = $chunk2[$a][2];
					if ($chunkdata2 == "")
						$chunkdata2 .= $chunk2[$a][0].",".$chunk2[$a][1].",".$chunk2[$a][2];
					else
						$chunkdata2 .= "|".$chunk2[$a][0].",".$chunk2[$a][1].",".$chunk2[$a][2];
					if ($modules !="")
						$modules = $modules.",".$chunk2[$a][1];
					else
						$modules = $chunk2[$a][1];
					if ($weights !="")
						$weights = $weights.",".$chunk2[$a][2];
					else
						$weights = $chunk2[$a][2];
				}
			}
			$chunkdata = $chunkdata2;
		}
	}
}

if ($add_module) {
	if ($editGraph) {
		$id = get_parameter('id');
		$id_module = get_parameter('id_module');
		if ($id_module != -1) {
			$factor = get_parameter('factor');
			process_sql_insert('tgraph_source', array('id_graph' => $id, 'id_agent_module' => $id_module, 'weight' => $factor));
			
			$period = get_parameter('period');
			$width = get_parameter('width');
			$height = get_parameter('height');
			$events = get_parameter('events');		
			$stacked = get_parameter('stacked');
			
			process_sql_update('tgraph', array('period' => $period, 
				'width' => $width, 'height' => $height, 'events' => $events,
				'stacked' => $stacked), array('id_graph' => $id));
		}
	}
	else {
		$id_agent = $_POST["id_agent"];
		$id_module = $_POST["id_module"];
		if (isset($_POST["factor"]))
			$factor = $_POST["factor"];
		else
			$factor = 1;
		$period = $_POST["period"];
		$render = $_POST["render"];
		$stacked = get_parameter ("stacked",0);
	// 	$alerts = $_POST["alerts"];
		if (isset($_POST["chunk"]))
			$chunkdata = $_POST["chunk"];
		$events = $_POST["events"];
		$factor = $_POST["factor"];
		if ($_POST["width"]!= "")
			$width = $_POST["width"];
		if ($_POST["height"]!= "")
			$height = $_POST["height"];
		if ($id_module > 0){	
			if (!isset($chunkdata) OR ($chunkdata == ""))
				$chunkdata = "$id_agent,$id_module,$factor";
			else
				$chunkdata = $chunkdata."|$id_agent,$id_module,$factor";
		}
	}
}

// Parse CHUNK information into showable information
// Split id to get all parameters
if (! isset($_GET["delete_module"])) {
	if (isset($_POST["period"]))
		$period = $_POST["period"];
	if ((isset($chunkdata) )&& ($chunkdata != "")) {
		$module_array = array();
		$weight_array = array();
		$agent_array = array();
		$chunk1 = array();
		$chunk1 = split ("\|", $chunkdata);
		$modules="";$weights="";
		for ($a=0; $a < count($chunk1); $a++){
			$chunk2[$a] = array();
			$chunk2[$a] = split ( ",", $chunk1[$a]);
			if (strpos($modules, $chunk2[$a][1]) == 0){  // Skip dupes
				$module_array[] = $chunk2[$a][1];
				$agent_array[] = $chunk2[$a][0];
				$weight_array[] = $chunk2[$a][2];
				if ($modules !="")
					$modules = $modules.",".$chunk2[$a][1];
				else
					$modules = $chunk2[$a][1];
				if ($weights !="")
					$weights = $weights.",".$chunk2[$a][2];
				else
					$weights = $chunk2[$a][2];
			}
		}
	}
}

if ($editGraph) {
	$id = (integer) get_parameter('id');
	$graphRows = get_db_all_rows_sql("SELECT t1.*,
		(SELECT t3.nombre 
			FROM tagente AS t3 
			WHERE t3.id_agente = 
				(SELECT t2.id_agente 
					FROM tagente_modulo AS t2
					WHERE t2.id_agente_modulo = t1.id_agent_module)) 
		AS agent_name
		FROM tgraph_source AS t1
		WHERE t1.id_graph = " . $id);
	$chunk1 = true;
	$module_array = array();
	$weight_array = array();
	$agent_array = array();
	$chunk1 = array();
	$tempChunkdata = array();
	$chunkdata = "";
	
	foreach ($graphRows as $graphRow) {
		$idsTable[] = $graphRow['id_gs'];
		$module_array[] = $graphRow['id_agent_module'];
		$weight_array[] = $graphRow['weight'];
		$agent_array[] = $graphRow['agent_name'];
		
		$tempChunkdata[] = $graphRow['agent_name'] . "," . 
			$graphRow['id_agent_module'] . "," .
			$graphRow['weight'];
	}
	
	$graphInTgraph = get_db_row_sql("SELECT * FROM tgraph WHERE id_graph = " . $id);
	$stacked = $graphInTgraph['stacked'];
	$events = $graphInTgraph['events'];
	
	$modules = implode(',', $module_array);
	$weights = implode(',', $weight_array);
	$chunkdata = implode('|', $tempChunkdata);
}

if (isset ($chunk1)) {
	// Header
	print_page_header (__('Graph builder module list'), "", false, "", true);
	if ($editGraph) {
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&edit_graph=1&delete_module=1&id=" . $id . "'>";
	}
	else {
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&delete_module=1'>";
	}
	if (isset($chunkdata))
		echo "<input type='hidden' name='chunk' value='$chunkdata'>";
	if ($id_agent)
		echo "<input type='hidden' name='id_agent' value='$id_agent'>";
	if ($period)
		echo "<input type='hidden' name='period' value='$period'>";

	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox'>";
	echo "<tr>
	<th>".__('Agent')."</th>
	<th>".__('Module')."</th>
	<th>".__('Weight')."</th>
	<th>".__('Delete')."</th>";
	$color = 0;
	for ($a = 0; $a < count($module_array); $a++){
		// Calculate table line color
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}

		echo "<tr><td class='$tdcolor'>" . $agent_array[$a] . "</td>";
		echo "<td class='$tdcolor'>";
		echo get_agentmodule_name ($module_array[$a])."</td>";
		echo "<td class='$tdcolor'>";
		echo $weight_array[$a]."</td>";
		echo "<td class='$tdcolor' align='center'>";
		if ($editGraph) {
			echo "<input type=checkbox name='delete[]" . $idsTable[$a] . "' value='".$idsTable[$a]."'></td></tr>";
		} else
		{
			echo "<input type=checkbox name='delete_$a' value='".$module_array[$a]."'></td></tr>";
		}
	}
	echo "</table>";
	echo "<table width='500px'>";
	echo "<tr><td align='right'><input type=submit name='update_agent' class='sub delete' value='".__('Delete')."'>";
	echo "</table>";
	echo "</form>";
}

// --------------------------------------
// Parse chunkdata and render graph
// --------------------------------------
if (($render == 1) && (isset($modules))) {
	// parse chunk
	echo "<h3>".__('Combined image render')."</h3>";
	echo "<table class='databox'>";
	echo "<tr><td>";
	if ($config['flash_charts']) {
		echo graphic_combined_module (split (',', $modules), split (',', $weights), $period, $width, $height,
				'Combined%20Sample%20Graph', '', $events, 0, 0, $stacked);
	} else {
		echo "<img src='include/fgraph.php?tipo=combined&id=$modules&weight_l=$weights&label=Combined%20Sample%20Graph&height=$height&width=$width&stacked=$stacked&period=$period' border=1 alt=''>";
	}
	echo "</td></tr></table>";

}

// -----------------------
// SOURCE AGENT TABLE/FORM
// -----------------------

// Header
print_page_header (__('Graph builder'), "", false, "", true);
echo "<table width='500' cellpadding='4' cellpadding='4' class='databox_color'>";
if ($editGraph) {
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&edit_graph=1&add_module=1&id=" . $id . "'>";
}
else {
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder'>";
	print_input_hidden ('add_module', 1);
}
if (isset($period))
    echo "<input type='hidden' name='period' value='$period'>";

echo "<tr>";
echo "<td class='datos'><b>".__('Source agent')."</td>";
echo "</b>";

// Show combo with agents
echo "<td class='datos' colspan=2>";

$user_groups = implode (',', array_keys (get_user_groups ($config["id_user"])));

print_input_text_extended ('id_agent', get_agent_name ($id_parent), 'text-id_agent', '', 30, 100, false, '',
	array('style' => 'background: url(images/lightning.png) no-repeat right;'));
echo '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';
//print_select_from_sql ("SELECT id_agente, nombre FROM tagente WHERE disabled = 0 AND id_grupo IN ($user_groups) ORDER BY nombre", 'id_agent', $id_agent, '', '--', 0);

// SOURCE MODULE FORM
if (isset ($chunkdata))
	echo "<input type='hidden' name='chunk' value='$chunkdata'>";

echo "<tr><td class='datos2'>";
echo "<b>".__('Modules')."</b>";
echo "<td class='datos2' colspan=3>";
echo "<select id='id_module' name='id_module' size=1 style='width:180px;'>";
		echo "<option value=-1> -- </option>";
if ($id_agent != 0){
	// Populate Module/Agent combo
	$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$id_agent. " ORDER BY nombre";
	$result = mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"]."</option>";
	}
}
echo "</select>";

echo "<tr><td class='datos'>";
echo "<b>".__('Factor')."</b></td>";
echo "<td class='datos'>";
echo "<input type='text' name='factor' value='$factor' size=6></td>";
echo "<td class='datos'>";
echo "<b>".__('Width')."</b>";
echo "<td class='datos'>";
echo "<input type='text' name='width' value='$width' size=6></td>";


echo "<tr><td class='datos2'>";
echo "<b>".__('Render now')."</b></td>";
echo "<td class='datos2'>";
echo "<select name='render'>";
if ($render == 1){
	echo "<option value=1>".__('Yes')."</option>";
	echo "<option value=0>".__('No')."</option>";
} else {
	echo "<option value=0>".__('No')."</option>";
	echo "<option value=1>".__('Yes')."</option>";
}
echo "</select></td>";
echo "<td class='datos2'>";
echo "<b>".__('Height')."</b></td>";
echo "<td class='datos2'>";
echo "<input type='text' name='height' value='$height' size=6>";


switch ($period) {
	case 3600:
		$period_label = "1 ".__('hour');
		break;
	case 7200:
		$period_label = "2 ".__('hours');
		break;
	case 10800:
		$period_label = "3 ".__('hours');
		break;
	case 21600:
		$period_label = "6 ".__('hours');
		break;
	case 43200:
		$period_label = "12 ".__('hours');
		break;
	case 86400:
		$period_label = "1 ".__('day');
		break;
	case 172800:
		$period_label = "2 ".__('days');
		break;
	case 345600:
		$period_label = "4 ".__('days');
		break;
	case 604800:
		$period_label = __('Last week');
		break;
	case 1296000:
		$period_label = "15 ".__('days');
		break;
	case 2592000:
		$period_label = __('Last month');
		break;
	case 5184000:
		$period_label = "2 ".__('months');
		break;
	case 15552000:
		$period_label = "6 ".__('months');
		break;
	case 31104000:
		$period_label = __('1 year');
		break;
	default:
		$period_label = "1 ".__('day');
}


echo "<tr><td class='datos'>";
echo "<b>".__('Period')."</b></td>";
echo "<td class='datos'>";

echo "<select name='period'>";
if ($period==0) {
	echo "<option value=86400>".$period_label."</option>";
} else {
	echo "<option value=$period>".$period_label."</option>";
}
echo "<option value=3600>"."1 ".__('hour')."</option>";
echo "<option value=7200>"."2 ".__('hours')."</option>";
echo "<option value=10800>"."3 ".__('hours')."</option>";
echo "<option value=21600>"."6 ".__('hours')."</option>";
echo "<option value=43200>"."12 ".__('hours')."</option>";
echo "<option value=86400>".__('Last day')."</option>";
echo "<option value=172800>"."2 ".__('days')."</option>";
echo "<option value=345600>"."4 ".__('days')."</option>";
echo "<option value=604800>".__('Last week')."</option>";
echo "<option value=1296000>"."15 ".__('15 days')."</option>";
echo "<option value=2592000>".__('Last month')."</option>";
echo "<option value=5184000>"."2 ".__('months')."</option>";
echo "<option value=15552000>"."6 ".__('months')."</option>";
echo "</select>";

echo "<td class='datos'>";
echo "<b>".__('View events')."</b></td>";
echo "<td class='datos'>";
echo "<select name='events'>";
if ($events == 1){
	echo "<option value=1>".__('Yes')."</option>";
	echo "<option value=0>".__('No')."</option>";
} else {
	echo "<option value=0>".__('No')."</option>";
	echo "<option value=1>".__('Yes')."</option>";
}
echo "</select></td>";

echo "<tr>";
echo "<td class='datos2'>";
echo "<b>".__('Stacked')."</b></td>";
echo "<td class='datos2'>";


$stackeds[0] = __('Area');
$stackeds[1] = __('Stacked area');
$stackeds[2] = __('Line');
$stackeds[3] = __('Stacked line');
print_select ($stackeds, 'stacked', $stacked, '', '', 0);
echo "</td>";


/*
echo "<td class='datos'>";
echo "<b>Show alert limit</b>";
echo "<td class='datos'>";
echo "<select name='alerts'>";
if ($alerts == 1){
	echo "<option value=1>Yes";
	echo "<option value=0>No";
} else {
	echo "<option value=0>No";
	echo "<option value=1>Yes";
}
echo "</select>";
*/


echo "</tr></table>";
echo "<table width='500px'>";
echo "<tr><td align='right'><input type=submit name='update_agent' class='sub upd' value='".__('Add')."/".__('Redraw')."'>";

echo "</form>";
echo "</td></tr></table>";

// -----------------------
// STORE GRAPH FORM
// -----------------------

// If we have something to save..
if (isset($module_array)){
	echo "<h3>".__('Custom graph store')."</h3>";
	echo "<table width='500' cellpadding=4 cellpadding=4 class='databox_color'>";
	
	if ($editGraph) {
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&edit_graph=1&change_graph=1&id=" . $id . "'>";
	}
	else
	{
		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_builder&store_graph=1'>";
	
		// hidden fields with data begin
		echo "<input type='hidden' name='module_number' value='".count($module_array)."'>";
		echo "<input type='hidden' name='width' value='$width'>";
		echo "<input type='hidden' name='height' value='$height'>";
		echo "<input type='hidden' name='period' value='$period'>";
		echo "<input type='hidden' name='events' value='$events'>";
		echo "<input type='hidden' name='stacked' value='$stacked'>";
	
		for ($a=0; $a < count($module_array); $a++){
				$id_agentemodulo = $module_array[$a];
				$id_agentemodulo_w = $weight_array[$a];
				echo "<input type='hidden' name='module_$a' value='$id_agentemodulo'>";
				echo "<input type='hidden' name='module_weight_$a' value='$id_agentemodulo_w'>";
		}
	}
	// hidden fields end
	echo "<tr>";
	echo "<td class='datos'><b>".__('Name')."</b></td>";
	echo "</b>";
	echo "<td class='datos'><input type='text' name='name' size='35' ";
	if ($editGraph) {
		echo "value='" . $graphInTgraph['name'] . "' ";
	}
	echo ">";
	
	$group_select = get_user_groups ($config['id_user']);
	echo "<td><b>".__('Group')."</b></td><td>" .
		print_select ($group_select, 'graph_id_group', $graphInTgraph['id_group'], '', '', '', true) .
		"</td></tr>";
	echo "<tr>";
	echo "<td class='datos2'><b>".__('Description')."</b></td>";
	echo "</b>";
	echo "<td class='datos2' colspan=4><textarea name='description' style='height:45px;' cols=55 rows=2>";
	if ($editGraph) {
		echo $graphInTgraph['description'];
	}
	echo "</textarea>";
	echo "</td></tr></table>";
	echo "<table width='500px'>";
	if ($editGraph) {
		echo "<tr><td align='right'><input type=submit name='store' class='sub wand' value='".__('Edit')."'>";
	}
	else {
		echo "<tr><td align='right'><input type=submit name='store' class='sub wand' value='".__('Store')."'>";
	}


	echo "</form>";
	echo "</table>";
}

require_jquery_file ('pandora.controls');
require_jquery_file ('ajaxqueue');
require_jquery_file ('bgiframe');
require_jquery_file ('autocomplete');

?>
<script language="javascript" type="text/javascript">

function agent_changed () {
	var id_agent = this.value;
	$('#id_module').fadeOut ('normal', function () {
		$('#id_module').empty ();
		var inputs = [];
		inputs.push ("id_agent=" + id_agent);
		inputs.push ('filter=delete_pending = 0');
		inputs.push ("get_agent_modules_json=1");
		inputs.push ("page=operation/agentes/ver_agente");
		jQuery.ajax ({
			data: inputs.join ("&"),
			type: 'GET',
			url: action="ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				$('#id_module').append ($('<option></option>').attr ('value', 0).text ("--"));
				jQuery.each (data, function (i, val) {
					s = js_html_entity_decode (val['nombre']);
					$('#id_module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
				});
				$('#id_module').fadeIn ('normal');
			}
		});
	});
}

$(document).ready (function () {
	//$('#id_agent').change (agent_changed);
	
	
		$("#text-id_agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "godmode/reporting/graph_builder",
					search_agents: 1,
					id_group: function() { return $("#group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text-id_agent").css ('background-color', '#cc0000');
					else
						$("#text-id_agent").css ('background-color', '');
					if (data == "")
						return false;
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
				},
				delay: 200
			}
		);
		
		$("#text-id_agent").result (
			function () {
	
				
				var agent_name = this.value;
				$('#id_module').fadeOut ('normal', function () {
					$('#id_module').empty ();
					var inputs = [];
					inputs.push ("agent_name=" + agent_name);
					inputs.push ('filter=delete_pending = 0');
					inputs.push ("get_agent_modules_json=1");
					inputs.push ("page=operation/agentes/ver_agente");
					jQuery.ajax ({
						data: inputs.join ("&"),
						type: 'GET',
						url: action="ajax.php",
						timeout: 10000,
						dataType: 'json',
						success: function (data) {
							$('#id_module').append ($('<option></option>').attr ('value', 0).text ("--"));
							jQuery.each (data, function (i, val) {
								s = js_html_entity_decode (val['nombre']);
								$('#id_module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
							});
							$('#id_module').fadeIn ('normal');
						}
					});
				});
		
				
			}
		);
		
}); 
</script>
