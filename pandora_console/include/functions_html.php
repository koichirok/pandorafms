<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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
 * Prints an array of fields in a popup menu of a form.
 * 
 * Based on choose_from_menu() from Moodle 
 * 
 * @param array Array with dropdown values. Example: $fields["value"] = "label"
 * @param string Select form name
 * @param variant Current selected value. Can be a single value or an
 * array of selected values (in combination with multiple)
 * @param string Javascript onChange code.
 * @param string Label when nothing is selected.
 * @param variant Value when nothing is selected
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Set the input to allow multiple selections (optional, single selection by default).
 * @param bool Whether to sort the options or not (optional, unsorted by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_select ($fields, $name, $selected = '', $script = '', $nothing = '', $nothing_value = '0', $return = false, $multiple = false, $sort = true, $class = '', $disabled = false) {
	$output = "\n";
	
	static $idcounter = array ();
	
	//If duplicate names exist, it will start numbering. Otherwise it won't (for backwards compatibility)
	if (isset ($idcounter[$name])) {
		$idcounter[$name]++;
		$name = $name.$idcounter[$name];
	} else {
		$idcounter[$name] = 0;
	}
	
	$attributes = "";
	if (!empty ($script)) {
		$attributes .= ' onchange="'.$script.'"';
	}
	if (!empty ($multiple)) {
		$attributes .= ' multiple="yes" size="10"';
	}
	if (!empty ($class)) {
		$attributes .= ' class="'.$class.'"';
	}
	if (!empty ($disabled)) {
		$attributes .= ' disabled="disabled"';
	}

	$output .= '<select id="'.$name.'" name="'.$name.'"'.$attributes.'>';

	if ($nothing != '') {
		$output .= '<option value="'.$nothing_value.'"';
		if ($nothing_value == $selected) {
			$output .= ' selected="selected"';
		}
		//You should pass a translated string already
		$output .= '>'.$nothing.'</option>';
	}

	if (!empty ($fields)) {
		if ($sort !== false) {
			asort ($fields);
		}
		foreach ($fields as $value => $label) {
			$output .= '<option value="'.$value.'"';
			if (is_array ($selected) && in_array ($value, $selected)) {
				$output .= ' selected="selected"';
			} elseif (!is_array ($selected) && $value === $selected) { //Needs type comparison otherwise if $selected = 0 and $value = "string" this would evaluate to true
				$output .= ' selected="selected"';
			}
			if ($label === '') {
				$output .= '>'.$value."</option>";
			} else {
				$output .= '>'.$label."</option>";
			}
		}
	}

	$output .= "</select>";

	if ($return)
		return $output;

	echo $output;
}

/**
 * Prints an array of fields in a popup menu of a form based on a SQL query.
 * The first and second columns of the query will be used.
 * 
 * The element will have an id like: "password-$value". Based on choose_from_menu() from Moodle.
 * 
 * @param string $sql SQL sentence, the first field will be the identifier of the option. 
 *      The second field will be the shown value in the dropdown.
 * @param string $name Select form name
 * @param string $selected Current selected value.
 * @param string $script Javascript onChange code.
 * @param string $nothing Label when nothing is selected.
 * @param string $nothing_value Value when nothing is selected
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 * @param bool $multiple Whether to allow multiple selections or not. Single by default
 * @param bool $sort Whether to sort the options or not. Sorted by default.
 *
 * @return string HTML code if return parameter is true.
 */
function print_select_from_sql ($sql, $name, $selected = '', $script = '', $nothing = '', $nothing_value = '0', $return = false, $multiple = false, $sort = true) {
	
	$fields = array ();
	$result = get_db_all_rows_sql ($sql);
	if ($result === false)
		$result = array ();
	
	foreach ($result as $row) {
		$fields[$row[0]] = $row[1];
	}
	
	return print_select ($fields, $name, $selected, $script, $nothing, $nothing_value, $return, $multiple, $sort);
}

/**
 * Render an input text element. Extended version, use print_input_text() to simplify.
 * 
 * @param string $name Input name.
 * @param string $value Input value.
 * @param string $id Input HTML id.
 * @param string $alt Do not use, invalid for text and password. Use print_input_image
 * @param int $size Size of the input.
 * @param int $maxlength Maximum length allowed.
 * @param bool $disabled Disable the button (optional, button enabled by default).
 * @param string $script JavaScript to attach to this 
 * @param mixed $attributes Attributes to add to this tag. Should be an array for correction.
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 * @param bool $password Whether it is a password input or not. Not password by default.
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_text_extended ($name, $value, $id, $alt, $size, $maxlength, $disabled, $script, $attributes, $return = false, $password = false) {
	static $idcounter = 0;
	
	++$idcounter;
	
	$valid_attrs = array ("accept", "disabled", "maxlength", "name", "readonly", "size", "value",
					"accesskey", "class", "dir", "id", "lang", "style", "tabindex", "title", "xml:lang",
					"onfocus", "onblur", "onselect", "onchange", "onclick", "ondblclick", "onmousedown", 
					"onmouseup", "onmouseover", "onmousemove", "onmouseout", "onkeypress", "onkeydown", "onkeyup");

	$output = '<input '.($password ? 'type="password" ' : 'type="text" ');

	if ($disabled && (!is_array ($attributes) || !array_key_exists ("disabled", $attributes))) {
		$output .= 'readonly="readonly" ';
	}
				
	if (is_array ($attributes)) {
		foreach ($attributes as $attribute => $value) {
			if (!in_array ($valid_attrs)) {
				continue;
			}
			$output .= $attribute.'="'.$value.'" ';
		}
	} else {
		$output .= trim ($attributes)." ";
		$attributes = array ();
	}
	
	//Attributes specified by function call
	$attrs = array ("name" => "unnamed", "value" => "", "id" => "text-".sprintf ('%04d', $idcounter), "size" => "", "maxlength" => "");

	foreach ($attrs as $attribute => $default) {
		if (array_key_exists ($attribute, $attributes)) {
			continue;
		} //If the attribute was already processed, skip
		
		if ($$attribute) {
			$output .= $attribute.'="'.$$attribute.'" ';
		} elseif ($default != '') {
			$output .= $attribute.'="'.$default.'" ';
		}
	}

	$output .= '/>';
	
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Render an input password element.
 *
 * The element will have an id like: "password-$name"
 * 
 * @param string $name Input name.
 * @param string $value Input value.
 * @param string $alt Alternative HTML string (optional).
 * @param int $size Size of the input (optional).
 * @param int $maxlength Maximum length allowed (optional).
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_password ($name, $value, $alt = '', $size = 50, $maxlength = 0, $return = false) {
	$output = print_input_text_extended ($name, $value, 'password-'.$name, $alt, $size, $maxlength, false, '', '', true, true);

	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input text element.
 *
 * The element will have an id like: "text-$name"
 * 
 * @param string $name Input name.
 * @param string $value Input value.
 * @param string $alt Alternative HTML string (invalid - not used).
 * @param int $size Size of the input (optional).
 * @param int $maxlength Maximum length allowed (optional).
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_text ($name, $value, $alt = '', $size = 50, $maxlength = 0, $return = false) {
	return print_input_text_extended ($name, $value, 'text-'.$name, '', $size, $maxlength, false, '', '', $return);
}

/**
 * Render an input image element.
 *
 * The element will have an id like: "image-$name"
 * 
 * @param string $name Input name.
 * @param string $src Image source.
 * @param string $value Input value.
 * @param string $style HTML style property.
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_image ($name, $src, $value, $style = '', $return = false) {
	static $idcounter = 0;
	
	++$idcounter;
	$output = '<input id="image-'.$name.$idcounter.'" src="'.$src.'" style="'.$style.'" name="'.$name.'" type="image" value="'.$value.'" />';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input hidden element.
 *
 * The element will have an id like: "hidden-$name"
 * 
 * @param string $name Input name.
 * @param string $value Input value.
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_hidden ($name, $value, $return = false) {
	$output = '<input id="hidden-'.$name.'" name="'.$name.'" type="hidden" value="'.$value.'" />';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an submit input button element.
 *
 * The element will have an id like: "submit-$name"
 * 
 * @param string $label Input label.
 * @param string $name Input name.
 * @param bool $disabled Whether to disable by default or not. Enabled by default.
 * @param string $attributes Additional HTML attributes.
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_submit_button ($label = 'OK', $name = '', $disabled = false, $attributes = '', $return = false) {
	if (!$name) {
		$name="unnamed";
	} 
		
	$output = '<input type="submit" id="submit-'.$name.'" name="'.$name.'" value="'. $label .'" '. $attributes;
	if ($disabled)
		$output .= ' disabled="disabled"';
	$output .= ' />';
	if (!$return)
		echo $output;
	
	return $output;
}

/**
 * Render an submit input button element.
 *
 * The element will have an id like: "button-$name"
 * 
 * @param string $label Input label.
 * @param string $name Input name.
 * @param bool $disabled Whether to disable by default or not. Enabled by default.
 * @param string $script JavaScript to attach
 * @param string $attributes Additional HTML attributes.
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_button ($label = 'OK', $name = '', $disabled = false, $script = '', $attributes = '', $return = false) {
	$output = '';

	$output .= '<input type="button" id="button-'.$name.'" name="'.$name.'" value="'. $label .'" onClick="'. $script.'" '.$attributes;
	if ($disabled)
		$output .= ' disabled';
	$output .= ' />';
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render an input textarea element.
 *
 * The element will have an id like: "textarea_$name"
 * 
 * @param string $name Input name.
 * @param int $rows How many rows (height)
 * @param int $columns How many columns (width)
 * @param string $value Text in the textarea
 * @param string $attributes Additional attributes
 * @param bool $return Whether to return an output string or echo now (optional, echo by default). *
 *
 * @return string HTML code if return parameter is true.
 */
function print_textarea ($name, $rows, $columns, $value = '', $attributes = '', $return = false) {
	static $idcounter = array ();
	
	//If duplicate names exist, it will start numbering. Otherwise it won't
	if (isset ($idcounter[$name])) {
		$idcounter[$name]++;
		$name = $name.$idcounter;
	} else {
		$idcounter[$name] = 0;
	}
	
	$output = '<textarea id="textarea_'.$name.'" name="'.$name.'" cols="'.$columns.'" rows="'.$rows.'" '.$attributes.' >';
	$output .= safe_input ($value);
	$output .= '</textarea>';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Print a nicely formatted table. Code taken from moodle.
 *
 * @param object Object with several properties:
 *     $table->head - An array of heading names.
 *     $table->align - An array of column alignments
 *     $table->valign - An array of column alignments
 *     $table->size  - An array of column sizes
 *     $table->wrap - An array of "nowrap"s or nothing
 *     $table->style  - An array of personalized style for each column.
 *     $table->rowstyle  - An array of personalized style of each row.
 *     $table->rowclass  - An array of personalized classes of each row (odd-evens classes will be ignored).
 *     $table->colspan  - An array of colspans of each column.
 *     $table->data[] - An array of arrays containing the data.
 *     $table->width  - A percentage of the page
 *     $table->border  - Border of the table.
 *     $table->tablealign  - Align the whole table (float left or right)
 *     $table->cellpadding  - Padding on each cell
 *     $table->cellspacing  - Spacing between cells
 *     $table->class  - CSS table class
 *	   $table->id - Table ID (useful in JavaScript)
 *	   $table->headclass[] - An array of classes for each heading
 *     $table->title - Title of the table is a single string that will be on top of the table in the head spanning the whole table
 *	   $table->titlestyle - Title style
 *	   $table->titleclass - Title class
 * @param  bool Whether to return an output string or echo now
 *
 * @return string HTML code if return parameter is true.
 */
function print_table (&$table, $return = false) {
	$output = '';
	static $table_count = 0;

	$table_count++;
	if (isset ($table->align)) {
		foreach ($table->align as $key => $aa) {
			if ($aa) {
				$align[$key] = ' text-align:'. $aa.';';
			} else {
				$align[$key] = '';
			}
		}
	}
	if (isset ($table->valign)) {
		foreach ($table->valign as $key => $aa) {
			if ($aa) {
				$valign[$key] = ' vertical-align:'. $aa.';';
			} else {
				$valign[$key] = '';
			}
		}
	}
	if (isset ($table->size)) {
		foreach ($table->size as $key => $ss) {
			if ($ss) {
				$size[$key] = ' width:'. $ss .';';
			} else {
				$size[$key] = '';
			}
		}
	}
	if (isset ($table->style)) {
		foreach ($table->style as $key => $st) {
			if ($st) {
				$style[$key] = ' '. $st .';';
			} else {
				$style[$key] = '';
			}
		}
	}
	if (isset ($table->rowstyle)) {
		foreach ($table->rowstyle as $key => $st) {
			$rowstyle[$key] = ' '. $st .';';
		}
	}
	if (isset ($table->rowclass)) {
		foreach ($table->rowclass as $key => $class) {
			$rowclass[$key] = $class;
		}
	}
	if (isset ($table->colspan)) {
		foreach ($table->colspan as $keyrow => $cspan) {
			foreach ($cspan as $key => $span) {
				$colspan[$keyrow][$key] = ' colspan="'.$span.'"';
			}
		}
	}
	if (empty ($table->width)) {
		$table->width = '80%';
	}
	
	if (empty ($table->border)) {
		$table->border = '0';
	}
	
	if (empty ($table->tablealign) || $table->tablealign != 'left' || $table->tablealign != 'right') {
		$table->tablealign = '';
	} else {
		$table->tablealign = 'style="float:'.$table->tablealign.';"'; //Align is deprecated. Use float instead
	}

	if (!isset ($table->cellpadding)) {
		$table->cellpadding = '4';
	}

	if (!isset ($table->cellspacing)) {
		$table->cellspacing = '4';
	}

	if (empty ($table->class)) {
		$table->class = 'databox';
	}
	
	if (empty ($table->titlestyle)) {
		$table->titlestyle = 'text-align:center;';
	}
	
	$tableid = empty ($table->id) ? 'table'.$table_count : $table->id;

	$output .= '<table width="'.$table->width.'"'.$table->tablealign;
	$output .= ' cellpadding="'.$table->cellpadding.'" cellspacing="'.$table->cellspacing.'"';
	$output .= ' border="'.$table->border.'" class="'.$table->class.'" id="'.$tableid.'">';
	$countcols = 0;
	if (!empty ($table->head)) {
		$countcols = count ($table->head);
		$output .= '<thead><tr>';
		
		if (isset ($table->title)) {
			$output .= '<th colspan="'.$countcols.'"';
			if (isset ($table->titlestyle)) {
				$output .= ' style="'.$table->titlestyle.'"';
			}
			if (isset ($table->titleclass)) {
				$output .= ' class="'.$table->titleclass.'"';
			}
			$output .= '>'.$table->title.'</th></tr><tr>';
		}
		
		foreach ($table->head as $key => $heading) {
			if (!isset ($size[$key])) {
				$size[$key] = '';
			}
			if (!isset ($align[$key])) {
				$align[$key] = '';
			}
			if (!isset ($table->headclass[$key])) {
				$table->headclass[$key] = 'header c'.$key;
			}	
			$output .= '<th class="'.$table->headclass[$key].'" scope="col">'. $heading .'</th>';
		}
		$output .= '</tr></thead>'."\n";
	}

	$output .= '<tbody>'."\n";
	if (!empty ($table->data)) {
		$oddeven = 1;
		foreach ($table->data as $keyrow => $row) {
			
			if (!isset ($rowstyle[$keyrow])) {
				$rowstyle[$keyrow] = '';
			}
			$oddeven = $oddeven ? 0 : 1;
			$class = 'datos'.($oddeven ? "" : "2");
			if (isset ($rowclass[$keyrow])) {
				$class = $rowclass[$keyrow];
			}
			$output .= '<tr id="'.$tableid."-".$keyrow.'" style="'.$rowstyle[$keyrow].'" class="'.$class.'">'."\n";
			/* Special separator rows */
			if ($row == 'hr' and $countcols) {
				$output .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
				continue;
			}
			
			/* It's a normal row */
			foreach ($row as $key => $item) {
				if (!isset ($size[$key])) {
					$size[$key] = '';
				}
				if (!isset ($colspan[$keyrow][$key])) {
					$colspan[$keyrow][$key] = '';
				}
				if (!isset ($align[$key])) {
					$align[$key] = '';
				}
				if (!isset ($valign[$key])) {
					$valign[$key] = '';
				}
				if (!isset ($wrap[$key])) {
					$wrap[$key] = '';
				}
				if (!isset ($style[$key])) {
					$style[$key] = '';
				}
				
				$output .= '<td id="'.$tableid.'-'.$keyrow.'-'.$key.'" style="'. $style[$key].$valign[$key].$align[$key].$size[$key].$wrap[$key] .'" '.$colspan[$keyrow][$key].' class="'.$class.'">'. $item .'</td>'."\n";
			}
			$output .= '</tr>'."\n";
		}
	}
	$output .= '</tbody></table>'."\n";

	if ($return) 
		return $output;
	
	echo $output;
}

/**
 * Render a radio button input. Extended version, use print_radio_button() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Disable the button (optional, button enabled by default).
 * @param string Script to execute when onClick event is triggered (optional).
 * @param string Optional HTML attributes. It's a free string which will be 
	inserted into the HTML tag, use it carefully (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_radio_button_extended ($name, $value, $label, $checkedvalue, $disabled, $script, $attributes, $return = false) {
	static $idcounter = 0;

	$output = '';
	
	$output = '<input type="radio" name="'.$name.'" value="'.$value.'"';
	$htmlid = 'radiobtn'.sprintf ('%04d', ++$idcounter);
	$output .= ' id="'.$htmlid.'"';

	if ($value == $checkedvalue) {
		 $output .= ' checked="checked"';
	}
	if ($disabled) {
		 $output .= ' disabled="disabled"';
	}
	if ($script != '') {
		 $output .= ' onClick="'. $script . '"';
	}
	$output .= ' ' . $attributes ;
	$output .= ' />';
	
	if ($label != '') {
		$output .= '<label for="'.$htmlid.'">'.  $label .'</label>' .  "\n";
	}
	
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render a radio button input.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Label to add after the radio button (optional).
 * @param string Checked and selected value, the button will be selected if it matches $value (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_radio_button ($name, $value, $label = '', $checkedvalue = '', $return = false) {
	$output = print_radio_button_extended ($name, $value, $label, $checkedvalue, false, '', '', true);
	
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render a checkbox button input. Extended version, use print_checkbox() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Disable the button  (optional, button enabled by default).
 * @param string Script to execute when onClick event is triggered (optional).
 * @param string Optional HTML attributes. It's a free string which will be 
	inserted into the HTML tag, use it carefully (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_checkbox_extended ($name, $value, $checked, $disabled, $script, $attributes, $return = false) {
	static $idcounter = array ();
	
	//If duplicate names exist, it will start numbering. Otherwise it won't
	if (isset ($idcounter[$name])) {
		$idcounter[$name]++;
		$name = $name.$idcounter[$name];
	} else {
		$idcounter[$name] = 0;
	}
		
	$output = '<input name="'.$name.'" type="checkbox" value="'.$value.'" '. ($checked ? 'checked="checked"': '');
	$output .= ' id="checkbox-'.str_replace (array ("[", "]"), '', $name).'"'; //[ and ] are some of those things that can't be in an id string. Add more where necessary
	
	if ($script != '') {
		 $output .= ' onClick="'. $script . '"';
	}
	
	if ($disabled) {
		 $output .= ' disabled="disabled"';
	}
	
	$output .= ' />';
	$output .= "\n";
	if ($return)
		return $output;
	echo $output;
}

/**
 * Render a checkbox button input.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_checkbox ($name, $value, $checked = false, $return = false) {
	$output = print_checkbox_extended ($name, $value, (bool) $checked, false, '', '', true);

	if ($return)
		return $output;
	echo $output;
}

/**
 * Prints an image HTML element.
 *
 * @param string $src Image source filename.
 * @param bool $return Whether to return or print
 * @param array $options Array with optional HTML options to set. At this moment, the 
 * following options are supported: alt, style, title, width, height, class.
 *
 * @return string HTML code if return parameter is true.
 */
function print_image ($src, $return = false, $options = false) {
	$output = '<img src="'.safe_input ($src).'" '; //safe input necessary to strip out html entities correctly
	$style = '';
	
	if (!empty ($options)) {
		//Deprecated or value-less attributes
		if (isset ($options["align"])) {
			$style .= 'align:'.$options["align"].';'; //Align is deprecated, use styles.
		}
		
		if (isset ($options["border"])) {
			$style .= 'border:'.$options["border"].'px;'; //Border is deprecated, use styles
		}
				
		if (isset ($options["hspace"])) {
			$style .= 'margin-left:'.$options["hspace"].'px;'; //hspace is deprecated, use styles
			$style .= 'margin-right:'.$options["hspace"].'px;';
		}
		
		if (isset ($options["ismap"])) {
			$output .= 'ismap="ismap" '; //Defines the image as a server-side image map
		}
		
		if (isset ($options["vspace"])) {
			$style .= 'margin-top:'.$options["vspace"].'px;'; //hspace is deprecated, use styles
			$style .= 'margin-bottom:'.$options["vspace"].'px;';
		}
				
		if (isset ($options["style"])) {
			$style .= $options["style"]; 
		}
		
		//Valid attributes (invalid attributes get skipped)
		$attrs = array ("height", "longdesc", "usemap","width","id","class","title","lang","xml:lang", 
						"onclick", "ondblclick", "onmousedown", "onmouseup", "onmouseover", "onmousemove", 
						"onmouseout", "onkeypress", "onkeydown", "onkeyup");
		
		foreach ($attrs as $attribute) {
			if (isset ($options[$attribute])) {
				$output .= $attribute.'="'.safe_input ($options[$attribute]).'" ';
			}
		}
	}
	
	if (!isset ($options["alt"]) && isset ($options["title"])) {
		$options["alt"] = $options["title"]; //Set alt to title if it's not set
	} elseif (!isset ($options["alt"])) {
		$options["alt"] = ''; //Alt is mandatory, empty string will do
	}

	if (!empty ($style)) {
		$output .= 'style="'.$style.'" ';
	}
	
	$output .= 'alt="'.safe_input ($options['alt']).'" />';
	
	if (!$return) {
		echo $output;
	}
	
	return $output;
}

/**
 * Render an input text element. Extended version, use print_input_text() to simplify.
 *
 * @param string Input name.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param array An array with optional HTML parameters. 
 *	Key size: HTML size attribute.
 *	Key disabled: Whether to disable the input or not.
 *	Key class: HTML class
 */
function print_input_file ($name, $return = false, $options = false) {
	$output = '';
	
	$output .= '<input type="file" value="" name="'.$name.'" id="file-'.$name.'" ';
	
	if ($options) {
		if (isset ($options['size']))
			$output .= 'size="'.$options['size'].'"';
		
		if (isset ($options['disabled']))
			$output .= 'disabled="disabled"';
		
		if (isset ($options['class']))
			$output .= 'class="'.$options['class'].'"';
	}
	
	$output .= ' />';

	if ($return)
		return $output;
	echo $output;
}

/**
 * Render a label for a input elemennt.
 *
 * @param string Label text.
 * @param string Input id to refer.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param array An array with optional HTML parameters. 
 *	Key html: Extra HTML to add after the label.
 *	Key class: HTML class
 */
function print_label ($text, $id, $return = false, $options = false) {
	$output = '';
	
	$output .= '<label id="label-'.$id.'" ';
	
	if ($options) {
		if (isset ($options['class']))
			$output .= 'class="'.$options['class'].'" ';
	}
	
	$output .= 'for="'.$id.'" >';
	$output .= $text;
	$output .= '</label>';
	
	if ($options) {
		if (isset ($options['html']))
			$output .= $options['html'];
	}
	
	if ($return)
		return $output;
	
	echo $output;
}
?>
