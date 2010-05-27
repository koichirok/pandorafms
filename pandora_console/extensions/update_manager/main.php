<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

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

$db =& um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);

$settings = um_db_load_settings ();

print_page_header (__('Update manager'), "images/extensions.png", false, "", false, "" );

if ($settings->customer_key == FREE_USER) {
	echo '<div class="notify" style="width: 80%; text-align:left;" >';
	echo '<img src="images/information.png" /> ';
	/* Translators: Do not translade Update Manager, it's the name of the program */
	echo __('The new <a href="http://updatemanager.sourceforge.net">Update Manager</a> client is shipped with Pandora FMS 3.0. It helps system administrators to update their Pandora FMS automatically, since the Update Manager does the task of getting new modules, new plugins and new features (even full migrations tools for future versions) automatically.');
	echo '<p />';
	echo __('Update Manager is one of the most advanced features of Pandora FMS 3.0 Enterprise version, for more information visit <a href="http://pandorafms.com">http://pandorafms.com</a>.');
	echo '<p />';
	echo __('Update Manager sends anonymous information about Pandora FMS usage (number of agents and modules running). To disable it, just delete extension or remove remote server address from Update Manager plugin setup.');
	echo '</div>';
}

$user_key = get_user_key ($settings);
$update_package = (bool) get_parameter_post ('update_package');

if ($update_package) {
	if ($config['enterprise_installed'] == 1) {
		echo '<h2>'.__('Updating').'...</h2>';
		flush ();
		$force = (bool) get_parameter_post ('force_update');
		
		um_client_upgrade_to_latest ($user_key, $force);
		/* TODO: Add a new in tnews */
		$settings = um_db_load_settings ();
	} else {
		echo '<h5 class="error">' . __('This is an Enterprise feature. Visit %s for more information.', '<a href="http://pandorafms.com">http://pandorafms.com</a>') . '</h5>';
	}
}

$package = um_client_check_latest_update ($settings, $user_key);

if (give_acl ($config['id_user'], 0, 'PM')) {
	
	if (is_int ($package) && $package == 1) {
		echo '<h5 class="suc">'.__('Your system is up-to-date').'.</h5>';
	} elseif ($package === false) {
		echo '<h5 class="error">'.__('Server connection failed')."</h5>";
	} elseif (is_int ($package) && $package == 0) {
		echo '<h5 class="error">'.__('Server authorization rejected')."</h5>";
	} else {
		echo '<h5 class="suc">'.__('There\'s a new update for Pandora FMS')."</h5>";
	
		$table->width = '80%';
		$table->data = array ();
	
		$table->data[0][0] = '<strong>'.__('Id').'</strong>';
		$table->data[0][1] = $package->id;
	
		$table->data[1][0] = '<strong>'.__('Timestamp').'</strong>';
		$table->data[1][1] = $package->timestamp;
	
		$table->data[2][0] = '<strong>'.__('Description').'</strong>';
		$table->data[2][1] = html_entity_decode ($package->description);
	
		print_table ($table);
		echo '<div class="action-buttons" style="width: '.$table->width.'">';
		echo '<form method="post">';
		echo __('Overwrite local changes');
		print_checkbox ('force_update', '1', false);
		echo '<p />';
		print_input_hidden ('update_package', 1);
		print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
		echo '</form>';
		echo '</div>';
	}
} 

echo '<h4>'.__('Your system version number is').': '.$settings->current_update.'</h4>';

?>
