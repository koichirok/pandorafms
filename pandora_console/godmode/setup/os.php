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

check_login ();

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

$action = get_parameter('action', 'new');
$idOS = get_parameter('id_os', 0);
$tab = get_parameter('tab', 'builder');

if ($idOS) {
	$os = get_db_row_filter('tconfig_os', array('id_os' => $idOS));
	$name = $os['name'];
	$description = $os['description'];
	$icon = $os['icon_name'];
}
else {
	$name = get_parameter('name', '');
	$description = get_parameter('description', '');
	$icon = get_parameter('icon',0);
}

$message = '';

switch ($action) {
	default:
	case 'new':
		$actionHidden = 'save';
		$textButton = __('Create');
		$classButton = 'class="sub next"';
		break;
	case 'edit':
		$actionHidden = 'update';
		$textButton = __('Update');
		$classButton = 'class="sub upd"';
		break;
	case 'save':
		$values = array();
		$values['name'] = $name;
		$values['description'] = $description;
		
		if (($icon !== 0) && ($icon != '')) {
			$values['icon_name'] = $icon;
		}
		$resultOrId = process_sql_insert('tconfig_os', $values);
		
		if ($resultOrId === false) {
			$message = print_error_message(__('Fail to create OS'), '', true);
			$tab = 'builder';
			$actionHidden = 'save';
			$textButton = __('Create');
			$classButton = 'class="sub next"';
		}
		else {
			$message = print_success_message(__('Success to create OS'), '', true);
			$tab = 'list';
		}
		break;
	case 'update':
		$name = get_parameter('name', '');
		$description = get_parameter('description', '');
		$icon = get_parameter('icon',0);
		
		$values = array();
		$values['name'] = $name;
		$values['description'] = $description;
		
		if (($icon !== 0) && ($icon != '')) {
			$values['icon_name'] = $icon;
		}
		$result = process_sql_update('tconfig_os', $values, array('id_os' => $idOS));
		
		$message = print_result_message($result, __('Success to update OS'), __('Error to update OS'), '', true);
		if ($result !== false) {
			$tab = 'list';
		}
		
		$actionHidden = 'update';
		$textButton = __('Update');
		$classButton = 'class="sub upd"';
		break;
	case 'delete':
		$sql = 'SELECT COUNT(id_os) AS count FROM tagente WHERE id_os = ' . $idOS;
		$count = get_db_all_rows_sql($sql);
		$count = $count[0]['count'];
		
		if ($count > 0) {
			$message = print_error_message(__('There are agents with this OS.'), '', true);
		}
		else {
			$result = (bool)process_sql_delete('tconfig_os', array('id_os' => $idOS));
			
			$message = print_result_message($result, __('Success to delete'), __('Error to delete'), '', true);
		}
		break;
}

$buttons = array(
	'list' => array(
		'active' => false,
		'text' => '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&tab=list">' . 
			print_image ("images/god6.png", true, array ("title" => __('List OS'))) .'</a>'),
	'builder' => array(
		'active' => false,
		'text' => '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&tab=builder">' . 
			print_image ("images/config.png", true, array ("title" => __('Builder OS'))) .'</a>'));

			
$buttons[$tab]['active'] = true;

// Header
print_page_header(__('Edit OS'), "", false, "", true, $buttons);

echo $message;

switch ($tab) {
	case 'list':
		require_once('godmode/setup/os.list.php');
		return;
		break;
	case 'builder':
		require_once('godmode/setup/os.builder.php');
		return;
		break;
}
?>