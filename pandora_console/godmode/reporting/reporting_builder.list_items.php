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

if (! check_acl ($config['id_user'], 0, "RW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

include_once($config['homedir'] . "/include/functions_agents.php");
enterprise_include_once ('include/functions_metaconsole.php');

if ($config ['metaconsole'] == 1 and defined('METACONSOLE')) {
	$agents = array();
	$agents = metaconsole_get_report_agents($idReport);
	$modules = array();
	$modules = metaconsole_get_report_modules($idReport);
	$types = array ();
	$types = metaconsole_get_report_types($idReport);
}
else {
	//FORM FILTER
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			$rows = db_get_all_rows_sql('
				SELECT t5.nombre, t5.id_agente
				FROM
					(
					SELECT t1.*, id_agente
					FROM treport_content AS t1
						LEFT JOIN tagente_modulo AS t2
							ON t1.id_agent_module = id_agente_modulo
					) AS t4
					INNER JOIN tagente AS t5
						ON (t4.id_agent = t5.id_agente OR t4.id_agente = t5.id_agente)
				WHERE t4.id_report = ' . $idReport);
			break;
		case "oracle":
			$rows = db_get_all_rows_sql('
				SELECT t5.nombre, t5.id_agente
				FROM
					(
					SELECT t1.*, id_agente
					FROM treport_content t1
						LEFT JOIN tagente_modulo t2
							ON t1.id_agent_module = id_agente_modulo
					) t4
					INNER JOIN tagente t5
						ON (t4.id_agent = t5.id_agente OR t4.id_agente = t5.id_agente)
				WHERE t4.id_report = ' . $idReport);
			break;
	}
	
	if ($rows === false) {
		$rows = array();
	}
	
	$agents = array();
	foreach ($rows as $row) {
		$agents[$row['id_agente']] = $row['nombre'];
	}
	
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			$rows = db_get_all_rows_sql('
				SELECT t1.id_agent_module, t2.nombre
				FROM treport_content AS t1
					INNER JOIN tagente_modulo AS t2
						ON t1.id_agent_module = t2.id_agente_modulo
				WHERE t1.id_report = ' . $idReport);
			break;
		case "oracle":
			$rows = db_get_all_rows_sql('
				SELECT t1.id_agent_module, t2.nombre
				FROM treport_content t1
					INNER JOIN tagente_modulo t2
						ON t1.id_agent_module = t2.id_agente_modulo
				WHERE t1.id_report = ' . $idReport);
			break;
	}
	if ($rows === false) {
		$rows = array();
	}
	
	$modules = array();
	foreach ($rows as $row) {
		$modules[$row['id_agent_module']] = $row['nombre'];
	}
	
	// Filter report items created from metaconsole in normal console list and the opposite
	if (defined('METACONSOLE') and $config['metaconsole'] == 1) {
		$where_types = ' AND ((server_name IS NOT NULL AND length(server_name) != 0) OR type IN (\'general\',\'SLA\',\'exception\',\'top_n\'))';
	}
	else
		$where_types = ' AND ((server_name IS NULL OR length(server_name) = 0) OR type IN (\'general\',\'SLA\',\'exception\',\'top_n\'))';
	
	$rows = db_get_all_rows_sql('
		SELECT DISTINCT(type)
		FROM treport_content
		WHERE id_report = ' . $idReport . $where_types);
	if ($rows === false) {
		$rows = array();
	}
	
	$types = array();
	foreach ($rows as $row) {
		if ($row['type'] == 'automatic_custom_graph')
			$types['custom_graph'] = get_report_name($row['type']);
		else
			$types[$row['type']] = get_report_name($row['type']);
	}
}

$agentFilter = get_parameter('agent_filter', 0);
$moduleFilter = get_parameter('module_filter', 0);
$typeFilter = get_parameter('type_filter', 0);

$filterEnable = true;
$urlFilter = '';
if (($agentFilter == 0) && ($moduleFilter == 0) && ($typeFilter == 0)) {
	$filterEnable = false;
}

$urlFilter = '&agent_filter=' . $agentFilter . '&module_filter=' . $moduleFilter . '&type_filter=' . $typeFilter;

echo '<a href="javascript: toggleFormFilter();"><b>' .
	__('Items filter') . '</b> ' .
	html_print_image("images/down.png", true,
		array("title" => __('Toggle filter(s)'),
		"id" => 'image_form_filter')) . '</a>';

$table = null;
$table->width = '100%';
$table->data[0][0] = __('Agents');
$table->data[0][1] = html_print_select($agents, 'agent_filter', $agentFilter, '', __('All'), 0, true);
$table->data[0][2] = __('Modules');
$table->data[0][3] = html_print_select($modules, 'module_filter', $moduleFilter, '', __('All'), 0, true);
$table->data[1][0] = __('Type');
$table->data[1][1] = html_print_select($types, 'type_filter', $typeFilter, '', __('All'), 0, true);
$table->data[1][2] = $table->data[1][3] = '';

echo '<div id="form_filter" style="display: none;">';
echo '<form method="post" action ="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=filter&id_report=' . $idReport . '">';

html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button(__('Filter'), 'filter', false, 'class="sub upd"');
html_print_input_hidden('action', 'filter');
echo '</div>';
echo '</form>';
echo '</div>';

$where = '1=1';
if ($typeFilter != '0') {
	if ($typeFilter == 'custom_graph')
		$where .= ' AND (type = "' . $typeFilter . '"
			OR type = "automatic_custom_graph") ';
	else
		$where .= ' AND type = "' . $typeFilter . '"';
}
if ($agentFilter != 0) {
	$where .= ' AND id_agent = ' . $agentFilter;
}
if ($moduleFilter != 0) {
	$where .= ' AND id_agent_module = ' . $moduleFilter;
}

// Filter report items created from metaconsole in normal console list and the opposite
if (defined('METACONSOLE') and $config['metaconsole'] == 1) {
	$where .= ' AND ((server_name IS NOT NULL AND length(server_name) != 0) OR type IN (\'general\',\'SLA\',\'exception\',\'top_n\'))';
}
else
	$where .= ' AND ((server_name IS NULL OR length(server_name) = 0) OR type IN (\'general\',\'SLA\',\'exception\',\'top_n\'))';

switch ($config["dbtype"]) {
	case "mysql":
		$items = db_get_all_rows_sql('SELECT *
			FROM treport_content
			WHERE ' . $where . ' AND id_report = ' . $idReport . '
			ORDER BY `order`
			LIMIT ' . $offset . ', ' . $config["block_size"]);
		break;
	case "postgresql":
		$items = db_get_all_rows_sql('SELECT *
			FROM treport_content
			WHERE ' . $where . ' AND id_report = ' . $idReport . '
			ORDER BY "order"
			LIMIT ' . $config["block_size"] . ' OFFSET ' . $offset);
		break;
	case "oracle":
		$set = array();
		$set['limit'] = $config["block_size"];
		$set['offset'] = $offset;
		$items = oracle_recode_query ('SELECT *
			FROM treport_content
			WHERE ' . $where . ' AND id_report = ' . $idReport . '
			ORDER BY "order"', $set, 'AND', false);
		// Delete rnum row generated by oracle_recode_query() function
		if ($items !== false) {
			for ($i=0; $i < count($items); $i++) {
				unset($items[$i]['rnum']);
			}
		}
		break;
}
$countItems = db_get_sql('SELECT COUNT(id_rc)
	FROM treport_content
	WHERE ' . $where . ' AND id_report = ' . $idReport);
$table = null;

$table->style[0] = 'text-align: right;';


if ($items) {
	$table->width = '98%';
	
	$table->size = array();
	$table->size[0] = '5px';
	$table->size[1] = '15%';
	$table->size[4] = '8%';
	$table->size[6] = '90px';
	$table->size[7] = '30px';
	
	$table->head[0] = '<span title="' . __('Position') . '">' . __('P.') . '</span>';
	$table->head[1] = __('Type');
	if (!$filterEnable) {
		$table->head[1] .= ' <a onclick="return message_check_sort_items();" href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=up&field=type&id_report=' . $idReport . $urlFilter . '&pure=' . $config['pure'] . '">' . html_print_image("images/sort_up.png", true, array("title" => __('Ascendent'))) . '</a>' .
			'<a onclick="return message_check_sort_items();" href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=down&field=type&id_report=' . $idReport . $urlFilter . '&pure=' . $config['pure'] . '">' . html_print_image("images/sort_down.png", true, array("title" => __('Descent'))) . '</a>';
	}
	$table->head[2] = __('Agent');
	if (!$filterEnable) {
		$table->head[2] .= ' <a onclick="return message_check_sort_items();" href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=up&field=agent&id_report=' . $idReport . $urlFilter . '&pure=' . $config['pure'] . '">' . html_print_image("images/sort_up.png", true, array("title" => __('Ascendent'))) . '</a>' .
			'<a onclick="return message_check_sort_items();" href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=down&field=agent&id_report=' . $idReport . $urlFilter . '&pure=' . $config['pure'] . '">' . html_print_image("images/sort_down.png", true, array("title" => __('Descent'))) . '</a>';
	}
	$table->head[3] = __('Module');

	if (!$filterEnable) {
		$table->head[3] .= ' <a onclick="return message_check_sort_items();" href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=up&field=module&id_report=' . $idReport . $urlFilter . '&pure=' . $config['pure'] . '">' . html_print_image("images/sort_up.png", true, array("title" => __('Ascendent'))) . '</a>' .
			'<a onclick="return message_check_sort_items();" href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=down&field=module&id_report=' . $idReport . $urlFilter . '&pure=' . $config['pure'] . '">' . html_print_image("images/sort_down.png", true, array("title" => __('Descent'))) . '</a>';
	}
	$table->head[4] = __('Period');
	$table->head[5] = __('Description');
	if (check_acl ($config['id_user'], 0, "RM")) {
		$table->head[6] = '<span title="' . __('Options') . '">' . __('Op.') . '</span>';
	}
	$table->head[7] = __('Sort');
	
	$table->align[6] = 'center';
	$table->align[7] = 'center';
}
else {
	echo '<br><br><div class="nf">'. __('No items') . '</div>';
}

$lastPage = true;
if (((($offset == 0) && ($config["block_size"] > $countItems)) ||
	($countItems >= ($config["block_size"] + $offset))) &&
	($countItems > $config["block_size"])) {
	$lastPage = false;
}

$count = 0;
$rowPair = true;

if ($items === false) {
	$items = array();
}

$count = 0;
foreach ($items as $item) {
	if ($rowPair)
		$table->rowclass[$count] = 'rowPair';
	else
		$table->rowclass[$count] = 'rowOdd';
	$rowPair = !$rowPair;
	
	$row = array();
	
	$row[0] = $count + $offset + 1; //The 1 is for do not start in 0.
	
	if ($filterEnable) {
		$row[0] = '';
	}
	
	$row[1] = get_report_name($item['type']);
	
	$server_name = $item ['server_name'];
	
	if (($config ['metaconsole'] == 1) && ($server_name != '') && defined('METACONSOLE')) {
		
		$connection = metaconsole_get_connection($server_name);
		if (metaconsole_load_external_db($connection) != NOERR) {
			//ui_print_error_message ("Error connecting to ".$server_name);
		}
	}
	
	if ($item['id_agent'] == 0) {
		$is_inventory_item = $item['type'] == 'inventory' || $item['type'] == 'inventory_changes';
		// Due to SLA or top N or general report items
		if (!$is_inventory_item && ($item['id_agent_module'] == '' || $item['id_agent_module'] == 0)) {
			$row[2] = '';
			$row[3] = '';
		}
		else {
			// The inventory items have the agents and modules in json format in the field external_source
			if ($is_inventory_item) {
				$external_source = json_decode($item['external_source'], true);
				$agents = $external_source['id_agents'];
				$modules = $external_source['id_agents'];
				
				$agent_name_db = array();
				foreach ($agents as $a) {
					$agent_name_db[] = agents_get_name($a);
				}
				$agent_name_db = implode('<br>',$agent_name_db);
				
				$module_name_db = array();
				foreach ($modules as $m) {
					$module_name_raw = db_get_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $m));
					$module_name_db[] = ui_print_truncate_text(io_safe_output($module_name_raw), 'module_small');
				}
				$module_name_db = implode('<br>',$module_name_db);
			}
			else {
				$agent_name_db = agents_get_name(agents_get_agent_id_by_module_id($item['id_agent_module']));
				$agent_name_db = ui_print_truncate_text($agent_name_db, 'agent_small');
				$module_name_db = db_get_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module']));
				$module_name_db = ui_print_truncate_text(io_safe_output($module_name_db), 'module_small');
			}
			
			$row[2] = $agent_name_db;
			$row[3] = $module_name_db;
		}
	}
	else {
		$row[2] = ui_print_truncate_text(agents_get_name($item['id_agent']), 'agent_small');
		
		if ($item['id_agent_module'] == '') {
			$row [3] = '';
		}
		else {
			$module_name_db = db_get_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module']));
			
			$row[3] =
				ui_print_truncate_text(io_safe_output($module_name_db), 'module_small');
		}
	}
	
	if ($item['period'] > 0) {
		$row[4] = human_time_description_raw($item['period']);
	}
	else {
		$row[4] = '-';
	}
	
	if ($item['description'] == '') {
		$row[5] = '-';
	}
	else {
		$row[5] = ui_print_truncate_text($item['description'], 'description', true, true);
	}
	
	$row[6] = '';
	
	if (check_acl ($config['id_user'], $item['id_group'], "RM")) {
		$row[6] .= '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=edit&id_report=' . $idReport . '&id_item=' . $item['id_rc'] . '">' . html_print_image("images/wrench_orange.png", true, array("title" => __('Edit'))) . '</a>';
		$row[6] .= '&nbsp;';
		$row[6] .= '<a  onClick="if (!confirm (\'Are you sure?\')) return false;" href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=delete&id_report=' . $idReport . '&id_item=' . $item['id_rc'] . $urlFilter . '">' . html_print_image("images/cross.png", true, array("title" => __('Delete'))) .'</a>';
		$row[6] .= '&nbsp;';
		$row[6] .= html_print_checkbox_extended ('delete_multiple[]', $item['id_rc'], false, false, '', 'class="check_delete"', true);
	}
	$row[7] = '';
	//You can sort the items if the filter is not enable.
	if (!$filterEnable) {
		$row[7] .= html_print_checkbox_extended('sorted_items[]', $item['id_rc'], false, false, '', 'class="selected_check"', true);
	}
	$table->data[] = $row;
	$count++;
	//Restore db connection
	if (($config ['metaconsole'] == 1) && ($server_name != '') && defined('METACONSOLE')) {
		metaconsole_restore_db();
	}
}
ui_pagination ($countItems, 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report=' . $idReport . $urlFilter);
html_print_table($table);
ui_pagination ($countItems, 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report=' . $idReport . $urlFilter);

echo "<form action='index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=delete_items&id_report=" . $idReport . "'
	method='post' onSubmit='return added_ids_deleted_items_to_hidden_input();'>";
	echo "<div style='padding-bottom: 20px; text-align: right; width:100%'>";
	html_print_input_hidden('ids_items_to_delete', '');
	html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
	echo "</div>";
echo "</form>";

$table = null;
$table->width = '100%';
$table->colspan[0][0] = 3;
$table->size = array();
$table->size[0] = '25%';
$table->size[1] = '25%';
$table->size[2] = '25%';
$table->size[3] = '25%';
$table->data[0][0] = "<b>". __("Sort items") . "</b>";
$table->data[1][0] = __('Sort selected items from position: ');
$table->data[1][1] = html_print_select_style(
	array('before' => __('Move before to'), 'after' => __('Move after to')), 'move_to',
	'', '', '', '', 0, true);
$table->data[1][2] = html_print_input_text_extended('position_to_sort', 1,
	'text-position_to_sort', '', 3, 10, false, "only_numbers('position_to_sort');", '', true);
$table->data[1][2] .= html_print_input_hidden('ids_items_to_sort', '', true);
$table->data[1][3] = html_print_submit_button(__('Sort'), 'sort_submit', false, 'class="sub upd"', true);

echo "<form action='index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=sort_items&id_report=" . $idReport . "'
	method='post' onsubmit='return added_ids_sorted_items_to_hidden_input();'>";
html_print_table($table);
echo "</form>";

$table = null;
$table->width = '100%';
$table->colspan[0][0] = 3;
$table->size = array();
$table->size[0] = '25%';
$table->size[1] = '25%';
$table->size[2] = '25%';
$table->size[3] = '25%';
$table->data[0][0] = "<b>". __("Delete items") . "</b>";
$table->data[1][0] = __('Delete selected items from position: ');
$table->data[1][1] = html_print_select_style(
	array('above' => __('Delete above to'), 'below' => __('Delete below to')), 'delete_m',
	'', '', '', '', 0, true);
$table->data[1][2] = html_print_input_text_extended('position_to_delete', 1,
	'text-position_to_delete', '', 3, 10, false, "only_numbers('position_to_delete');", '', true);
$table->data[1][2] .= html_print_input_hidden('ids_items_to_delete', '', true);
$table->data[1][3] = html_print_submit_button(__('Delete'), 'delete_submit', false, 'class="sub upd"', true);

echo "<form action='index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=delete_items_pos&id_report=" . $idReport . "'
	method='post'>";
html_print_table($table);
echo "</form>";

?>
<script type="text/javascript">
function toggleFormFilter() {
	if ($("#form_filter").css('display') == 'none') {
		$("#image_form_filter").attr('src', <?php echo "'" . html_print_image('images/up.png', true, false, true) . "'"; ?> );
		$("#form_filter").css('display','');
	}
	else {
		$("#image_form_filter").attr('src', <?php echo "'" . html_print_image('images/down.png', true, false, true) . "'"; ?> );
		$("#form_filter").css('display','none');
	}
}

function message_check_sort_items() {
	var return_value = false;
	
	return_value = confirm("<?php echo __("Are you sure to sort the items into the report?\\n" .
		"This action change the sorting of items into data base."); ?>");
	
	return return_value;
}

function added_ids_sorted_items_to_hidden_input() {
	var ids = '';
	var first = true;
	
	$("input.selected_check:checked").each(function(i, val) {
		if (!first)
			ids = ids + '|';
		first = false;
		
		ids = ids + $(val).val();
	});
	
	$("input[name='ids_items_to_sort']").val(ids);
	
	if (ids == '') {
		alert("<?php echo __("Please select any item to order");?>");
		
		return false;
	}
	else {
		return true;
	}
}

function only_numbers(name) {
	var value = $("input[name='" + name + "']").val();
	
	value = parseInt(value);
	
	if (isNaN(value)) {
		value = 1;
	}
	
	$("input[name='" + name + "']").val(value);
}


function message_check_delete_items() {
	var return_value = false;
	
	return_value = confirm("<?php echo __("Are you sure to delete the items into the report?\\n"); ?>");
	
	return return_value;
}

function added_ids_deleted_items_to_hidden_input() {
	message_check_delete_items();
	
	var ids = '';
	var first = true;
	
	$("input.check_delete:checked").each(function(i, val) {
		if (!first)
			ids = ids + ',';
		first = false;
		
		ids = ids + $(val).val();
	});

	$("input[name='ids_items_to_delete']").val(ids);
	
	if (ids == '') {
		alert("<?php echo __("Please select any item to delete");?>");
		
		return false;
	}
	else {
		return true;
	}
}
</script>
