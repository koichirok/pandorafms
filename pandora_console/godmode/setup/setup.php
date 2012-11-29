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

// See http://php.net/manual/es/class.datetimezone.php
define ("AFRICA", 1);
define ("AMERICA", 2);
define ("ANTARCTICA", 4);
define ("ARCTIC", 8);
define ("ASIA", 16);
define ("ATLANTIC", 32);
define ("AUSTRALIA", 64);
define ("EUROPE", 128);
define ("INDIAN", 256);
define ("PACIFIC", 512);
define ("UTC", 1024);

if (is_ajax ()) {
	$get_os_icon = (bool) get_parameter ('get_os_icon');
	$select_timezone = get_parameter ('select_timezone', 0);
	
	if ($get_os_icon) {
		$id_os = (int) get_parameter ('id_os');
		ui_print_os_icon ($id_os, false);
		return;
	}
	
	if ($select_timezone) {
		$zone = get_parameter('zone');
		
		switch ($zone) {
			case 'Africa':
				$timezones = timezone_identifiers_list(AFRICA);
				break;
			case 'America':
				$timezones = timezone_identifiers_list(AMERICA);
				break;
			case 'Antarctica':
				$timezones = timezone_identifiers_list(ANTARCTICA);
				break;
			case 'Arctic':
				$timezones = timezone_identifiers_list(ARCTIC);
				break;
			case 'Asia':
				$timezones = timezone_identifiers_list(ASIA);
				break;
			case 'Atlantic':
				$timezones = timezone_identifiers_list(ATLANTIC);
				break;
			case 'Australia':
				$timezones = timezone_identifiers_list(AUSTRALIA);
				break;
			case 'Europe':
				$timezones = timezone_identifiers_list(EUROPE);
				break;
			case 'Indian':
				$timezones = timezone_identifiers_list(INDIAN);
				break;
			case 'Pacific':
				$timezones = timezone_identifiers_list(PACIFIC);
				break;
			case 'UTC':
				$timezones = timezone_identifiers_list(UTC);
				break;
			default:
				$timezones = array();
				break;
		}
		echo json_encode($timezones);
	}
	return;
}


if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}
// Load enterprise extensions
enterprise_include_once ('godmode/setup/setup.php');

/*
 NOTICE FOR DEVELOPERS:
 
 Update operation is done in config_process.php
 This is done in that way so the user can see the changes inmediatly.
 If you added a new token, please check config_update_config() in functions_config.php
 to add it there.
*/

// Header
ui_print_page_header (__('General configuration'), "", false, "", true);


$table->width = '100%';
$table->data = array ();
$table->size = array();
$table->size[0] = '30%';
$table->size[1] = '70%';

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

if ($current_system_lang == "") {
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
$table->data[10][1] = __('Yes').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button ('autoupdate', 1, '', $config["autoupdate"], true).'&nbsp;&nbsp;';
$table->data[10][1] .= __('No').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button ('autoupdate', 0, '', $config["autoupdate"], true);

$table->data[11][0] = __('Enforce https');
$table->data[11][1] = __('Yes').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button_extended ('https', 1, '', $config["https"], false, "if (! confirm ('" . __('If SSL is not properly configured you will lose access to Pandora FMS Console. Do you want to continue?') . "')) return false", '', true) .'&nbsp;&nbsp;';
$table->data[11][1] .= __('No').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button ('https', 0, '', $config["https"], true);

$table->data[14][0] = __('Attachment store') . ui_print_help_tip (__("Directory where temporary data is stored."), true);
$table->data[14][1] = html_print_input_text ('attachment_store', $config["attachment_store"], '', 50, 255, true);

$table->data[15][0] = __('IP list with API access') . ui_print_help_icon ("ip_api_list", true);
if (isset($_POST["list_ACL_IPs_for_API"])) {
	$list_ACL_IPs_for_API = get_parameter_post('list_ACL_IPs_for_API');
}
else {
	$list_ACL_IPs_for_API = get_parameter_get('list_ACL_IPs_for_API', implode("\n", $config['list_ACL_IPs_for_API']));
}
$table->data[15][1] = html_print_textarea('list_ACL_IPs_for_API', 2, 25, $list_ACL_IPs_for_API, 'style="height: 50px; width: 300px"', true);

$table->data[16][0] = __('API password') . 
	ui_print_help_tip (__("Please be careful if you put a password put https access."), true);
$table->data[16][1] = html_print_input_text('api_password', $config['api_password'], '', 25, 255, true);

$table->data[17][0] = __('Enable GIS features in Pandora Console');
$table->data[17][1] = __('Yes').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button ('activate_gis', 1, '', $config["activate_gis"], true).'&nbsp;&nbsp;';
$table->data[17][1] .= __('No').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button ('activate_gis', 0, '', $config["activate_gis"], true);

$table->data[18][0] = __('Enable Integria incidents in Pandora Console');
$table->data[18][1] = __('Yes').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button ('integria_enabled', 1, '', $config["integria_enabled"], true).'&nbsp;&nbsp;';
$table->data[18][1] .= __('No').'&nbsp;&nbsp;&nbsp;'.html_print_radio_button ('integria_enabled', 0, '', $config["integria_enabled"], true);

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

$zone_name = array('Africa' => __('Africa'), 'America' => __('America'), 'Antarctica' => __('Antarctica'), 'Arctic' => __('Arctic'), 'Asia' => __('Asia'), 'Atlantic' => __('Atlantic'), 'Australia' => __('Australia'), 'Europe' => __('Europe'), 'Indian' => __('Indian'), 'Pacific' => __('Pacific'), 'UTC' => __('UTC'));

$zone_selected = get_parameter('zone');
if ($zone_selected == "") {
	if ($config["timezone"] != "") {
		list($zone) = explode("/", $config["timezone"]);
		$zone_selected = $zone;
	}
	else {
		$zone_selected = 'Europe';
	}
}

switch ($zone_selected) {
	case 'Africa':
		$timezones = timezone_identifiers_list(AFRICA);
		break;
	case 'America':
		$timezones = timezone_identifiers_list(AMERICA);
		break;
	case 'Antarctica':
		$timezones = timezone_identifiers_list(ANTARCTICA);
		break;
	case 'Arctic':
		$timezones = timezone_identifiers_list(ARCTIC);
		break;
	case 'Asia':
		$timezones = timezone_identifiers_list(ASIA);
		break;
	case 'Atlantic':
		$timezones = timezone_identifiers_list(ATLANTIC);
		break;
	case 'Australia':
		$timezones = timezone_identifiers_list(AUSTRALIA);
		break;
	case 'Europe':
		$timezones = timezone_identifiers_list(EUROPE);
		break;
	case 'Indian':
		$timezones = timezone_identifiers_list(INDIAN);
		break;
	case 'Pacific':
		$timezones = timezone_identifiers_list(PACIFIC);
		break;
	case 'UTC':
		$timezones = timezone_identifiers_list(UTC);
		break;
	default:
		$timezones = array();
		break;
}
foreach ($timezones as $timezone) {
	$timezone_n[$timezone] = $timezone;
}

$table->data[23][0] = __('Timezone setup');
$table->data[23][1] = html_print_input_text_extended ('timezone_text', $config["timezone"], 'text-timezone_text', '', 25, 25, false, '', 'readonly', true); 
$table->data[23][1] .= '<a id="change_timezone">'.html_print_image ('images/pencil.png', true, array ('title' => __('Change timezone'))).'</a>';
$table->data[23][1] .= "&nbsp;&nbsp;". html_print_select($zone_name, 'zone', $zone_selected, 'show_timezone();', '', '', true);
$table->data[23][1] .= "&nbsp;&nbsp;". html_print_select($timezone_n, 'timezone', $config["timezone"], '', '', '', true);


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

$table->data[27][0] = __('License information');
$license_info = db_get_value ('value', 'tupdate_settings', '`key`', 'customer_key');
if ($license_info === false)
	$license_info = '';
$table->data[27][1] = html_print_input_text ('license_info_key', $license_info, '', 40, 255, true);
$table->data[27][1] .= '&nbsp;<a id="dialog_license_info" title="'.__("License Info").'" href="#">'.html_print_image('images/lock.png', true, array('class' => 'bot', 'title' => __('License info'))).'</a>';
$table->data[27][1] .= '<div id="dialog_show_license" style="display:none"></div>';

$table->data[28][0] = __('Public URL');
$table->data[28][0] .= ui_print_help_tip(__('Set this value when your PandoraFMS across inverse proxy or for example with mod_proxy of Apache.'), true);
$table->data[28][1] = html_print_input_text ('public_url', $config['public_url'], '', 40, 255, true);

$table->data[29][0] = __('Referer security');
$table->data[29][0] .= ui_print_help_tip(__('When it is set as "yes" in some important sections check if the user have gone from url Pandora.'), true);
$table->data[29][1] = __('Yes') . '&nbsp;&nbsp;&nbsp;' .
	html_print_radio_button ('referer_security', 1, '', $config["referer_security"], true) .
	'&nbsp;&nbsp;';
$table->data[29][1] .= __('No') . '&nbsp;&nbsp;&nbsp;' .
	html_print_radio_button ('referer_security', 0, '', $config["referer_security"], true);


?>
<script type="text/javascript">
function toggleButton(type) {
	if ($("#button_sound_" + type).attr('src') == 'images/control_pause.png') {
		$("#button_sound_" + type).attr('src', 'images/control_play.png');
		$('#layer_sound_' + type).html("");
	}
	else {
		$("#button_sound_" + type).attr('src', 'images/control_pause.png');
		$('#layer_sound_' + type).html("<audio src='" + $("#sound_" + type).val() + "' autoplay='true' hidden='true' loop='true'>");
	}
}

function replaySound(type) {
	if ($("#button_sound_" + type).attr('src') == 'images/control_pause.png') {
		$('#layer_sound_' + type).html("");
		$('#layer_sound_' + type).html("<audio src='" + $("#sound_" + type).val() + "' autoplay='true' hidden='true' loop='true'>");
	}
}

function show_timezone () {
	zone = $("#zone").val();
	$.ajax({
		type: "POST",
		url: "ajax.php",
		data: "page=<?php echo $_GET['sec2']; ?>&select_timezone=1&zone=" + zone,
		dataType: "json",
		success: function(data){
				$("#timezone").empty();
				jQuery.each (data, function (id, value) {
					timezone = value;
					$("select[name='timezone']").append($("<option>").val(timezone).html(timezone));
				});	
			}
		});
}

$(document).ready (function () {
	
	$("#zone").attr("disabled", true);
	$("#timezone").attr("disabled", true);
	
	$("#radiobtn0011").click(function() {
			flag = $("#radiobtn0011").is(':checked');
			if (flag == true) {
				message = "<?php
					echo __('If Enterprise ACL System is enabled without rules you will lose access to Pandora FMS Console (even admin). Do you want to continue?');
					?>";
				if (!confirm(message)) return false;
			}
	});
	
	$("#change_timezone").click(function () {
		$("#zone").attr("disabled", false);
		$("#timezone").attr("disabled", false);
	});
});
</script>
<?php

echo '<form id="form_setup" method="post">';
echo "<fieldset>";
echo "<legend>" . __('General options') . "</legend>";
	html_print_input_hidden ('update_config', 1);
	html_print_table ($table);
echo "</fieldset>";
	enterprise_hook ('setup');
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
