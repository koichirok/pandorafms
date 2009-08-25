<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 */

/**
 * Prints a complete menu structure.
 *
 * @param array Menu structure to print.
 */
function print_menu (&$menu) {
	static $idcounter = 0;
	
	echo '<div class="menu">';
	
	$sec = (string) get_parameter ('sec');
	$sec2 = (string) get_parameter ('sec2');
	
	echo '<ul'.(isset ($menu['class']) ? ' class="'.$menu['class'].'"' : '').'>';
	
	foreach ($menu as $mainsec => $main) {
		if ($mainsec == 'class')
			continue;
		
		if (! isset ($main['id'])) {
			$id = 'menu_'.++$idcounter;
		} else {
			$id = $main['id'];
		}
		
		$submenu = false;
		$classes = array ('menu_icon');
		if (isset ($main["sub"])) {
			$classes[] = 'has_submenu';
			$submenu = true;
		}
		if (!isset ($main["refr"]))
			$main["refr"] = 0;
		
		if ($sec == $mainsec) {
			$classes[] = 'selected';
		} else {
			$classes[] = 'not_selected';
		}
		
		$output = '';
		
		if (! $submenu) {
			$main["sub"] = array (); //Empty array won't go through foreach
		}
		
		$submenu_output = '';
		$selected = false;
		$visible = false;
		
		foreach ($main["sub"] as $subsec2 => $sub) {
			//Set class
			if ($sec2 == $subsec2 && isset ($sub[$subsec2]["options"])
				&& (get_parameter_get ($sub[$subsec2]["options"]["name"]) == $sub[$subsec2]["options"]["value"])) {
				//If the subclass is selected and there are options and that options value is true
				$class = 'submenu_selected';
				$selected = true;
				$visible = true;
			} elseif ($sec2 == $subsec2 && !isset ($sub[$subsec2]["options"])) {
				//If the subclass is selected and there are no options
				$class = 'submenu_selected';
				$selected = true;
				$visible = true;
			} else {
				//Else it's not selected
				$class = 'submenu_not_selected';
			}
			
			if (! isset ($sub["refr"])) {
				$sub["refr"] = 0;
			} 
			
			if (isset ($sub["type"]) && $sub["type"] == "direct") {
				//This is an external link
				$submenu_output .= '<li class="'.$class.'"><a href="'.$subsec2.'">'.$sub["text"]."</a></li>";
			} else {
				//This is an internal link
				if (isset ($sub[$subsec2]["options"])) {
					$link_add = "&amp;".$sub[$subsec2]["options"]["name"]."=".$sub[$subsec2]["options"]["value"];
				} else {
					$link_add = "";
				}
				$submenu_output .= '<li'.($class ? ' class="'.$class.'"' : '').'>';
				$submenu_output .= '<a href="index.php?sec='.$mainsec.'&amp;sec2='.$subsec2.($main["refr"] ? '&amp;refr='.$main["refr"] : '').$link_add.'">'.$sub["text"].'</a>';
				$submenu_output .= '</li>';
			}
		}
		
		//Print out the first level
		$output .= '<li class="'.implode (" ", $classes).'" id="icon_'.$id.'">';
		$output .= '<a href="index.php?sec='.$mainsec.'&amp;sec2='.$main["sec2"].($main["refr"] ? '&amp;refr='.$main["refr"] : '').'">'.$main["text"].'</a><img class="toggle" src="include/styles/images/toggle.png" alt="toggle" />';
		if ($submenu_output != '') {
			//WARNING: IN ORDER TO MODIFY THE VISIBILITY OF MENU'S AND SUBMENU'S (eg. with cookies) YOU HAVE TO ADD TO THIS ELSEIF. DON'T MODIFY THE CSS
			if ($visible || in_array ("selected", $classes)) {
				$visible = true;
			}
			$output .= '<ul class="submenu'.($visible ? '' : ' invisible').'">';
			$output .= $submenu_output;
			$output .= '</ul>';
		}
		$output .= '</li>';
		echo $output;
	}
	echo '</ul>';
	//Invisible UL for adding border-top
	echo '<ul style="height: 0px;"><li>&nbsp;</li></ul></div>';
}

?>
