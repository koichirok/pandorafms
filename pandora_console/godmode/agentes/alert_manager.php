<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ('include/config.php');
require_once ('include/functions_alerts.php');

if (!isset ($id_agente)) {
	die ("Not Authorized");
}

echo "<h2>".__('Agent configuration')." &gt; ".__('Alerts')."</h2>";

$create_alert = (bool) get_parameter ('create_alert');
$add_action = (bool) get_parameter ('add_action');

if ($create_alert) {
	$id_alert_template = (int) get_parameter ('template');
	$id_agent_module = (int) get_parameter ('id_agent_module');
	
	$id = create_alert_agent_module ($id_agent_module, $id_alert_template);
	print_error_message ($id, __('Successfully created'),
		__('Could not be created'));
	if ($id !== false) {
		$id_alert_action = (int) get_parameter ('action');
		$fires_min = (int) get_parameter ('fires_min');
		$fires_max = (int) get_parameter ('fires_max');
		$values = array ();
		if ($fires_min != -1)
			$values['fires_min'] = $fires_min;
		if ($fires_max != -1)
			$values['fires_max'] = $fires_max;
		
		add_alert_agent_module_action ($id, $id_alert_action, $values);
	}
}

if ($add_action) {
	$id_action = (int) get_parameter ('action');
	$id_alert_module = (int) get_parameter ('id_alert_module');
	$fires_min = (int) get_parameter ('fires_min');
	$fires_max = (int) get_parameter ('fires_max');
	$values = array ();
	if ($fires_min != -1)
		$values['fires_min'] = $fires_min;
	if ($fires_max != -1)
		$values['fires_max'] = $fires_max;
	
	$result = add_alert_agent_module_action ($id_alert_module, $id_action, $values);
	print_error_message ($id, __('Successfully added'),
		__('Could not be added'));
}

$modules = get_agent_modules ($id_agente,
	array ('id_tipo_modulo', 'nombre', 'id_agente'));

echo "<h3>".__('Modules defined')."</h3>";

$table->id = 'modules';
$table->cellspacing = '0';
$table->width = '90%';
$table->head = array ();
$table->head[0] = __('Module');
$table->data = array ();
$table->style = array ();
$table->style[1] = 'vertical-align: top';

$table_alerts->class = 'listing';
$table_alerts->width = '100%';
$table_alerts->size = array ();
$table_alerts->size[0] = '50%';
$table_alerts->size[1] = '50%';
$table_alerts->style = array ();
$table_alerts->style[0] = 'vertical-align: top';
$table_alerts->style[1] = 'vertical-align: top';

foreach ($modules as $id_agent_module => $module) {
	$data = array ();
	
	$last_data = return_value_agent_module ($id_agent_module);
	if ($last_data === false)
		$last_data = '<em>'.__('N/A').'</em>';
	
	$data[0] = '<span>'.$module['nombre'].'</span>';
	$data[0] .= '<div class="actions left" style="visibility: hidden;">';
	$data[0] .= '<span class="module_values" style="float: right;">';
	$data[0] .= '<em>'.__('Latest value').'</em>: ';
	$data[0] .= $last_data;
	$data[0] .= '</span>';
	$data[0] .= '</div>';
	$data[0] .= '<div class="actions right" style="visibility: hidden;">';
	$data[0] .= '<span class="add">';
	$data[0] .= '<a href="#" class="add_alert" id="module-'.$id_agent_module.'">';
	$data[0] .= __('Add alert');
	$data[0] .= '</a>';
	$data[0] .= '</span>';
	$data[0] .= '</div>';
	
	
	/* Alerts in module list */
	$table_alerts->id = 'alerts-'.$id_agent_module;
	$table_alerts->data = array ();
	
	$alerts = get_alerts_agent_module ($id_agent_module);
	if ($alerts === false) {
		$alerts = array ();
	} else {
		$data[0] .= '<h4 class="left" style="clear: left">';
		$data[0] .= __('Alerts');
		$data[0] .= '</h4>';
	}
	
	foreach ($alerts as $alert) {
		$alert_data = array ();
		
		$alert_actions = get_alert_agent_module_actions ($alert['id']);
		
		$alert_data[0] = get_alert_template_name ($alert['id_alert_template']);
		$alert_data[0] .= '<span class="actions" style="visibility: hidden">';
		$alert_data[0] .= '<a href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'"
			class="template_details">';
		$alert_data[0] .= print_image ("images/zoom.png", true,
			array ("id" => 'template-details-'.$alert['id'],
				"class" => "left img_help")
			);
		$alert_data[0] .= '</a>';
		$alert_data[0] .= '</span>';
		
		$alert_data[1] = '<ul style="float: left; margin-bottom: 10px">';
		foreach ($alert_actions as $action) {
			$alert_data[1] .= '<li><div>';
			$alert_data[1] .= '<span class="left">';
			$alert_data[1] .= $action['name'].' ';
			$alert_data[1] .= '<em>(';
			if ($action['fires_min'] == $action['fires_max']) {
				if ($action['fires_min'] == 0)
					$alert_data[1] .= __('Always');
				else
					$alert_data[1] .= __('On').' '.$action['fires_min'];
			} else {
				if ($action['fires_min'] == 0)
					$alert_data[1] .= __('Until').' '.$action['fires_max'];
				else
					$alert_data[1] .= __('From').' '.$action['fires_min'].
						' '.__('to').' '.$action['fires_max'];
			}
			
			$alert_data[1] .= ')</em>';
			$alert_data[1] .= '</span>';
			$alert_data[1] .= ' <span class="actions" style="visibility: hidden">';
			$alert_data[1] .= '<span class="delete">';
			$alert_data[1] .= '<a href="#">';
			$alert_data[1] .= '<img src="images/cross.png" />';
			$alert_data[1] .= '</a>';
			$alert_data[1] .= '</span>';
			$alert_data[1] .= '</span>';
			$alert_data[1] .= '</div></li>';
		}
		$alert_data[1] .= '</ul>';
		
		$alert_data[1] .= '<div class="actions left" style="visibility: hidden; clear: left">';
		$alert_data[1] .= '<a class="add_action" id="add-action-'.$alert['id'].'" href="#">';
		$alert_data[1] .= __('Add action');
		$alert_data[1] .= '</a>';
		$alert_data[1] .= '</div>';
		
		$table_alerts->data['alert-'.$alert['id']] = $alert_data;
	}
	
	$data[0] .= print_table ($table_alerts, true);
	array_push ($table->data, $data);
}

print_table ($table);

/* This hidden value is used in Javascript. It's a workaraound for IE because
   it doesn't allow input elements creation. */
print_input_hidden ('add_action', 1);
print_input_hidden ('id_alert_module', 0);

echo '<form class="add_alert_form" method="post" style="display: none"
	action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.
	$module['id_agente'].'">';
echo '<div style="float:left">';
print_label (__('Template'), 'template');
$templates = get_alert_templates ();
if (empty ($templates))
	$templates = array ();
print_select ($templates, 'template', '', '', __('None'), 0);
echo '</div><div style="margin-left: 270px">';
print_label (__('Action'), 'action');
$actions = get_alert_actions ();
if (empty ($actions))
	$actions = array ();
print_select ($actions, 'action', '', '', __('None'), 0);
echo '<br />';
echo '<span><a href="#" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
echo '<span class="advanced_actions" style="display: none">';
echo __('From').' ';
print_input_text ('fires_min', -1, '', 4, 10);
echo ' '.__('to').' ';
print_input_text ('fires_max', -1, '', 4, 10);
echo ' '.__('matches of the alert');
echo '</span></div>';
echo '<div style="float: right; margin-left: 30px;"><br />';
print_submit_button (__('Add'), 'add', false, 'class="sub next"');
print_input_hidden ('id_agent_module', 0);
print_input_hidden ('create_alert', 1);
echo '</div></form>';

?>

<link rel="stylesheet" href="include/styles/cluetip.css" type="text/css" />
<script type="text/javascript" src="include/javascript/jquery.cluetip.js"></script>

<script type="text/javascript">
$(document).ready (function () {
	$("table#modules tr, table#listing tr").hover (
		function () {
			$(".actions", this).css ("visibility", "");
		},
		function () {
			$(".actions", this).css ("visibility", "hidden");
		}
	);
	
	$("a.add_alert").click (function () {
		if ($("form.add_alert_form", $(this).parents ("td")).length > 0) {
			return false;
		}
		id = this.id.split ("-").pop ();
		form = $("form.add_alert_form:last").clone (true);
		$("input#hidden-id_agent_module", form).attr ("value", id);
		$(this).parents ("td").append (form);
		$(form).show ();
		return false;
	});
	
	$(".add_alert_form").submit (function () {
		if ($("#template", this).attr ("value") == 0) {
			return false;
		}
		
		if ($("#action", this).attr ("value") == 0) {
			return false;
		}
		return true;
	});
	
	$("a.show_advanced_actions").click (function () {
		/* It can be done in two different site, so it must use two different selectors */
		actions = $(this).parents ("form").children ("span.advanced_actions");
		if (actions.length == 0)
			actions = $(this).parents ("div").children ("span.advanced_actions")
		$("#text-fires_min", actions).attr ("value", 0);
		$("#text-fires_max", actions).attr ("value", 0);
		$(actions).show ();
		$(this).remove ();
		return false;
	});
	
	$(".actions a.add_action").click (function () {
		id = this.id.split ("-").pop ();
		
		/* Remove new alert form (if shown) to clean a bit the UI */
		$(this).parents ("td:last").children ("form.add_alert_form")
			.remove ();
		
		/* Replace link with a combo with the actions and a form */
		a = $("a.show_advanced_actions:first").clone (true);
		advanced = $("span.advanced_actions:first").clone (true).hide ();
		select = $("select#action:first").clone ();
		button = $('<input type="image" class="sub next" value="'+"<?php echo __('Add');?>"+'"></input>');
		divbutton = $("<div></div>").css ("float", "right").html (button);
		input1 = $("input#hidden-add_action");
		input2 = $("input#hidden-id_alert_module").clone ().attr ("value", id);
		form = $('<form method="post"></form>')
			.append (select)
			.append ("<br></br>")
			.append (a)
			.append (advanced)
			.append (divbutton)
			.append (input1)
			.append (input2);
		
		$(this).parents (".actions:first").replaceWith (form);
		
		return false;
	});
	
	$("a.template_details").cluetip ({
		arrows: true,
		attribute: 'href',
		cluetipClass: 'default',
		fx: { open: 'fadeIn', openSpeed: 'slow' },
	}).click (function () {
		return false;
	});;
	
	$("select[name=template]").change (function () {
		if (this.value == 0) {
			$(this).parents ("div:first").children ("a").remove ();
			return;
		}
		
		details = $("a.template_details:first").clone (true)
			.attr ("href",
				"ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template=" + this.value);
		$(this).after (details);
	});
});
</script>
