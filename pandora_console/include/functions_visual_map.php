<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/**
 * Prints visual map
 *
 * @param int $id_layout Layout id
 * @param bool $show_links
 * @param bool $draw_lines
 */
function print_pandora_visual_map ($id_layout, $show_links = true, $draw_lines = true) {
	global $config;
	$layout = get_db_row ('tlayout', 'id', $id_layout);
	
	echo '<div id="layout_map" style="z-index: 0; position:relative; background: url(\'images/console/background/'.safe_input ($layout["background"]).'\'); width:'.$layout["width"].'px; height:'.$layout["height"].'px;">';
	$layout_datas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $id_layout);
	$lines = array ();
	
	if ($layout_datas !== false) {
		foreach ($layout_datas as $layout_data) {
			// Linked to other layout ?? - Only if not module defined
			if ($layout_data['id_layout_linked'] != 0) {
				$status = get_layout_status ($layout_data['id_layout_linked']);
				$status_parent = 3;
			} else {
				// Status for a simple module
				if ($layout_data['id_agente_modulo'] != 0) {
					$id_agent = get_db_value ("id_agente", "tagente_estado", "id_agente_modulo", $layout_data['id_agente_modulo']);
					$id_agent_module_parent = get_db_value ("id_agente_modulo", "tlayout_data", "id", $layout_data["parent_item"]);
					// Item value
					$status = get_agentmodule_status ($layout_data['id_agente_modulo']);
					if ($layout_data['no_link_color'] == 1)
						$status_parent = 3;
					else
						$status_parent = get_agentmodule_status ($id_agent_module_parent);
				// Status for a whole agent
				} elseif ($layout_data['id_agent'] != 0) {
					$status = get_agent_status ($layout_data["id_agent"]);
					echo '<h1>'.$status.'</h1>';
					$status_parent = $status;
				} else {
					$status = 3;
					$status_parent = 3;
					$id_agent = 0;
				}
			}
			
			// STATIC IMAGE (type = 0)
			if ($layout_data['type'] == 0) {
				// Link image
				//index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=1
				if ($status == 0) // Bad monitor
					$z_index = 3;
				elseif ($status == 2) // Warning
					$z_index = 2;
				elseif ($status == 4) // Alert
					$z_index = 4;
				else
					$z_index = 1; // Print BAD over good
				
				// Draw image 
				echo '<div style="z-index: '.$z_index.'; '.($layout_data['label_color'][0] == '#' ? 'color: '.$layout_data['label_color'].';' : '').' position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">'; 
				
				if (!isset ($id_agent))
					$id_agent = 0;
					
				if ($show_links) {
					if (($id_agent > 0) && ($layout_data['id_layout_linked'] == "" || $layout_data['id_layout_linked'] == 0)) {
						// Link to an agent
						echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'">';
					} elseif ($layout_data['id_layout_linked'] > 0) {
						// Link to a map
						echo '<a href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data["id_layout_linked"].'">';
					} else {
						// A void object
						echo '<a href="#">';
					}
				}
				
				$img_style = array ();
				$img_style["title"] = $layout_data["label"];
				
				if (!empty ($layout_data["width"])) {
					$img_style["width"] = $layout_data["width"];
				} 
				if (!empty ($layout_data["height"])) {
					$img_style["height"] = $layout_data["height"];
				}
				
				$img = "images/console/icons/".$layout_data["image"];
				switch ($status) {
				case 1:
				case 4:
					//Critical (BAD or ALERT)
					$img .= "_bad.png";
					break;
				case 0:
					//Normal (OK)
					$img .= "_ok.png";
					break;
				case 2:
					//Warning
					$img .= "_warning.png";
					break;
				default:
					$img .= ".png";
					// Default is Grey (Other)
				}
				
				print_image ($img, false, $img_style);
		
				echo "</a>";
				
				// Print label if valid label_color (only testing for starting with #) otherwise print nothing
				if ($layout_data['label_color'][0] == '#') {
					echo "<br />";
					echo $layout_data['label'];	
				}
				echo "</div>";
			}

			// SIMPLE DATA VALUE (type = 2)
			switch ($layout_data['type']) {
			case 2:
				echo '<div style="z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
				echo '<strong>'.$layout_data['label']. ' ';
				echo get_db_value ('datos', 'tagente_estado', 'id_agente_modulo', $layout_data['id_agente_modulo']);
				echo '</strong></div>';
				break;
			case 3:
				// Percentile bar (type = 3)
				echo '<div style="z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
				$valor = get_db_sql ('SELECT datos FROM tagente_estado WHERE id_agente_modulo = '.$layout_data['id_agente_modulo']);
				$width = $layout_data['width'];
				if ( $layout_data['height'] > 0)
					$percentile = $valor / $layout_data['height'] * 100;
				else
					$percentile = 100;
				echo $layout_data['label'];
				echo "<br>";

				echo "<img src='".$config["homeurl"]."/reporting/fgraph.php?tipo=progress&height=15&width=$width&mode=1&percent=$percentile'>";

				echo '</div>';
				break;
			case 1;
				// SINGLE GRAPH (type = 1)
				echo '<div style="z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
				if ($show_links) {
					if (($layout_data['id_layout_linked'] == "") || ($layout_data['id_layout_linked'] == 0)) {
						echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.'&amp;tab=data">';
					} else {
						echo '<a href="index.php?sec=visualc&amp;sec2=operation/visual_console/render_view&amp;pure='.$config["pure"].'&amp;id='.$layout_data['id_layout_linked'].'">';
					}
				}
				print_image ("reporting/fgraph.php?tipo=sparse&amp;id=".$layout_data['id_agente_modulo']."&amp;label=".safe_input ($layout_data['label'])."&amp;height=".$layout_data['height']."&amp;width=".$layout_data['width']."&amp;period=".$layout_data['period'], false, array ("title" => $layout_data['label'], "border" => 0));
				echo "</a>";
				echo "</div>";
			}
			// Line, not implemented in editor
			/*
			} elseif ($layout_data['type'] == 2) {
				$line['id'] = $layout_data['id'];
				$line['x'] = $layout_data['pos_x'];
				$line['y'] = $layout_data['pos_y'];
				$line['width'] = $layout_data['width'];
				$line['height'] = $layout_data['height'];
				$line['color'] = $layout_data['label_color'];
				array_push ($lines, $line);
			}
			*/
			
			// Get parent relationship - Create line data
			if ($layout_data["parent_item"] != "" && $layout_data["parent_item"] != 0) {
				$line['id'] = $layout_data['id'];
				$line['node_begin'] = 'layout-data-'.$layout_data["parent_item"];
				$line['node_end'] = 'layout-data-'.$layout_data["id"];
				$line['color'] = $status_parent ? '#00dd00' : '#dd0000';
				array_push ($lines, $line);
			}
		}
	}
	
	if ($draw_lines) {
		/* If you want lines in the map, call using Javascript:
		 draw_lines (lines, id_div);
		 on body load, where id_div is the id of the div which holds the map */
		echo '<script type="text/javascript">/* <![CDATA[ */'."\n";
		
		echo 'var lines = Array ();'."\n";
		
		foreach ($lines as $line) {
			echo 'lines.push (eval ('.json_encode ($line).'));'."\n";
		}
		echo '/* ]]> */</script>';
	}
	// End main div
	echo "</div>";
}

/**
 * @return array Layout data types
 */
function get_layout_data_types () {
	$types = array ();
	$types[0] = __('Static graph');
	$types[1] = __('Module graph');
	$types[2] = __('Simple value');
	$types[3] = __('Percentile bar');
	
	return $types;
}

/**
 * Get a list with the layouts for a user.
 *
 * @param int User id.
 * @param bool Wheter to return all the fields or only the name (to use in
 * print_select() directly)
 * @param array Additional filters to filter the layouts.
 *
 * @return array A list of layouts the user can see.
 */
function get_user_layouts ($id_user = 0, $only_names = false, $filter = false) {
	if (! is_array ($filter))
		$filter = array ();
	
	$where = format_array_to_where_clause_sql ($filter);
	if ($where != '') {
		$where .= ' AND ';
	}
	$groups = get_user_groups ($id_user);
	$where .= sprintf ('id_group IN (%s)', implode (",", array_keys ($groups)));
	
	$layouts = get_db_all_rows_filter ('tlayout', $where);
	
	if ($layouts == false)
		return array ();
	
	$retval = array ();
	foreach ($layouts as $layout) {
		if ($only_names)
			$retval[$layout['id']] = $layout['name'];
		else
			$retval[$layout['id']] = $layout;
	}
	
	return $retval;
}


/** 
 * Get the status of a layout.
 *
 * It gets all the data of the contained elements (including nested
 * layouts), and makes an AND operation to be sure that all the items
 * are OK. If any of them is down, then result is down (0)
 * 
 * @param int Id of the layout
 * 
 * @return bool The status of the given layout. True if it's OK, false if not.
 */
function get_layout_status ($id_layout = 0) {
	$temp_status = 0;
	$temp_total = 0;

	$id_layout = (int) $id_layout;
	
	$sql = sprintf ('SELECT id_agente_modulo, parent_item, id_layout_linked, id_agent
		FROM `tlayout_data` WHERE `id_layout` = %d', $id_layout);
	$result = get_db_all_rows_filter ('tlayout_data', array ('id_layout' => $id_layout),
		array ('id_agente_modulo', 'parent_item', 'id_layout_linked', 'id_agent'));
	if ($result === false)
		return 0;
	
	foreach ($result as $rownum => $data) {
		if ($data["id_layout_linked"] == 0 && $data["id_agente_modulo"] == 0 && $data["id_agent"] == 0)
			continue;
		// Other Layout (Recursive!)
		if (($data["id_layout_linked"] != 0) && ($data["id_agente_modulo"] == 0)) {
			$status = get_layout_status ($data["id_layout_linked"]);
		// Module
		} elseif ($data["id_agente_modulo"] != 0) {
			$status = get_agentmodule_status ($data["id_agente_modulo"]);
		// Agent
		} else {
			$status = get_agent_status ($data["id_agent"]);
		}
		if ($status == 1)
			return 1;
		if ($status > $temp_total)
			$temp_total = $status;
	}
	
	return $temp_total;
}
?>
