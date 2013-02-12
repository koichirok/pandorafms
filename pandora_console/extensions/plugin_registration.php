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

function pluginreg_extension_main () {
	global $config;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	
	ui_print_page_header (__('Plugin registration'), "images/extensions.png", false, "", true, "" );
	
	echo "<div class=notify>";
	printf(__("This extension makes registration of server plugins more easy. Here you can upload a server plugin in Pandora FMS 3.x zipped format (.pspz). Please refer to documentation on how to obtain and use Pandora FMS Server Plugins.<br><br>You can get more plugins in our <a href='%s'>Public Resource Library</a>") , "http://pandorafms.org/index.php?sec=community&sec2=repository&lng=en");
	echo "</div>";
	
	echo "<br><br>";
	
	if (!isset ($_FILES['plugin_upload']['tmp_name'])){
		// Upload form
		echo "<form name='submit_plugin' method='post' enctype='multipart/form-data'>";
		echo '<table class="databox" id="table1" width="98%" border="0" cellpadding="4" cellspacing="4">';
		echo "<tr><td class='datos'><input type='file' name='plugin_upload' />";
		echo "<td class='datos'><input type='submit' class='sub next' value='".__('Upload')."' />";
		echo "</form></table>";
		
		return;
	}
	
	$config["plugin_store"] = $config["attachment_store"] . "/plugin";
	$zip = zip_open($_FILES['plugin_upload']['tmp_name']);
	
	if ($zip) {
		while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip, $zip_entry, "r")) {
				if (zip_entry_name($zip_entry) == "plugin_definition.ini") {
					$basepath = $config["attachment_store"];
				}
				else {
					$basepath = $config["plugin_store"];
				}
				$filename = $basepath . "/". zip_entry_name($zip_entry);
				$fp = fopen($filename, 'w');
				$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					fwrite($fp, $buf);
					fclose($fp);
					chmod ($filename, 0755);
				zip_entry_close($zip_entry);
			}
		}
		zip_close($zip);
	}
	
	// Parse with sections
	if (! $ini_array = parse_ini_file($config["attachment_store"] . "/plugin_definition.ini", true)){
		echo "<h2 class=error>".__("Cannot load INI file")."</h2>";
		return;
	}
	
	// Get the version of the pspz
	// Version 1: Until 4.0.x
	// Version 2: From 5.0
	$version = isset($ini_array["plugin_definition"]["version"]) ? $ini_array["plugin_definition"]["version"] : 1;
	
	if(!isset($ini_array["plugin_definition"]["execution_postcommand"])) {
		$ini_array["plugin_definition"]["execution_postcommand"] = '';
	}
	
	// From Pandora 5.0 the pspz are in different format (version 2)
	// If pspz is a version 1, we convert it to 2
	if($version == 1) {
		$ini_array["plugin_definition"] = pluginreg_convert_plugin_1_to_2($ini_array["plugin_definition"]);
	}
	
	// Build plugin_exec
	$exec_path = $config["plugin_store"] . "/" . $ini_array["plugin_definition"]["filename"];
	
	$file_exec_path = $exec_path;
	
	if (isset($ini_array["plugin_definition"]["execution_command"]) && ($ini_array["plugin_definition"]["execution_command"] != "")){
		$exec_path = $ini_array["plugin_definition"]["execution_command"] . " " . $config["plugin_store"] . "/" . $ini_array["plugin_definition"]["filename"];
	}
	
	if (!file_exists($file_exec_path)){
		echo "<h2 class=error>".__("Plugin exec not found. Aborting!")."</h2>";
		unlink ($config["attachment_store"] . "/plugin_definition.ini");
		return;
	}
	
	// Verify if a plugin with the same name is already registered
	$sql0 = "SELECT COUNT(*) FROM tplugin WHERE name = '" . io_safe_input ($ini_array["plugin_definition"]["name"]) . "'";
	$result = db_get_sql ($sql0);
	
	
	if ($result> 0) {
		echo "<h2 class=error>".__("Plugin already registered. Aborting!")."</h2>";
		unlink ($config["attachment_store"] . "/plugin_definition.ini");
		return;
	}
	
	// Build macros
	$macros = array();
	$n = 1;
	while (1) {
		if(!isset($ini_array["plugin_definition"]["macro_desc_field".$n."_"])) {
			break;
		}
		
		$macros[$n]['macro'] = "_field".$n."_";
		$macros[$n]['desc'] = $ini_array["plugin_definition"]["macro_desc_field".$n."_"];
		$macros[$n]['value'] = "";
		$macros[$n]['help'] = "";
		
		$n++;
	}
	
	if (empty($macros)) {
		$macros = '';
	}
	else {
		$macros = json_encode($macros);
	}
	
	$values = array(
		'name' => io_safe_input ($ini_array["plugin_definition"]["name"]),
		'description' => io_safe_input ($ini_array["plugin_definition"]["description"]),
		'max_timeout' => $ini_array["plugin_definition"]["timeout"],
		'max_retries' => isset($ini_array["plugin_definition"]["retries"]) ? $ini_array["plugin_definition"]["retries"] : 0,
		'execute' => io_safe_input ($exec_path),
		'parameters' => io_safe_input ($ini_array["plugin_definition"]["execution_postcommand"]),
		'macros' => $macros,
		'plugin_type' => $ini_array["plugin_definition"]["plugin_type"]);
	
	$create_id = db_process_sql_insert('tplugin', $values);
	
	for ($ax=1; $ax <= $ini_array["plugin_definition"]["total_modules_provided"]; $ax++){
		$label = "module".$ax;
		
		$values = array(
			'name' => io_safe_input ($ini_array[$label]["name"]),
			'description' => io_safe_input ($ini_array[$label]["description"]),
			'id_group' => $ini_array[$label]["id_group"],
			'type' => $ini_array[$label]["type"],
			'max' => isset($ini_array[$label]["max"]) ? $ini_array[$label]["max"] : '',
			'min' => isset($ini_array[$label]["min"]) ? $ini_array[$label]["min"] : '',
			'module_interval' => isset($ini_array[$label]["module_interval"]) ? $ini_array[$label]["module_interval"] : '',
			'id_module_group' => $ini_array[$label]["id_module_group"],
			'id_modulo' => $ini_array[$label]["id_modulo"], 
			'plugin_user' => io_safe_input ($ini_array[$label]["plugin_user"]),
			'plugin_pass' => io_safe_input ($ini_array[$label]["plugin_pass"]),
			'plugin_parameter' => io_safe_input ($ini_array[$label]["plugin_parameter"]),
			'max_timeout' => isset($ini_array[$label]["max_timeout"]) ? $ini_array[$label]["max_timeout"] : '',
			'history_data' => isset($ini_array[$label]["history_data"]) ? $ini_array[$label]["history_data"] : '',
			'min_warning' => isset($ini_array[$label]["min_warning"]) ? $ini_array[$label]["min_warning"] : '',
			'max_warning' => isset($ini_array[$label]["max_warning"]) ? $ini_array[$label]["max_warning"] : '',
			'str_warning' => isset($ini_array[$label]["str_warning"]) ? $ini_array[$label]["str_warning"] : '',
			'min_critical' => isset($ini_array[$label]["min_critical"]) ? $ini_array[$label]["min_critical"] : '',
			'max_critical' => isset($ini_array[$label]["max_critical"]) ? $ini_array[$label]["max_critical"] : '',
			'str_critical' => isset($ini_array[$label]["str_critical"]) ? $ini_array[$label]["str_critical"] : '',
			'min_ff_event' => isset($ini_array[$label]["min_ff_event"]) ? $ini_array[$label]["min_ff_event"] : '',
			'tcp_port' => isset($ini_array[$label]["tcp_port"]) ? $ini_array[$label]["tcp_port"] : '',
			'critical_inverse' => isset($ini_array[$label]["critical_inverse"]) ? $ini_array[$label]["critical_inverse"] : '',
			'warning_inverse' => isset($ini_array[$label]["warning_inverse"]) ? $ini_array[$label]["warning_inverse"] : '',
			'critical_instructions' => isset($ini_array[$label]["critical_instructions"]) ? $ini_array[$label]["critical_instructions"] : '',
			'warning_instructions' => isset($ini_array[$label]["warning_instructions"]) ? $ini_array[$label]["warning_instructions"] : '',
			'unknown_instructions' => isset($ini_array[$label]["unknown_instructions"]) ? $ini_array[$label]["unknown_instructions"] : '',
			'id_plugin' => $create_id);
		
		db_process_sql_insert('tnetwork_component', $values);
		
		echo "<h3 class=suc>".__("Module plugin registered"). " : ". $ini_array[$label]["name"] ."</h2>";
	}
	
	echo "<h2 class=suc>".__("Plugin"). " ". $ini_array["plugin_definition"]["name"] . " ". __("Registered successfully")."</h2>";
	unlink ($config["attachment_store"] . "/plugin_definition.ini");

}

function pluginreg_convert_plugin_1_to_2($plugin) {
	$ip_desc = 'Target IP';
	$port_desc = 'Port';
	$user_desc = 'Username';
	$pass_desc = 'Password';
	$param_desc = 'Parameters';
	
	$parameters = '';
	$n_field = 1;
	$macro = "_field".$n_field."_";
	
	if(!empty($plugin["ip_opt"])) {
		$parameters.= $plugin["ip_opt"]." $macro ";
		unset($plugin["ip_opt"]);
		$plugin['macro_desc'.$macro] = $ip_desc;
		$n_field ++;
		$macro = "_field".$n_field."_";
	}
	
	if(!empty($plugin["port_opt"])) {
		$parameters.= $plugin["port_opt"]." $macro ";
		unset($plugin["port_opt"]);
		$plugin['macro_desc'.$macro] = $port_desc;
		$n_field ++;
		$macro = "_field".$n_field."_";
	}
	
	if(!empty($plugin["user_opt"])) {
		$parameters.= $plugin["user_opt"]." $macro ";
		unset($plugin["user_opt"]);
		$plugin['macro_desc'.$macro] = $user_desc;
		$n_field ++;
		$macro = "_field".$n_field."_";
	}
	
	if(!empty($plugin["pass_opt"])) {
		$parameters.= $plugin["pass_opt"]." $macro ";
		unset($plugin["pass_opt"]);
		$plugin['macro_desc'.$macro] = $pass_desc;
		$n_field ++;
		$macro = "_field".$n_field."_";
	}
	
	// Always add a parameter to 
	$parameters.= " $macro ";
	$plugin['macro_desc'.$macro] = $param_desc;
	
	$plugin["execution_postcommand"] .= " $parameters";
	
	return $plugin;
}

extensions_add_godmode_menu_option (__('Register plugin'), 'PM','gservers', null, "v1r1");
extensions_add_godmode_function('pluginreg_extension_main');

?>
