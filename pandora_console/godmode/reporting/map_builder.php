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

require_once ($config['homedir'] . '/include/functions_visual_map.php');

$pure = (int)get_parameter('pure', 0);
$hack_metaconsole = '';
if (defined('METACONSOLE'))
	$hack_metaconsole = '../../';

if (!defined('METACONSOLE')) {
	ui_print_page_header (__('Reporting') .' &raquo; ' . __('Visual Console'), "images/op_reporting.png", false, "map_builder");
}

$id_layout = (int) get_parameter ('id_layout');
$copy_layout = (bool) get_parameter ('copy_layout');
$delete_layout = (bool) get_parameter ('delete_layout');
$refr = (int) get_parameter('refr');

if ($delete_layout) {
	db_process_sql_delete ('tlayout_data', array ('id_layout' => $id_layout));
	$result = db_process_sql_delete ('tlayout', array ('id' => $id_layout));
	if ($result) {
		db_pandora_audit( "Visual console builder", "Delete visual console #$id_layout");
		ui_print_success_message(__('Successfully deleted'));
		db_clean_cache();
	}
	else {
		db_pandora_audit( "Visual console builder", "Fail try to delete visual console #$id_layout");
		ui_print_error_message(__('Not deleted. Error deleting data'));
	}
	$id_layout = 0;
}

if ($copy_layout) {	
	// Number of inserts
	$ninsert = (int) 0;
	
	// Return from DB the source layout
	$layout_src = db_get_all_rows_filter ("tlayout","id = " . $id_layout);
	
	// Name of dst
	$name_dst = get_parameter ("name_dst", $layout_src[0]['name'] . " copy");
	
	// Create the new Console
	$idGroup = $layout_src[0]['id_group'];
	$background = $layout_src[0]['background'];
	$height = $layout_src[0]['height'];
	$width = $layout_src[0]['width'];
	$visualConsoleName = $name_dst;
	
	$values = array('name' => $visualConsoleName, 'id_group' => $idGroup, 'background' => $background, 'height' => $height, 'width' => $width);
	$result = db_process_sql_insert('tlayout', $values);
	
	$idNewVisualConsole = $result;
	
	if ($result) {
		$ninsert = 1;
		
		// Return from DB the items of the source layout
		$data_layout_src = db_get_all_rows_filter ("tlayout_data", "id_layout = " . $id_layout);
		
		if (!empty($data_layout_src)) {
			
			//By default the id parent 0 is always 0.
			$id_relations = array(0 => 0);
			
			for ($a=0; $a < count($data_layout_src); $a++) { 
				
				// Changing the source id by the new visual console id
				$data_layout_src[$a]['id_layout'] = $idNewVisualConsole;
				
				$old_id = $data_layout_src[$a]['id'];
				
				// Unsetting the source's id
				unset($data_layout_src[$a]['id']);
			
				// Configure the cloned Console
				$result = db_process_sql_insert('tlayout_data', $data_layout_src[$a]);
				
				$id_relations[$old_id] = 0;
				
				if ($result !== false) {
					$id_relations[$old_id] = $result; 
				}
				
				if ($result)
					$ninsert++;
			}// for each item of console
				
			$inserts = count($data_layout_src) + 1;
				
			// If the number of inserts is correct, the copy is completed
			if ($ninsert == $inserts) {
				
				//Update the ids of parents
				$items = db_get_all_rows_filter ("tlayout_data", "id_layout = " . $idNewVisualConsole);
				
				foreach ($items as $item) {
					$new_parent = $id_relations[$item['parent_item']];
					
					db_process_sql_update('tlayout_data',
						array('parent_item' => $new_parent), array('id' => $item['id']));
				}
				
				
				ui_print_success_message(__('Successfully copied'));
				db_clean_cache();
			}
			else {
				ui_print_error_message(__('Not copied. Error copying data'));
			}
		}
		else{
			// If the array is empty the copy is completed
			ui_print_success_message(__('Successfully copied'));
			db_clean_cache();
		}
	}
	else {
		ui_print_error_message(__('Not copied. Error copying data'));
	}
	
}

$table->width = '98%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Map name');
$table->head[1] = __('Group');
$table->head[2] = __('Items');

//Only for IW flag
if (check_acl ($config['id_user'], 0, "IW")) {
	$table->head[3] = __('Copy');
	$table->head[4] = __('Delete');
}

$table->align = array ();
$table->align[0] = 'left';
$table->align[1] = 'center';
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'center';

// Only display maps of "All" group if user is administrator
// or has "RR" privileges, otherwise show only maps of user group
$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "RR"))
	$maps = visual_map_get_user_layouts ();	
else
	$maps = visual_map_get_user_layouts ($config['id_user'], false, false, false);

if (!$maps) {
	echo '<div class="nf">'.('No maps defined').'</div>';
}
else {
	foreach ($maps as $map) {
		
		$data = array ();
		
		if (!defined('METACONSOLE')) {
			$data[0] = '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;id='.
				$map['id'].'&amp;refr=' . $refr . '">'.$map['name'].'</a>';
		}
		else {
			$data[0] = '<a href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure .
				'&id_visualmap=' . $map['id'].'&amp;refr=' . $refr . '">'.$map['name'].'</a>';
		}
		
		$data[1] = ui_print_group_icon ($map['id_group'], true);
		$data[2] = db_get_sql ("SELECT COUNT(*) FROM tlayout_data WHERE id_layout = ".$map['id']);
		
		if (check_acl ($config['id_user'], 0, "IW")) {
			
			if (!defined('METACONSOLE')) {
				$data[3] = '<a class="copy_visualmap" href="index.php?sec=reporting&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;copy_layout=1">'.html_print_image ("images/copy.png", true).'</a>';
				$data[4] = '<a class="delete_visualmap" href="index.php?sec=reporting&amp;sec2=godmode/reporting/map_builder&amp;id_layout='.$map['id'].'&amp;delete_layout=1">'.html_print_image ("images/cross.png", true).'</a>';
			}
			else {
				$data[3] = '<a class="copy_visualmap" href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '&id_layout='.$map['id'].'&amp;copy_layout=1">'.html_print_image ("images/copy.png", true).'</a>';
				$data[4] = '<a class="delete_visualmap" href="index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '&id_layout='.$map['id'].'&amp;delete_layout=1">'.html_print_image ("images/cross.png", true).'</a>';
			}
		}
		array_push ($table->data, $data);
	}
	html_print_table ($table);
}
if (!$maps) {
	if (!defined('METACONSOLE'))
		echo '<div class="action-buttons" style="width: 0px;">';
	else
		echo '<div class="action-buttons" style="width: 500px; text-align: right;">';
}
else {
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
}

//Only for IW flag
if (check_acl ($config['id_user'], 0, "IW")) {
	if (!defined('METACONSOLE'))
		echo '<form action="index.php?sec=reporting&amp;sec2=godmode/reporting/visual_console_builder" method="post">';
	else {		
		echo '<form action="index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '" method="post">';
	}
	html_print_input_hidden ('edit_layout', 1);
	html_print_submit_button (__('Create'), '', false, 'class="sub next"');
	echo '</form>';
}
echo '</div>';
?>
