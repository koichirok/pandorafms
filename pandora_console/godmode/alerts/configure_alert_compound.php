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

check_login ();

if (! check_acl ($config['id_user'], 0, "AW")) {
	pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	return;
}

$id = (int) get_parameter ('id');
$id_agent = (int) get_parameter ('id_agent');

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

function print_alert_compound_steps ($step, $id) {
	echo '<ol class="steps">';
	
	/* Step 1 */
	if ($step == 1)
		echo '<li class="first current">';
	elseif ($step > 1)
		echo '<li class="first visited">';
	else
		echo '<li class="first">';
	
	if ($id) {
		echo '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound&id='.$id.'">';
		echo __('Step').' 1 &raquo; ';
		echo '<span>'.__('Conditions').'</span>';
		echo '</a>';
	} else {
		echo __('Step').' 1 &raquo; ';
		echo '<span>'.__('Conditions').'</span>';
	}
	echo '</li>';
	
	/* Step 2 */
	if ($step == 2)
		echo '<li class="current">';
	elseif ($step > 2)
		echo '<li class="visited">';
	else
		echo '<li>';
	
	if ($id) {
		echo '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound&id='.$id.'&step=2">';
		echo __('Step').' 2 &raquo; ';
		echo '<span>'.__('Firing').'</span>';
		echo '</a>';
	} else {
		echo __('Step').' 2 &raquo; ';
		echo '<span>'.__('Firing').'</span>';
	}
	
	/* Step 3 */
	if ($step == 3)
		echo '<li class="last current">';
	elseif ($step > 3)
		echo '<li class="last visited">';
	else
		echo '<li class="last">';
	
	if ($id) {
		echo '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound&id='.$id.'&step=3">';
		echo __('Step').' 3 &raquo; ';
		echo '<span>'.__('Recovery').'</span>';
		echo '</a>';
	} else {
		echo __('Step').' 3 &raquo; ';
		echo '<span>'.__('Recovery').'</span>';
	}
	
	echo '</ol>';
	echo '<div id="steps_clean"> </div>';
}

function update_compound ($step) {
	$id = (int) get_parameter ('id');
	
	if (empty ($id))
		return false;
	
	if ($step == 1) {
		$id_agent = (int) get_parameter ('id_agent');
		$name = (string) get_parameter ('name');
		$description = (string) get_parameter ('description');
		
		$result = update_alert_compound ($id,
			array ('name' => $name,
				'description' => $description,
				'id_agent' => $id_agent));
		/* Temporary disable the alert for update all elements */
		set_alerts_compound_disable ($id, true);
		/* Delete all elements of the alert and create them again */
		delete_alert_compound_elements ($id);
		$alerts = (array) get_parameter ('conditions');
		$operations = (array) get_parameter ('operations');
		
		foreach ($alerts as $id_alert) {
			add_alert_compound_element ($id, (int) $id_alert, $operations[$id_alert]);
		}
		
		set_alerts_compound_disable ($id, false);
	} elseif ($step == 2) {
		$monday = (bool) get_parameter ('monday');
		$tuesday = (bool) get_parameter ('tuesday');
		$wednesday = (bool) get_parameter ('wednesday');
		$thursday = (bool) get_parameter ('thursday');
		$friday = (bool) get_parameter ('friday');
		$saturday = (bool) get_parameter ('saturday');
		$sunday = (bool) get_parameter ('sunday');
		$time_from = (string) get_parameter ('time_from');
		$time_from = date ("H:s:00", strtotime ($time_from));
		$time_to = (string) get_parameter ('time_to');
		$time_to = date ("H:s:00", strtotime ($time_to));
		$threshold = (int) get_parameter ('threshold');
		$max_alerts = (int) get_parameter ('max_alerts');
		$min_alerts = (int) get_parameter ('min_alerts');
		if ($threshold == -1)
			$threshold = (int) get_parameter ('other_threshold');
		
		$values = array ('monday' => $monday,
			'tuesday' => $tuesday,
			'wednesday' => $wednesday,
			'thursday' => $thursday,
			'friday' => $friday,
			'saturday' => $saturday,
			'sunday' => $sunday,
			'time_from' => $time_from,
			'time_to' => $time_to,
			'time_threshold' => $threshold,
			'max_alerts' => $max_alerts,
			'min_alerts' => $min_alerts
			);
		
		$result = update_alert_compound ($id, $values);
		
		/* Update actions */
		$actions = (array) get_parameter ('actions');
		
		foreach ($actions as $id_action) {
			/* TODO: fires_min and fires_max missing */
			add_alert_compound_action ($id, (int) $id_action);
		}
	} elseif ($step == 3) {
		$recovery_notify = (bool) get_parameter ('recovery_notify');
		$field2_recovery = (bool) get_parameter ('field2_recovery');
		$field3_recovery = (bool) get_parameter ('field3_recovery');
	
		$result = update_alert_compound ($id,
			array ('recovery_notify' => $recovery_notify,
				'field2_recovery' => $field2_recovery,
				'field3_recovery' => $field3_recovery));
	} else {
		return false;
	}
	
	return $result;
}

/* We set here the number of steps */
define ('LAST_STEP', 3);

$step = (int) get_parameter ('step', 1);

$create_compound = (bool) get_parameter ('create_compound');
$update_compound = (bool) get_parameter ('update_compound');

$name = '';
$description = '';
$time_from = '12:00';
$time_to = '12:00';
$monday = true;
$tuesday = true;
$wednesday = true;
$thursday = true;
$friday = true;
$saturday = true;
$sunday = true;
$default_action = 0;
$field1 = '';
$field2 = '';
$field3 = '';
$min_alerts = 0;
$max_alerts = 1;
$threshold = 300;
$recovery_notify = false;
$field2_recovery = '';
$field3_recovery = '';

if ($id && ! $create_compound) {
	$compound = get_alert_compound ($id);
	$name = $compound['name'];
	$description = $compound['description'];
	$time_from = $compound['time_from'];
	$time_to = $compound['time_to'];
	$monday = (bool) $compound['monday'];
	$tuesday = (bool) $compound['tuesday'];
	$wednesday = (bool) $compound['wednesday'];
	$thursday = (bool) $compound['thursday'];
	$friday = (bool) $compound['friday'];
	$saturday = (bool) $compound['saturday'];
	$sunday = (bool) $compound['sunday'];
	$max_alerts = $compound['max_alerts'];
	$min_alerts = $compound['min_alerts'];
	$threshold = $compound['time_threshold'];
	$recovery_notify = $compound['recovery_notify'];
	$field2_recovery = $compound['field2_recovery'];
	$field3_recovery = $compound['field3_recovery'];
	$id_agent = $compound['id_agent'];
	$id_group = get_agent_group ($id_agent);
	if (! check_acl ($config['id_user'], $id_group, "AW")) {
		pandora_audit("ACL Violation",
			"Trying to access Alert Management");
		require ("general/noaccess.php");
		return;
	}
}

// Header
ui_print_page_header (__('Alerts').' &raquo; '.__('Configure correlated alert'), "images/god2.png", false, "", true);

if ($create_compound) {
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	
	$result = create_alert_compound ($name, $id_agent,
		array ('description' => $description));
	
	ui_print_result_message ($result,
		__('Successfully created'),
		__('Could not be created'));
	/* Go to previous step in case of error */
	if ($result === false) {
		$step = $step - 1;
	} else {
		$id = $result;
		$alerts = (array) get_parameter ('conditions');
		$operations = (array) get_parameter ('operations');
		
		foreach ($alerts as $id_alert) {
			add_alert_compound_element ($id, (int) $id_alert, $operations[$id_alert]);
		}
	}
}

if ($update_compound) {
	$result = update_compound ($step - 1);
	
	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
	/* Go to previous step in case of error */
	if ($result === false) {
		$step = $step - 1;
	}
}

print_alert_compound_steps ($step, $id);

$groups = get_user_groups ();

$table->id = 'compound';
$table->width = '90%';
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align: top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->size[2] = '20%';
if ($step == 2) {
	/* Firing conditions and events */
	$threshold_values = get_alert_compound_threshold_values ();
	if (in_array ($threshold, array_keys ($threshold_values))) {
		$table->style['other_label'] = 'display:none; font-weight: bold';
		$table->style['other_input'] = 'display:none';
		$threshold_selected = $threshold;
	} else {
		$table->style['other_label'] = 'font-weight: bold';
		$threshold_selected = -1;
	}
	
	if ($default_action == 0) {
		$table->rowstyle = array ();
		$table->rowstyle['field1'] = 'display: none';
		$table->rowstyle['field2'] = 'display: none';
		$table->rowstyle['field3'] = 'display: none';
		$table->rowstyle['preview'] = 'display: none';
	}
	$table->colspan = array ();
	$table->colspan[0][1] = 3;
	$table->colspan[4][1] = 3;
	$table->colspan['actions'][1] = 3;
	$table->colspan['field1'][1] = 3;
	$table->colspan['field2'][1] = 3;
	$table->colspan['field3'][1] = 3;
	$table->colspan['preview'][1] = 3;
	
	$table->data[0][0] = __('Days of week');
	$table->data[0][1] = __('Mon');
	$table->data[0][1] .= print_checkbox ('monday', 1, $monday, true);
	$table->data[0][1] .= __('Tue');
	$table->data[0][1] .= print_checkbox ('tuesday', 1, $tuesday, true);
	$table->data[0][1] .= __('Wed');
	$table->data[0][1] .= print_checkbox ('wednesday', 1, $wednesday, true);
	$table->data[0][1] .= __('Thu');
	$table->data[0][1] .= print_checkbox ('thursday', 1, $thursday, true);
	$table->data[0][1] .= __('Fri');
	$table->data[0][1] .= print_checkbox ('friday', 1, $friday, true);
	$table->data[0][1] .= __('Sat');
	$table->data[0][1] .= print_checkbox ('saturday', 1, $saturday, true);
	$table->data[0][1] .= __('Sun');
	$table->data[0][1] .= print_checkbox ('sunday', 1, $sunday, true);
	
	$table->data[1][0] = __('Time from');
	$table->data[1][1] = print_input_text ('time_from', $time_from, '', 7, 7,
		true);
	$table->data[1][2] = __('Time to');
	$table->data[1][3] = print_input_text ('time_to', $time_to, '', 7, 7,
		true);
	
	$table->data['threshold'][0] = __('Time threshold');
	$table->data['threshold'][1] = print_select ($threshold_values,
		'threshold', $threshold_selected, '', '', '', true, false, false);
	$table->data['threshold']['other_label'] = __('Other value');
	$table->data['threshold']['other_input'] = print_input_text ('other_threshold',
		$threshold, '', 5, 7, true);
	$table->data['threshold']['other_input'] .= ' '.__('seconds');
	
	$table->data[3][0] = __('Min. number of alerts');
	$table->data[3][1] = print_input_text ('min_alerts', $min_alerts, '',
		5, 7, true);
	$table->data[3][2] = __('Max. number of alerts');
	$table->data[3][3] = print_input_text ('max_alerts', $max_alerts, '',
		5, 7, true);
	
	$table->data[4][0] = __('Actions');
	$table->data[4][1] = print_select_from_sql ('SELECT id, name FROM talert_actions ORDER BY name',
		'action', '', '', __('Select'), 0, true, false, false).' ';
	$table->data[4][1] .= print_button (__('Add'), 'add_action', false, '',
		'class="sub next"', true);
	$table->data[4][1] .=  '<br />';
	/* TODO: Finish fires_max and fires_min support */
/*	$table->data[4][1] .=  '<span><a href="" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
	$table->data[4][1] .=  '<span class="advanced_actions invisible">';
	$table->data[4][1] .=  __('From').' ';
	$table->data[4][1] .= print_input_text ('fires_min', 0, '', 4, 10, true);
	$table->data[4][1] .=  ' '.__('to').' ';
	$table->data[4][1] .= print_input_text ('fires_max', 0, '', 4, 10, true);
	$table->data[4][1] .=  ' '.__('matches of the alert');
	$table->data[4][1] .=  ui_print_help_icon("alert-matches", true);
	$table->data[4][1] .=  '</span>';
*/	
	$table->data['actions'][0] = __('Assigned actions');
	$table->data['actions'][1] = '<ul id="alert_actions">';
	if ($id) {
		$actions = get_alert_compound_actions ($id);
		if (empty ($actions))
			$table->rowstyle['actions'] = 'display: none';
		foreach ($actions as $action) {
			$table->data['actions'][1] .= '<li>';
			$table->data['actions'][1] .= $action['name'];
			$table->data['actions'][1] .= ' <em>(';
			if ($action['fires_min'] == $action['fires_max']) {
				if ($action['fires_min'] == 0)
					$table->data['actions'][1] .= __('Always');
				else
					$table->data['actions'][1] .= __('On').' '.$action['fires_min'];
			} else {
				if ($action['fires_min'] == 0)
					$table->data['actions'][1] .= __('Until').' '.$action['fires_max'];
				else
					$table->data['actions'][1] .= __('From').' '.$action['fires_min'].
						' '.__('to').' '.$action['fires_max'];
			}
			$table->data['actions'][1] .= ')</em></li>';
		}
	}
	$table->data['actions'][1] .= '</ul>';
	
} else if ($step == 3) {
	/* Alert recover */
	if (! $recovery_notify) {
		$table->rowstyle = array ();
		$table->rowstyle['field2'] = 'display:none;';
		$table->rowstyle['field3'] = 'display:none';
	}
	$table->data[0][0] = __('Alert recovery');
	$values = array (false => __('Disabled'), true => __('Enabled'));
	$table->data[0][1] = print_select ($values,
		'recovery_notify', $recovery_notify, '', '', '', true, false,
		false);
	
	$table->data['field2'][0] = __('Field 2');
	$table->data['field2'][1] = print_input_text ('field2_recovery',
		$field2_recovery, '', 35, 255, true);
	
	$table->data['field3'][0] = __('Field 3');
	$table->data['field3'][1] = print_textarea ('field3_recovery', 10, 30,
		$field3_recovery, '', true);
} else {
	/* Step 1 by default */
	$table->size = array ();
	$table->size[0] = '20%';
	$table->data = array ();
	$table->rowstyle = array ();
	$table->rowstyle['value'] = 'display: none';
	$table->rowstyle['max'] = 'display: none';
	$table->rowstyle['min'] = 'display: none';
	$table->rowstyle['conditions'] = 'display: none';
	
	$show_matches = false;
	if ($id) {
		$table->rowstyle['conditions'] = '';
	}

	$table->data[0][0] = __('Name');
	$table->data[0][1] = print_input_text ('name', $name, '', 35, 255, true);
	
	$table->data[1][0] = __('Assigned to');
	$table->data[1][1] = print_select (get_group_agents (array_keys ($groups)),
		'id_agent', $id_agent, '', __('Select'), 0, true);
	$table->data[2][0] = __('Description');
	$table->data[2][1] =  print_textarea ('description', 10, 30,
		$description, '', true);

	$table->data[3][0] = __('Condition');
	$table->data[3][0] .= '<a name="condition" />';
	$table->colspan[3][0] = 2;
	
	$table_alerts->id = 'conditions_list';
	$table_alerts->width = '100%';
	$table_alerts->data = array ();
	$table_alerts->head = array ();
	$table_alerts->head[0] = '';
	$table_alerts->head[1] = __('Agent');
	$table_alerts->head[2] = __('Module');
	$table_alerts->head[3] = __('Alert');
	$table_alerts->head[4] = __('Operator');
	$table_alerts->size = array ();
	$table_alerts->size[0] = '20px';
	$table_alerts->size[1] = '20%';
	$table_alerts->size[2] = '40%';
	$table_alerts->size[3] = '40%';
	$table_alerts->size[4] = '10%';
	
	if ($id) {
		$conditions = get_alert_compound_elements ($id);
		if ($conditions === false)
			$conditions = array ();
		foreach ($conditions as $condition) {
			$data = array ();
			
			$alert = get_alert_agent_module ($condition['id_alert_template_module']);
			$data[0] = '<a href="#" class="remove_alert" id="alert-'.$alert['id'].'" />';
			$data[0] .= print_image("images/delete.png", true);
			$data[0] .= '</a>';
			$idAgent = get_agent_module_id($alert['id_agent_module']);
			$nameAgent = get_agent_name($idAgent);
			$data[1] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $idAgent . '">' . $nameAgent . '</a>';
			$data[2] = get_alert_template_name ($alert['id_alert_template']);
			$data[3] = get_agentmodule_name ($alert['id_agent_module']);
			if ($condition['operation'] == 'NOP') {
				$data[4] = print_input_hidden ('operations['.$alert['id'].']', 'NOP', true);
			} else {
				$data[4] = print_select (get_alert_compound_operations (),
					'operations['.$alert['id'].']', $condition['operation'], '', '', '', true);
			}
			$data[4] .= print_input_hidden ("conditions[]", $alert['id'], true);
			
			array_push ($table_alerts->data, $data);
		}
	}
	
	$table->data['conditions'][1] = print_table ($table_alerts, true);
	$table->colspan['conditions'][1] = 2;
}

/* If it's the last step it will redirect to compound lists */
if ($step >= LAST_STEP) {
	echo '<form method="post" id="alert_form" action="index.php?sec=galertas&sec2=godmode/alerts/alert_compounds">';
} else {
	echo '<form method="post" id="alert_form">';
}
print_table ($table);

echo '<div id="message" class="invisible error">&nbsp;</div>';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	print_input_hidden ('id', $id);
	print_input_hidden ('update_compound', 1);
} else {
	print_input_hidden ('create_compound', 1);
}

if ($step >= LAST_STEP) {
	print_submit_button (__('Finish'), 'finish', false, 'class="sub upd"');
} else {
	print_input_hidden ('step', $step + 1);
	print_submit_button (__('Next'), 'next', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';

/* Show alert search when we're on the first step */
if ($step == 1) {
	echo '<h3>'.__('Add condition').'</h3>';
	
	$id_group = (int) get_parameter ('id_group');
	
	$table->id = 'alert_search';
	$table->data = array ();
	$table->head = array ();
	$table->size = array ();
	$table->size[0] = '10%';
	$table->size[1] = '40%';
	$table->size[2] = '10%';
	$table->size[3] = '40%';
	
	$table->data[0][0] = __('Group');
	$table->data[0][1] = print_select_groups(false, "AR", true, 'search_id_group', $id_group,
		false, '', '', true);
	$table->data[0][2] = __('Agent');
	$table->data[0][3] = print_select (get_group_agents ($id_group, false, "none"),
		'search_id_agent', $id_agent, false, __('Select'), 0, true);
	$table->data[0][3] .= '<span id="agent_loading" class="invisible">';
	$table->data[0][3] .= print_image('images/spinner.png', true);
	$table->data[0][3] .= '</span>';
	
	print_table ($table);
	echo '<div id="alerts_loading" class="loading invisible">';
	echo print_image('images/spinner.png', true);
	echo __('Loading').'&hellip;';
	echo '</div>';
	
	/* Rest of fields are reused */
	$table_alerts->id = 'alert_list';
	$table_alerts->width = '80%';
	$table_alerts->data = array ();
	unset ($table_alerts->head[3]);
	
	if (! $id_agent) {
		$table_alerts->class = 'invisible';
	} else {
		$alerts = get_agent_alerts_simple ($id_agent);
		
		if (empty ($alerts)) {
			$table_alerts->data[0][0] = "<div class='nf'>".__('No alerts found')."</div>";
			$table_alerts->colspan[0][0] = 3;
			$id_agent = 0;
		}
		
		foreach ($alerts as $alert) {
			$data = array ();
			
			$data[0] = '<a href="#" class="add_alert" id="add-'.$alert['id'].'" />';
			$data[0] .= print_image('images/add.png', true);
			$data[0] .= '</a>';
			$data[1] = get_agentmodule_name ($alert['id_agent_module']);
			$data[2] = get_alert_template_name ($alert['id_alert_template']);
			
			array_push ($table_alerts->data, $data);
		}
	}
	
	print_table ($table_alerts);
	
	/* Pager for alert list using Javascript */
	echo '<div id="alerts_pager" class="'.($id_agent ? '' : 'invisible ').'pager">';
	echo '<form>';
	echo print_image("images/go_first.png", true, array("class" => "first"));
	echo print_image("images/go_previous.png", true, array("class" => "prev"));
	echo '<input type="text" class="pagedisplay" />';
	echo print_image("images/go_next.png", true, array("class" => "next"));
	echo print_image("images/go_last.png", true, array("class" => "last"));
	echo '<select class="pagesize invisible">';
	echo '<option selected="selected" value="'.$config['block_size'].'">'.$config['block_size'].'</option>';
	echo '</select>';
	echo '</form>';
	echo '</div>';
	
	echo '<div class="invisible">';
	print_select (get_alert_compound_operations (), 'operations');
	echo '</div>';
}

ui_require_css_file ('timeentry');
ui_require_jquery_file ('form');
ui_require_jquery_file ('tablesorter');
ui_require_jquery_file ('tablesorter.pager');
ui_require_jquery_file ('ui.core');
ui_require_jquery_file ('timeentry');
?>

<script type="text/javascript" src="include/javascript/pandora_alerts.js"></script>
<script src="include/languages/time_<?php echo $config['language']; ?>.js"></script>

<script type="text/javascript">
var block_size = <?php echo $config['block_size']; ?>;
var alerts;
var compound_alerts;
<?php if ($id_agent && isset ($alerts) && $alerts) : ?>
	alerts = Array ();
	<?php foreach ($alerts as $alert) : ?>
	alerts[<?php echo $alert['id'] ?>] = eval ("("+'<?php echo json_encode ($alert); ?>'+")");
	<?php endforeach; ?>
<?php endif; ?>

function remove_alert () {
	$(this).parents ("tr:first").remove ();
	len = $("#conditions_list tbody tr").length;
	if (len == 1) {
		id = this.id.split ("-").pop ();
		tr = $("#conditions_list tbody tr:first");
		$("select", tr).remove ();
		input = $("<input type=\"hidden\"></input>")
			.attr ("name", "operations["+id+"]")
			.attr ("value", "NOP");
		$("td:last", tr).append (input);
	} else if (len == 0) {
		$("#conditions_list").hide ();
	}
	return false;
}

function add_alert () {
	id = this.id.split ("-").pop ();
	if (alerts[id] == null)
		return;
	input = $("<input type=\"hidden\"></input>")
		.attr ("name", "conditions[]")
		.attr ("value", id);
	td = $("<td></td>").append (input);
	
	/* Select NOP operation if there's only one alert */
	if ($("#conditions_list tbody tr").length == 0) {
		input = $("<input type=\"hidden\"></input>")
			.attr ("name", "operations["+id+"]")
			.attr ("value", "NOP");
		$(td).append (input);
	} else {
		$(td).append ($("select#operations:last").clone ()
				.show ()
				.attr ("name", "operations["+id+"]")
			);
	}
	tr = $(this).parents ("tr")
		.clone ()
		.append (td);
	
	var params = [];
	params.push("get_image_path=1");
	params.push("img_src=images/delete.png");
	params.push("page=include/ajax/skins.ajax");
	params.push("only_src=1");
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: false,
		timeout: 10000,
			success: function (data) {
				$("img", tr).attr ("src", data);
			}
		});	
	$("a", tr).attr("id", "remove-"+id)
		.click (remove_alert);
	
	$("#conditions_list tbody").append (tr);
	$("#conditions_list").show ();
	$("#compound-conditions").show ();
	
	return false;
}

$(document).ready (function () {
<?php if ($step == 1): ?>
	$("a.add_alert").click (add_alert);
	$("a.remove_alert").click (remove_alert);
	
	$("#alert_list").tablesorter ();
<?php if ($id_agent && isset ($alerts) && $alerts) : ?>
	$("#alert_list").tablesorterPager ({
			container: $("#alerts_pager"),
			size: block_size
		});
<?php endif; ?>
	$("#search_id_group").change (function () {
		$("#agent_loading").show ();
		var select = $("#search_id_agent").disable ();
		/* Remove all but "Select" */
		$("option[value!=0]", select).remove ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/groups/group_list",
			"get_group_agents" : 1,
			"id_group" : this.value
			},
			function (data, status) {
				jQuery.each (data, function (id, value) {
					$(select).append ($("<option></option>").attr ("value", id).html (value));
				});
				$("#agent_loading").hide ();
				$("#search_id_agent").enable ();
			},
			"json"
		);
	});
	
	$("#search_id_agent").change (function () {
		$("#alerts_pager").hide ();
		$("#alert_list").hide ();
		if (this.value == 0) {
			$("#alert_list tr:gt(0)").remove ();
			return;
		}
		
		$("#alerts_loading").show ();
		$("#alert_list tbody").empty ();
		jQuery.post ("ajax.php",
			{"page" : "include/ajax/alert_list.ajax",
			"get_agent_alerts_simple" : 1,
			"id_agent" : this.value
			},
			function (data, status) {
				if (! data) {
					$("#alerts_loading").hide ();
					tr = $('<tr></tr>').append ($('<td></td>')
						.append ("<?php echo '<div class=\'nf\'>'.__('No alerts found').'</div>'; ?>")
						.attr ("colspan", 3));
					$("#alert_list").append (tr)
						.trigger ("update")
						.show ();
					
					return;
				}
				alerts = Array ();
				jQuery.each (data, function () {
					tr = $('<tr></tr>');
					
					var params = [];
					params.push("get_image_path=1");
					params.push("img_src=images/add.png");
					params.push("page=include/ajax/skins.ajax");
					params.push("only_src=1");
					jQuery.ajax ({
						data: params.join ("&"),
						type: 'POST',
						url: action="ajax.php",
						async: false,
						timeout: 10000,
							success: function (data) {
								img = $("<img></img>").attr ("src", data).addClass ("clickable");
							}
						});
					a = $("<a></a>").append (img)
						.attr ("id", "add-"+this["id"])
						.attr ("href", "#condition")
						.click (add_alert);
					td = $('<td></td>').append (a)
						.attr ("width", "20px")
						.attr ("id", "img_action");
					tr.append (td);
					a = $("<a></a>")
						.attr ("id", "view_agent-"+this["id"])
						.attr ("href", "index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=" + $("#search_id_agent").val())
						.text($("#search_id_agent :selected").text());
					td = $('<td></td>').append (a)
						.attr ("width", "20%")
						.attr ("id", "img_action");
					tr.append (td);
					
					td = $('<td></td>').append (this["module_name"])
						.attr ("width", "40%");
					tr.append (td);
					td = $('<td></td>').append (this["template"]["name"])
						.attr ("width", "40%");
					tr.append (td);
					$("#alert_list").append (tr);
					alerts[this["id"]] = this;
				});
				$("#alert_list").trigger ("update").tablesorterPager ({
						container: $("#alerts_pager"),
						size: block_size
					}).show ();
				$("#alerts_pager").show ();
				$("#alerts_loading").hide ();
			},
			"json"
		);
	});
	$("#alert_form").submit (function () {
		values = $(this).formToArray ();
		if ($("#text-name").attr ("value") == '') {
			$("#message").showMessage ("<?php echo __('No name was given') ?>");
			return false;
		}
		if ($("#id_agent").attr ("value") == 0) {
			$("#message").showMessage ("<?php echo __('No agent was given') ?>");
			return false;
		}
		if ($("input[name^=conditions]").length == 0) {
			$("#message").showMessage ("<?php echo __('No conditions were given') ?>");
			return false;
		}
		
		return true;
	});
<?php elseif ($step == 2): ?>
	$("#text-time_from, #text-time_to").timeEntry ({
		spinnerImage: 'images/time-entry.png',
		spinnerSize: [20, 20, 0]
		}
	);
	
	$("#threshold").change (function () {
		if (this.value == -1) {
			$("#text-other_threshold").attr ("value", "");
			$("#compound-threshold-other_label").show ();
			$("#compound-threshold-other_input").show ();
		} else {
			$("#compound-threshold-other_label").hide ();
			$("#compound-threshold-other_input").hide ();
		}
	});
	$("a.show_advanced_actions").click (function () {
		$("#text-fires_min").attr ("value", 0);
		$("#text-fires_max").attr ("value", 0);
		$(this).parents ("td").children ("span.advanced_actions").show ();
		$(this).remove ();
		return false;
	});
	$("#button-add_action").click (function () {
		value = $("#action option[selected]").html ();
		id = $("#action").fieldValue ();
		input = input = $("<input type=\"hidden\"></input>")
			.attr ("name", "actions[]")
			.attr ("value", id);
		li = $("<li></li>").append (value).append (input);
		$("ul#alert_actions").append (li);
		$("#compound-actions").show ();
	});
<?php elseif ($step == 3): ?>
	$("#recovery_notify").change (function () {
		if (this.value == 1) {
			$("#compound-field2, #compound-field3").show ();
		} else {
			$("#compound-field2, #compound-field3").hide ();
		}
	});
<?php endif; ?>
});
</script>
