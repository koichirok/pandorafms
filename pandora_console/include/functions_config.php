<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Config
 */

/**
 * Creates a single config value in the database.
 * 
 * @param string Config token to create.
 * @param string Value to set.
 *
 * @return bool Config id if success. False on failure.
 */
function create_config_value ($token, $value) {
	return process_sql_insert ('tconfig',
		array ('value' => $value,
			'token' => $token));
}

/**
 * Update a single config value in the database.
 * 
 * If the config token doesn't exists, it's created.
 * 
 * @param string Config token to update.
 * @param string New value to set.
 *
 * @return bool True if success. False on failure.
 */
function update_config_value ($token, $value) {
	global $config;
	
	if (!isset ($config[$token]))
		return (bool) create_config_value ($token, $value);
	
	/* If it has not changed */
	if ($config[$token] == $value)
		return true;
	
	$config[$token] = $value;
	
	return (bool) process_sql_update ('tconfig', 
		array ('value' => $value),
		array ('token' => $token));
}

/**
 * Updates all config values in case setup page was invoked 
 */
function update_config () {
	global $config;
	
	/* If user is not even log it, don't try this */
	if (! isset ($config['id_user']))
		return false;
	
	if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user']))
		return false;
	
	$update_config = (bool) get_parameter ('update_config');
	if (! $update_config)
		return false;
	
	$style = (string) get_parameter ('style', $config["style"]);
	if ($style != $config['style'])
		$style = substr ($style, 0, strlen ($style) - 4);
	
	/* Workaround for ugly language and language_code missmatch */
	$config['language_code'] = $config['language']; //Old value for comparation into update_config_value because in php use language but in db is language_code
	update_config_value ('language_code', (string) get_parameter ('language', $config["language"]));
	$config["language"] = (string) get_parameter ('language', $config["language"]);
	
	update_config_value ('remote_config', (string) get_parameter ('remote_config', $config["remote_config"]));
	update_config_value ('block_size', (int) get_parameter ('block_size', $config["block_size"]));
	update_config_value ('days_purge', (int) get_parameter ('days_purge', $config["days_purge"]));
	update_config_value ('days_compact', (int) get_parameter ('days_compact', $config["days_compact"]));
	update_config_value ('graph_res', (int) get_parameter ('graph_res', $config["graph_res"]));
	update_config_value ('step_compact', (int) get_parameter ('step_compact', $config["step_compact"]));
	update_config_value ('style', $style);
	update_config_value ('graph_color1', (string) get_parameter ('graph_color1', $config["graph_color1"]));
	update_config_value ('graph_color2', (string) get_parameter ('graph_color2', $config["graph_color2"]));
	update_config_value ('graph_color3', (string) get_parameter ('graph_color3', $config["graph_color3"]));
	update_config_value ('sla_period', (int) get_parameter ('sla_period', $config["sla_period"]));
	update_config_value ('date_format', (string) get_parameter ('date_format', $config["date_format"]));
	update_config_value ('trap2agent', (string) get_parameter ('trap2agent', $config["trap2agent"]));
	update_config_value ('autoupdate', (bool) get_parameter ('autoupdate', $config["autoupdate"]));
	update_config_value ('prominent_time', (string) get_parameter ('prominent_time', $config["prominent_time"]));
	update_config_value ('timesource', (string) get_parameter ('timesource', $config["timesource"]));
	update_config_value ('event_view_hr', (int) get_parameter ('event_view_hr', $config["event_view_hr"]));
	update_config_value ('loginhash_pwd', (string) get_parameter ('loginhash_pwd', $config["loginhash_pwd"]));
	update_config_value ('https', (bool) get_parameter ('https', $config["https"]));
	update_config_value ('compact_header', (bool) get_parameter ('compact_header', $config["compact_header"]));
	update_config_value ('fontpath', (string) get_parameter ('fontpath', $config["fontpath"]));
	update_config_value ('round_corner', (bool) get_parameter ('round_corner', $config["round_corner"]));
	update_config_value ('status_images_set', (string) get_parameter ('status_images_set', $config["status_images_set"]));
	update_config_value ('agentaccess', (int) get_parameter ('agentaccess', $config['agentaccess']));
	update_config_value ('flash_charts', (bool) get_parameter ('flash_charts', $config["flash_charts"]));
	update_config_value ('attachment_store', (string) get_parameter ('attachment_store', $config["attachment_store"]));
}

/**
 * Process config variables
 */
function process_config () {
	global $config;
	
	$configs = get_db_all_rows_in_table ('tconfig');
	
	if (empty ($configs)) {
		include ($config["homedir"]."/general/error_emptyconfig.php");
		exit;
	}
	
	/* Compatibility fix */
	foreach ($configs as $c) {
		switch ($c["token"]) {
		case "language_code":
			$config['language'] = $c['value'];
			break;
		case "auth":
			include ($config["homedir"]."/general/error_authconfig.php");
			exit;
		default:
			$config[$c['token']] = $c['value'];
		}
	}
	
	if (isset ($config['homeurl']) && $config['homeurl'][0] != '/') {
		$config['homeurl'] = '/'.$config['homeurl'];
	}
	
	if (!isset ($config['date_format'])) {
		update_config_value ('date_format', 'F j, Y, g:i a');
	}
	
	if (!isset ($config['event_view_hr'])) {
		update_config_value ('event_view_hr', 8);
	}
	
	if (!isset ($config['loginhash_pwd'])) {
		update_config_value ('loginhash_pwd', rand (0, 1000) * rand (0, 1000)."pandorahash");
	}
	
	if (!isset ($config["trap2agent"])) {
		update_config_value ('trap2agent', 0);
	}
	
	if (!isset ($config["sla_period"]) || empty ($config["sla_period"])) {
		update_config_value ('sla_period', 604800);
	}
	
	if (!isset ($config["prominent_time"])) {
		// Prominent time tells us what to show prominently when a timestamp is
		// displayed. The comparation (... days ago) or the timestamp (full date)
		update_config_value ('prominent_time', 'comparation');
	}
	
	if (!isset ($config["timesource"])) {
		// Timesource says where time comes from (system or mysql)
		update_config_value ('timesource', 'system');
	}
	
	if (!isset ($config["https"])) {
		// Sets whether or not we want to enforce https. We don't want to go to a
		// potentially unexisting config by default
		update_config_value ('https', false);
	}
	
	if (!isset ($config["compact_header"])) {
		update_config_value ('compact_header', false);
	}
	
	if (!isset ($config['status_images_set'])) {
		update_config_value ('status_images_set', 'default');
	}
	
	if (isset ($_SESSION['id_usuario']))
		$config["id_user"] = $_SESSION["id_usuario"];

	if (!isset ($config["round_corner"])) {
		update_config_value ('round_corner', false);
	}

	if (!isset ($config["agentaccess"])){
		update_config_value ('agentaccess', true);
	}

	// This is not set here. The first time, when no
	// setup is done, update_manager extension manage it
	// the first time make a conenction and disable itself
	// Not Managed here !
	
	// if (!isset ($config["autoupdate"])){
	// 	update_config_value ('autoupdate', true);
        // }

	if (!isset ($config["auth"])) {
		require_once ($config["homedir"]."/include/auth/mysql.php");
	} else {
		require_once ($config["homedir"]."/include/auth/".$config["auth"]["scheme"].".php");
	}
	
	
	// Next is the directory where "/attachment" directory is placed, to upload files stores. 
	// This MUST be writtable by http server user, and should be in pandora root. 
	// By default, Pandora adds /attachment to this, so by default is the pandora console home dir
	if (!isset ($config['attachment_store'])) {
		update_config_value ( 'attachment_store', $config['homedir'].'/attachment');
	}
	
	if (!isset ($config['fontpath'])) {
		update_config_value ( 'fontpath', $config['homedir'].'/include/FreeSans.ttf');
	}

	if (!isset ($config['style'])) {
		update_config_value ( 'style', 'pandora');
	}

	if (!isset ($config['flash_charts'])) {
		update_config_value ( 'flash_charts', true);
	}
			
	/* Finally, check if any value was overwritten in a form */
	update_config ();
}
?>
