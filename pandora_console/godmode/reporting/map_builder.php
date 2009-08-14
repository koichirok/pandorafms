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

if (is_ajax ()) {
	$search_agents = (bool) get_parameter ('search_agents');
	
	if ($search_agents) {
		
		require_once ('include/functions_agents.php');
		
		$id_agent = (int) get_parameter ('id_agent');
		$string = (string) get_parameter ('q'); /* q is what autocomplete plugin gives */
		
		$filter = array ();
		$filter[] = '(nombre LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
		
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

require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_visual_map.php');

$id_layout = (int) get_parameter ('id_layout');
$edit_layout = (bool) get_parameter ('edit_layout');
$create_layout = (bool) get_parameter ('create_layout');
$update_layout = (bool) get_parameter ('update_layout');
$delete_layout = (bool) get_parameter ('delete_layout');
$create_layout_data = (bool) get_parameter ('create_layout_data');
$update_layout_data = (bool) get_parameter ('update_layout_data');
$delete_layout_data = (bool) get_parameter ('delete_layout_data');
$update_layout_data_coords = (bool) get_parameter ('update_layout_data_coords');
$get_layout_data = (bool) get_parameter ('get_layout_data');
$get_background_info = (bool) get_parameter ('get_background_info');

$name = '';
$id_group = 0;
$width = 0;
$height = 0;
$background = '';

if ($create_layout) {
	$name = (string) get_parameter ('name');
	$id_group = (int) get_parameter ('id_group');
	$width = (int) get_parameter ('width');
	$height = (int) get_parameter ('height');
	$background = (string) get_parameter ('background');
	if ($background != '') {
		$bg_info = getimagesize ('images/console/background/'.$background);
		$width = $bg_info[0];
		$height = $bg_info[1];
	}
	$values = array ();
	$values['name'] = $name;
	$values['id_group'] = $id_group;
	$values['background'] = $background;
	$values['height'] = $height;
	$values['width'] = $width;
	
	$id_layout = process_sql_insert ('tlayout', $values);
	if ($id_layout !== false) {
		echo '<h3 class="suc">'.__('Successfully created').'</h3>';
	} else {
		echo '<h3 class="err">'.__('Could not be created').'</h3>';
	}
	if (is_ajax ()) {
		return;
	}
}

if ($delete_layout) {
	process_sql_delete ('tlayout_data', array ('id_layout' => $id_layout));
	$result = process_sql_delete ('tlayout', array ('id' => $id_layout));
	if ($result) {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
	} else {
		echo '<h3 class="err">'.__('Not deleted. Error deleting data').'</h3>';
	}
	$id_layout = 0;
}

if ($update_layout) {
	$name = (string) get_parameter ('name');
	$id_group = (int) get_parameter ('id_group', 1);
	$width = (int) get_parameter ('width', 0);
	$height = (int) get_parameter ('height', 0);
	$background = (string) get_parameter ('background');
	$bg_info = array (0, 0);
	if (file_exists ('images/console/background/'.$background))
		$bg_info = getimagesize ('images/console/background/'.$background);
	
	if (! $width)
		$width = $bg_info[0];
	if (! $height)
		$height = $bg_info[1];
	
	$values = array ('name' => $name,
		'background' => $background,
		'height' => $height,
		'width' => $width,
		'id_group' => $id_group);
	
	$result = process_sql_update ('tlayout', $values, array ('id' => $id_layout));

	print_result_message ($result,
		__('Update layout successful'),
		__('Update layout failed'));

	if (is_ajax ()) {
		return;
	}
}

if ($get_background_info) {
	$file = (string) get_parameter ('background');
	if (file_exists ('images/console/background/'.$file)){
		$info = getimagesize ('images/console/background/'.$file);
		$info['width'] = $info[0];
		$info['height'] = $info[1];
	}
	if (is_ajax ()) {
		echo json_encode ($info);
		return;
	}
}

if ($get_layout_data) {
	$id_layout_data = (int) get_parameter ('id_layout_data');
	$layout_data = get_db_row ('tlayout_data', 'id', $id_layout_data);
	if ($layout_data['id_agente_modulo'])
		$layout_data['id_agent'] = give_agent_id_from_module_id ($layout_data['id_agente_modulo']);
	
	if (is_ajax ()) {
		echo json_encode ($layout_data);
		return;
	}
}

if ($create_layout_data) {
	$layout_data_type = (int) get_parameter ("type");
	$layout_data_label = (string) get_parameter ("label");
	$layout_data_image = (string) get_parameter ("image");
	$layout_data_id_agent = (int) get_parameter ("agent");
	$layout_data_id_agent_module = (int) get_parameter ("module");
	$layout_data_label_color = (string) get_parameter ("label_color");
	$layout_data_parent_item = (int) get_parameter ("parent_item");
	$layout_data_period = (int) get_parameter ("period");
	$layout_data_map_linked = (int) get_parameter ("map_linked");
	$layout_data_width = (int) get_parameter ("width");
	$layout_data_height = (int) get_parameter ("height");
	
	$values = array ('id_layout' => $id_layout,
		'label' => $layout_data_label,
		'id_layout_linked' => $layout_data_map_linked,
		'label_color' => $layout_data_label_color,
		'image' => $layout_data_image,
		'type' => $layout_data_type,
		'id_agent' => $layout_data_id_agent,
		'id_agente_modulo' => $layout_data_id_agent_module,
		'parent_item' => $layout_data_parent_item,
		'period' => $layout_data_period * 3600,
		'no_link_color' => 1,
		'width' => $layout_data_width,
		'height' => $layout_data_height);
	$result = process_sql_insert ('tlayout_data', $values);
	
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Successfully created').'</h3>';
	} else {
		echo '<h3 class="error">'.__('Could not be created').'</h3>';
	}
	if (is_ajax ()) {
		return;
	}
}

if ($update_layout_data_coords) {
	$id_layout_data = (int) get_parameter ('id_layout_data');
	$layout_data_x = (int) get_parameter ("coord_x");
	$layout_data_y = (int) get_parameter ("coord_y");
	
	$sql = sprintf ('UPDATE tlayout_data SET
			pos_x = %d, pos_y = %d
			WHERE id = %d',
			$layout_data_x, $layout_data_y, $id_layout_data);
	process_sql_update ('tlayout_data',
		array ('pos_x' => $layout_data_x, 'pos_y' => $layout_data_y),
		array ('id' => $id_layout_data));
	
	if (is_ajax ()) {
		return;
	}
}

if ($delete_layout_data) {
	$ids_layout_data = (array) get_parameter ('ids_layout_data');
	
	foreach ($ids_layout_data as $id_layout_data) {
		process_sql_update ('tlayout_data', array ('parent_item' => 0),
			array ('parent_item' => $id_layout_data));
		$sql = sprintf ('DELETE FROM tlayout_data WHERE id = %d',
				$id_layout_data);
		process_sql_delete ('tlayout_data', array ('id' => $id_layout_data));
	}
	
	if (is_ajax ()) {
		return;
	}
}

if ($update_layout_data) {
	$id_layout_data = (int) get_parameter ('id_layout_data');
	$layout_data_type = (int) get_parameter ("type");
	$layout_data_label = (string) get_parameter ("label");
	$layout_data_image = (string) get_parameter ("image");
	$layout_data_id_agent = (int) get_parameter ("agent");
	$layout_data_id_agent_module = (int) get_parameter ("module");
	$layout_data_label_color = (string) get_parameter ("label_color");
	$layout_data_parent_item = (int) get_parameter ("parent_item");
	$layout_data_period = (int) get_parameter ("period");
	$layout_data_map_linked = (int) get_parameter ("map_linked");
	$layout_data_width = (int) get_parameter ("width");
	$layout_data_height = (int) get_parameter ("height");
	
	$values = array ();
	$values['image'] = $layout_data_image;
	$values['label'] = $layout_data_label;
	$values['label_color'] = $layout_data_label_color;
	$values['id_agent'] = $layout_data_id_agent;
	$values['id_agente_modulo'] = $layout_data_id_agent_module;
	$values['type'] = $layout_data_type;
	$values['parent_item'] = $layout_data_parent_item;
	$values['period'] = $layout_data_period;
	$values['id_layout_linked'] = $layout_data_map_linked;
	$values['height'] = $layout_data_height;
	$values['width'] = $layout_data_width;
	$result = process_sql_update ('tlayout_data', $values, array ('id' => $id_layout_data));
	
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Updated successfully').'</h3>';
	} else {
		echo '<h3 class="error">'.__('Not updated. Error updating data').'</h3>';
	}
}

if ($id_layout) {
	$layout = get_db_row ('tlayout', 'id', $id_layout);
	$name = $layout['name'];
	$background = $layout['background'];
	$id_group = $layout['id_group'];
	$width = $layout['width'];
	$height = $layout['height'];
}

echo "<h2>".__('Reporting')." &raquo; ".__('Map builder');
print_help_icon ("map_builder");
echo "</h2>";

if (! $edit_layout && ! $id_layout) {
	$table->width = '500px';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Map name');
	$table->head[1] = __('Group');
	$table->head[2] = __('Items');
	$table->head[3] = __('Wizard');
	$table->head[4] = __('Delete');
	$table->align = array ();
	$table->align[3] = 'center';
	$table->align[4] = 'center';
	
	$maps = get_db_all_rows_in_table ('tlayout','name');
	if (!$maps) {
		echo '<div class="nf">'.('No maps defined').'</div>';
	} else {
		foreach ($maps as $map) {			
			if (give_acl ($config['id_user'], $map['id_group'], "AW")){
				$data = array ();
				$data[0] = '<a href="index.php?sec=greporting&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'">'.$map['name'].'</a>';
			
				$data[1] = print_group_icon ($map['id_group'], true).'&nbsp;';
				$data[1] .= get_group_name ($map['id_group']);
				$data[2] = get_db_sql ("SELECT COUNT(*) FROM tlayout_data WHERE id_layout = ".$map['id']);
				$data[3] = '<a href="index.php?sec=greporting&amp;sec2=godmode/reporting/map_builder_wizard&amp;id_layout='.$map['id'].'">'.print_image ("images/pill.png", true).'</a>';
			
				$data[4] = '<a href="index.php?sec=greporting&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;delete_layout=1">'.print_image ("images/cross.png", true).'</a>';
				array_push ($table->data, $data);
			}
		}
		print_table ($table);
	}
	
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	echo '<form action="index.php?sec=greporting&amp;sec2=godmode/reporting/map_builder" method="post">';
	print_input_hidden ('edit_layout', 1);
	print_submit_button (__('Create'), '', false, 'class="sub wand"');
	echo '</form>';
	echo '</div>';
} else {
	$backgrounds_list = list_files ('images/console/background/', "jpg", 1, 0);
	$backgrounds_list = array_merge ($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));
	$groups = get_user_groups ($config['id_user']);
	
	$table->width = '340px';
	$table->data = array ();
	$table->data[0][0] = __('Name');
	$table->data[0][1] = print_input_text ('name', $name, '', 15, 50, true);
	
	if ($id_layout){
		$table->data[0][1] .= '&nbsp;&nbsp;<a href="index.php?sec=greporting&amp;sec2=godmode/reporting/map_builder_wizard&amp;id_layout='.$id_layout.'">'.print_image ("images/pill.png", true).'</a>';
		
		$table->data[0][1] .= '&nbsp;&nbsp;<a href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;id='.$id_layout.'&amp;refr=60">'.print_image ("images/eye.png", true).'</a>';
	}
	
	$table->data[1][0] = __('Group');
	$table->data[1][1] = print_select ($groups, 'id_group', $id_group, '', '', '', true);
	$table->data[2][0] = __('Background');
	$table->data[2][1] = print_select ($backgrounds_list, 'background', $background, '', 'None', '', true);
	
	if ($id_layout) {
		$table->data[3][0] = __('Width');
		$table->data[3][1] = print_input_text ('width', $width, '', 3, 5, true);
		$table->data[4][0] = __('Height');
		$table->data[4][1] = print_input_text ('height', $height, '', 3, 5, true);
	}
	echo '<form action="index.php?sec=greporting&amp;sec2=godmode/reporting/map_builder" method="post">';
	print_table ($table);
	
	echo '<div style="width: '.$table->width.'" class="action-buttons">';
	if ($id_layout) {
		print_submit_button (__('Update'), 'update_layout', false, 'class="sub upd"');
		print_input_hidden ('update_layout', 1);
		print_input_hidden ('id_layout', $id_layout);
	} else {
		print_submit_button (__('Create'), 'create_layout', false, 'class="sub wand"');
		print_input_hidden ('create_layout', 1);
	}
	echo '</div>';
	echo '</form>';
	
	if ($id_layout) {
		/* Show visual map preview */
		echo '<h1>'.__('preview').'</h1>';
		print_pandora_visual_map ($id_layout, false, true);
		
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
		
		echo '<div style="width: 770px">';
		/* Layout data trash */
		echo '<form id="form_layout_data_trash" action="" method="post">';
		echo '<div id="layout_trash_drop">';
		echo '<h1>'.__('Map element trash').'</h1>';
		echo __('Drag an element here to delete from the map');
		echo '<span id="elements"> </span>';
		print_input_hidden ('delete_layout_data', 1);
		print_input_hidden ('id_layout', $id_layout);
		
		echo '<div class="action-buttons" style="margin-top: 180px">';
		print_submit_button (__('Delete'), 'delete_buttons', true, 'class="sub delete"');
		echo '</div>';
		echo '</div>';
		echo '</form>';
		
		/* Layout_data editor form */
		$intervals = array ();
		$intervals[3600] = "1 ".__('hour');
		$intervals[7200] = "2 ".__('hours');
		$intervals[10800] = "3 ".__('hours');
		$intervals[21600] = "6 ".__('hours');
		$intervals[43200] = "12 ".__('hours');
		$intervals[86400] = __('Last day');
		$intervals[172800] = "2 ". __('days');
		$intervals[1209600] = __('Last week');
		$intervals[2419200] = "15 ".__('days');
		$intervals[4838400] = __('Last month');
		$intervals[9676800] = "2 ".__('months');
		$intervals[29030400] = "6 ".__('months');
		
		$agents = get_group_agents ($id_group);
					
		echo '<div id="layout_editor_drop">';
		echo '<h1>'.__('Map element editor').'</h1>';
		echo __('Drag an element here to edit the properties');
		
		$table->data = array ();
		$table->id = 'table_layout_data';
		$table->rowstyle = array ();
		
		$table->data[0][0] = __('Label');
		$table->data[0][1] = print_input_text ('label', '', '', 20, 200, true);
		$table->data[1][0] = __('Label color');
		$table->data[1][1] = print_input_text ('label_color', '#000000', '', 7, 7, true);
		$table->data[2][0] = __('Type');
		$table->data[2][1] = print_select (get_layout_data_types (), 'type', '', '', '', 0, true, false, false); // Dont want to be sorted !
		$table->data[3][0] = __('Height');
		$table->data[3][1] = print_input_text ('height', '', '', 5, 5, true);
		$table->data[4][0] = __('Width');
		$table->data[4][1] = print_input_text ('width', '', '', 5, 5, true);
		$table->data[5][0] = __('Agent');
//		$table->data[5][1] = print_select ($agents, 'agent', '', '', '--', 0, true);
		$table->data[5][1] = print_input_text_extended ('agent', '', 'text-agent', '', 30, 100, false, '',
	array('style' => 'background: url(images/lightning.png) no-repeat right;'), true)
	. '<a href="#" class="tip">&nbsp;<span>' . __("Type two chars at least for search") . '</span></a>';
		$table->data[6][0] = __('Module');
		$table->data[6][1] = print_select (array (), 'module', '', '', '--', 0, true);
		$table->data[7][0] = __('Period');
		$table->data[7][1] = print_select ($intervals, 'period', '', '', '--', 0, true);
		$table->data[8][0] = __('Image');
		$table->data[8][1] = print_select ($images_list, 'image', '', '', 'None', '', true);
		$table->data[8][1] .= '<div id="image_preview"> </div>';
		$table->data[9][0] = __('Parent');
		$table->data[9][1] = print_select_from_sql ('SELECT id, label FROM tlayout_data WHERE id_layout = '.$id_layout,
							'parent_item', '', '', 'None', '', true);
		$table->data[10][0] = __('Map linked');
		$table->data[10][1] = print_select_from_sql ('SELECT id, name FROM tlayout WHERE id != '.$id_layout,
							'map_linked', '', '', 'None', '', true);
		
		echo '<form id="form_layout_data_editor" method="post" action="index.php?sec=greporting&amp;sec2=godmode/reporting/map_builder"  onsubmit="javascript: return testAgentCorrect();">';
		print_table ($table);
		print_input_hidden ('create_layout_data', 1);
		print_input_hidden ('update_layout_data', 0);
		print_input_hidden ('id_layout', $id_layout);
		print_input_hidden ('id_layout_data', 0);
		echo '<div style="width: '.$table->width.'" class="action-buttons">';
		print_submit_button (__('Create'), 'create_layout_data_button', false, 'class="sub wand"');
		echo '</div>';
		echo '</form>';
		echo '</div>';
		echo '</div>';
	}
}

require_css_file ('color-picker');

require_jquery_file ('ui.core');
require_jquery_file ('ui.draggable');
require_jquery_file ('ui.droppable');
require_jquery_file ('colorpicker');
require_jquery_file ('pandora.controls');
require_javascript_file ('wz_jsgraphics');
require_javascript_file ('pandora_visual_console');
require_jquery_file ('ajaxqueue');
require_jquery_file ('bgiframe');
require_jquery_file ('autocomplete');
?>
<script language="javascript" type="text/javascript">
var selectAgent = false;
var id_agent_module = 0;

function testAgentCorrect() {
	if (selectAgent) return true;
	else {
		alert ("<? echo __("No selected agent, please select any agent."); ?>");
		return false;
	}
}


$(document).ready (function () {
	
		$("#text-agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "operation/agentes/exportdata",
					search_agents: 1,
					id_group: function() { return $("#group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text-agent").css ('background-color', '#cc0000');
					else
						$("#text-agent").css ('background-color', 'none');
					if (data == "")
						return false;
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
				},
				delay: 200
			}
		);
		
		$("#text-agent").result (
			function () {
				selectAgent = true;
				var agent_name = this.value;
				$('#module').fadeOut ('normal', function () {
					$('#module').empty ();
					var inputs = [];
					inputs.push ("agent_name=" + agent_name);
					inputs.push ("get_agent_modules_json=1");
					inputs.push ("page=operation/agentes/ver_agente");
					jQuery.ajax ({
						data: inputs.join ("&"),
						type: 'GET',
						url: action="ajax.php",
						timeout: 10000,
						dataType: 'json',
						success: function (data) {
							$('#module').append ($('<option></option>').attr ('value', 0).text ("--"));
							jQuery.each (data, function (i, val) {
								s = html_entity_decode (val['nombre']);
								$('#module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
							});
							$('#module').fadeIn ('normal');
						}
					});
				});
		
				
			}
		);
	
	
<?php if ($id_layout): ?>
	if (lines)
		draw_lines (lines, 'layout_map');
<?php endif; ?>
	$('#background').change (function () {
		background = this.value;
		if (background == '')
			return;
		/* We have to get the info using AJAX because it was not 
		  possible to kwown the image dimensions using javascript 
		  in some cases where the image was not loaded */
		jQuery.post ('ajax.php', 
			{"page": "godmode/reporting/map_builder",
			"get_background_info": 1,
			"background": background
			},
			function (data) {
				$("#layout_map").css ('backgroundImage', 'url(images/console/background/' + background + ')');
				$("#layout_map").css ('width', data['width'] + 'px');
				$("#layout_map").css ('height', data['height'] + 'px');
				$('#text-width').attr ('value', data['width']);
				$('#text-height').attr ('value', data['height']);
			},
			"json"
		);
	});
	$('#text-width').keyup (function () {
		$("#layout_map").css ('width', this.value + 'px');
	});
	$('#text-height').keyup (function () {
		$("#layout_map").css ('height', this.value + 'px');
	});
	$(".layout-data").draggable ({helper: 'clone'});
	$("#layout_map").droppable ({
		accept: ".layout-data",
		drop: function (ev, ui) {
			margin_left = parseInt ($(ui.draggable[0]).css ('margin-left'));
			margin_top = parseInt ($(ui.draggable[0]).css ('margin-top'));
			coord_x = margin_left + ui.position.left;
			coord_y = margin_top + ui.position.top;
			$(ui.draggable[0]).css ('margin-left', coord_x + 'px');
			$(ui.draggable[0]).css ('margin-top', coord_y + 'px');
			id = ui.draggable[0].id.split ("-").pop ();
			jQuery.post ('ajax.php', 
				{page: "godmode/reporting/map_builder",
				update_layout_data_coords: 1,
				id_layout_data: id,
				coord_x: coord_x,
				coord_y: coord_y
				},
				function () {
					refresh_lines (lines, 'layout_map');
				},
				"html"
			);
		}
	});
	$("#layout_editor_drop").droppable ({
		accept: ".layout-data",
		drop: function (ev, ui) {
			id = ui.draggable[0].id.split ("-").pop ();
			jQuery.post ('ajax.php', 
				{"page": "godmode/reporting/map_builder",
				"get_layout_data": 1,
				"id_layout_data": id
				},
				function (data) {
					$("#form_layout_data_editor #text-label").attr ('value', data['label']);
					$("#form_layout_data_editor #type").attr ('value', data['type']);
					$("#form_layout_data_editor #type").change ();
					$("#form_layout_data_editor #image").attr ('value', data['image']);
					$("#form_layout_data_editor #text-width").attr ('value', data['width']);
					$("#form_layout_data_editor #text-height").attr ('value', data['height']);
					$("#form_layout_data_editor #image").change ();
					$("#form_layout_data_editor #id_layout_data").attr ('value', data['id']);
					$("#form_layout_data_editor #period").attr ('value', data['period']);
					$("#form_layout_data_editor #agent").attr ('value', data['id_agent']);
					$("#form_layout_data_editor #parent_item").attr ('value', data['parent_item']);
					$("#form_layout_data_editor #map_linked").attr ('value', data['id_layout_linked']);
					$("#form_layout_data_editor #hidden-update_layout_data").attr ('value', 1);
					$("#form_layout_data_editor #hidden-create_layout_data").attr ('value', 0);
					if (jQuery.browser.msie) {
						$("#form_layout_data_editor #hidden-id_layout_data").remove ();
						input = $('<input type="hidden" name="id_layout_data"></input>').attr ('value', id);
						$("#form_layout_data_editor").append (input);
					} else {
						$("#form_layout_data_editor #hidden-id_layout_data").attr ('value', id);
					}
					$("#form_layout_data_editor #submit-create_layout_data_button").attr ('value', "<?php echo __('Update'); ?>").removeClass ('wand').addClass ('upd');
					$("#form_layout_data_editor #text-label_color").attr ('value', data['label_color']);
					$(".ColorPickerDivSample").css ('background-color', data['label_color']);
					$("#form_layout_data_editor #agent").change ();
					id_agent_module = data['id_agente_modulo'];
				},
				"json"
			);
		}
	});
	$("#layout_trash_drop").droppable ({
		accept: ".layout-data",
		drop: function (ev, ui) {
			image = $('#'+ ui.draggable[0].id + " img").eq (0);
			total = $("img", this).length;
			
			id = ui.draggable[0].id.split ("-").pop ();
			$(ui.draggable[0]).clone ().css ('margin-left', 60 * total).
				css ('margin-top', 0). attr ('id', 'delete-layout-data-' + id).
				appendTo ("#"+this.id + " #elements");
			$(ui.draggable[0]).remove ();
			$('<input type="hidden" name="ids_layout_data[]"></input>').attr ('value', id).
				appendTo ($("#form_layout_data_trash"));
			$("#form_layout_data_trash #submit-delete_buttons").removeAttr ('disabled');
			setTimeout (function() { refresh_lines (lines, 'layout_map'); }, 1000);
		}
	});
	$("#form_layout_data_editor #image").change (function () {
		$("#image_preview").empty ();
		if (this.value != '') {
			$("#image_preview").append ($('<img></img>').attr ('src', 'images/console/icons/' + this.value + '.png'));
			$("#image_preview").append ($('<img></img>').attr ('src', 'images/console/icons/' + this.value + '_ok.png'));
			$("#image_preview").append ($('<img></img>').attr ('src', 'images/console/icons/' + this.value + '_warning.png'));
			$("#image_preview").append ($('<img></img>').attr ('src', 'images/console/icons/' + this.value + '_bad.png'));
		}
	});
	$("#form_layout_data_editor #agent").pandoraSelectAgentModule ({
		moduleSelect: "#module",
		callbackAfter : function () {
			$("#module").attr ("value", id_agent_module);
		}
	});
	$("#form_layout_data_editor #type").change (function () {
		if (this.value == 0) {
			$("#table_layout_data #table_layout_data-8").fadeIn ();
		} else {
			$("#table_layout_data #table_layout_data-8").fadeOut ();
		}
		
	});
	$("#form_layout_data_editor #text-label_color").attachColorPicker();
});
</script>
