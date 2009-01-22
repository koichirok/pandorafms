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
	
	echo "<div id='layout_map' style='z-index: 0; position:relative; background: url(images/console/background/".$layout["background"]."); width:".$layout["width"]."px; height:".$layout["height"]."px;'>";
	$layout_datas = get_db_all_rows_field_filter ('tlayout_data', 'id_layout', $id_layout);
	$lines = array ();
	
	if ($layout_datas !== false) {
		foreach ($layout_datas as $layout_data) {
			// Linked to other layout ?? - Only if not module defined
			if ($layout_data['id_layout_linked'] != 0) { 
				$status = return_status_layout ($layout_data['id_layout_linked']);
				$status_parent = 3;
			} else {

				// Status for a simple module
				if ($layout_data['id_agente_modulo'] != 0) {
					$id_agent = get_db_value ("id_agente", "tagente_estado", "id_agente_modulo", $layout_data['id_agente_modulo']);
					$id_agent_module_parent = get_db_value ("id_agente_modulo", "tlayout_data", "id", $layout_data["parent_item"]);
					// Item value
					$status = return_status_agent_module ($layout_data['id_agente_modulo']);
					if ($layout_data['no_link_color'] == 1)
						$status_parent = 3;
					else
						$status_parent = return_status_agent_module ($id_agent_module_parent);
						
				// Status for a whole agent
				} elseif ($layout_data['id_agent'] != 0) {
					$id_agent = $layout_data['id_agent'];
					$agent_interval = get_agent_interval ($id_agent);
					$sql = sprintf ('SELECT MAX(estado)
						FROM tagente_estado, tagente_modulo 
						WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente_modulo.disabled = 0 
						AND tagente_modulo.id_agente = %d
						AND ((module_interval > 0
							AND utimestamp >= UNIX_TIMESTAMP() - module_interval * 2)
							OR (module_interval = 0
								AND utimestamp >= UNIX_TIMESTAMP() - %d))',
						$id_agent, $agent_interval * 2);
					$status = get_db_sql ($sql);
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
					$z_index =  1; // Print BAD over good
				
				// Draw image 
				echo '<div style="z-index: '.$z_index.'; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">'; 
				
				if (!isset($id_agent))
					$id_agent = 0;
					
				if ($show_links) {
				
					// Link to an agent
					if (($id_agent > 0) && ($layout_data['id_layout_linked'] == "" || $layout_data['id_layout_linked'] == 0)) {
						echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agent'>";
						
					// Link to a map
					} elseif ($layout_data['id_layout_linked']>0){
						echo '<a href="index.php?sec=visualc&sec2=operation/visual_console/render_view&pure='.$config["pure"].'&id='.$layout_data['id_layout_linked'].'">';
						
					// A void object
					} else { 
						echo "<a href='#'>";
					}
				}
				
				// Critical (BAD) or ALERT
				if (($status == 1) OR ($status == 4)){
					if ($layout_data['width'] != "" && $layout_data['width'] != 0)
						echo '<img src="images/console/icons/'.$layout_data['image'].'_bad.png" width="'.$layout_data['width'].'" height="'.$layout_data['height'].'" title="'.$layout_data['label'].'">';
					else
						echo '<img src="images/console/icons/'.$layout_data['image'].'_bad.png" 
							title="'.$layout_data['label'].'">';
							
				// Normal (OK)
				} elseif ($status == 0){
					if ($layout_data['width'] != "" && $layout_data['width'] != 0)
						echo '<img src="images/console/icons/'.$layout_data['image'].'_ok.png" width="'.$layout_data['width'].'" 
							height="'.$layout_data['height'].'" title="'.$layout_data['label'].'">';
					else
						echo '<img src="images/console/icons/'.$layout_data['image'].'_ok.png" 
							title="'.$layout_data['label'].'">';
						
				// Warning
				} elseif ($status == 2){
					if ($layout_data['width'] != "" && $layout_data['width'] != 0)
						echo '<img src="images/console/icons/'.$layout_data['image'].'_warning.png" width="'.$layout_data['width'].'" 
							height="'.$layout_data['height'].'" title="'.$layout_data['label'].'">';
					else
						echo '<img src="images/console/icons/'.$layout_data['image'].'_warning.png" 
							title="'.$layout_data['label'].'">';
							
				// Other (GREY)
				} else {
					if ($layout_data['width'] != "" && $layout_data['width'] != 0)
						echo '<img src="images/console/icons/'.$layout_data['image'].'.png" width="'.$layout_data['width'].'" 
							height="'.$layout_data['height'].'" title="'.$layout_data['label'].'">';
					else
						echo '<img src="images/console/icons/'.$layout_data['image'].'.png" 
							title="'.$layout_data['label'].'">';
				}
				echo "</a>";
				
				// Draw label
				echo "<br />";
				echo $layout_data['label'];
				echo "</div>";
			}

			// SINGLE GRAPH (type = 1)
			if ($layout_data['type'] == 1) { // single graph
		
				// Draw image
				echo '<div style="z-index: 1; color: '.$layout_data['label_color'].'; position: absolute; margin-left: '.$layout_data['pos_x'].'px; margin-top:'.$layout_data['pos_y'].'px;" id="layout-data-'.$layout_data['id'].'" class="layout-data">';
				if ($show_links) {
					if (($layout_data['id_layout_linked'] == "") || ($layout_data['id_layout_linked'] == 0)) {
						echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agent.'&tab=data">';
					} else {
						echo '<a href="index.php?sec=visualc&sec2=operation/visual_console/render_view&pure='.$config["pure"].'&id='.$layout_data['id_layout_linked'].'">';
					}
				}
				echo '<img src="reporting/fgraph.php?tipo=sparse&id='.$layout_data['id_agente_modulo'].'&label='.$layout_data['label'].'&height='.$layout_data['height'].'&width='.$layout_data['width'].'&period='.$layout_data['period'].'" title="'.$layout_data['label'].'" border="0">';
				echo "</a>";
				echo "</div>";
				
			// Line, not implemented in editor
			} else if ($layout_data['type'] == 2) {
				$line['id'] = $layout_data['id'];
				$line['x'] = $layout_data['pos_x'];
				$line['y'] = $layout_data['pos_y'];
				$line['width'] = $layout_data['width'];
				$line['height'] = $layout_data['height'];
				$line['color'] = $layout_data['label_color'];
				array_push ($lines, $line);
			}
			
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
		echo "\n".'<script type="text/javascript">'."\n";
		echo 'var lines = Array ();'."\n";
		
		foreach ($lines as $line) {
			echo 'lines.push (eval ('.json_encode ($line)."));\n";
		}
		echo '</script>'."\n";
	}
	// End main div
	echo "</div>";
}

/**
 * @return array Layout data types
 */
function get_layout_data_types () {
	$types = array (0 => __('Static graph'),
			1 => __('Module graph'));
	return $types;
}

?>
