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

global $config;

// Login check
check_login ();

if (! give_acl ($config['id_user'], 0, "IW")) {
	pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once('godmode/reporting/visual_console_builder.constans.php');
require_once ('include/functions_visual_map.php');

if (is_ajax ()) {
	$get_original_size_background = get_parameter('get_original_size_background', false);
	
	if ($get_original_size_background) {
		$background = get_parameter('background', '');
		$size = getimagesize($background);
		echo json_encode($size);
		return;
	}
}

//Arrays for select box.
$backgrounds_list = list_files('images/console/background/', "jpg", 1, 0);
$backgrounds_list = array_merge($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));

$images_list = array ();
$all_images = list_files ('images/console/icons/', "png", 1, 0);
foreach ($all_images as $image_file) {
	if (strpos ($image_file, "_bad"))
		continue;
	if (strpos ($image_file, "_ok"))
		continue;
	if (strpos ($image_file, "_warning"))
		continue;	
	$image_file = substr ($image_file, 0, strlen ($image_file) - 4);
	$images_list[$image_file] = $image_file;
}

echo '<div id="editor" style="margin-top: -10px;">';
	echo '<div id="toolbox">';
		printButtonEditorVisualConsole('static_graph', __('Static Graph'), 'left', false, 'camera_min', true);
		printButtonEditorVisualConsole('percentile_bar', __('Percentile Bar'), 'left', false, 'percentile_min', true);
		printButtonEditorVisualConsole('module_graph', __('Module Graph'), 'left', false, 'graph_min', true);
		printButtonEditorVisualConsole('simple_value', __('Simple Value'), 'left', false, 'binary_min', true);
		printButtonEditorVisualConsole('label', __('Label'), 'left', false, 'label_min', true);
		printButtonEditorVisualConsole('icon', __('Icon'), 'left', false, 'icon_min', true);
		
		printButtonEditorVisualConsole('edit_item', __('Edit item'), 'right', true, 'config_min', true);
		printButtonEditorVisualConsole('delete_item', __('Delete item'), 'right', true, 'delete_min', true);
	echo '</div>';
echo '</div>';
echo '<div style="clear: right; margin-bottom: 10px;"></div>';

echo "<form id='form_visual_map' method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab . "&id_visual_console=" . $idVisualConsole . "'>";
print_input_hidden('action', 'update');
$background = $visualConsole['background'];
$widthBackground = $visualConsole['width'];
$heightBackground = $visualConsole['height'];

$layoutDatas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $idVisualConsole);
if ($layoutDatas === false)
	$layoutDatas = array();

/* Layout_data editor form */
$intervals = array ();
$intervals[3600] = "1 ".__('hour');
$intervals[7200] = "2 ".__('hours');
$intervals[10800] = "3 ".__('hours');
$intervals[21600] = "6 ".__('hours');
$intervals[43200] = "12 ".__('hours');
$intervals[86400] = __('Last day');
$intervals[172800] = "2 ". __('days');
$intervals[604800] = __('Last week');
$intervals[1209600] = "14 ".__('days');
$intervals[2592000] = __('Last month');
$intervals[5184000] = "2 ".__('months');
$intervals[15552000] = "6 ".__('months');

//Trick for it have a traduct text for javascript.
echo '<span id="any_text" style="display: none;">' . __('Any') . '</span>';
echo '<span id="ip_text" style="display: none;">' . __('IP') . '</span>';

echo '<div id="properties_panel" style="display: none; position: absolute; border: 2px solid #114105; padding: 5px; background: white; z-index: 90;">';
//----------------------------Hiden Form----------------------------------------
?>
<table class="databox" border="0" cellpadding="4" cellspacing="4" width="300">
	<caption>
		<span id="title_panel_span_background" class="title_panel_span" style="display: none; visibility:hidden; font-weight: bolder;"><?php echo  __('Background');?></span>
		<span id="title_panel_span_static_graph" class="title_panel_span" style="display: none; font-weight: bolder;"><?php echo  __('Static Graph');?></span>
		<span id="title_panel_span_percentile_bar" class="title_panel_span" style="display: none; font-weight: bolder;"><?php echo  __('Percentile Bar');?></span>
		<span id="title_panel_span_simple_value" class="title_panel_span" style="display: none; font-weight: bolder;"><?php echo  __('Simple value');?></span>
		<span id="title_panel_span_label" class="title_panel_span" style="display: none; font-weight: bolder;"><?php echo  __('Label');?></span>
		<span id="title_panel_span_icon" class="title_panel_span" style="display: none; font-weight: bolder;"><?php echo  __('Icon');?></span>
	</caption>
	<tbody>
		<tr id="label_row" style="" class="static_graph percentile_bar module_graph simple_value label datos">
			<td style=""><?php echo __('Label');?></td>
			<td style=""><?php print_input_text ('label', '', '', 20, 200); ?></td>
		</tr>
		<tr id="image_row" style="" class="static_graph icon datos">
			<td><?php echo __('Image');?></td>
			<td><?php print_select ($images_list, 'image', '', 'showPreview(this.value);', 'None', '');?></td>
		</tr>
		<tr id="preview_row" style="" class="static_graph datos icon">
			<td colspan="2" style="text-align: right;"><div id="preview" style="text-align: right;"></div></td>
		</tr>
		<tr id="agent_row" class="static_graph percentile_bar module_graph simple_value datos">
			<td><?php echo __('Agent') . '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search.") . '</span></a>';?></td>
			<td><?php print_input_text_extended ('agent', '', 'text-agent', '', 25, 100, false, '',
				array('style' => 'background: #ffffff url(images/lightning.png) no-repeat right;'), false);?></td>
		</tr>
		<tr id="module_row" class="static_graph percentile_bar module_graph simple_value datos">
			<td><?php echo __('Module');?></td>
			<td><?php print_select (array (), 'module', '', '', __('Any'), 0);?></td>
		</tr>
		<tr id="background_row_1" class="background datos">
			<td><?php echo __('Background');?></td>
			<td><?php print_select($backgrounds_list, 'background_image', $background, '', 'None', '');?></td>
		</tr>
		<tr id="background_row_2" class="background datos">
			<td><?php echo __('Original Size');?></td>
			<td><?php print_button(__('Apply'), 'original_false', false, 'setOriginalSizeBackground()', 'class="sub"');?></td>
		</tr>
		<tr id="background_row_3" class="background datos">
			<td><?php echo __('Aspect ratio');?></td>
			<td><?php print_button(__('Width proportional'), 'original_false', false, "setAspectRatioBackground('width')", 'class="sub"');?></td>
		</tr>
		<tr id="background_row_4" class="background datos">
			<td></td>
			<td><?php print_button(__('Height proportional'), 'original_false', false, "setAspectRatioBackground('height')", 'class="sub"');?></td>
		</tr>
		<tr id="percentile_bar_row_1" class="percentile_bar datos">
			<td><?php echo __('Width');?></td>
			<td>
				<?php
				print_input_text('width_percentile', 0, '', 3, 5);
				?>
			</td>
		</tr>
		<tr id="percentile_bar_row_2" class="percentile_bar datos">
			<td><?php echo __('Max value');?></td>
			<td>
				<?php
				print_input_text('max_percentile', 0, '', 3, 5);
				?>
			</td>
		</tr>
		<tr id="period_row" class="module_graph datos">
			<td><?php echo __('Period');?></td>
			<td><?php print_select ($intervals, 'period', '', '', '--', 0, false, false, false);?></td>
		</tr>
		<tr id="module_graph_size_row" class="module_graph datos">
			<td><?php echo __('Size');?></td>
			<td>
				<?php
				print_input_text('width_module_graph', 300, '', 3, 5);
				echo ' X ';
				print_input_text('height_module_graph', 180, '', 3, 5);
				?>
			</td>
		</tr>
		<tr id="button_update_row" class="datos">
			<td colspan="2" style="text-align: right;">
			<?php
			print_button(__('Cancel'), 'cancel_button', false, 'cancelAction();', 'class="sub cancel"');
			print_button(__('Update'), 'update_button', false, 'updateAction();', 'class="sub upd"');
			?>
			</td>
		</tr>
		<tr id="button_create_row" class="datos">
			<td colspan="2" style="text-align: right;">
			<?php
			print_button(__('Cancel'), 'cancel_button', false, 'cancelAction();', 'class="sub cancel"');
			print_button(__('Create'), 'create_button', false, 'createAction();', 'class="sub wand"');
			?>
			</td>
		</tr>
		<tr id="advance_options_link" class="datos">
			<td colspan="2" style="text-align: center;">
				<a href="javascript: showAdvanceOptions()"><?php echo __('Advanced options');?></a>
			</td>
		</tr>
	</tbody>
	<tbody id="advance_options" style="display: none;">
		<tr id="position_row" class="static_graph percentile_bar module_graph simple_value label icon datos">
			<td><?php echo __('Position');?></td>
			<td>
				<?php
				echo '(';
				print_input_text('left', '0', '', 3, 5);
				echo ' , ';
				print_input_text('top', '0', '', 3, 5);
				echo ')';
				?>
			</td>
		</tr>
		<tr id="size_row" class="background static_graph icon datos">
			<td><?php echo __('Size') . '<a href="#" class="tip">&nbsp;<span>' . __("For use the original image file size, set 0 width and 0 height.") . '</span></a>';?></td>
			<td>
				<?php
				print_input_text('width', 0, '', 3, 5);
				echo ' X ';
				print_input_text('height', 0, '', 3, 5);
				?>
			</td>
		</tr>
		<tr id="parent_row" class="static_graph percentile_bar module_graph simple_value label icon datos">
			<td><?php echo __('Parent');?></td>
			<td>
				<?php
				$parents = get_items_parents($visualConsole['id']);
				print_select($parents, 'parent', '', '', __('None'), 0);
				?>
			</td>
		</tr>
		<tr id="map_linked_row" class="static_graph percentile_bar module_graph simple_value icon label datos">
			<td><?php echo __('Map linked');?></td>
			<td>
				<?php
				print_select_from_sql ('SELECT id, name FROM tlayout WHERE id != ' . $idVisualConsole, 'map_linked', '', '', 'None', '0');
				?>
			</td>
		</tr>
		<tr id="label_color_row" class="static_graph percentile_bar module_graph simple_value label datos">
			<td><?php echo __('Label color');?></td>
			<td><?php print_input_text_extended ('label_color', '#000000', 'text-'.'label_color', '', 7, 7, false, '', 'class="label_color"', false);?></td>
		</tr>				
	</tbody>
</table>
<?php
//------------------------------------------------------------------------------
echo '</div>';
echo '<div id="frame_view" style="width: 100%; height: 500px; overflow: scroll;">';
//echo '<div id="background" class="ui-widget-content" style="background: url(images/console/background/' . $background . ');
//	border: 2px black solid; width: ' . $widthBackground . 'px; height: ' . $heightBackground . 'px;">';
echo '<div id="background" class="ui-widget-content" style="
	border: 2px black solid; width: ' . $widthBackground . 'px; height: ' . $heightBackground . 'px;">';
echo "<img id='background_img' src='images/console/background/" . $background . "' width='100%' height='100%' />";

foreach ($layoutDatas as $layoutData) {
			
	// Pending delete and disable modules must be ignored
	$delete_pending_module = get_db_value ("delete_pending", "tagente_modulo", "id_agente_modulo", $layoutData["id_agente_modulo"]);
	$disabled_module = get_db_value ("disabled", "tagente_modulo", "id_agente_modulo", $layoutData["id_agente_modulo"]);

	if($delete_pending_module == 1 || $disabled_module == 1)
		continue;
	
	printItemInVisualConsole($layoutData);
	print_input_hidden('status_'.$layoutData['id'], getStatusElement($layoutData));
}

echo '</div>';
echo '</div>';

print_input_hidden('background_width', $widthBackground);
print_input_hidden('background_height', $heightBackground);

$backgroundSizes = getimagesize('images/console/background/' . $background);

print_input_hidden('background_original_width', $backgroundSizes[0]);
print_input_hidden('background_original_height', $backgroundSizes[1]);
echo "</form>";

//Hack to translate messages in javascript
echo '<span style="display: none" id="message_alert_no_label_no_image">' . __('No image or name defined.') .'</span>';
echo '<span style="display: none" id="message_alert_no_label">' . __('No label defined.') .'</span>';
echo '<span style="display: none" id="message_alert_no_image">' . __('No image defined.') .'</span>';

require_css_file ('color-picker');

require_jquery_file('ui.core');
require_jquery_file('ui.resizable');
require_jquery_file('colorpicker');
require_jquery_file('ui.draggable');
require_javascript_file('wz_jsgraphics');
require_javascript_file('pandora_visual_console');
require_jquery_file ('autocomplete');
require_javascript_file('visual_console_builder.editor', 'godmode/reporting/');
// Javascript file for base 64 encoding of label parameter 
require_javascript_file ('encode_decode_base64');
?>
<style type="text/css">
.ui-resizable-handle {
	background: transparent !important;
	border: transparent !important;
}
</style>
<script type="text/javascript">
	id_visual_console = <?php echo $visualConsole['id']; ?>;
	$(document).ready (initJavascript);
</script>
