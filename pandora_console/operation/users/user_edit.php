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
include_once($config['homedir'] . '/include/functions_users.php');
include_once ($config['homedir'] . '/include/functions_groups.php');

$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
$user_info = get_user_info ($id);
if ($user_info["language"] == ""){
	$user_info["language"] = $config["language"];
}

$id = $user_info["id_user"]; //This is done in case there are problems with uppercase/lowercase (MySQL auth has that problem)

if ((!check_acl ($config["id_user"], users_get_groups ($id), "UM")) AND ($id != $config["id_user"])){
	db_pandora_audit("ACL Violation","Trying to view a user without privileges");
	require ("general/noaccess.php");
	exit;
}

//If current user is editing himself or if the user has UM (User Management) rights on any groups the user is part of AND the authorization scheme allows for users/admins to update info
if (($config["id_user"] == $id || check_acl ($config["id_user"], users_get_groups ($id), "UM")) && $config["user_can_update_info"]) {
	$view_mode = false;
} else {
	$view_mode = true;
}

// Header
ui_print_page_header (__('User detail editor'), "images/group.png", false, "", false, "");

if (isset ($_GET["modified"]) && !$view_mode) {
	$upd_info = array ();
	$upd_info["fullname"] = get_parameter_post ("fullname", $user_info["fullname"]);
	$upd_info["firstname"] = get_parameter_post ("firstname", $user_info["firstname"]);
	$upd_info["lastname"] = get_parameter_post ("lastname", $user_info["lastname"]);
	$password_new = get_parameter_post ("password_new", "");
	$password_confirm = get_parameter_post ("password_conf", "");
	$upd_info["email"] = get_parameter_post ("email", $user_info["email"]);
	$upd_info["phone"] = get_parameter_post ("phone", $user_info["phone"]);
	$upd_info["comments"] = get_parameter_post ("comments", $user_info["comments"]);
	$upd_info["language"] = get_parameter_post ("language", $user_info["language"]);
	$upd_info["id_skin"] = get_parameter ("skin", $user_info["id_skin"]);	
	$upd_info["block_size"] = get_parameter ("block_size", $config["block_size"]);
	$upd_info["flash_chart"] = get_parameter ("flash_charts", $config["flash_charts"]);

	if ( !empty ($password_new)) {
		if ($config["user_can_update_password"] && $password_confirm == $password_new) {
			$return = update_user_password ($id, $password_new);
			ui_print_result_message ($return,
				__('Password successfully updated'),
				__('Error updating passwords: %s', $config['auth_error']));
		} elseif ($password_new !== "NON-INIT") {
			ui_print_error_message (__('Passwords didn\'t match or other problem encountered while updating passwords'));
		}
	}

	// No need to display "error" here, because when no update is needed (no changes in data) 
	// SQL function returns	0 (FALSE), but is not an error, just no change. Previous error
	// message could be confussing to the user.

	$return = update_user ($id, $upd_info);
	if ($return > 0) {
		ui_print_result_message ($return,
			__('User info successfully updated'),
			__('Error updating user info'));
	}

	$user_info = $upd_info;
}

echo '<form name="user_mod" method="post" action="index.php?sec=usuarios&amp;sec2=operation/users/user_edit&amp;modified=1&amp;id='.$id.'">';

echo '<table cellpadding="4" cellspacing="4" class="databox" width="90%">';

echo '<tr><td class="datos">'.__('User ID').'</td>';
echo '<td class="datos">';
echo "<b>$id</b>";
echo "</td>";

// Show "Picture" (in future versions, why not, allow users to upload it's own avatar here.
echo "<td rowspan=4>";
if (is_user_admin ($id)) {
	echo html_print_image('images/people_1.png', true); 
} 
else {
	echo html_print_image('images/people_2.png', true); 
}

echo '</td></tr><tr><td class="datos2">'.__('Full (display) name').'</td><td class="datos2">';
html_print_input_text_extended ("fullname", $user_info["fullname"], '', '', 35, 100, $view_mode, '', 'class="input"');

// Not used anymore. In 3.0 database schema continues storing it, but will be removed in the future, or we will 'reuse'
// the database fields for anything more useful.

/*
echo '</td></tr><tr><td class="datos">'.__('First name').'</td><td class="datos">';
html_print_input_text_extended ("firstname", $user_info["firstname"], '', '', 25, 100, $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos2">'.__('Last name').'</td><td class="datos2">';
html_print_input_text_extended ("lastname", $user_info["lastname"], '', '', 25, 100, $view_mode, '', 'class="input"');

*/
if ($view_mode === false) {
	if ($config["user_can_update_password"]) {
		echo '</td></tr><tr><td class="datos">'.__('New Password').'</td><td class="datos">';
		html_print_input_text_extended ("password_new", "", '', '', '15', '25', $view_mode, '', 'class="input"', false, true);
		echo '</td></tr><tr><td class="datos">'.__('Password confirmation').'</td><td class="datos">';
		html_print_input_text_extended ("password_conf", "", '', '', '15', '25', $view_mode, '', 'class="input"', false, true);
	} else {
		echo '<i>'.__('You can not change your password from Pandora FMS under the current authentication scheme').'</i>';
	}
}

echo '</td></tr><tr><td class="datos2">'.__('E-mail').'</td><td class="datos2">';
html_print_input_text_extended ("email", $user_info["email"], '', '', '40', '100', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('Phone number').'</td><td class="datos">';
html_print_input_text_extended ("phone", $user_info["phone"], '', '', '10', '30', $view_mode, '', 'class="input"');

echo '</td></tr><tr><td class="datos">'.__('Language').'</td><td class="datos2">';
echo html_print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $user_info["language"], '', '', '', true);

echo '</td></tr><tr><td class="datos2">'.__('Comments').'</td><td class="datos">';
html_print_textarea ("comments", 2, 60, $user_info["comments"], ($view_mode ? 'readonly="readonly"' : ''));
   
$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_all_group = true;
else
	$display_all_group = false;		

$usr_groups = (users_get_groups($config['id_user'], 'AR', $display_all_group));
$id_usr = $config['id_user'];

// User only can change skins if has more than one group 
if (count($usr_groups) > 1){

	$isFunctionSkins = enterprise_include_once ('include/functions_skins.php');
	if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
		echo '</td></tr><tr><td class="datos">' . __('Skin') . '</td><td class="datos2">';
		echo skins_print_select($id_usr,'skin', $user_info['id_skin'], '', __('None'), 0, true);
	}
}

echo '</td></tr><tr><td class="datos">'.__('Flash charts').'</td><td class="datos2">';
$values = array(-1 => __('Default'),1 => __('Yes'),0 => __('No'));
echo html_print_select($values, 'flash_charts', $user_info["flash_chart"], '', '', -1, true, false, false);
echo '</td></tr><tr><td class="datos">'.__('Block size for pagination').'</td><td class="datos2">';
echo html_print_input_text ('block_size', $user_info["block_size"], '', 5, 5, true);

echo '</td></tr></table>';

echo '<div style="width:90%; text-align:right;">';
if (!$config["user_can_update_info"]) {
	echo '<i>'.__('You can not change your user info from Pandora FMS under the current authentication scheme').'</i>';
} else {
	html_print_submit_button (__('Update'), 'uptbutton', $view_mode, 'class="sub upd"');
}
echo '</div></form>';

echo '<h3>'.__('Profiles/Groups assigned to this user').'</h3>';

$table->width = '50%';
$table->data = array ();
$table->head = array ();
$table->align = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[1] = 'font-weight: bold';
$table->head[0] = __('Profile name');
$table->head[1] = __('Group');

$table->data = array ();

$result = db_get_all_rows_field_filter ("tusuario_perfil", "id_usuario", $id);
if ($result === false) {
	$result = array ();
}

foreach ($result as $profile) {
	$data[0] = '<b>'.profile_get_name ($profile["id_perfil"]).'</b>';
	$data[1] = ui_print_group_icon ($profile["id_grupo"], true).' <a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'">'.groups_get_name ($profile["id_grupo"], true).'</a>';
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	html_print_table ($table);
} else {
	echo '<div class="nf">'.__('This user doesn\'t have any assigned profile/group').'</div>'; 
}
?>
