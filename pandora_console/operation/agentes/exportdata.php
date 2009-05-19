<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Copyright (c) 2007 Leandro Doctors, ldoctors@gusila.org.ar
// For code belongs to average_per_hourday matrix report code

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_agents.php");

check_login();

if (!give_acl ($config['id_user'], 0, "AR")) {
	require ("general/noaccess.php");
	return;
}

require_javascript_file ('calendar');

echo "<h2>".__('Pandora Agents')." &raquo; ".__('Export data')."</h2>";

$group = get_parameter_post ('group', 1);
$agent = get_parameter_post ('agent', 0);
$module = (array) get_parameter_post ('module_arr', array ());
$start_date = get_parameter_post ('start_date', 0);
$end_date = get_parameter_post ('end_date', 0);
$start_time = get_parameter_post ('start_time', 0);
$end_time = get_parameter_post ('end_time', 0);
$export_type = get_parameter_post ('export_type', 'data');
$export_btn = get_parameter_post ('export_btn', 0);

if (!empty ($export_btn) && !empty ($module)) {
	//Convert start time and end time to unix timestamps
	$start = strtotime ($start_date." ".$start_time);
	$end = strtotime ($end_date." ".$end_time);
	$period = $end - $start;
	$data = array ();	
	
	//If time is negative or zero, don't process - it's invalid
	if ($start < 1 || $end < 1) {
		print_error_message (__('Invalid time specified'));
		return;
	}
	
	// Data
	$data = array ();
	switch ($export_type) {
		case "data":
		case "excel":
		case "csv":
			foreach ($module as $selected) {
				$data_single = get_agentmodule_data ($selected, $period, $start);
				
				if (!empty ($data_single)) {
					$data = array_merge ($data, $data_single);
				}
			}
		break;
		case "avg":
			foreach ($module as $selected) {
				$arr = array ();
				$arr["data"] = get_agentmodule_data_average ($selected, $period, $start);
				if ($arr["data"] === false) {
					continue;
				}	
				$arr["module_name"] = get_agentmodule_name ($selected);
				$arr["agent_name"] = get_agentmodule_agent_name ($selected);
				$arr["agent_id"] = get_agentmodule_agent ($selected);
				$arr["utimestamp"] = $end;				
				array_push ($data, $arr);
			}
		break;
		default:
			print_error_message (__('Invalid method supplied'));
			return;
		break;
	}
	
	// Starts, ends and dividers
	switch ($export_type) {
		case "data":
		case "avg":
		default:
			//HTML output - don't style or use XHTML just in case somebody needs to copy/paste it. (Office doesn't handle <thead> and <tbody>)
			$datastart = '<table style="width:700px;"><tr><td>'.__('Agent').'</td><td>'.__('Module').'</td><td>'.__('Data').'</td><td>'.__('Timestamp').'</td></tr>';
			$rowstart = '<tr><td>';
			$divider = '</td><td>';
			$rowend = '</td></tr>';
			$dataend = '</table>';
		break;
		case "excel":
			//Excel is tab-delimited, needs quotes and needs Windows-style newlines
			$datastart = __('Agent')."\t".__('Module')."\t".__('Data')."\t".__('Timestamp')."\r\n";
			$rowstart = '"';
			$divider = '"'."\t".'"';
			$rowend = '"'."\r\n";
			$dataend = "\r\n";
			$extension = "xls";
		break;
		case "csv":
			//Pure CSV is comma delimited
			$datastart = __('Agent').','.__('Module').','.__('Data').','.__('Timestamp')."\n";
			$rowstart = '"';
			$divider = '","';
			$rowend = '"'."\n";
			$dataend = "\n";
			$extension = "csv";
		break;
	}

	$output = $datastart;
	foreach ($data as $key => $module) {
		$output .= $rowstart;
		$output .= $module['agent_name'];
		$output .= $divider;
		$output .= $module['module_name'];
		$output .= $divider;
		$output .= $module['data'];
		$output .= $divider;
		$output .= date ($config["date_format"], $module['utimestamp']);
		$output .= $rowend;
	}
	$output .= $dataend;
	
	switch ($export_type) {
		default:
		case "data":
		case "avg":
			echo $output;
			return;
		break;
		case "excel":
		case "csv":
			//Encase into a file and offer download
			//Flush buffers - we don't need them.
			$config['ignore_callback'] = true;
			while (@ob_end_clean ());
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=export_".date("Ymd", $start)."_".date("Ymd", $end).".".$extension);
			header("Pragma: no-cache");
			header("Expires: 0");
			echo $output;
			exit;
			//Exit necessary so it doesn't continue processing and give erroneous downloads
		break;
	}
} elseif (!empty ($export_btn) && empty ($module)) {
	print_error_message (__('No modules specified'));
}

echo '<form method="post" action="index.php?sec=estado&amp;sec2=operation/agentes/exportdata" name="export_form">';

$table->width = 550;
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";

$table->data = array ();

//Group selector
$table->data[0][0] = '<b>'.__('Group').'</b>';
	
$groups = get_user_groups ($config['id_user'], "AR");
	
$table->data[0][1] = print_select ($groups, "group", $group, 'this.form.submit();', '', 0, true, false, true, 'w130', false);
	
//Agent selector
$table->data[1][0] = '<b>'.__('Source agent').'</b>';

if ($group > 0) {
	$filter['id_grupo'] = (array) $group;
} else {
	$filter['id_grupo'] = array_keys ($groups);
}

$agents = array ();
$rows = get_agents ($filter, false, 'AR');
foreach ($rows as $row) {
	$agents[$row['id_agente']] = $row['nombre'];
}

if (!in_array ($agent, array_keys ($agents))) {
	$agent = current (array_keys ($agents));
}

$table->data[1][1] = print_select ($agents, "agent", $agent, 'this.form.submit();', '', 0, true, false, true, 'w130', false);

//Module selector
$table->data[2][0] = '<b>'.__('Modules').'</b>';

if ($agent > 0) {
	$modules = get_agent_modules ($agent);
} else {
	$modules = array ();
}

$table->data[2][1] = print_select ($modules, "module_arr[]", array_keys ($modules), '', '', 0, true, true, true, 'w130', false);

//Start date selector
$table->data[3][0] = '<b>'.__('Begin date (*)').'</b>';

$table->data[3][1] = print_input_text ('start_date', date ("Y-m-d", get_system_time () - 86400), false, 10, 10, true);
$table->data[3][1] .= print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => 'scwShow(scwID("text-start_date"),this);'));
$table->data[3][1] .= print_input_text ('start_time', date ("H:m", get_system_time () - 86400), false, 10, 5, true);
	
//End date selector
$table->data[4][0] = '<b>'.__('End date (*)').'</b>';
$table->data[4][1] = print_input_text ('end_date', date ("Y-m-d", get_system_time ()), false, 10, 10, true);
$table->data[4][1] .= print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => 'scwShow(scwID("text-end_date"),this);'));
$table->data[4][1] .= print_input_text ('end_time', date ("H:m", get_system_time ()), false, 10, 5, true);
	
//Export type
$table->data[5][0] = '<b>'.__('Export type').'</b>';

$export_types = array ();
$export_types["data"] = __('Data table');
$export_types["csv"] = __('CSV');
$export_types["excel"] = __('MS Excel');
$export_types["avg"] = __('Average per hour/day');

$table->data[5][1] = print_select ($export_types, "export_type", $export_type, '', '', 0, true, false, true, 'w130', false);

print_table ($table);

// Submit button
echo '<div class="action-buttons" style="width:550px;">';
	print_submit_button (__('Export'), 'export_btn', false, 'class="sub wand"');
echo '</div></form>';

?>