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

// Login check
require ($config['homedir'] . '/include/functions_visual_map.php');

check_login ();

if (!defined('METACONSOLE'))
	$id_layout = (int) get_parameter ('id');
else
	$id_layout = (int) get_parameter ('id_visualmap');

$refr = (int) get_parameter ('refr', $config['vc_refr']);
$vc_refr = false;

// Get input parameter for layout id
if (! $id_layout) {
	db_pandora_audit("ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$layout = db_get_row ('tlayout', 'id', $id_layout);

if (! $layout) {
	db_pandora_audit("ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$id_group = $layout["id_group"];
$layout_name = $layout["name"];
$fullscreen = $layout["fullscreen"];
$background = $layout["background"];
$bwidth = $layout["width"];
$bheight = $layout["height"];

$pure_url = "&pure=".$config["pure"];

if (! check_acl ($config["id_user"], $id_group, "RR")) {
	db_pandora_audit("ACL Violation", "Trying to access visual console without group access");
	require ("general/noaccess.php");
	exit;
}

// Render map
$options = array();

if (check_acl ($config["id_user"], $id_group, "RW")) {
	
	$hash = md5($config["dbpass"]. $id_layout. $config["id_user"]);
	
	if (!defined('METACONSOLE'))
		$url = ui_get_full_url('operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$id_layout.'&id_user='.$config["id_user"]);
	else
		$url = ui_get_full_url('operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$id_layout.'&id_user='.$config["id_user"], false, false, false);
	$options['public_link']['text'] = '<a href="' . $url . '">'.
	
	html_print_image ("images/camera.png", true, array ("title" => __('Show link to public Visual Console'))).'</a>';
	$options['public_link']['active'] = false;
	
	if (!defined('METACONSOLE'))
		$options['setup']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=editor&action=edit&id_visual_console='.$id_layout.'">'.html_print_image ("images/setup.png", true, array ("title" => __('Setup'))).'</a>';
	else {
		$pure = get_parameter('pure', 0);
		$options['setup']['text'] = '<a href="index.php?action2=edit&tab=editor&operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '&id_visual_console='.$id_layout.'">'.html_print_image ("images/setup.png", true, array ("title" => __('Setup'))).'</a>';
	}
	$options['setup']['active'] = false;
}

if (!defined('METACONSOLE')) {
	if ($config["pure"] == 0) {
		$options['pure']['text'] = '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;id='.$id_layout.'&amp;refr='.$config["refr"].'&amp;pure=1">' . html_print_image ("images/fullscreen.png", true, array ("title" => __('Full screen mode')))
			. "</a>";
	}
	else {
		$options['pure']['text'] = '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;id='.$id_layout.'&amp;refr='.$config["refr"].'">'
			. html_print_image ("images/normalscreen.png", true, array ("title" => __('Back to normal mode')))
			. "</a>";
	}
	$options['pure']['active'] = false;
}


if (!defined('METACONSOLE'))
	ui_print_page_header (__("Visual console") . " &raquo; " . $layout_name, "images/reporting.png", false, '', false, $options);
else
	//ui_meta_print_header(__('Visual console') . " &raquo; " . $layout_name, "", $options);

//Set the hidden value for the javascript
if (defined('METACONSOLE')) {
	html_print_input_hidden('metaconsole', 1);
}
else {
	html_print_input_hidden('metaconsole', 0);
}

visual_map_print_visual_map ($id_layout);

$values = array ();
$values[5] = human_time_description_raw (5);
$values[30] = human_time_description_raw (30);
$values[SECONDS_1MINUTE] = human_time_description_raw(SECONDS_1MINUTE);
$values[SECONDS_2MINUTES] = human_time_description_raw(SECONDS_2MINUTES);
$values[SECONDS_5MINUTES] = human_time_description_raw(SECONDS_5MINUTES);
$values[SECONDS_10MINUTES] = human_time_description_raw(SECONDS_10MINUTES);
$values[SECONDS_30MINUTES] = human_time_description_raw(SECONDS_30MINUTES);

$table->width = '90%';
$table->data = array ();
$table->style = array ();
$table->style[2] = 'text-align: center';
$table->data[0][0] = __('Autorefresh time');

if (empty($config["vc_refr"])) {
	$vc_refr = true;
	$config["vc_refr"] = $refr;
}

$table->data[0][1] = html_print_select ($values, 'refr', $config["vc_refr"], '', 'N/A', 0, true, false, false);
$table->data[0][2] = html_print_submit_button (__('Refresh'), '', false, 'class="sub next"', true);
$table->data[0][2] .= html_print_input_hidden ('vc_refr', $config["vc_refr"], true);

if ($vc_refr) {
	$config["vc_refr"] = 0;
}

echo '<div style="height:30px">&nbsp;</div>';

if ($config['pure'] && $config["refr"] != 0) {
	echo '<div id="countdown"><br /></div>';
}

echo '<div style="height:30px">&nbsp;</div>';

if (!defined('METACONSOLE'))
	echo '<form method="post" action="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view">';
else
	echo '<form method="post" action="index.php?sec=screen&sec2=screens/screens&action=visualmap">';

html_print_input_hidden ('pure', $config["pure"]);
if (!defined('METACONSOLE'))
	html_print_input_hidden ('id', $id_layout);
else
	html_print_input_hidden ('id_visualmap', $id_layout);
html_print_table ($table);
echo '</form>';

if ($config["pure"] && $config["refr"] != 0) {
	ui_require_jquery_file ('countdown');
	ui_require_css_file ('countdown');
}
ui_require_javascript_file ('wz_jsgraphics');
ui_require_javascript_file ('pandora_visual_console');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#refr").change(function () {
		$("#hidden-vc_refr").val($("#refr option:selected").val());
	});
	
	<?php
	if ($config["pure"] && $config["refr"] > 0) {
	?>
		t = new Date();
		t.setTime (t.getTime() + <?php echo $config["refr"] * 1000; ?>);
		$("#countdown").countdown({
			until: t,
			format: 'MS',
			description: '<?php echo __('Until refresh'); ?>'
			});
	<?php
	}
	?>
	draw_lines (lines, 'layout_map');
});
/* ]]> */
</script>