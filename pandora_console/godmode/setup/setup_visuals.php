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

check_login ();

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Visual Setup Management");
	require ("general/noaccess.php");
	return;
}

// Load enterprise extensions
enterprise_include ('godmode/setup/setup_visuals.php');

/*
 NOTICE FOR DEVELOPERS:
 
 Update operation is done in config_process.php
 This is done in that way so the user can see the changes inmediatly.
 If you added a new token, please check config_update_config() in functions_config.php
 to add it there.
*/

require_once ('include/functions_themes.php');

// Header
ui_print_page_header (__('Visual configuration'), "", false, "", true);

$table->width = '90%';
$table->data = array ();

$table->data[1][0] = __('Date format string') . ui_print_help_icon("date_format", true);
$table->data[1][1] = '<em>'.__('Example').'</em> '.date ($config["date_format"]);
$table->data[1][1] .= html_print_input_text ('date_format', $config["date_format"], '', 30, 100, true);

$table->data[2][0] = __('Graph color (min)');
$table->data[2][1] = html_print_input_text ('graph_color1', $config["graph_color1"], '', 8, 8, true);

$table->data[3][0] = __('Graph color (avg)');
$table->data[3][1] = html_print_input_text ('graph_color2', $config["graph_color2"], '', 8, 8, true);

$table->data[4][0] = __('Graph color (max)');
$table->data[4][1] = html_print_input_text ('graph_color3', $config["graph_color3"], '', 8, 8, true);

$table->data[5][0] = __('Graphic resolution (1-low, 5-high)');
$table->data[5][1] = html_print_input_text ('graph_res', $config["graph_res"], '', 5, 5, true);

$table->data[6][0] = __('Style template');
$table->data[6][1] = html_print_select (themes_get_css (), 'style', $config["style"].'.css', '', '', '', true);

$table->data[7][0] = __('Block size for pagination');
$table->data[7][1] = html_print_input_text ('block_size', $config["block_size"], '', 5, 5, true);

$table->data[8][0] = __('Use round corners');
$table->data[8][1] = __('Yes').'&nbsp;'.html_print_radio_button ('round_corner', 1, '', $config["round_corner"], true).'&nbsp;&nbsp;';
$table->data[8][1] .= __('No').'&nbsp;'.html_print_radio_button ('round_corner', 0, '', $config["round_corner"], true);

$table->data[9][0] = __('Status icon set');
$iconsets["default"] = __('Colors');
$iconsets["faces"] = __('Faces');
$iconsets["color_text"] = __('Colors and text');
$table->data[9][1] = html_print_select ($iconsets, 'status_images_set', $config["status_images_set"], '', '', '', true);


$table->data[10][0] = __('Font path');
$fonts = load_fonts();
$table->data[10][1] = html_print_select($fonts, 'fontpath', $config["fontpath"], '', '', 0, true);


$table->data[11][0] = __('Font size');
$table->data[11][1] = html_print_select(range(1, 15), 'font_size', $config["font_size"], '', '', 0, true); 

$table->data[12][0] = __('Flash charts');
$table->data[12][1] = __('Yes').'&nbsp;'.html_print_radio_button ('flash_charts', 1, '', $config["flash_charts"], true).'&nbsp;&nbsp;';
$table->data[12][1] .= __('No').'&nbsp;'.html_print_radio_button ('flash_charts', 0, '', $config["flash_charts"], true);

if (!defined ('PANDORA_ENTERPRISE')){
	$table->data[13][0] = __('Custom logo') . ui_print_help_icon("custom_logo", true);
	$table->data[13][1] = html_print_select (list_files ('images/custom_logo', "png", 1, 0), 'custom_logo', $config["custom_logo"], '', '', '', true);
}

echo '<form id="form_setup" method="post">';
html_print_input_hidden ('update_config', 1);
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

ui_require_css_file ("color-picker");
ui_require_jquery_file ("colorpicker");

function load_fonts() {
	global $config;
	
	$dir = scandir($config['homedir'] . '/include/fonts/');
	
	$fonts = array();
	
	foreach ($dir as $file) {
		if (strstr($file, '.ttf') !== false) {
			$fonts[$config['homedir'] . '/include/fonts/' . $file] = $file;
		}
	}
	
	return $fonts;
}
?>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
	$("#form_setup #text-graph_color1").attachColorPicker();
	$("#form_setup #text-graph_color2").attachColorPicker();
	$("#form_setup #text-graph_color3").attachColorPicker();
});
</script>
