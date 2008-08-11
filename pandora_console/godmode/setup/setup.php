<?php 

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

// Load global vars
require("include/config.php");

if (comprueba_login()) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
}
if (! give_acl ($config['id_user'], 0, "PM") || ! dame_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
}

$update_settings = (bool) get_parameter ('update_settings');

if ($update_settings) {
	$config["block_size"] = (int) get_parameter ('block_size');
	$config["language"] = (string) get_parameter ('language_code');
	$config["days_compact"] = (int) get_parameter ('days_compact');
	$config["days_purge"] = (int) get_parameter ('days_purge');
	$config["graph_res"] = (int) get_parameter ('graph_res');
	$config["step_compact"] = (int) get_parameter ('step_compact');
	$config["show_unknown"] = (int) get_parameter ('show_unknown');
	$config["show_lastalerts"] = (int) get_parameter ('show_lastalerts');
	$config["style"] = (string) get_parameter ('style', 'pandora.css');
	$config["remote_config"] = (string) get_parameter ('remote_config');
	$config["graph_color1"] = (string) get_parameter ('graph_color1');
	$config["graph_color2"] = (string) get_parameter ('graph_color2');
	$config["graph_color3"] = (string) get_parameter ('graph_color3');	
	$config["sla_period"] = (int) get_parameter ("sla_period");

	$config["style"] = substr ($config["style"], 0, strlen ($config["style"]) - 4);
	mysql_query ("UPDATE tconfig SET VALUE='".$config["remote_config"]."' WHERE token = 'remote_config'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["block_size"]."' WHERE token = 'block_size'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["language"]."' WHERE token = 'language_code'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["days_purge"]."' WHERE token = 'days_purge'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["days_compact"]." ' WHERE token = 'days_compact'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["graph_res"]."' WHERE token = 'graph_res'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["step_compact"]."' WHERE token = 'step_compact'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["show_unknown"]."' WHERE token = 'show_unknown'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["show_lastalerts"]."' WHERE token = 'show_lastalerts'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["style"]."' WHERE token = 'style'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["graph_color1"]."' WHERE token = 'graph_color1'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["graph_color2"]."' WHERE token = 'graph_color2'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["graph_color3"]."' WHERE token = 'graph_color3'");
	mysql_query ("UPDATE tconfig SET VALUE='".$config["sla_period"]."' WHERE token = 'sla_period'");
}

echo "<h2>".__('setup_screen')." &gt; ";
echo __('general_config')."</h2>";

$file_styles = list_files('include/styles/', "pandora", 1, 0);

$table->width = '500px';
$table->data = array ();
$table->data[0][0] = __('language_code');
$table->data[0][1] = print_select_from_sql ('SELECT id_language, name FROM tlanguage', 'language_code', $config["language"], '', '', '', true);
$table->data[1][0] = __('Remote config directory');
$table->data[1][1] = print_input_text ('remote_config', $config["remote_config"], '', 30, 100, true);
$table->data[2][0] = __('Graph color (min)');
$table->data[2][1] = print_input_text ('graph_color1', $config["graph_color1"], '', 8, 8, true);
$table->data[3][0] = __('Graph color (avg)');
$table->data[3][1] = print_input_text ('graph_color2', $config["graph_color2"], '', 8, 8, true);
$table->data[4][0] = __('Graph color (max)');
$table->data[4][1] = print_input_text ('graph_color3', $config["graph_color3"], '', 8, 8, true);
$table->data[5][0] = __('sla_period');
$table->data[5][1] = print_input_text ('sla_period', $config["sla_period"], '', 5, 5, true);
$table->data[5][0] = __('days_compact');
$table->data[5][1] = print_input_text ('days_compact', $config["days_compact"], '', 5, 5, true);
$table->data[6][0] = __('days_purge');
$table->data[6][1] = print_input_text ('days_purge', $config["days_purge"], '', 5, 5, true);
$table->data[7][0] = __('graph_res');
$table->data[7][1] = print_input_text ('graph_res', $config["graph_res"], '', 5, 5, true);
$table->data[8][0] = __('step_compact');
$table->data[8][1] = print_input_text ('step_compact', $config["step_compact"], '', 5, 5, true);
$table->data[9][0] = __('show_unknown');
$table->data[9][1] = print_checkbox ('show_unknown', 1, $config["show_unknown"], true);
$table->data[10][0] = __('show_lastalerts');
$table->data[10][1] = print_checkbox ('show_lastalerts', 1, $config["show_lastalerts"], true);
$table->data[11][0] = __('style_template');
$table->data[11][1] = print_select ($file_styles, 'style', $config["style"], '', '', '', true);
$table->data[12][0] = __('block_size');
$table->data[12][1] = print_input_text ('block_size', $config["block_size"], '', 5, 5, true);
$table->data[13][0] = __('sla_period');
$table->data[13][1] = print_input_text ('sla_period', $config["sla_period"], '', 5, 5, true);

echo '<form id="form_setup" method="POST" action="index.php?sec=gsetup&amp;sec2=godmode/setup/setup">';
print_input_hidden ('update_settings', 1);
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<link rel="stylesheet" href="include/styles/color-picker.css" type="text/css" />
<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script type="text/javascript" src="include/javascript/jquery.colorpicker.js"></script>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
	$("#form_setup #text-graph_color1").attachColorPicker();
	$("#form_setup #text-graph_color2").attachColorPicker();
	$("#form_setup #text-graph_color3").attachColorPicker();
});
</script>
