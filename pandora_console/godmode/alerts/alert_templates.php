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

require_once ('include/functions_alerts.php');

check_login ();

if (is_ajax ()) {
	$get_template_tooltip = (bool) get_parameter ('get_template_tooltip');
	
	if ($get_template_tooltip) {
		$id_template = (int) get_parameter ('id_template');
		$template = get_alert_template ($id_template);
		if ($template === false)
			return;
		
		echo '<h3>'.$template['name'].'</h3>';
		echo '<strong>'.__('Type').': </strong>';
		echo get_alert_templates_type_name ($template['type']);
		
		echo '<br />';
		echo print_alert_template_example ($template['id'], true);
		
		echo '<br />';
		
		if ($template['description'] != '') {
			echo '<strong>'.__('Description').':</strong><br />';
			echo $template['description'];
			echo '<br />';
		}
		
		echo '<strong>'.__('Priority').':</strong> ';
		echo get_priority_name ($template['priority']);
		echo '<br />';
		
		if ($template['monday'] && $template['tuesday']
			&& $template['wednesday'] && $template['thursday']
			&& $template['friday'] && $template['saturday']
			&& $template['sunday']) {
			
			/* Everyday */
			echo '<strong>'.__('Everyday').'</strong><br />';
		}
		else {
			$days = array ('monday' => __('Monday'),
				'tuesday' => __('Tuesday'),
				'wednesday' => __('Wednesday'),
				'thursday' => __('Thursday'),
				'friday' => __('Friday'),
				'saturday' => __('Saturday'),
				'sunday' => __('Sunday'));
			
			echo '<strong>'.__('Days').'</strong>: '.__('Every').' ';
			$actives = array ();
			foreach ($days as $day => $name) {
				if ($template[$day])
					array_push ($actives, $name);
			}
			
			$last = array_pop ($actives);
			if (count ($actives)) {
				echo implode (', ', $actives);
				echo ' '.__('and').' ';
			}
			echo $last;
			echo "<br />";
			
		}
		echo "<strong>" . __('Time threshold') . ": </strong>";
		echo human_time_description_raw($template['time_threshold']);
		echo '<br />';
		
		if ($template['time_from'] != $template['time_to']) {
			echo '<strong>'.__('From').'</strong> ';
			echo $template['time_from'];
			echo ' <strong>'.__('to').'</strong> ';
			echo $template['time_to'];
			echo '<br />';
		}
		
		return;
	}
	
	return;
}

if (! give_acl ($config['id_user'], 0, "LM")) {
	pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

$update_template = (bool) get_parameter ('update_template');
$delete_template = (bool) get_parameter ('delete_template');

// This prevents to duplicate the header in case delete_templete action is performed
if (!$delete_template) 
// Header
print_page_header (__('Alerts')." &raquo; ". __('Alert templates'), "images/god2.png", false, "", true);

if ($update_template) {
	$id = (int) get_parameter ('id');
	
	$recovery_notify = (bool) get_parameter ('recovery_notify');
	$field2_recovery = (string) get_parameter ('field2_recovery');
	$field3_recovery = (string) get_parameter ('field3_recovery');
	
	$result = update_alert_template ($id,
		array ('recovery_notify' => $recovery_notify,
			'field2_recovery' => $field2_recovery,
			'field3_recovery' => $field3_recovery));
	
	print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}

// If user tries to delete a template with group=ALL then must have "PM" access privileges
if ($delete_template) {
	$id = get_parameter ('id');
	$al_template = get_alert_template($id);

	if ($al_template !== false){
		if ($al_template['id_group'] == 0){
			if (! give_acl ($config['id_user'], 0, "PM")) {
				pandora_audit("ACL Violation",
					"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}else
				// Header
				print_page_header (__('Alerts')." &raquo; ". __('Alert templates'), "images/god2.png", false, "", true);
		}else
			// Header
			print_page_header (__('Alerts')." &raquo; ". __('Alert templates'), "images/god2.png", false, "", true);		
	}else
		// Header
		print_page_header (__('Alerts')." &raquo; ". __('Alert templates'), "images/god2.png", false, "", true);

	$result = delete_alert_template ($id);
	
	if ($result) {
		pandora_audit("Template alert management", "Delete alert template " . $id);
	}
	else {
		pandora_audit("Template alert management", "Fail try to delete alert template " . $id);
	}
	
	print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

$url = get_url_refresh (array ('offset' => false));

$search_string = (string) get_parameter ('search_string');
$search_type = (string) get_parameter ('search_type');

$table->width = '600px';
$table->data = array ();
$table->head = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';

$table->data[0][0] = __('Type');
$table->data[0][1] = print_select (get_alert_templates_types (), 'search_type',
	$search_type, '', __('All'), '', true, false, false);
$table->data[0][2] = __('Search');
$table->data[0][3] = print_input_text ('search_string', $search_string, '', 25,
	255, true);
$table->data[0][4] = '<div class="action-buttons">';
$table->data[0][4] .= print_submit_button (__('Search'), 'search', false,
	'class="sub search"', true);
$table->data[0][4] .= '</div>';

echo '<form method="post" action="'.$url.'">';
print_table ($table);
echo '</form>';

unset ($table);

$filter = array ();
if ($search_type != '')
	$filter['type'] = $search_type;
if ($search_string)
	$filter[] = '(name LIKE "%'.$search_string.'%" OR description LIKE "%'.$search_string.'%" OR value LIKE "%'.$search_string.'%")';
$total_templates = get_alert_templates ($filter, array ('COUNT(*) AS total'));
$total_templates = $total_templates[0]['total'];
$filter['offset'] = (int) get_parameter ('offset');
$filter['limit'] = (int) $config['block_size'];
$templates = get_alert_templates ($filter,
	array ('id', 'name', 'description', 'type', 'id_group'));
if ($templates === false)
	$templates = array ();

$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Group');
//$table->head[2] = __('Description');
$table->head[3] = __('Type');
$table->head[4] = __('Op.');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[4] = '50px';
$table->align = array ();
$table->align[4] = 'center';

$rowPair = true;
$iterator = 0;
foreach ($templates as $template) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&id='.$template['id'].'">'.
		$template['name'].'</a>';

	$data[1] = print_group_icon ($template["id_group"], true) .'&nbsp;'. get_group_name ($template['id_group'], true);
	$data[3] = get_alert_templates_type_name ($template['type']);

	$data[4] = '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template" style="display: inline; float: left">';
	$data[4] .= print_input_hidden ('duplicate_template', 1, true);
	$data[4] .= print_input_hidden ('source_id', $template['id'], true);
	$data[4] .= print_input_image ('dup', 'images/copy.png', 1, '', true, array ('title' => __('Duplicate')));
	$data[4] .= '</form> ';
	
	$data[4] .= '<form method="post" style="display: inline; float: right" onsubmit="if (!confirm(\''.__('Are you sure?').'\')) return false;">';
	$data[4] .= print_input_hidden ('delete_template', 1, true);
	$data[4] .= print_input_hidden ('id', $template['id'], true);
	$data[4] .= print_input_image ('del', 'images/cross.png', 1, '', true, array ('title' => __('Delete')));
	$data[4] .= '</form> ';
	
	array_push ($table->data, $data);
}

pagination ($total_templates, $url);
if (isset($data)){
	print_table ($table);
} else {
	echo "<div class='nf'>".__('No alert templates defined')."</div>";
}
echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template">';
print_submit_button (__('Create'), 'create', false, 'class="sub next"');
print_input_hidden ('create_alert', 1);
echo '</form>';
echo '</div>';

?>
