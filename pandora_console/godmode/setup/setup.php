<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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

check_login ();

if (is_ajax ()) {
	$get_os_icon = (bool) get_parameter ('get_os_icon');
	
	if ($get_os_icon) {
		$id_os = (int) get_parameter ('id_os');
		ui_print_os_icon ($id_os, false);
		return;
	}
	
	return;
}


if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}
// Load enterprise extensions
enterprise_include ('godmode/setup/setup.php');

/*
 NOTICE FOR DEVELOPERS:
 
 Update operation is done in config_process.php
 This is done in that way so the user can see the changes inmediatly.
 If you added a new token, please check config_update_config() in functions_config.php
 to add it there.
*/

// Header
ui_print_page_header (__('General configuration'), "", false, "", true);


$table->width = '98%';
$table->data = array ();

// Current config["language"] could be set by user, not taken from global setup !

switch ($config["dbtype"]) {
	case "mysql":
		$current_system_lang = db_get_sql ('SELECT `value` FROM tconfig WHERE `token` = "language"');
		break;
	case "postgresql":
		$current_system_lang = db_get_sql ('SELECT "value" FROM tconfig WHERE "token" = \'language\'');
		break;
	case "oracle":
		$current_system_lang = db_get_sql ('SELECT value FROM tconfig WHERE token = \'language\'');
		break;
}

if ($current_system_lang == ""){
	$current_system_lang = "en";
}

$table->data[0][0] = __('Language code for Pandora');
$table->data[0][1] = html_print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $current_system_lang , '', '', '', true);

$table->data[1][0] = __('Remote config directory') . ui_print_help_tip (__("Directory where agent remote configuration is stored."), true);

$table->data[1][1] = html_print_input_text ('remote_config', $config["remote_config"], '', 30, 100, true);

$table->data[6][0] = __('Auto login (hash) password');
$table->data[6][1] = html_print_input_text ('loginhash_pwd', $config["loginhash_pwd"], '', 15, 15, true);

$table->data[9][0] = __('Time source') . ui_print_help_icon ("timesource", true);
$sources["system"] = __('System');
$sources["sql"] = __('Database');
$table->data[9][1] = html_print_select ($sources, 'timesource', $config["timesource"], '', '', '', true);

$table->data[10][0] = __('Automatic check for updates');
$table->data[10][1] = __('Yes').'&nbsp;'.html_print_radio_button ('autoupdate', 1, '', $config["autoupdate"], true).'&nbsp;&nbsp;';
$table->data[10][1] .= __('No').'&nbsp;'.html_print_radio_button ('autoupdate', 0, '', $config["autoupdate"], true);

$table->data[11][0] = __('Enforce https');
$table->data[11][1] = __('Yes').'&nbsp;'.html_print_radio_button_extended ('https', 1, '', $config["https"], false, "if (! confirm ('" . __('If SSL is not properly configured you will lose access to Pandora FMS Console. Do you want to continue?') . "')) return false", '', true) .'&nbsp;&nbsp;';
$table->data[11][1] .= __('No').'&nbsp;'.html_print_radio_button ('https', 0, '', $config["https"], true);

$table->data[14][0] = __('Attachment store') . ui_print_help_tip (__("Directory where temporary data is stored."), true);
$table->data[14][1] = html_print_input_text ('attachment_store', $config["attachment_store"], '', 50, 255, true);

$table->data[15][0] = __('IP list with API access') . ui_print_help_icon ("ip_api_list", true);
$list_ACL_IPs_for_API = get_parameter('list_ACL_IPs_for_API', implode("\n", $config['list_ACL_IPs_for_API']));
$table->data[15][1] = html_print_textarea('list_ACL_IPs_for_API', 2, 25, $list_ACL_IPs_for_API, 'style="height: 50px; width: 300px"', true);

$table->data[16][0] = __('API password') . 
	ui_print_help_tip (__("Please be careful if you put a password put https access."), true);
$table->data[16][1] = html_print_input_text('api_password', $config['api_password'], '', 25, 255, true);

$table->data[17][0] = __('Enable GIS features in Pandora Console');
$table->data[17][1] = __('Yes').'&nbsp;'.html_print_radio_button ('activate_gis', 1, '', $config["activate_gis"], true).'&nbsp;&nbsp;';
$table->data[17][1] .= __('No').'&nbsp;'.html_print_radio_button ('activate_gis', 0, '', $config["activate_gis"], true);

$table->data[18][0] = __('Enable Integria incidents in Pandora Console');
$table->data[18][1] = __('Yes').'&nbsp;'.html_print_radio_button ('integria_enabled', 1, '', $config["integria_enabled"], true).'&nbsp;&nbsp;';
$table->data[18][1] .= __('No').'&nbsp;'.html_print_radio_button ('integria_enabled', 0, '', $config["integria_enabled"], true);

$table->data[19][0] = __('Enable Netflow');
$table->data[19][1] = __('Yes').'&nbsp;'.html_print_radio_button ('activate_netflow', 1, '', $config["activate_netflow"], true).'&nbsp;&nbsp;';
$table->data[19][1] .= __('No').'&nbsp;'.html_print_radio_button ('activate_netflow', 0, '', $config["activate_netflow"], true);

if ($config["integria_enabled"]) {
	require_once('include/functions_incidents.php');
	$invent = incidents_call_api($config['integria_url']."/include/api.php?user=".$config['id_user']."&pass=".$config['integria_api_password']."&op=get_inventories"); 
	$bad_input = false;
	// Wrong connection to api, bad password
	if (empty($invent)) {
		$bad_input = true;
	}
	
	$inventories = array();   
	// Right connection but  theres is no inventories 
	if ($invent == 'false') {
		unset($invent);
		$invent = array();
	}
	// Checks if URL is right
	else {
		$invent = explode("\n",$invent);
	}
	// Wrong URL 
	if ((strripos($config['integria_url'], '.php') !== false)) {
		$bad_input = true;
	}
	// Check page result and detect errors
	else {
		foreach ($invent as $inv) {
			if ((stristr($inv, 'ERROR 404') !== false)
				OR (stristr($inv, 'Status 404') !== false)
				OR (stristr($inv, 'Internal Server Error') !== false)) {
				$inventories[""] = __('None'); 
				$bad_input = true; 
				break;
			}
		}
	}
	$table->data[20][0] = __('Integria URL') . ui_print_help_icon ("integria_url", true);
	$table->data[20][1] = html_print_input_text ('integria_url', $config["integria_url"], '', 25, 255, true); 
	// If something goes wrong
	if ($bad_input){
		$table->data[20][1] .= html_print_image('images/error.png', true, array('title' => __('URL and/or Integria password are incorrect')));
	}

	$table->data[21][0] = __('Integria API password');
	$table->data[21][1] = html_print_input_text ('integria_api_password', $config["integria_api_password"], '', 25, 25, true);
	
	if (!$bad_input) {
		foreach ($invent as $inv) {
			if ($inv == '') {
				continue;
			}
			$invexp = explode(',',$inv);
			if (substr($invexp[1], 0, 1) == '"'
				&& substr($invexp[1], strlen($invexp[1])-1, 1) == '"') {
				$invexp[1] = substr($invexp[1], 1, strlen($invexp[1])-2);
			}
			
			$inventories[$invexp[0]] = $invexp[1];
		}
	}
	$table->data[22][0] = __('Integria inventory');
	$table->data[22][1] = html_print_select($inventories, 'integria_inventory', $config["integria_inventory"], '', '', '', true);
}

$table->data[23][0] = __('Timezone setup');
$table->data[23][1] = html_print_input_text ('timezone', $config["timezone"], '', 25, 25, true);

$sounds = get_sounds();
$table->data[24][0] = __('Sound for Alert fired');
$table->data[24][1] = html_print_select($sounds, 'sound_alert', $config['sound_alert'], 'replaySound(\'alert\');', '', '', true);
$table->data[24][1] .= ' <a href="javascript: toggleButton(\'alert\');">' . html_print_image("images/control_play.png", true, array("id" => "button_sound_alert", "style" => "vertical-align: middle;", "width" => "16")) . '</a>';
$table->data[24][1] .= '<div id="layer_sound_alert"></div>';

$table->data[25][0] = __('Sound for Monitor critical');
$table->data[25][1] = html_print_select($sounds, 'sound_critical', $config['sound_critical'], 'replaySound(\'critical\');', '', '', true);
$table->data[25][1] .= ' <a href="javascript: toggleButton(\'critical\');">' . html_print_image("images/control_play.png", true, array("id" => "button_sound_critical", "style" => "vertical-align: middle;", "width" => "16")) . '</a>';
$table->data[25][1] .= '<div id="layer_sound_critical"></div>';

$table->data[26][0] = __('Sound for Monitor warning');
$table->data[26][1] = html_print_select($sounds, 'sound_warning', $config['sound_warning'], 'replaySound(\'warning\');', '', '', true);
$table->data[26][1] .= ' <a href="javascript: toggleButton(\'warning\');">' . html_print_image("images/control_play.png", true, array("id" => "button_sound_warning", "style" => "vertical-align: middle;", "width" => "16")) . '</a>';
$table->data[26][1] .= '<div id="layer_sound_warning"></div>';
?>
<script type="text/javascript">
function toggleButton(type) {
	if ($("#button_sound_" + type).attr('src') == 'images/control_pause.png') {
		$("#button_sound_" + type).attr('src', 'images/control_play.png');
		$('#layer_sound_' + type).html("");
	}
	else {
		$("#button_sound_" + type).attr('src', 'images/control_pause.png');
		$('#layer_sound_' + type).html("<embed src='" + $("#sound_" + type).val() + "' autostart='true' hidden='true' loop='true'>");
	}
}

function replaySound(type) {
	if ($("#button_sound_" + type).attr('src') == 'images/control_pause.png') {
		$('#layer_sound_' + type).html("");
		$('#layer_sound_' + type).html("<embed src='" + $("#sound_" + type).val() + "' autostart='true' hidden='true' loop='true'>");
	}
}


$(document).ready (function () {
	$("#radiobtn0011").click(function(){
			flag = $("#radiobtn0011").is(':checked');
			if (flag == true){
				<?php echo "if (! confirm ('" . __('If Enterprise ACL System is enabled without rules you will lose access to Pandora FMS Console (even admin). Do you want to continue?') . "')) return false" ?>
			}
	});
});
</script>
<?php

enterprise_hook ('setup');

echo '<form id="form_setup" method="post">';
html_print_input_hidden ('update_config', 1);
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

function get_sounds() {
	global $config;
	
	$return = array();
	
	$files = scandir($config['homedir'] . '/include/sounds');
	
	foreach ($files as $file) {
		if (strstr($file, 'wav') !== false) {
			$return['include/sounds/' . $file] = $file;
		}
	}
	
	return $return;
}
?>
