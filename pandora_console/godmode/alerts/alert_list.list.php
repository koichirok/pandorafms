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

if (! give_acl ($config['id_user'], 0, "LW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

/* Check if this page is included from a agent edition */

if (! give_acl ($config['id_user'], 0, "LW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["tab"])) {
	echo "<h2>".__('Alerts')."</h2>";
}

// Table for filter controls
$form_filter = '<form method="post" action="index.php?sec=galertas&amp;sec2=godmode/alerts/alert_list&amp;refr='.$config["refr"].'&amp;pure='.$config["pure"].'">';
$form_filter .= "<input type='hidden' name='search' value='1' />\n";
$form_filter .= '<table style="width: 90%;" cellpadding="4" cellspacing="4" class="databox">'."\n";
$form_filter .= "<tr>\n";
$form_filter .= "<td>".__('Template name')."</td><td>";
$form_filter .= print_input_text ('template_name', $templateName, '', 12, 255, true);
$form_filter .= "</td>\n";
$temp = get_agents();
$arrayAgents = array();
foreach ($temp as $agentElement) {
	$arrayAgents[$agentElement['id_agente']] = $agentElement['nombre'];
}
$form_filter .= "<td>".__('Agents')."</td><td>";
$form_filter .= print_input_text_extended ('agent_name', $agentName, 'text-agent_name', '', 12, 100, false, '',
array('style' => 'background: url(images/lightning.png) no-repeat right;'), true);
$form_filter .= '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';
$form_filter .= "</td>\n";


$form_filter .= "<td>".__('Module name')."</td><td>";
$form_filter .= print_input_text ('module_name', $moduleName, '', 12, 255, true);
$form_filter .= "</td>\n";
$form_filter .= "</tr>\n";

$form_filter .= "<tr>\n";
$temp = get_db_all_rows_sql("SELECT id, name FROM talert_actions;");
$arrayActions = array();
if (is_array($temp)) {
	foreach ($temp as $actionElement) {
		$arrayActions[$actionElement['id']] = $actionElement['name'];
	}
}
$form_filter .= "<td>".__('Actions')."</td><td>";
$form_filter .= print_select ($arrayActions, "action_id", $actionID,  '', __('All'), -1, true);
$form_filter .= "</td>\n";
$form_filter .= "<td>".__('Field content')."</td><td>";
$form_filter .= print_input_text ('field_content', $fieldContent, '', 12, 255, true);
$form_filter .= "</td>\n";
$form_filter .= "<td>".__('Priority')."</td><td>";
$form_filter .= print_select (get_priorities (), 'priority',$priority, '', __('All'), -1, true);
$form_filter .= "</td>";
$form_filter .= "</tr>\n";

$form_filter .= "<tr>\n";
$form_filter .= "<td>".__('Enabled / Disabled')."</td><td>";
$ed_list = array ();
$ed_list[0] = __('Enable');
$ed_list[1] = __('Disable');
$form_filter .= print_select ($ed_list, 'enabledisable', $enabledisable, '', __('All'), -1, true);
$form_filter .= "</td></tr>\n";

$form_filter .= "<tr>\n";
$form_filter .= "<td colspan='6' align='right'>";
$form_filter .= print_submit_button (__('Update'), '', false, 'class="sub upd"', true);
$form_filter .= "</td>";
$form_filter .= "</tr>\n";
$form_filter .= "</table>\n";
$form_filter .= "</form>\n";

toggle($form_filter,__('Alert control filter'), __('Toggle filter(s)'));

$simple_alerts = array();

$total = 0;
$where = '';

if ($searchFlag) {
	if ($priority != -1 )
		$where .= " AND id_alert_template IN (SELECT id FROM talert_templates WHERE priority = " . $priority . ")";
	if (strlen(trim($templateName)) > 0)
		$where .= " AND id_alert_template IN (SELECT id FROM talert_templates WHERE name LIKE '%" . trim($templateName) . "%')";
	if (strlen(trim($fieldContent)) > 0)
		$where .= " AND id_alert_template IN (SELECT id FROM talert_templates
			WHERE field1 LIKE '%" . trim($fieldContent) . "%' OR field2 LIKE '%" . trim($fieldContent) . "%' OR
				field3 LIKE '%" . trim($fieldContent) . "%' OR
				field2_recovery LIKE '%" . trim($fieldContent) . "%' OR
				field3_recovery LIKE '%" . trim($fieldContent) . "%')";
	if (strlen(trim($moduleName)) > 0)
		$where .= " AND id_agent_module IN (SELECT id_agente_modulo FROM tagente_modulo WHERE nombre LIKE '%" . trim($moduleName) . "%')";
	//if ($agentID != -1)
		//$where .= " AND id_agent_module IN (SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = " . $agentID . ")";
	if (strlen(trim($agentName)) > 0)
		$where .= " AND id_agent_module IN (SELECT t2.id_agente_modulo
			FROM tagente AS t1 INNER JOIN tagente_modulo AS t2 ON t1.id_agente = t2.id_agente
			WHERE t1.nombre LIKE '" . trim($agentName) . "')";
	if ($actionID != -1)
		$where .= " AND id IN (SELECT id_alert_template_module FROM talert_template_module_actions WHERE id_alert_action = " . $actionID . ")";
	if ($enabledisable != -1)
		$where .= " AND talert_template_modules.disabled =" . $enabledisable;
}

$total = get_agent_alerts_simple (array_keys ($agents), false,
	false, $where, false, false, false, true);

if(empty($total)) $total = 0;

$order = null;

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = 'border: 1px solid black;';
$selectDisabledUp = '';
$selectDisabledDown = '';
$selectAgentUp = '';
$selectAgentDown = '';
$selectModuleUp = '';
$selectModuleDown = '';
$selectTemplateUp = '';
$selectTemplateDown = '';

switch ($sortField) {
	case 'disabled':
		switch ($sort) {
			case 'up':
				$selectDisabledUp = $selected;
				$order = array('field' => 'disabled', 'order' => 'ASC');
				break;
			case 'down':
				$selectDisabledDown = $selected;
				$order = array('field' => 'disabled', 'order' => 'DESC');
				break;
		}
		break;
	case 'agent':
		switch ($sort) {
			case 'up':
				$selectAgentUp = $selected;
				$order = array('field' => 'agent_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentDown = $selected;
				$order = array('field' => 'agent_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'module':
		switch ($sort) {
			case 'up':
				$selectModuleUp = $selected;
				$order = array('field' => 'agent_module_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleDown = $selected;
				$order = array('field' => 'agent_module_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'template':
		switch ($sort) {
			case 'up':
				$selectTemplateUp = $selected;
				$order = array('field' => 'template_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectTemplateDown = $selected;
				$order = array('field' => 'template_name', 'order' => 'DESC');
				break;
		}
		break;
	default:
		if (!$id_agente) {
			$selectDisabledUp = '';
			$selectDisabledDown = '';
			$selectAgentUp = $selected;
			$selectAgentDown = '';
			$selectModuleUp = '';
			$selectModuleDown = '';
			$selectTemplateUp = '';
			$selectTemplateDown = '';
			$order = array('field' => 'agent_name', 'order' => 'ASC');
		}
		else {
			$selectDisabledUp = '';
			$selectDisabledDown = '';
			$selectAgentUp = '';
			$selectAgentDown = '';
			$selectModuleUp = $selected;
			$selectModuleDown = '';
			$selectTemplateUp = '';
			$selectTemplateDown = '';
			$order = array('field' => 'agent_module_name', 'order' => 'ASC');
		}
		break;
}

pagination ($total, 'index.php?sec=gagente&sec2=godmode/alerts/alert_list');
$simple_alerts = get_agent_alerts_simple (array_keys ($agents), false,
	array ('offset' => (int) get_parameter ('offset'),
		'limit' => $config['block_size'], 'order' => $order), $where, false);

$offset = get_parameter('offset');
if (!$id_agente) {
	$url = 'index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&offset=' . $offset;
}
else {
	$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=' . $id_agente;
}
	
$table->class = 'alert_list';
$table->width = '90%';
$table->size = array ();

$table->align[0] = 'center';

$table->head = array ();
$table->head[0] = "<span title='" . __('Enabled / Disabled') . "'>" . __('E/D') . "</span>" . ' ' .
	'<a href="' . $url . '&sort_field=disabled&sort=up"><img src="images/sort_up.png" style="' . $selectDisabledUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=disabled&sort=down"><img src="images/sort_down.png" style="' . $selectDisabledDown . '" /></a>';
if (! $id_agente) {
	$table->style = array ();
	$table->style[1] = 'font-weight: bold';
	$table->head[1] = __('Agent') . ' ' .
		'<a href="' . $url . '&sort_field=agent&sort=up"><img src="images/sort_up.png" style="' . $selectAgentUp . '" /></a>' .
		'<a href="' . $url . '&sort_field=agent&sort=down"><img src="images/sort_down.png" style="' . $selectAgentDown . '" /></a>';
	$table->size[0] = '12%';
	$table->size[1] = '15%';
	$table->size[2] = '20%';
	$table->size[3] = '15%';
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$table->size[4] = '20px';
	}
	$table->size[5] = '50%';
}
else {
	/* Different sizes or the layout screws up */
	$table->size[0] = '12%';
	$table->size[2] = '30%';
	$table->size[3] = '20%';
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$table->size[4] = '20px';
	}
	$table->size[5] = '50%';
}

$table->head[2] = __('Module') . ' ' .
	'<a href="' . $url . '&sort_field=module&sort=up"><img src="images/sort_up.png" style="' . $selectModuleUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=module&sort=down"><img src="images/sort_down.png" style="' . $selectModuleDown . '" /></a>';
$table->head[3] = __('Template') . ' ' .
	'<a href="' . $url . '&sort_field=template&sort=up"><img src="images/sort_up.png" style="' . $selectTemplateUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=template&sort=down"><img src="images/sort_down.png" style="' . $selectTemplateDown . '" /></a>';
if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
	$table->head[4] = "<span title='" . __('Policy') . "'>" . __('P.') . "</span>";
}
$table->head[5] = __('Actions');
$table->head[6] = __('Status');
$table->head[7] = __('Delete');

$table->valign[6] = 'middle';
$table->align[6] = 'center';
$table->align[7] = 'center';

$table->data = array ();

$rowPair = true;
$iterator = 0;
foreach ($simple_alerts as $alert) {
	if ($alert['disabled']) {
		 $table->rowstyle[$iterator] = 'font-style: italic; color: #aaaaaa;';
		 $table->style[$iterator][1] = 'font-style: italic; color: #aaaaaa;';
	}
	
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;

	$data = array ();
	
	$data[0] = '<form class="disable_alert_form" method="post" style="display: inline;">';
	if ($alert['disabled']) {
		$data[0] .= print_input_image ('enable', 'images/lightbulb_off.png', 1, '', true);
		$data[0] .= print_input_hidden ('enable_alert', 1, true);
	}
	else {
		$data[0] .= print_input_image ('disable', 'images/lightbulb.png', 1, '', true);
		$data[0] .= print_input_hidden ('disable_alert', 1, true);
	}
	$data[0] .= print_input_hidden ('id_alert', $alert['id'], true);
	$data[0] .= '</form>';
	if (! $id_agente) {
		$id_agent = get_agentmodule_agent ($alert['id_agent_module']);
		$data[1] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente='.$id_agent.'">';
		if ($alert['disabled'])
			$data[1] .= '<span style="font-style: italic; color: #aaaaaa;">';
		$data[1] .= get_agent_name ($id_agent);
		if ($alert['disabled'])
			$data[1] .= '</span>';
		$data[1] .= '</a>';
	}
	$data[2] = get_agentmodule_name ($alert['id_agent_module']);
	$data[3] = ' <a class="template_details"
		href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'">
		<img id="template-details-'.$alert['id_alert_template'].'" class="img_help" src="images/zoom.png"/></a> ';

	$data[3] .= "<a href='index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&id=".$alert['id_alert_template']."'>";
	$data[3] .= get_alert_template_name ($alert['id_alert_template']);
	$data[3] .= "</a>";
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$policyInfo = isAlertInPolicy($alert['id_agent_module'], $alert['id_alert_template'], false);
		if ($policyInfo === false)
			$data[4] = '';
		else {
			$img = 'images/policies.png';
				
			$data[4] = '<a href="?sec=gpolicies&sec2=enterprise/godmode/policies/policies&id=' . $policyInfo['id_policy'] . '">' . 
				print_image($img,true, array('title' => $policyInfo['name_policy'])) .
				'</a>';
		}
	}
	
	$actions = get_alert_agent_module_actions ($alert['id']);

	$data[5] = '';
	if (empty($actions)){
		// Get and show default actions for this alert
		$default_action = get_db_sql ("SELECT id_alert_action FROM talert_templates WHERE id = ".$alert["id_alert_template"]);
		if ($default_action != ""){
			$data[5] = __("Default"). " : ".get_db_sql ("SELECT name FROM talert_actions WHERE id = $default_action");
		}

	} else {
		$data[5] = '<ul class="action_list">';
		foreach ($actions as $action_id => $action) {
			$data[5] .= '<li>';
			if ($alert['disabled'])
				$data[5] .= '<font class="action_name" style="font-style: italic; color: #aaaaaa;">';
			else
				$data[5] .= '<font class="action_name">';
			$data[5] .= $action['name'];
			$data[5] .= ' <em>(';
			if ($action['fires_min'] == $action['fires_max']) {
				if ($action['fires_min'] == 0)
					$data[5] .= __('Always');
				else
					$data[5] .= __('On').' '.$action['fires_min'];
			}
			else {
				if ($action['fires_min'] == 0)
					$data[5] .= __('Until').' '.$action['fires_max'];
				else
					$data[5] .= __('From').' '.$action['fires_min'].
						' '.__('to').' '.$action['fires_max'];
			}
			$data[5] .= ')</em>';
			$data[5] .= '</font>';
//			$data[5] .= ' <span class="delete" style="clear:right">';
			$data[5] .= '<form method="post" class="delete_link" style="display: inline; vertical-align: -50%;">';
			$data[5] .= print_input_image ('delete', 'images/cross.png', 1, '', true);
			$data[5] .= print_input_hidden ('delete_action', 1, true);
			$data[5] .= print_input_hidden ('id_alert', $alert['id'], true);
			$data[5] .= print_input_hidden ('id_action', $action_id, true);
			$data[5] .= '</form>';
//			$data[5] .= '</span>';
			$data[5] .= '</li>';
		}
		$data[5] .= '</ul>';
	}

	
	$data[5] .= '<a class="add_action" id="add-action-'.$alert['id'].'" href="#">';
	$data[5] .= print_image ('images/add.png', true);
	if ($alert['disabled'])
		$data[5] .= ' '. '<span style="font-style: italic; color: #aaaaaa;">' .__('Add action') . '</span>';
	else
		$data[5] .= ' ' . __('Add action');
	$data[5] .= '</a>';
	
	$status = STATUS_ALERT_NOT_FIRED;
	$title = "";
	
	if ($alert["times_fired"] > 0) {
		$status = STATUS_ALERT_FIRED;
		$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
	} elseif ($alert["disabled"] > 0) {
		$status = STATUS_ALERT_DISABLED;
		$title = __('Alert disabled');
	} else {
		$status = STATUS_ALERT_NOT_FIRED;
		$title = __('Alert not fired');
	}
	
	$data[6] = print_status_image($status, $title, true);
	
	$data[7] = '<form class="delete_alert_form" method="post" style="display: inline;">';
	
	$data[7] .= print_input_image ('delete', 'images/cross.png', 1, '', true);
	$data[7] .= print_input_hidden ('delete_alert', 1, true);
	$data[7] .= print_input_hidden ('id_alert', $alert['id'], true);
	$data[7] .= '</form>';
	array_push ($table->data, $data);
}

if (isset($data)){
	print_table ($table);
} else {
	echo "<div class='nf'>".__('No alerts defined')."</div>";
}

echo '<form id="add_action_form" method="post" class="invisible">';
print_input_hidden ('add_action', 1);
print_input_hidden ('id_alert_module', 0);
$actions = get_alert_actions ();
print_select ($actions, 'action', '', '', __('None'), 0);
echo '<br />';
echo '<span><a href="#" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
echo '<span class="advanced_actions invisible">';
echo __('Number of alerts match from').' ';
print_input_text ('fires_min', -1, '', 4, 10);
echo ' '.__('to').' ';
print_input_text ('fires_max', -1, '', 4, 10);
echo print_help_icon ("alert-matches", true);
echo '</span>';
echo '<div class="right">';
print_submit_button (__('Add'), 'add_action', false, 'class="sub next"');
echo '</div>';
echo '</form>';

// Create alert button
echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=builder">';
print_submit_button (__('Create'), 'crtbtn', false, 'class="sub next"');
echo '</form>';
echo '</div>';

require_css_file ('cluetip');
require_jquery_file ('cluetip');
require_jquery_file ('pandora.controls');
require_jquery_file ('bgiframe');
require_jquery_file ('autocomplete');
?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#text-agent_name").autocomplete ("ajax.php",
		{
			scroll: true,
			minChars: 2,
			extraParams: {
				page: "godmode/agentes/agent_manager",
				search_parents: 1,
				id_group: function() { return $("#grupo").val(); },
				id_agent: <?php echo $id_agente ?>
			},
			formatItem: function (data, i, total) {
				if (total == 0)
					$("#text-id_parent").css ('background-color', '#cc0000');
				else
					$("#text-id_parent").css ('background-color', 'none');
				if (data == "")
					return false;

				return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
			},
			delay: 200
		}
	);
//----------------------------


<?php if (! $id_agente) : ?>
	$("#id_group").pandoraSelectGroupAgent ({
		callbackBefore: function () {
			$select = $("#id_agent_module").disable ();
			$select.siblings ("span#latest_value").hide ();
			$("option[value!=0]", $select).remove ();
			return true;
		}
	});
	
	//$("#id_agent").pandoraSelectAgentModule ();
<?php endif; ?>
	$("a.template_details").cluetip ({
		arrows: true,
		attribute: 'href',
		cluetipClass: 'default'
	}).click (function () {
		return false;
	});
	
	$("#tgl_alert_control").click (function () {
		$("#alert_control").toggle ();
		return false;
	});
	
	$("input[name=disable]").attr ("title", "<?php echo __('Disable')?>")
		.hover (function () {
				$(this).attr ("src", "images/lightbulb_off.png");
			},
			function () {
				$(this).attr ("src", "images/lightbulb.png");
			}
		);
	$("input[name=enable]").attr ("title", "<?php echo __('Enable')?>")
		.hover (function () {
				$(this).attr ("src", "images/lightbulb.png");
			},
			function () {
				$(this).attr ("src", "images/lightbulb_off.png");
			}
		);
	$("form.disable_alert_form").submit (function () {
		return true;
	});
	
	
	$("a.add_action").click (function () {
		id = this.id.split ("-").pop ();
		
		/* Replace link with a combo with the actions and a form */
		$form = $('form#add_action_form:last').clone (true).show ();
		$("input#hidden-id_alert_module", $form).attr ("value", id);
		$(this).replaceWith ($form);
		return false;
	});
	
	$("form.delete_link, form.delete_alert_form").submit (function () {
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;
		return true;
	});
	
	$("a.show_advanced_actions").click (function () {
		/* It can be done in two different sites, so it must use two different selectors */
		actions = $(this).parents ("form").children ("span.advanced_actions");
		if (actions.length == 0)
			actions = $(this).parents ("div").children ("span.advanced_actions")
		$("#text-fires_min", actions).attr ("value", 0);
		$("#text-fires_max", actions).attr ("value", 0);
		$(actions).show ();
		$(this).remove ();
		return false;
	});
	

});
/* ]]> */
</script>
