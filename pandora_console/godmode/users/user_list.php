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

include_once($config['homedir'] . "/include/functions_profile.php");
include_once ($config['homedir'].'/include/functions_users.php');
require_once ($config['homedir'] . '/include/functions_groups.php');
enterprise_include_once ('include/functions_metaconsole.php');
enterprise_include_once ('meta/include/functions_users_meta.php');

if (! check_acl ($config['id_user'], 0, "UM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access User Management");
	require ("general/noaccess.php");
	exit;
}

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$tab = get_parameter('tab', 'user');
$pure = get_parameter('pure', 0);

$selected = 'border: 1px solid black;';
$selectUserIDUp = '';
$selectUserIDDown = '';
$selectFullnameUp = '';
$selectFullnameDown = '';
$selectLastConnectUp = '';
$selectLastConnectDown = '';
$order = null;

switch ($sortField) {
	case 'id_user':
		switch ($sort) {
			case 'up':
				$selectUserIDUp = $selected;
				$order = array('field' => 'id_user', 'order' => 'ASC');
				break;
			case 'down':
				$selectUserIDDown = $selected;
				$order = array('field' => 'id_user', 'order' => 'DESC');
				break;
		}
		break;
	case 'fullname':
		switch ($sort) {
			case 'up':
				$selectFullnameUp = $selected;
				$order = array('field' => 'fullname', 'order' => 'ASC');
				break;
			case 'down':
				$selectFullnameDown = $selected;
				$order = array('field' => 'fullname', 'order' => 'DESC');
				break;
		}
		break;
	case 'last_connect':
		switch ($sort) {
			case 'up':
				$selectLastConnectUp = $selected;
				$order = array('field' => 'fullname', 'order' => 'ASC');
				break;
			case 'down':
				$selectLastConnectDown = $selected;
				$order = array('field' => 'fullname', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectUserIDUp = '';
		$selectUserIDDown = '';
		$selectFullnameUp = $selected;
		$selectFullnameDown = '';
		$selectLastConnectUp = '';
		$selectLastConnectDown = '';
		$order = array('field' => 'fullname', 'order' => 'ASC');
		break;
}


$buttons[$tab]['active'] = true;

// Header
if (defined('METACONSOLE')) {
	
	user_meta_print_header();
	$sec = 'advanced';
	
}
else {
	
	$buttons = array(
		'user' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">' . 
				html_print_image ("images/gm_users.png", true, array ("title" => __('User management'))) .'</a>'),
		'profile' => array(
			'active' => false,
			'text' => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">' . 
				html_print_image ("images/profiles.png", true, array ("title" => __('Profile management'))) .'</a>'));
	
	$buttons[$tab]['active'] = true;
	
	ui_print_page_header (__('User management').' &raquo; '.__('Users defined in Pandora'), "images/gm_users.png", false, "", true, $buttons);
	
	$sec = 'gusuarios';
	
}

enterprise_hook('open_meta_frame');

$disable_user = get_parameter ("disable_user", false);

if (isset ($_GET["user_del"])) { //delete user
	$id_user = get_parameter ("delete_user", 0);
	// Only allow delete user if is not the actual user
	if ($id_user != $config['id_user']) {
		
		$user_row = users_get_user_by_id($id_user);
		
		$result = delete_user ($id_user);
		
		if ($result) {
			users_save_logout($user_row, true);
			
			db_pandora_audit("User management",
				__("Deleted user %s", io_safe_input($id_user)));
		}
		
		ui_print_result_message ($result,
			__('Successfully deleted'),
			__('There was a problem deleting the user'));
		
		// Delete the user in all the consoles
		if (defined ('METACONSOLE') && isset ($_GET["delete_all"])) {
			
			$servers = metaconsole_get_servers();
			foreach ($servers as $server) {
				
				// Connect to the remote console
				metaconsole_connect($server);
				
				// Delete the user
				$result = delete_user ($id_user);
				if ($result) {
					db_pandora_audit("User management",
					__("Deleted user %s from metaconsole", io_safe_input($id_user)));
				}
				
				// Restore the db connection
				metaconsole_restore_db();
				
				// Log to the metaconsole too
				if ($result) {
					db_pandora_audit("User management",
				                     __("Deleted user %s from %s", io_safe_input($id_user), io_safe_input($server['server_name'])));
				}
				ui_print_result_message ($result,
					__('Successfully deleted from %s', io_safe_input($server['server_name'])),
					__('There was a problem deleting the user from %s', io_safe_input($server['server_name'])));
			}
		}
	}
	else {
		ui_print_error_message (__('There was a problem deleting the user'));
	}
}
elseif (isset ($_GET["profile_del"])) { //delete profile
	$id_profile = (int) get_parameter_post ("delete_profile");
	$result = profile_delete_profile ($id_profile);
	ui_print_result_message ($result, 
		__('Successfully deleted'),
		__('There was a problem deleting the profile'));
}
elseif ($disable_user !== false) { //disable_user
	$id_user = get_parameter ("id", 0);
	
	if ($id_user !== 0) {
		$result = users_disable ($id_user, $disable_user);
	}
	else {
		$result = false;
	}
	
	if ($disable_user == 1) {
		ui_print_result_message ($result, 
			__('Successfully disabled'),
			__('There was a problem disabling user'));
	}
	else {
		ui_print_result_message ($result, 
			__('Successfully enabled'),
			__('There was a problem enabling user'));
	}
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = '99%';
$table->class = "databox";
$table->head = array ();
$table->data = array ();
$table->align = array ();
$table->size = array ();
$table->valign = array();

$table->head[0] = __('User ID') . ' ' .
	'<a href="?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=id_user&sort=up&pure='.$pure.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectUserIDUp)) . '</a>' .
	'<a href="?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=id_user&sort=down&pure='.$pure.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectUserIDDown)) . '</a>';
$table->head[1] = __('Name') . ' ' .
	'<a href="?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=fullname&sort=up&pure='.$pure.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectFullnameUp )) . '</a>' .
	'<a href="?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=fullname&sort=down&pure='.$pure.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectFullnameDown)) . '</a>';
$table->head[2] = __('Last contact') . ' ' . 
	'<a href="?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=last_connect&sort=up&pure='.$pure.'">' . html_print_image("images/sort_up.png", true, array("style" => $selectLastConnectUp )) . '</a>' .
	'<a href="?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=last_connect&sort=down&pure='.$pure.'">' . html_print_image("images/sort_down.png", true, array("style" => $selectLastConnectDown)) . '</a>';
$table->head[3] = __('Admin');
$table->head[4] = __('Profile');
$table->head[5] = __('Description');
$table->head[6] = '<span title="Operations">' . __('Op.') . '</span>';

$table->align[2] = "right";
$table->align[3] = "center";
$table->size[2] = '150px';

if (defined('METACONSOLE')) {
	$table->size[6] = '110px';
}
else {
	$table->size[6] = '85px';
}

$table->valign[0] = 'top';
$table->valign[1] = 'top';
$table->valign[2] = 'top';
$table->valign[3] = 'top';
$table->valign[4] = 'top';
$table->valign[5] = 'top';
$table->valign[6] = 'top';

$info1 = array ();

$info1 = get_users ($order);

$info = array();
$own_info = get_user_info ($config['id_user']);
$own_groups = users_get_groups ($config['id_user'], 'AR', $own_info['is_admin']);

if ($own_info['is_admin']) {
	$info = $info1;
}
// If user is not admin then don't display admin users and user of others groups.
else {
	foreach ($info1 as $key => $usr) {
		$u = get_user_info ($key);
		$g = users_get_groups ($key, 'AR', $u['is_admin']);
		$result = array_intersect($g, $own_groups);
		if (!$usr['is_admin'] && !empty($result))
			$info[$key] = $usr;
		unset($u);
		unset($g);
	}
}

// Prepare pagination
ui_pagination (count($info));

$offset = (int) get_parameter ('offset');
$limit = (int) $config['block_size'];

$rowPair = true;
$iterator = 0;
$cont = 0;
foreach ($info as $user_id => $user_info) {
	$cont++;
	
	////////////////////
	// Manual pagination due the complicated process of the ACL data
	if ($cont <= $offset) {
		continue;
	}
	
	if ($cont > ($limit + $offset)) {
		break;
	}
	////////////////////
	
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	if ($user_info['disabled']) {
		$table->rowclass[$iterator] .= ' disabled_row_user';
	}
	$iterator++;
	
	$data[0] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'&amp;id='.$user_id.'">'.$user_id.'</a>';
	$data[1] = '<ul style="margin-top: 0 !important; margin-left: auto !important; padding-left: 10px !important; list-style-type: none !important;">';
	$data[1] .= '<li>' . $user_info["fullname"] . '</li>';
	$data[1] .= '<li><b>' . __('First name') . ':</b> ' . $user_info["firstname"] . '</li>';
	$data[1] .= '<li><b>' . __('Last name') . ':</b> ' . $user_info["lastname"] . '</li>';
	$data[1] .= '<li><b>' . __('Phone') . ':</b> ' . $user_info["phone"] . '</li>';
	$data[1] .= '<li><b>' . __('E-mail') . ':</b> ' . $user_info["email"] . '</li>';
	$data[1] .= '</ul>';
	$data[2] = ui_print_timestamp ($user_info["last_connect"], true);
	
	if ($user_info["is_admin"]) {
		$data[3] = html_print_image ("images/user_suit.png", true,
			array ("alt" => __('Admin'),
				"title" => __('Administrator'))) . '&nbsp;';
	}
	else {
		/*
		$data[3] = html_print_image ("images/user_green.png", true,
			array ("alt" => __('User'),
				"title" => __('Standard User'))) . '&nbsp;';
		*/
		$data[3] = "";
	}
	
	$data[4] = "";
	$result = db_get_all_rows_field_filter ("tusuario_perfil", "id_usuario", $user_id);
	if ($result !== false) {
		$data[4] .= "<table width='100%'>";
		foreach ($result as $row) {
			$data[4] .= "<tr>";
			$data[4] .= "<td>";
			$data[4] .= profile_get_name ($row["id_perfil"]);
			$data[4] .= "</td>";
			$data[4] .= "<td align='right'>";
			$data[4] .= groups_get_name ($row["id_grupo"], true);
			$data[4] .= "</td>";
			$data[4] .= "</tr>";
		}
		$data[4] .= "</table>";
	}
	else {
		$data[4] .= __('The user doesn\'t have any assigned profile/group');
	}
	
	$data[5] = ui_print_string_substr ($user_info["comments"], 24, true);
	
	if ($user_info['disabled'] == 0) {
		$data[6] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/user_list&amp;disable_user=1&pure='.$pure.'&amp;id='.$user_info['id_user'].'">'.html_print_image('images/lightbulb.png', true, array('title' => __('Disable'))).'</a>';
	}
	else {
		$data[6] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/user_list&amp;disable_user=0&pure='.$pure.'&amp;id='.$user_info['id_user'].'">'.html_print_image('images/lightbulb_off.png', true, array('title' => __('Enable'))).'</a>';
	}
	$data[6] .= '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'&amp;id='.$user_id.'">'.html_print_image('images/config.png', true, array('title' => __('Edit'))).'</a>';
	if ($config["admin_can_delete_user"] && $user_info['id_user'] != $config['id_user']) {
		$data[6] .= "<a href='index.php?sec=".$sec."&sec2=godmode/users/user_list&user_del=1&pure=".$pure."&delete_user=".$user_info['id_user']."'>".html_print_image('images/cross.png', true, array ('title' => __('Delete'), 'onclick' => "if (! confirm ('" .__('Deleting User'). " ". $user_info['id_user'] . ". " . __('Are you sure?') ."')) return false"))."</a>";
		if (defined('METACONSOLE')) {
			$data[6] .= "<a href='index.php?sec=".$sec."&sec2=godmode/users/user_list&user_del=1&pure=".$pure."&delete_user=".$user_info['id_user']."&delete_all=1'>".html_print_image('images/cross_double.png', true, array ('title' => __('Delete from all consoles'), 'onclick' => "if (! confirm ('" .__('Deleting User %s from all consoles', $user_info['id_user']) . ". " . __('Are you sure?') ."')) return false"))."</a>";	
		}
	}
	else {
		$data[6] .= ''; //Delete button not in this mode
	}
	array_push ($table->data, $data);
}

html_print_table ($table);

echo '<div style="width: '.$table->width.'" class="action-buttons">';
unset ($table);
if ($config["admin_can_add_user"] !== false) {
	echo '<form method="post" action="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'">';
	html_print_input_hidden ('new_user', 1);
	html_print_submit_button (__('Create user'), "crt", false, 'class="sub next"');
	echo '</form>';
}
else {
	echo '<i>'.__('The current authentication scheme doesn\'t support creating users from Pandora FMS').'</i>';
}
echo '</div>';

enterprise_hook('close_meta_frame');

?>
