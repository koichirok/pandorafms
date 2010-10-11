<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage DataBase
 */

/**
 * When you delete (with the function "process_sql_delete" or other) any row in
 * any table, some times the cache save the data just deleted, because you
 * must use "clean_cache". 
 */

/** 
 * Check if login session variables are set.
 *
 * It will stop the execution if those variables were not set
 * 
 * @return bool 0 on success exit() on no success
 */

function check_login () {
	global $config;
	
	if (!isset ($config["homedir"])) {
		// No exists $config. Exit inmediatly
		include("general/noaccess.php");
		exit;
	}
	if ((isset($_SESSION["id_usuario"])) AND ($_SESSION["id_usuario"] != "")) {
		if (is_user ($_SESSION["id_usuario"])) {
			return 0;
		}
	}
	else {
		require_once($config["homedir"].'/mobile/include/user.class.php');
		session_start ();
		session_write_close ();
		$user = $_SESSION['user'];
		$id_user = $user->getIdUser();
		
		if (is_user ($id_user)) {
			return 0;
		}
	}
	audit_db ("N/A", getenv ("REMOTE_ADDR"), "No session", "Trying to access without a valid session");
	include ($config["homedir"]."/general/noaccess.php");
	exit;
}

/**
 * 
 * Escape string to set it properly to use in sql queries
 * 
 * @param string String to be cleaned.
 * 
 * @return string String cleaned.
 */
function escape_string_sql ($string) {
	
	$str = mysql_real_escape_string($string);
	
	return $str;
}



/**
 * Return a array of id_group of childrens (to branches down)
 * 
 * @param integer $parent The id_group parent to search the childrens. 
 * @param array $groups The groups, its for optimize the querys to DB.
 */
function get_childrens($parent, $groups = null) {
	if (empty($groups)) {
		$groups = get_db_all_rows_in_table('tgrupo');
	}
	
	$return = array();
	
	foreach ($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		if ($group['parent'] == $parent) {
			$return = $return + array($group['id_grupo'] => $group) + get_childrens($group['id_grupo'], $groups);
		}
	}
	
	return $return;
}

/**
 * Return a array of id_group of parents (to roots up).
 * 
 * @param integer $parent The id_group parent to search the parent. 
 * @param boolean $onlyPropagate Flag to search only parents that true to propagate.
 * @param array $groups The groups, its for optimize the querys to DB.
 */
function get_parents($parent, $onlyPropagate = false, $groups = null) {
	if (empty($groups)) {
		$groups = get_db_all_rows_in_table('tgrupo');
	}
	
	$return = array();
	
	foreach ($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		if (($group['id_grupo'] == $parent) && ($group['propagate'] || !$onlyPropagate)) {
			$return = $return + array($group['id_grupo'] => $group) + get_parents($group['parent'], $groups);
		}
	}
	
	return $return;
}
	
/**
 * Check access privileges to resources
 *
 * Access can be:
 * IR - Incident/report Read
 * IW - Incident/report Write
 * IM - Incident/report Management
 * AR - Agent Read
 * AW - Agent Write
 * LW - Alert Write
 * UM - User Management
 * DM - DB Management
 * LM - Alert Management
 * PM - Pandora Management
 *
 * @param int $id_user User id
 * @param int $id_group Agents group id to check from
 * @param string $access Access privilege
 *
 * @return bool 1 if the user has privileges, 0 if not.
 */
function check_acl ($id_user, $id_group, $access) {
	if (empty ($id_user)) {
		//User ID needs to be specified
		trigger_error ("Security error: check_acl got an empty string for user id", E_USER_WARNING);
		return 0;
	}
	elseif (is_user_admin ($id_user)) {
		return 1;
	}
	else {
		$id_group = (int) $id_group;
	}
	
	$parents_id = array($id_group);
	if ($id_group != 0) {
		$group = get_db_row_filter('tgrupo', array('id_grupo' => $id_group));
		$parents = get_parents($group['parent'], true);
		
		foreach ($parents as $parent) {
			$parents_id[] = $parent['id_grupo'];
		}
	}
	else {
		$parents_id = array();
	}
	
	//Joined multiple queries into one. That saves on the query overhead and query cache.
	if ($id_group == 0) {
		$query = sprintf("SELECT tperfil.incident_view, tperfil.incident_edit,
				tperfil.incident_management, tperfil.agent_view,
				tperfil.agent_edit, tperfil.alert_edit,
				tperfil.alert_management, tperfil.pandora_management,
				tperfil.db_management, tperfil.user_management
			FROM tusuario_perfil, tperfil
			WHERE tusuario_perfil.id_perfil = tperfil.id_perfil
				AND tusuario_perfil.id_usuario = '%s'", $id_user);
		//GroupID = 0, group id doesnt matter (use with caution!)
	}
	else {
		$query = sprintf("SELECT tperfil.incident_view, tperfil.incident_edit,
				tperfil.incident_management, tperfil.agent_view,
				tperfil.agent_edit, tperfil.alert_edit,
				tperfil.alert_management, tperfil.pandora_management,
				tperfil.db_management, tperfil.user_management
			FROM tusuario_perfil, tperfil
			WHERE tusuario_perfil.id_perfil = tperfil.id_perfil 
				AND tusuario_perfil.id_usuario = '%s'
				AND (tusuario_perfil.id_grupo IN (%s)
				OR tusuario_perfil.id_grupo = 0)", $id_user, implode(', ', $parents_id));
	}
	
	$rowdup = get_db_all_rows_sql ($query);
	
	if (empty ($rowdup))
		return 0;
	
	$result = 0;
	foreach ($rowdup as $row) {
		// For each profile for this pair of group and user do...
		switch ($access) {
		case "IR":
			$result += $row["incident_view"];
			break;
		case "IW":
			$result += $row["incident_edit"];
			break;
		case "IM":
			$result += $row["incident_management"];
			break;
		case "AR":
			$result += $row["agent_view"];
			break;
		case "AW":
			$result += $row["agent_edit"];
			break;
		case "LW":
			$result += $row["alert_edit"];
			break;
		case "LM":
			$result += $row["alert_management"];
			break;
		case "PM":
			$result += $row["pandora_management"];
			break;
		case "DM":
			$result += $row["db_management"];
			break;
		case "UM":
			$result += $row["user_management"];
			break;
		}
	}
	
	if ($result >= 1)
		return 1;
		
	return 0;
}

/*
 * @deprecated Use check_acl instead
 */
function give_acl ($id_user, $id_group, $access) {
	return check_acl ($id_user, $id_group, $access);
}

/**
 * Filter out groups the user doesn't have access to
 *
 * Access can be:
 * IR - Incident Read
 * IW - Incident Write
 * IM - Incident Management
 * AR - Agent Read
 * AW - Agent Write
 * LW - Alert Write
 * UM - User Management
 * DM - DB Management
 * LM - Alert Management
 * PM - Pandora Management
 *
 * @param int $id_user User id
 * @param mixed $id_group Group ID(s) to check
 * @param string $access Access privilege
 *
 * @return array Groups the user DOES have acces to (or an empty array)
 */
function safe_acl_group ($id_user, $id_groups, $access) {
	if (!is_array ($id_groups) && check_acl ($id_user, $id_groups, $access)) {
		/* Return all the user groups if it's the group All */
		if ($id_groups == 0)
			return array_keys (get_user_groups ($id_user, $access));
		return array ($id_groups);
	} elseif (!is_array ($id_groups)) {
		return array ();
	}
	
	foreach ($id_groups as $group) {
		//Check ACL. If it doesn't match, remove the group
		if (!check_acl ($id_user, $group, $access)) {
			unset ($id_groups[$group]);
		}
	}
	
	return $id_groups;
}

	
/** 
 * Adds an audit log entry (DEPRECATED!)
 * 
 * @param string $id User id
 * @param string $ip Client IP
 * @param string $accion Action description
 * @param string $descripcion Long action description
 */
function audit_db ($id, $ip, $accion, $descripcion){
	$accion = safe_input($accion);
	$descripcion = safe_input($descripcion);
	$sql = sprintf ("INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion, utimestamp) VALUES ('%s','%s',NOW(),'%s','%s',UNIX_TIMESTAMP(NOW()))",$id,$accion,$ip,$descripcion);
	process_sql ($sql);
}


/** 
 * Adds an audit log entry (new function in 3.0)
 * 
 * @param string $accion Action description
 * @param string $descripcion Long action description
 */
function pandora_audit ($accion, $descripcion){
	global $config;
	$ip = $config["remote_addr"]; 
	$id = $config["id_user"];

	$accion = safe_input($accion);
	$descripcion = safe_input($descripcion);
	$sql = sprintf ("INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion, utimestamp) VALUES ('%s','%s',NOW(),'%s','%s',UNIX_TIMESTAMP(NOW()))",$id,$accion,$ip,$descripcion);
	process_sql ($sql);
}



/**
 * Log in a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function logon_db ($id_user, $ip) {
	audit_db ($id_user, $ip, "Logon", "Logged in");
	// Update last registry of user to set last logon. How do we audit when the user was created then?
	process_user_contact ($id_user);
}

/**
 * Log out a user into Pandora.
 *
 * @param string $id_user User id
 * @param string $ip Client user IP address.
 */
function logoff_db ($id_user, $ip) {
	audit_db ($id_user, $ip, "Logoff", "Logged out");
}

/**
 * Get profile name from id.
 * 
 * @param int $id_profile Id profile in tperfil
 * 
 * @return string Profile name of the given id
 */
function get_profile_name ($id_profile) {
	return (string) get_db_value ('name', 'tperfil', 'id_perfil', (int) $id_profile);
}

/**
 * Selects all profiles (array (id => name))
 *
 * @return array List of all profiles
 */
function get_profiles () {
	$profiles = get_db_all_rows_in_table ("tperfil", "name");
	$return = array ();
	if ($profiles === false) {
		return $return;
	}
	foreach ($profiles as $profile) {
		$return[$profile["id_perfil"]] = $profile["name"];
	}
	return $return;
}


/**
 * Create Profile for User
 *
 * @param string User ID
 * @param int Profile ID (default 1 => AR)
 * @param int Group ID (default 1 => All) 
 * @param string Assign User who assign the profile to user.
 *
 * @return bool True if succesful, false if not
 */
function create_user_profile ($id_user, $id_profile = 1, $id_group = 0, $assignUser = false) {
	global $config;
	
	if (empty ($id_profile) || $id_group < 0)
		return false;
	
	if (isset ($config["id_user"])) {
		//Usually this is set unless we call it while logging in (user known by auth scheme but not by pandora)
		$assign = $config["id_user"];
	} else {
		$assign = $id_user;
	}
	
	if ($assignUser !== false)
		$assign = $assignUser;
	
	$insert = array (
		"id_usuario" => $id_user,
		"id_perfil" => $id_profile,
		"id_grupo" => $id_group,
		"assigned_by" => $assign
	);

	return (bool) process_sql_insert ("tusuario_perfil", $insert);
}

/** 
 * Delete user profile from database
 * 
 * @param string User ID
 * @param int Profile ID
 * 
 * @return bool Whether or not it's deleted
 */
function delete_user_profile ($id_user, $id_profile) {
	$sql = sprintf ("DELETE FROM tusuario_perfil WHERE id_usuario = '%s' AND id_up = %d", $id_user, $id_profile);
	return (bool) process_sql ($sql);
}

/** 
 * Delete profile from database (not user-profile link (tusuario_perfil), but the actual profile (tperfil))
 * 
 * @param int Profile ID
 * 
 * @return bool Whether or not it's deleted
 */
function delete_profile ($id_profile) {
	$sql = sprintf ("DELETE FROM tperfil WHERE id_perfil = %d", $id_profile);
	return (bool) process_sql ($sql);
}

/** 
 * Get disabled field of a group
 * 
 * @param int id_group Group id
 * 
 * @return bool Disabled field of given group
 */
function give_disabled_group ($id_group) {
	return (bool) get_db_value ('disabled', 'tgrupo', 'id_grupo', (int) $id_group);
}

/**
 * Test if the param array is all groups in db.
 * 
 * @param array $id_groups
 * 
 * @return bool It's true when the array is all groups in db.
 */
function isAllGroups($idGroups) {
	if (!is_array($idGroups))
		$arrayGroups = array($idGroups);
	else
		$arrayGroups = $idGroups;
		
	$groupsDB = get_db_all_rows_in_table ('tgrupo');
	
	$returnVar = true;
	foreach ($groupsDB as $group) {
		if (!in_array($group['id_grupo'], $arrayGroups)) {
			$returnVar = false;
			break;
		}
	}
	
	return $returnVar;
}

/**
 * Get all the agents within a group(s).
 *
 * @param mixed $id_group Group id or an array of ID's. If nothing is selected, it will select all
 * @param mixed $search to add Default: False. If True will return disabled agents as well. If searching array (disabled => (bool), string => (string))
 * @param string $case Which case to return the agentname as (lower, upper, none)
 * @param boolean $noACL jump the ACL test.
 *
 * @return array An array with all agents in the group or an empty array
 */
function get_group_agents ($id_group = 0, $search = false, $case = "lower", $noACL = false) {
	global $config;
	
	if (!$noACL) {
		$id_group = safe_acl_group ($config["id_user"], $id_group, "AR");
		
		if (empty ($id_group)) {
			//An empty array means the user doesn't have access
			return array ();
		}
	}
	
	if (is_array($id_group)) {
		$search_sql = sprintf ('WHERE id_grupo IN (%s)', implode (",", $id_group));
	}
	else if ($id_group == 0) { //All group
		$search_sql = 'WHERE 1 = 1';
	}
	else {
		$search_sql = sprintf ('WHERE id_grupo = %d', $id_group);
	}

	if ($search === true) {
		//No added search. Show both disabled and non-disabled
	} 
	elseif (is_array ($search)) {
		if (isset ($search["disabled"])) {
			$search_sql .= ' AND disabled = '.($search["disabled"] ? 1 : 0); //Bool, no cleanup necessary
		} else {
			$search_sql .= ' AND disabled = 0';
		}
		unset ($search["disabled"]);
		if (isset ($search["string"])) {
			$string = safe_input ($search["string"]);
			$search_sql .= ' AND (nombre LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%")';
			
			unset ($search["string"]);
		}
		
		if (isset ($search["name"])) {
			$name = safe_input ($search["name"]);
			$search_sql .= ' AND nombre LIKE "' . $name . '" ';
			
			unset ($search["name"]);
		}
		
		if (! empty ($search)) {
			$search_sql .= ' AND '.format_array_to_where_clause_sql ($search);
		}
	}
	else {
		$search_sql .= ' AND disabled = 0';
	}
	
	$sql = sprintf ("SELECT id_agente, nombre FROM tagente %s ORDER BY nombre", $search_sql);
	$result = get_db_all_rows_sql ($sql);
	
	if ($result === false)
		return array (); //Return an empty array
	
	$agents = array ();
	foreach ($result as $row) {
		switch ($case) {
		case "lower":
			$agents[$row["id_agente"]] = mb_strtolower ($row["nombre"], "UTF-8");
		break;	
		case "upper":
			$agents[$row["id_agente"]] = mb_strtoupper ($row["nombre"], "UTF-8");
		break;
		default:
			$agents[$row["id_agente"]] = $row["nombre"];
		}
	}
	return ($agents);
}

/**
 * Get a single module information.
 *
 * @param int agentmodule id to get.
 *
 * @return array An array with module information
 */
function get_agentmodule ($id_agentmodule) {
	return get_db_row ('tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
}

/**
 * Get a id of module from his name and the agent id
 *
 * @param string agentmodule name to get.
 * @param int agent id.
 *
 * @return int the agentmodule id
 */
function get_agentmodule_id ($agentmodule_name, $agent_id) {
	return get_db_row_filter ('tagente_modulo', array('nombre' => $agentmodule_name, 'id_agente' => $agent_id, 'delete_pending' => 0)); 
}

/**
 * Get a if a module is init.
 *
 * @param int agentmodule id to get.
 *
 * @return bool true if is init and false if is not init
 */
function get_agentmodule_is_init ($id_agentmodule) {
	$result = get_db_row_filter ('tagente_estado', array('id_agente_modulo' => $id_agentmodule), 'utimestamp');
	return (bool)$result['utimestamp'];
}

/**
 * Get all the modules in an agent. If an empty list is passed it will select all
 *
 * @param mixed Agent id to get modules. It can also be an array of agent id's.
 * @param mixed Array, comma delimited list or singular value of rows to
 * select. If nothing is specified, nombre will be selected. A special
 * character "*" will select all the values.
 * @param mixed Aditional filters to the modules. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword).
 * @param bool Wheter to return the modules indexed by the id_agente_modulo or
 * not. Default is indexed.
 * Example:
<code>
Both are similars:
$modules = get_agent_modules ($id_agent, false, array ('disabled' => 0));
$modules = get_agent_modules ($id_agent, false, 'disabled = 0');

Both are similars:
$modules = get_agent_modules ($id_agent, '*', array ('disabled' => 0, 'history_data' => 0));
$modules = get_agent_modules ($id_agent, '*', 'disabled = 0 AND history_data = 0');
</code>
 *
 * @return array An array with all modules in the agent.
 * If multiple rows are selected, they will be in an array
 */
function get_agent_modules ($id_agent, $details = false, $filter = false, $indexed = true, $get_not_init_modules = true) {
	$id_agent = safe_int ($id_agent, 1);

	$where = '';
	if (! empty ($id_agent)) {
		$where = sprintf (' WHERE id_agente IN (%s)', implode (",", (array) $id_agent));
	}
	
	if ($where != '') {
		$where .= ' AND ';
	} else {
		$where .= ' WHERE ';
	}
	
	$where .= 'delete_pending = 0 ';
	
	if (! empty ($filter)) {
		$where .= ' AND ';
		if (is_array ($filter)) {
			$fields = array ();
			foreach ($filter as $field => $value) {
				array_push ($fields, $field.'="'.$value.'"');
			}
			$where .= implode (' AND ', $fields);
		} else {
			$where .= $filter;
		}
	}
	
	if (empty ($details)) {
		$details = "nombre";
	} else {
		$details = safe_input ($details);
	}
	
	$sql = sprintf ('SELECT %s%s
		FROM tagente_modulo
		%s
		ORDER BY nombre',
		($details != '*' && $indexed) ? 'id_agente_modulo,' : '',
		implode (",", (array) $details),
		$where);
	$result = get_db_all_rows_sql ($sql);
	
	if (empty ($result)) {
		return array ();
	}
	
	if (! $indexed)
		return $result;
	
	$modules = array ();
	foreach ($result as $module) {
		if($get_not_init_modules || get_agentmodule_is_init($module['id_agente_modulo'])) {
			if (is_array ($details) || $details == '*') {
				 //Just stack the information in array by ID
				$modules[$module['id_agente_modulo']] = $module;
			} else {
				$modules[$module['id_agente_modulo']] = $module[$details];
			}
		}
	}
	return $modules;
}

/**
 * Get the number of all agent modules in the database
 *
 * @param mixed Array of integers with agent(s) id or a single agent id. Default
 * value will select all.
 *
 * @return int The number of agent modules
 */
function get_agent_modules_count ($id_agent = 0) {
	//Make sure we're all int's and filter out bad stuff
	$id_agent = safe_int ($id_agent, 1);
		
	if (empty ($id_agent)) {
		//If the array proved empty or the agent is less than 1 (eg. -1)
		$filter = '';
	} else {
		$filter = sprintf (" WHERE id_agente IN (%s)", implode (",", (array) $id_agent));
	}
	
	return (int) get_db_sql ("SELECT COUNT(*) FROM tagente_modulo".$filter);
}

/** 
 * Get group icon from group.
 * 
 * @param int id_group Id group to get the icon
 * 
 * @return string Icon path of the given group
 */
function get_group_icon ($id_group) {
	if ($id_group == 0) {
		return 'world';
	}
	else {
		return (string) get_db_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	}
}

/** 
 * Get agent id from a module id that it has.
 * 
 * @param int $id_module Id module is list modules this agent.
 * 
 * @return int Id from the agent of the given id module.
 */
function get_agent_module_id ($id_agente_modulo) {
	return (int) get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agente_modulo);
}



/** 
 * Get agent id from an agent name.
 * 
 * @param string $agent_name Agent name to get its id.
 * 
 * @return int Id from the agent of the given name.
 */
function get_agent_id ($agent_name) {
	return (int) get_db_value ('id_agente', 'tagente', 'nombre', $agent_name);
}

/** 
 * Get name of an agent.
 * 
 * @param int $id_agent Agent id.
 * @param string $case Case (upper, lower, none)
 * 
 * @return string Name of the given agent.
 */
function get_agent_name ($id_agent, $case = "none") {
	$agent = (string) get_db_value ('nombre', 'tagente', 'id_agente', (int) $id_agent);
	// Version 3.0 has enforced case sensitive agent names
	// so we always should show real case names.
	switch ($case) {
	case "upper":
		return mb_strtoupper ($agent,"UTF-8");
		break;
	case "lower":
		return mb_strtolower ($agent,"UTF-8");
		break;
	case "none":
	default:
		return ($agent);
	}
}


/** 
 * Get type name for alerts (e-mail, text, internal, ...) based on type number
 * 
 * @param int id_alert Alert type id.
 * 
 * @return string Type name of the alert.
 */
function get_alert_type ($id_type) {
	return (string) get_db_value ('name', 'talert_templates', 'id', (int) $id_type);
}

/** 
 * Get the name of an exporting server
 * 
 * @param int $id_server Server id
 * 
 * @return string The name of given server.
 */
function dame_nombre_servidorexportacion ($id_server) {
	return (string) get_db_value ('name', 'tserver_export', 'id', (int) $id_server);
}

/** 
 * Get the name of a plugin
 * 
 * @param int id_plugin Plugin id.
 * 
 * @return string The name of the given plugin
 */
function dame_nombre_pluginid ($id_plugin) {
	return (string) get_db_value ('name', 'tplugin', 'id', (int) $id_plugin);
}

/** 
 * Get the name of a module type
 * 
 * @param int $id_type Type id
 * 
 * @return string The name of the given type.
 */
function get_module_type_name ($id_type) {
	return (string) get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/** 
 * Get the name of a module type
 * 
 * @param int $id_type Type id
 * 
 * @return string The name of the given type.
 */
function get_module_type_icon ($id_type) {
	return (string) get_db_value ('icon', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/** 
 * Get agent id of an agent module.
 * 
 * @param int $id_agentmodule Agent module id.
 * 
 * @return int The id of the agent of given agent module
 */
function get_agentmodule_agent ($id_agentmodule) {
	return (int) get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
}

/** 
 * Get agent name of an agent module.
 * 
 * @param int $id_agente_modulo Agent module id.
 * 
 * @return string The name of the given agent module.
 */
function get_agentmodule_agent_name ($id_agentmodule) {
	// Since this is a helper function we don't need to do casting
	return (string) get_agent_name (get_agentmodule_agent ($id_agentmodule));
}

/** 
 * Get the module name of an agent module.
 * 
 * @param int $id_agente_modulo Agent module id.
 * 
 * @return string Name of the given agent module.
 */
function get_agentmodule_name ($id_agente_modulo) {
	return (string) get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}

/** 
 * Get the module type of an agent module.
 * 
 * @param int $id_agentmodule Agent module id.
 * 
 * @return string Module type of the given agent module.
 */
function get_agentmodule_type ($id_agentmodule) {
	return (int) get_db_value ('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
}

/** 
 * DEPRECATED: User get_user_fullname
 */
function dame_nombre_real ($id_user) {
	return get_user_fullname ($id_user);
}

/**
 * Get all the times a monitor went down during a period.
 * 
 * @param int $id_agent_module Agent module of the monitor.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The number of times a monitor went down.
 */
function get_monitor_downs_in_period ($id_agent_module, $period, $date = 0) {
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT COUNT(`id_agentmodule`) FROM `tevento` WHERE 
			`event_type` = 'monitor_down' 
			AND `id_agentmodule` = %d 
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_agent_module, $datelimit, $date);
	 
	return get_db_sql ($sql);
}

/**
 * Get the last time a monitor went down during a period.
 * 
 * @param int $id_agent_module Agent module of the monitor.
 * @param int $period Period timed to check from date
 * @param int $date Date to check (now by default)
 *
 * @return int The last time a monitor went down.
 */
function get_monitor_last_down_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT MAX(`timestamp`) FROM `tevento` WHERE 
			event_type = 'monitor_down' 
			AND `id_agentmodule` = %d 
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_agent_module, $datelimit, $date);
	
	return get_db_sql ($sql);
}

/**
 * Get all the monitors defined in an group.
 * 
 * @param int $id_group Group id to get all the monitors.
 * 
 * @return array An array with all the monitors defined in the group (tagente_modulo).
 */
function get_monitors_in_group ($id_group) {
	if ($id_group <= 0) {
		//We select all groups the user has access to if it's 0 or -1
		global $config;
		$id_group = array_keys (get_user_groups ($config['id_user']));
	}
	
	if (is_array ($id_group)) {
		$id_group = implode (",",$id_group);
	}
	
	$sql = sprintf ("SELECT `tagente_modulo`.* FROM `tagente_modulo`, `ttipo_modulo`, `tagente` WHERE 
			`id_tipo_modulo` = `id_tipo` 
			AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente` 
			AND `ttipo_modulo`.`nombre` LIKE '%%_proc' 
			AND `tagente`.`id_grupo` IN (%s) ORDER BY `tagente`.`nombre`", $id_group);
	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the events happened in a group during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 * 
 * @param mixed $id_group Group id to get events for.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 * 
 * @return array An array with all the events happened.
 */
function get_group_events ($id_group, $period, $date) {
	global $config;
	
	$id_group = safe_acl_group ($config["id_user"], $id_group, "AR");
	
	if (empty ($id_group)) {
		//An empty array means the user doesn't have access
		return false;
	}
	
	$datelimit = $date - $period;
	
	$sql = sprintf ('SELECT * FROM tevento 
			WHERE utimestamp > %d AND utimestamp <= %d
			AND id_grupo IN (%s)
			ORDER BY utimestamp ASC',
			$datelimit, $date, implode (",", $id_group));
	
	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the events happened in an Agent during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 * 
 * @param int $id_agent Agent id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 * 
 * @return array An array with all the events happened.
 */
function get_agent_events ($id_agent, $period, $date = 0) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}

	$datelimit = $date - $period;
	
	$sql = sprintf ('SELECT evento, event_type, criticity, count(*) as count_rep, max(timestamp) AS time2
		FROM tevento WHERE id_agente = %d AND utimestamp > %d AND utimestamp <= %d 
		GROUP BY id_agentmodule, evento ORDER BY time2 DESC', $id_agent, $datelimit, $date);

	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the events happened in an Agent during a period of time.
 *
 * The returned events will be in the time interval ($date - $period, $date]
 * 
 * @param int $id_agent_module Module id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 * 
 * @return array An array with all the events happened.
 */
function get_module_events ($id_agent_module, $period, $date = 0) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}

	$datelimit = $date - $period;
	
	$sql = sprintf ('SELECT evento, event_type, criticity, count(*) as count_rep, max(timestamp) AS time2
		FROM tevento WHERE id_agentmodule = %d AND utimestamp > %d AND utimestamp <= %d 
		GROUP BY id_agentmodule, evento ORDER BY time2 DESC', $id_agent_module, $datelimit, $date);

	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the fired of alerts happened in an Agent during a period of time.
 *
 * The returned alerts will be in the time interval ($date - $period, $date]
 * 
 * @param int $id_agent Agent id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 * 
 * @return array An array with all the events happened.
 */
function get_agent_alert_fired ($id_agent, $id_alert, $period, $date = 0) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}

	$datelimit = $date - $period;
	
	$sql = sprintf ('SELECT timestamp
		FROM tevento
		WHERE id_agente = %d AND utimestamp > %d AND utimestamp <= %d
		AND id_alert_am = %d 
		ORDER BY timestamp DESC', $id_agent, $datelimit, $date, $id_alert);

	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the fired of alerts happened in an Agent module during a period of time.
 *
 * The returned alerts will be in the time interval ($date - $period, $date]
 * 
 * @param int $id_agent_module Agent module id to get events.
 * @param int $period Period of time in seconds to get events.
 * @param int $date Beginning date to get events.
 * 
 * @return array An array with all the events happened.
 */
function get_module_alert_fired ($id_agent_module, $id_alert, $period, $date = 0) {
	if (!is_numeric ($date)) {
		$date = strtotime ($date);
	}
	if (empty ($date)) {
		$date = get_system_time ();
	}

	$datelimit = $date - $period;
	
	$sql = sprintf ('SELECT timestamp
		FROM tevento
		WHERE id_agentmodule = %d AND utimestamp > %d AND utimestamp <= %d
		AND id_alert_am = %d 
		ORDER BY timestamp DESC', $id_agent_module, $datelimit, $date, $id_alert);

	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the monitors defined in an agent.
 * 
 * @param int $id_agent Agent id to get all the monitors.
 * 
 * @return array An array with all the monitors defined (tagente_modulo).
 */
function get_monitors_in_agent ($id_agent) {
	$sql = sprintf ("SELECT `tagente_modulo`.*
			FROM `tagente_modulo`, `ttipo_modulo`, `tagente`
			WHERE `id_tipo_modulo` = `id_tipo`
			AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente`
			AND `ttipo_modulo`.`nombre` LIKE '%%_proc'
			AND `tagente`.`id_agente` = %d", $id_agent);
	return get_db_all_rows_sql ($sql);
}

/** 
 * Get all the monitors down during a period of time.
 * 
 * @param array $monitors An array with all the monitors to check. Each
 * element of the array must be a dictionary.
 * @param int $period Period of time to check the monitors.
 * @param int $date Beginning date to check the monitors.
 * 
 * @return array An array with all the monitors that went down in that
 * period of time.
 */
function get_monitors_down ($monitors, $period = 0, $date = 0) {
	$monitors_down = array ();
	if (empty ($monitors))
		return $monitors_down;

	foreach ($monitors as $monitor) {
		$down = get_monitor_downs_in_period ($monitor['id_agente_modulo'], $period, $date);
		if ($down > 0)
			array_push ($monitors_down, $monitor);
	}
	return $monitors_down;
}

/**
 * Get all the times an alerts fired during a period.
 * 
 * @param int Alert module id.
 * @param int Period timed to check from date
 * @param int Date to check (current time by default)
 *
 * @return int The number of times an alert fired.
 */
function get_alert_fires_in_period ($id_alert_module, $period, $date = 0) {
	if (!$date)
		$date = get_system_time ();
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT COUNT(`id_agentmodule`) FROM `tevento` WHERE
			`event_type` = 'alert_fired'
			AND `id_alert_am` = %d
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_alert_module, $datelimit, $date);
	return (int) get_db_sql ($sql);
}

/** 
 * Get all the alerts defined in a group.
 *
 * It gets all the alerts of all the agents on a given group.
 * 
 * @param int $id_group Group id to check.
 * 
 * @return array An array with alerts dictionaries defined in a group.
 */
function get_group_alerts ($id_group) {
	global $config;
	
	require_once ($config["homedir"].'/include/functions_agents.php');
	
	$alerts = array ();
	$agents = get_group_agents ($id_group, false, "none");
		
	foreach ($agents as $agent_id => $agent_name) {
		$agent_alerts = get_agent_alerts ($agent_id);
		$alerts = array_merge ($alerts, $agent_alerts);
	}
	
	return $alerts;
}

/** 
 * Get all the alerts fired during a period, given a list of alerts.
 * 
 * @param array A list of alert modules to check. See get_alerts_in_group()
 * @param int Period of time to check fired alerts.
 * @param int Beginning date to check fired alerts in UNIX format (current date by default)
 * 
 * @return array An array with the alert id as key and the number of times
 * the alert was fired (only included if it was fired).
 */
function get_alerts_fired ($alerts, $period = 0, $date = 0) {
	if (! $date)
		$date = get_system_time ();
	$datelimit = $date - $period;

	$alerts_fired = array ();
	$agents = array ();
	
	foreach ($alerts as $alert) {
		if (isset($alert['id'])){
			$fires = get_alert_fires_in_period ($alert['id'], $period, $date);
			if (! $fires) {
				continue;
			}
			$alerts_fired[$alert['id']] = $fires;
		}
	}
	return $alerts_fired;
}

/**
 * Get the last time an alert fired during a period.
 * 
 * @param int Alert agent module id.
 * @param int Period timed to check from date
 * @param int Date to check (current date by default)
 *
 * @return int The last time an alert fired. It's an UNIX timestamp.
 */
function get_alert_last_fire_timestamp_in_period ($id_alert_module, $period, $date = 0) {
	if ($date == 0) {
		$date = get_system_time ();
	}
	$datelimit = $date - $period;
	$sql = sprintf ("SELECT MAX(`utimestamp`) FROM `tevento` WHERE
			`event_type` = 'alert_fired'
			AND `id_alert_am` = %d
			AND `utimestamp` > %d 
			AND `utimestamp` <= %d",
			$id_alert_module, $datelimit, $date);
	return get_db_sql ($sql);
}

/** 
 * Get the server name.
 * 
 * @param int Server id.
 * 
 * @return string Name of the given server
 */
function get_server_name ($id_server) {
	return (string) get_db_value ('name', 'tserver', 'id_server', (int) $id_server);
}

/** 
 * Get the module type name (type = generic_data, remote_snmp, ...)
 * 
 * @param int $id_type Type id
 * 
 * @return string Name of the given type.
 */
function get_moduletype_name ($id_type) {
	return (string) get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/** 
 * Get the module type description
 * 
 * @param int $id_type Type id
 * 
 * @return string Description of the given type.
 */
function get_moduletype_description ($id_type) {
	return (string) get_db_value ('descripcion', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/**
 * Returns an array with all module types (default) or if "remote" or "agent" 
 * is passed it will return only remote (ICMP, SNMP, TCP...) module types 
 * otherwise the full list + the column you specify 
 *
 * @param string Specifies which type to return (will return an array with id's)
 * @param string Which rows to select (defaults to nombre)
 *
 * @return array Either the full table or if a type is specified, an array with id's
 */
function get_moduletypes ($type = "all", $rows = "nombre") {
	$return = array ();
	$rows = (array) $rows; //Cast as array
	$row_cnt = count ($rows);
	if ($type == "remote") {
		return array_merge (range (6,18), (array) 100);
	} elseif ($type == "agent") {
		return array_merge (range (1,4), range (19,24));
	}
	
	$sql = sprintf ("SELECT id_tipo,%s FROM ttipo_modulo", implode (",", $rows));
	$result = get_db_all_rows_sql ($sql);
	if ($result === false) {
		return $return;
	}
	
	foreach ($result as $type) {
		if ($row_cnt > 1) {
			$return[$type["id_tipo"]] = $type;
		} else {
			$return[$type["id_tipo"]] = $type[reset ($rows)];
		}
	}
	return $return;
}


/** 
 * @deprecated Use get_agent_group ($id) now (fully compatible)
 */
function dame_id_grupo ($id_agent) {
	return get_agent_group ($id_agent);
}


/** 
 * Get the number of pandora data packets in the database. 
 *
 * In case an array is passed, it will have a value for every agent passed 
 * incl. a total otherwise it will just return the total
 * 
 * @param mixed Agent id or array of agent id's, 0 for all
 *
 * @return mixed The number of data in the database
 */
function get_agent_modules_data_count ($id_agent = 0) {
	$id_agent = safe_int ($id_agent, 1);
	
	if (empty ($id_agent)) {
		$id_agent = array ();
	} else {
		$id_agent = (array) $id_agent;
	}
	
	$count = array ();
	$count["total"] = 0;
	
	$query[0] = "SELECT COUNT(*) FROM tagente_datos";
	//$query[1] = "SELECT COUNT(*) FROM tagente_datos_inc";
	//$query[2] = "SELECT COUNT(*) FROM tagente_datos_string";
	
	foreach ($id_agent as $agent_id) {
		//Init value
		$count[$agent_id] = 0;
		$modules = array_keys (get_agent_modules ($agent_id));
		foreach ($query as $sql) {
			//Add up each table's data
			$count[$agent_id] += (int) get_db_sql ($sql." WHERE id_agente_modulo IN (".implode (",", $modules).")", 0, true);
		}
		//Add total agent count to total count
		$count["total"] += $count[$agent_id];
	}
	
	if ($count["total"] == 0) {
		foreach ($query as $sql) {
			$count["total"] += (int) get_db_sql ($sql, 0, true);
		}
	}
	
	if (!isset ($agent_id)) {
		//If agent_id is not set, it didn't loop through any agents
		return $count["total"];
	}
	return $count; //Return the array
}

/** 
 * Get the operating system name.
 * 
 * @param int Operating system id.
 * 
 * @return string Name of the given operating system.
 */
function get_os_name ($id_os) {
	return (string) get_db_value ('name', 'tconfig_os', 'id_os', (int) $id_os);
}

/** 
 * @deprecated Use is_user_admin
 */
function dame_admin ($id_user) {
	return is_user_admin ($id_user);
}

/** 
 * Check if an agent has alerts fired.
 * 
 * @param int Agent id.
 * 
 * @return bool True if the agent has fired alerts.
 */
function check_alert_fired ($id_agent) {
	$sql = sprintf ("SELECT COUNT(*)
		FROM talert_template_modules, tagente_modulo
		WHERE talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo
		AND times_fired > 0 AND id_agente = %d",
		$id_agent);
	
	$value = get_db_sql ($sql);
	if ($value > 0)
		return true;
	return false;
}

/** 
 * Get the interval value of an agent module.
 *
 * If the module interval is not set, the agent interval is returned
 * 
 * @param int Id agent module to get the interval value.
 * 
 * @return int Module interval or agent interval if no module interval
 */
function get_module_interval ($id_agent_module) {
	$interval = (int) get_db_value ('module_interval', 'tagente_modulo', 'id_agente_modulo', (int) $id_agent_module);
	if ($interval > 0)
		return $interval;
		
	$id_agent = give_agent_id_from_module_id ($id_agent_module);
	return (int) get_agent_interval ($id_agent);
}

/** 
 * Get the interval of an agent.
 * 
 * @param int Agent id.
 * 
 * @return int The interval value of a given agent
 */
function get_agent_interval ($id_agent) {
	return (int) get_db_value ('intervalo', 'tagente', 'id_agente', $id_agent);
}

/** 
 * Get the operating system of an agent.
 * 
 * @param int Agent id.
 * 
 * @return int The interval value of a given agent
 */
function get_agent_os ($id_agent) {
	return (int) get_db_value ('id_os', 'tagente', 'id_agente', $id_agent);
}

/** 
 * Get the flag value of an agent module.
 * 
 * @param int Agent module id.
 * 
 * @return bool The flag value of an agent module.
 */
function give_agentmodule_flag ($id_agent_module) {
	return get_db_value ('flag', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

/**
 * Get all groups in array with index as id_group
 */
function get_all_groups($groupWithAgents = false) {
	$sql = 'SELECT id_grupo, nombre FROM tgrupo';
	
	global $config;
	
	if ($groupWithAgents)
		$sql .= ' WHERE id_grupo IN (SELECT id_grupo FROM tagente GROUP BY id_grupo)';
	
	$sql .= ' ORDER BY nombre DESC';
	
	$rows = get_db_all_rows_sql ($sql);
	
	$return = array();
	foreach ($rows as $row) {
		if (give_acl ($config['id_user'], $row["id_grupo"], "AR"))
			$return[$row['id_grupo']] = $row['nombre'];
	}
		
	return $return;
}

/**
 * Get a list of all users in an array [username] => (info)
 * 
 * @param string Field to order by (id_usuario, nombre_real or fecha_registro)
 * @param string Which info to get (defaults to nombre_real)
 *
 * @return array An array of users
 */
function get_users_info ($order = "fullname", $info = "fullname") {
	$users = get_users ($order);
	$ret = array ();
	foreach ($users as $user_id => $user_info) {
		$ret[$user_id] = $user_info[$info];
	}
	return $ret;
}

/** 
 * Get all the Model groups a user has reading privileges.
 * 
 * @param string User id
 * @param string The privilege to evaluate
 *
 * @return array A list of the groups the user has certain privileges.
 */
function get_all_model_groups () {
	$groups = get_db_all_rows_in_table ('tmodule_group');
	
	$returnGroups = array();
	foreach ($groups as $group)
		$returnGroups[$group['id_mg']] = $group['name'];
		
	$returnGroups[0] = "Not assigned"; //Module group external to DB but it exist
		
	
	return $returnGroups;
}
 
/** 
 * Get all the groups a user has reading privileges.
 * 
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 *
 * @return array A list of the groups the user has certain privileges.
 */
function get_user_groups ($id_user = false, $privilege = "AR", $returnAllGroup = true, $returnAllColumns = false) {
	if (empty ($id_user)) {
		global $config;
		$id_user = $config['id_user'];
	}
	
	$user_groups = array ();
	$groups = get_db_all_rows_in_table ('tgrupo', 'nombre');

	if (!$groups)
		return $user_groups;
		
	if ($returnAllGroup) { //All group
		if ($returnAllColumns) {
			$groups[] = array('id_grupo' => 0, 'nombre' => __('All'),
				'icon' => 'world', 'parent' => 0, 'disabled' => 0,
				'custom_id' => null, 'propagate' => 0); 
		}
		else {
			$groups[] = array('id_grupo' => 0, 'nombre' => __("All"));
		}
	}
	
	foreach ($groups as $group) {
		if ($privilege === false) {
			if ($returnAllColumns) {
				$user_groups[$group['id_grupo']] = $group;
			}
			else {
				$user_groups[$group['id_grupo']] = $group['nombre'];
			}
		}
		else if (give_acl ($id_user, $group["id_grupo"], $privilege)) {
			if ($returnAllColumns) {
				$user_groups[$group['id_grupo']] = $group;
			}
			else {
				$user_groups[$group['id_grupo']] = $group['nombre'];
			}
		}
	}
	
	ksort($user_groups);
	
	return $user_groups;
}

/**
 * Make with a list of groups a treefied list of groups.
 * 
 * @param array $groups The list of groups to create the treefield list.
 * @param integer $parent The id_group of parent actual scan branch.
 * @param integer $deep The level of profundity in the branch.
 * 
 * @return array The treefield list of groups. 
 */
function get_user_groups_tree_recursive($groups, $parent = 0, $deep = 0) {
	$return = array();
	
	foreach ($groups as $key => $group) {
		if (($key == 0) && ($parent == 0)) { //When the groups is the all group
			$group['deep'] = $deep;
			$group['hash_branch'] = true;
			$deep ++;
			$return = $return + array($key => $group);
		}
		else if ($group['parent'] == $parent) {
			$group['deep'] = $deep;
			$branch = get_user_groups_tree_recursive($groups, $key, $deep + 1);
			if (empty($branch)) {
				$group['hash_branch'] = false;
			}
			else {
				$group['hash_branch'] = true;
			}
			$return = $return + array($key => $group) + $branch;
		}
	}
	
	return $return;
}

/** 
 * Get all the groups a user has reading privileges. Version for tree groups.
 * 
 * @param string User id
 * @param string The privilege to evaluate
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 *
 * @return array A treefield list of the groups the user has certain privileges.
 */
function get_user_groups_tree($id_user = false, $privilege = "AR", $returnAllGroup = true) {
	$user_groups = get_user_groups ($id_user, $privilege, $returnAllGroup, true);	
	
	$user_groups_tree = get_user_groups_tree_recursive($user_groups);
	
	return $user_groups_tree;
}

/** 
 * Get the first group of an user.
 *
 * Useful function when you need a default group for a user.
 * 
 * @param string User id
 * @param string The privilege to evaluate
 *
 * @return array The first group where the user has certain privileges.
 */
function get_user_first_group ($id_user = false, $privilege = "AR") {
	return array_shift (array_keys (get_user_groups ($id_user, $privilege)));
}

/** 
 * Get module type icon.
 *
 * TODO: Create print_moduletype_icon and print the full tag including hover etc.
 * @deprecated Use print_moduletype_icon instead
 * 
 * @param int Module type id
 * 
 * @return string Icon filename of the given group
 */
function show_icon_type ($id_type) { 
	return (string) get_db_value ('icon', 'ttipo_modulo', 'id_tipo', $id_type);
}

/**
 * Return a string containing image tag for a given target id (server)
 * TODO: Make this print_servertype_icon and move to functions_ui.php. Make XHTML compatible. Make string translatable
 *
 * @deprecated Use print_servertype_icon instead
 *
 * @param int Server type id
 *
 * @return string Fully formatted IMG HTML tag with icon
 */
function show_server_type ($id) {
	global $config;
	switch ($id) {
	case 1:
		return '<img src="images/database.png" title="Pandora FMS Data server">';
		break;
	case 2:
		return '<img src="images/network.png" title="Pandora FMS Network server">';
		break;
	case 4:
		return '<img src="images/plugin.png" title="Pandora FMS Plugin server">';
		break;
	case 5:
		return '<img src="images/chart_bar.png" title="Pandora FMS Prediction server">';
		break;
	case 6:
		return '<img src="images/wmi.png" title="Pandora FMS WMI server">';
		break;
	case 7: 
		return '<img src="images/server_web.png" title="Pandora FMS WEB server">';
		break;
	default:
		return "--";
	}
}

/** 
 * Get a module category name
 * 
 * @param int Id category
 * 
 * @return Name of the given category
 */
function give_modulecategory_name ($id_category) {
	switch ($id_category) {
	case 0: 
		return __('Software agent data');
		break;
	case 1: 
		return __('Software agent monitor');
		break;
	case 2: 
		return __('Network agent data');
		break;
	case 3: 
		return __('Network agent monitor');
		break;
	}
	return __('Unknown');
}

/** 
 * Get a network profile name.
 * 
 * @param int Id network profile
 * 
 * @return string Name of the given network profile.
 */
function get_networkprofile_name ($id_network_profile) {
	return (string) get_db_value ('name', 'tnetwork_profile', 'id_np', $id_network_profile);
}

/** 
 * Assign an IP address to an agent.
 * 
 * @param int Agent id
 * @param string IP address to assign
 */
function agent_add_address ($id_agent, $ip_address) {
	// Check if already is attached to agent
	$sql = sprintf ("SELECT COUNT(`ip`) FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND ip = '%s' AND id_agent = %d",$ip_address,$id_agent);
	$current_address = get_db_sql ($sql);
	if ($current_address > 0)
		return;
	
	// Look for a record with this IP Address
	$id_address = (int) get_db_value ('id_a', 'taddress', 'ip', $ip_address);
	
	if ($id_address === 0) {
		// Create IP address in tadress table
		$sql = sprintf("INSERT INTO taddress (ip) VALUES ('%s')",$ip_address);
		$id_address = process_sql ($sql, "insert_id");
	}
	
	// Add address to agent
	$sql = sprintf("INSERT INTO taddress_agent
			(id_a, id_agent) VALUES
			(%d, %d)",$id_address, $id_agent);
	process_sql ($sql);
}

/** 
 * Unassign an IP address from an agent.
 * 
 * @param int Agent id
 * @param string IP address to unassign
 */
function agent_delete_address ($id_agent, $ip_address) {
	global $config;
	
	$sql = sprintf ("SELECT id_ag FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a AND ip = '%s'
		AND id_agent = %d",$ip_address, $id_agent);
	$id_ag = get_db_sql ($sql);
	if ($id_ag !== false) {
		$sql = sprintf ("DELETE FROM taddress_agent WHERE id_ag = %d",$id_ag);	
		process_sql ($sql);
	}
	$agent_name = get_agent_name($id_agent, "");
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "Agent management",
	"Deleted IP $ip_address from agent '$agent_name'");

	// Need to change main address?
	if (get_agent_address ($id_agent) == $ip_address) {
		$new_ips = get_agent_addresses ($id_agent);
		// Change main address in agent to first one in the list
		$query = sprintf ("UPDATE tagente SET `direccion` = '%s' WHERE id_agente = %d", current ($new_ips), $id_agent);
		process_sql ($query);
	}
}

/** 
 * Get address of an agent.
 * 
 * @param int Agent id
 * 
 * @return string The address of the given agent 
 */
function get_agent_address ($id_agent) {
	return (string) get_db_value ('direccion', 'tagente', 'id_agente', (int) $id_agent);
}

/**
 * Get the agent that matches an IP address
 *
 * @param string IP address to get the agents.
 *
 * @return mixed The agent that has the IP address given. False if none were found.
 */
function get_agent_with_ip ($ip_address) {
	$sql = sprintf ('SELECT tagente.*
		FROM tagente, taddress, taddress_agent
		WHERE tagente.id_agente = taddress_agent.id_agent
		AND taddress_agent.id_a = taddress.id_a
		AND ip = "%s"', $ip_address);
	return get_db_row_sql ($sql);
}

/** 
 * Get all IP addresses of an agent
 * 
 * @param int Agent id
 * 
 * @return array Array with the IP address of the given agent or an empty array.
 */
function get_agent_addresses ($id_agent) {
	$sql = sprintf ("SELECT ip FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND id_agent = %d", $id_agent);
	
	$ips = get_db_all_rows_sql ($sql);
	
	if ($ips === false) {
		$ips = array ();
	}
	
	$ret_arr = array ();
	foreach ($ips as $row) {
		$ret_arr[$row["ip"]] = $row["ip"];
	}
	
	return $ret_arr;
}

/** 
 * Get agent id from an agent module.
 * 
 * @param int Id of the agent module.
 * 
 * @return int The agent if of the given module.
 */
function give_agent_id_from_module_id ($id_agent_module) {
	return (int) get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

$sql_cache = array ('saved' => 0);

/** 
 * Get the first value of the first row of a table in the database.
 * 
 * @param string Field name to get
 * @param string Table to retrieve the data
 * @param string Field to filter elements
 * @param string Condition the field must have
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function get_db_value ($field, $table, $field_search = 1, $condition = 1, $search_history_db = false) {
	if (is_int ($condition)) {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = %d LIMIT 1",
				$field, $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = %f LIMIT 1",
				$field, $table, $field_search, $condition);
	} else {
		$sql = sprintf ("SELECT %s FROM %s WHERE %s = '%s' LIMIT 1",
				$field, $table, $field_search, $condition);
	}
	$result = get_db_all_rows_sql ($sql, $search_history_db);
	
	if ($result === false)
		return false;
	if ($field[0] == '`')
		$field = str_replace ('`', '', $field);
	return $result[0][$field];
}

/** 
 * Get the first value of the first row of a table in the database from an
 * array with filter conditions.
 *
 * Example:
<code>
get_db_value_filter ('name', 'talert_templates',
	array ('value' => 2, 'type' => 'equal'));
// Equivalent to:
// SELECT name FROM talert_templates WHERE value = 2 AND type = 'equal' LIMIT 1

get_db_value_filter ('description', 'talert_templates',
	array ('name' => 'My alert', 'type' => 'regex'), 'OR');
// Equivalent to:
// SELECT description FROM talert_templates WHERE name = 'My alert' OR type = 'equal' LIMIT 1
</code>
 * 
 * @param string Field name to get
 * @param string Table to retrieve the data
 * @param array Conditions to filter the element. See format_array_to_where_clause_sql()
 * for the format
 * @param string Join operator for the elements in the filter.
 *
 * @return mixed Value of first column of the first row. False if there were no row.
 */
function get_db_value_filter ($field, $table, $filter, $where_join = 'AND') {
	if (! is_array ($filter) || empty ($filter))
		return false;
	
	/* Avoid limit and offset if given */
	unset ($filter['limit']);
	unset ($filter['offset']);
	
	$sql = sprintf ("SELECT %s FROM %s WHERE %s LIMIT 1",
		$field, $table,
		format_array_to_where_clause_sql ($filter, $where_join));
	$result = get_db_all_rows_sql ($sql);
	
	if ($result === false)
		return false;
	
	$fieldClean = str_replace('`', '', $field);
	
	return $result[0][$fieldClean];
}

/** 
 * Get the first value of the first row of a table result from query.
 * 
 * @param string SQL select statement to execute.
 * 
 * @return the first value of the first row of a table result from query.
 * 
 */
function get_db_value_sql ($sql) {
	$sql .= " LIMIT 1";
	$result = get_db_all_rows_sql ($sql);
	
	if($result === false) 
		return false;

	foreach ($result[0] as $f)
		return $f;
}

/** 
 * Get the first row of an SQL database query.
 * 
 * @param string SQL select statement to execute.
 * 
 * @return mixed The first row of the result or false
 */
function get_db_row_sql ($sql, $search_history_db = false) {
	$sql .= " LIMIT 1";
	$result = get_db_all_rows_sql ($sql, $search_history_db);
	
	if($result === false) 
		return false;
	
	return $result[0];
}

/** 
 * Get the first row of a database query into a table.
 *
 * The SQL statement executed would be something like:
 * "SELECT (*||$fields) FROM $table WHERE $field_search = $condition"
 *
 * @param string Table to get the row
 * @param string Field to filter elements
 * @param string Condition the field must have.
 * @param mixed Fields to select (array or string or false/empty for *)
 * 
 * @return mixed The first row of a database query or false.
 */
function get_db_row ($table, $field_search, $condition, $fields = false) {
	if (empty ($fields)) {
		$fields = '*';
	} else {
		if (is_array ($fields))
			$fields = implode (',', $fields);
		else if (! is_string ($fields))
			return false;
	}
		
	if (is_int ($condition)) {
		$sql = sprintf ("SELECT %s FROM `%s` WHERE `%s` = %d LIMIT 1",
			$fields, $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ("SELECT %s FROM `%s` WHERE `%s` = %f LIMIT 1",
			$fields, $table, $field_search, $condition);
	} else {
		$sql = sprintf ("SELECT %s FROM `%s` WHERE `%s` = '%s' LIMIT 1", 
			$fields, $table, $field_search, $condition);
	}
	$result = get_db_all_rows_sql ($sql);
		
	if ($result === false) 
		return false;
	
	return $result[0];
}

/** 
 * Get the row of a table in the database using a complex filter.
 * 
 * @param string Table to retrieve the data (warning: not cleaned)
 * @param mixed Filters elements. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword). Example:
<code>
Both are similars:
get_db_row_filter ('table', array ('disabled', 0));
get_db_row_filter ('table', 'disabled = 0');

Both are similars:
get_db_row_filter ('table', array ('disabled' => 0, 'history_data' => 0), 'name, description', 'OR');
get_db_row_filter ('table', 'disabled = 0 OR history_data = 0', 'name, description');
get_db_row_filter ('table', array ('disabled' => 0, 'history_data' => 0), array ('name', 'description'), 'OR');
</code>
 * @param mixed Fields of the table to retrieve. Can be an array or a coma
 * separated string. All fields are retrieved by default
 * @param string Condition to join the filters (AND, OR).
 *
 * @return mixed Array of the row or false in case of error.
 */
function get_db_row_filter ($table, $filter, $fields = false, $where_join = 'AND') {
	if (empty ($fields)) {
		$fields = '*';
	} else {
		if (is_array ($fields))
			$fields = implode (',', $fields);
		else if (! is_string ($fields))
			return false;
	}
	
	if (is_array ($filter))
		$filter = format_array_to_where_clause_sql ($filter, $where_join, ' WHERE ');
	else if (is_string ($filter))
		$filter = 'WHERE '.$filter;
	else
		$filter = '';
	$sql = sprintf ('SELECT %s FROM %s %s',
		$fields, $table, $filter);
	
	return get_db_row_sql ($sql);
}

/** 
 * Get a single field in the databse from a SQL query.
 *
 * @param string SQL statement to execute
 * @param mixed Field number or row to get, beggining by 0. Default: 0
 *
 * @return mixed The selected field of the first row in a select statement.
 */

function get_db_sql ($sql, $field = 0, $search_history_db = false) {
	$result = get_db_all_rows_sql ($sql, $search_history_db);
	if($result === false)
		return false;
	$ax = 0;
	foreach ($result[0] as $f){
		if ($field == $ax)
			return $f;
		$ax++;
	}
}

/**
 * Get all the result rows using an SQL statement.
 * 
 * @param string SQL statement to execute.
 * @param bool If want to search in history database also
 * @param bool If want to use cache (true by default)
 *
 * @return mixed A matrix with all the values returned from the SQL statement or
 * false in case of empty result
 */
function get_db_all_rows_sql ($sql, $search_history_db = false, $cache = true) {
	global $config;
	$history = array ();

	// To disable globally SQL cache depending on global variable.
	// Used in several critical places like Metaconsole trans-server queries
	if (isset($config["dbcache"]))
		$cache = $config["dbcache"];

	// Read from the history DB if necessary
	if ($search_history_db) {
		$cache = false;
		$history = false;
		
		if (isset($config['history_db_connection']))
			$history = process_sql ($sql, 'affected_rows', $config['history_db_connection'], false);
			
		if ($history === false) {
			$history = array ();
		}
	}

	$return = process_sql ($sql, 'affected_rows', $config['dbconnection'], $cache);
	if ($return === false) {
		return false;
	}

	// Append result to the history DB data
	if (! empty ($return)) {
		foreach ($return as $row) {
			array_push ($history, $row);
		}
	}

	if (! empty ($history))
		return $history;
	//Return false, check with === or !==
	return false;
}

/** 
 * Get all the rows of a table in the database that matches a filter.
 * 
 * @param string Table to retrieve the data (warning: not cleaned)
 * @param mixed Filters elements. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword). Example:
 * <code>
 * Both are similars:
 * get_db_all_rows_filter ('table', array ('disabled', 0));
 * get_db_all_rows_filter ('table', 'disabled = 0');
 * 
 * Both are similars:
 * get_db_all_rows_filter ('table', array ('disabled' => 0, 'history_data' => 0), 'name', 'OR');
 * get_db_all_rows_filter ('table', 'disabled = 0 OR history_data = 0', 'name');
 * </code>
 * @param mixed Fields of the table to retrieve. Can be an array or a coma
 * separated string. All fields are retrieved by default
 * @param string Condition of the filter (AND, OR).
 * @param bool $returnSQL Return a string with SQL instead the data, by default false.
 *
 * @return mixed Array of the row or false in case of error.
 */
function get_db_all_rows_filter ($table, $filter = array(), $fields = false, $where_join = 'AND', $search_history_db = false, $returnSQL = false) {
	//TODO: Validate and clean fields
	if (empty ($fields)) {
		$fields = '*';
	}
	elseif (is_array ($fields)) {
		$fields = implode (',', $fields);
	}
	elseif (! is_string ($fields)) {
		return false;	
	}
	
	//TODO: Validate and clean filter options
	if (is_array ($filter)) {
		$filter = format_array_to_where_clause_sql ($filter, $where_join, ' WHERE ');
	}
	elseif (is_string ($filter)) {
		$filter = 'WHERE '.$filter;
	}
	else {
		$filter = '';
	}

	$sql = sprintf ('SELECT %s FROM %s %s', $fields, $table, $filter);
	if ($returnSQL)
		return $sql;
	else
		return get_db_all_rows_sql ($sql, $search_history_db);
}

/**
 * Get row by row the DB by SQL query. The first time pass the SQL query and
 * rest of times pass none for iterate in table and extract row by row, and
 * the end return false.
 * 
 * @param bool $new Default true, if true start to query.
 * @param resource $result The resource of mysql for access to query.
 * @param string $sql 
 * @return mixed The row or false in error.
 */
function get_db_all_row_by_steps_sql($new = true, &$result, $sql = null) {
	if ($new == true)
		$result = mysql_query($sql);
	
	return mysql_fetch_assoc($result);
}

/**
 * Return the count of rows of query.
 * 
 * @param $sql
 * @return integer The count of rows of query.
 */
function get_db_num_rows ($sql) {
	$result = mysql_query($sql);
	
	return mysql_num_rows($result);
}

/**
 * Error handler function when an SQL error is triggered.
 * 
 * @param int Level of the error raised (not used, but required by set_error_handler()).
 * @param string Contains the error message.
 *
 * @return bool True if error level is lower or equal than errno.
 */
function sql_error_handler ($errno, $errstr) {
	global $config;
	
	/* If debug is activated, this will also show the backtrace */
	if (debug ($errstr))
		return false;
	
	if (error_reporting () <= $errno)
		return false;
	echo "<strong>SQL error</strong>: ".$errstr."<br />\n";
	return true;
}

/**
 * Add a database query to the debug trace.
 * 
 * This functions does nothing if the config['debug'] flag is not set. If a
 * sentence was repeated, then the 'saved' counter is incremented.
 *
 * @param string SQL sentence.
 * @param mixed Query result. On error, error string should be given.
 * @param int Affected rows after running the query.
 * @param mixed Extra parameter for future values.
 */
function add_database_debug_trace ($sql, $result = false, $affected = false, $extra = false) {
	global $config;
	
	if (! isset ($config['debug']))
		return false;
	
	if (! isset ($config['db_debug']))
		$config['db_debug'] = array ();
	
	if (isset ($config['db_debug'][$sql])) {
		$config['db_debug'][$sql]['saved']++;
		return;
	}
	
	$var = array ();
	$var['sql'] = $sql;
	$var['result'] = $result;
	$var['affected'] = $affected;
	$var['saved'] = 0;
	$var['extra'] = $extra;
	
	$config['db_debug'][$sql] = $var;
}

/**
 * Clean the cache for to have errors and ghost rows when you do "select <table>",
 * "delete <table>" and "select <table>".
 * 
 * @return None
 */
function clean_cache() {
	global $sql_cache;
	
	$sql_cache = array ('saved' => 0);
}

/**
 * This function comes back with an array in case of SELECT
 * in case of UPDATE, DELETE etc. with affected rows
 * an empty array in case of SELECT without results
 * Queries that return data will be cached so queries don't get repeated
 *
 * @param string SQL statement to execute
 *
 * @param string What type of info to return in case of INSERT/UPDATE.
 *		'affected_rows' will return mysql_affected_rows (default value)
 *		'insert_id' will return the ID of an autoincrement value
 *		'info' will return the full (debug) information of a query
 *
 * @return mixed An array with the rows, columns and values in a multidimensional array or false in error
 */
function process_sql ($sql, $rettype = "affected_rows", $dbconnection = '', $cache = true) {
	global $config;
	global $sql_cache;
	
	$retval = array();
	
	if ($sql == '')
		return false;
	
	if ($cache && ! empty ($sql_cache[$sql])) {
		$retval = $sql_cache[$sql];
		$sql_cache['saved']++;
		add_database_debug_trace ($sql);
	}
	else {
		$start = microtime (true);
		if ($dbconnection == '') {
			$result = mysql_query ($sql);
		}
		else {
			$result = mysql_query ($sql, $dbconnection);
		}
		$time = microtime (true) - $start;
		if ($result === false) {
			$backtrace = debug_backtrace ();
			$error = sprintf ('%s (\'%s\') in <strong>%s</strong> on line %d',
				mysql_error (), $sql, $backtrace[0]['file'], $backtrace[0]['line']);
			add_database_debug_trace ($sql, mysql_error ());
			set_error_handler ('sql_error_handler');
			trigger_error ($error);
			restore_error_handler ();
			return false;
		}
		elseif ($result === true) {
			if ($rettype == "insert_id") {
				$result = mysql_insert_id ();
			}
			elseif ($rettype == "info") {
				$result = mysql_info ();
			}
			else {
				$result = mysql_affected_rows ();
			}
			
			add_database_debug_trace ($sql, $result, mysql_affected_rows (),
				array ('time' => $time));
			return $result;
		}
		else {
			add_database_debug_trace ($sql, 0, mysql_affected_rows (), 
				array ('time' => $time));
			while ($row = mysql_fetch_assoc ($result)) {
				array_push ($retval, $row);
			}

			if ($cache === true)
				$sql_cache[$sql] = $retval;
			mysql_free_result ($result);
		}
	}
	
	if (! empty ($retval))
		return $retval;
	//Return false, check with === or !==
	return false;
}

/**
 * Get all the rows in a table of the database.
 * 
 * @param string Database table name.
 * @param string Field to order by.
 * @param string $order The type of order, by default 'ASC'.
 *
 * @return mixed A matrix with all the values in the table
 */
function get_db_all_rows_in_table ($table, $order_field = "", $order = 'ASC') {
	if ($order_field != "") {
		return get_db_all_rows_sql ("SELECT * FROM `".$table."` ORDER BY ".$order_field . " " . $order);
	} else {	
		return get_db_all_rows_sql ("SELECT * FROM `".$table."`");
	}
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param string Database table name.
 * @param string Field of the table.
 * @param string Condition the field must have to be selected.
 * @param string Field to order by.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field or false
 */
function get_db_all_rows_field_filter ($table, $field, $condition, $order_field = "") {
	if (is_int ($condition) || is_bool ($condition)) {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = %d", $table, $field, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = %f", $table, $field, $condition);
	} else {
		$sql = sprintf ("SELECT * FROM `%s` WHERE `%s` = '%s'", $table, $field, $condition);
	}

	if ($order_field != "")
		$sql .= sprintf (" ORDER BY %s", $order_field);
	return get_db_all_rows_sql ($sql);
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param string Database table name.
 * @param string Field of the table.
 *
 * @return mixed A matrix with all the values in the table that matches the condition in the field
 */
function get_db_all_fields_in_table ($table, $field = '', $condition = '', $order_field = '') {
	$sql = sprintf ("SELECT * FROM `%s`", $table);
	if ($condition != '') {
		$sql .= sprintf (" WHERE `%s` = '%s'", $field, $condition);
	}
	
	if ($order_field != "")
		$sql .= sprintf (" ORDER BY %s", $order_field);
	
	return get_db_all_rows_sql ($sql);
}

/**
 * Formats an array of values into a SQL string.
 *
 * This function is useful to generate an UPDATE SQL sentence from a list of
 * values. Example code:
 *
 * <code>
 * $values = array ();
 * $values['name'] = "Name";
 * $values['description'] = "Long description";
 * $sql = 'UPDATE table SET '.format_array_to_update_sql ($values).' WHERE id=1';
 * echo $sql;
 * </code>
 * Will return:
 * <code>
 * UPDATE table SET `name` = "Name", `description` = "Long description" WHERE id=1
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 *
 * @return string Values joined into an SQL string that can fits into an UPDATE
 * sentence.
 */
function format_array_to_update_sql ($values) {
	$fields = array ();
	
	foreach ($values as $field => $value) {
		if (is_numeric ($field)) {
			array_push ($fields, $value);
			continue;
		}
		else if ($field[0] == "`") {
			$field = str_replace('`', '', $field);
		}
		
		if ($value === NULL) {
			$sql = sprintf ("`%s` = NULL", $field);
		} elseif (is_int ($value) || is_bool ($value)) {
			$sql = sprintf ("`%s` = %d", $field, $value);
		} elseif (is_float ($value) || is_double ($value)) {
			$sql = sprintf ("`%s` = %f", $field, $value);
		} else {
			/* String */
			if (isset ($value[0]) && $value[0] == '`')
				/* Don't round with quotes if it references a field */
				$sql = sprintf ("`%s` = %s", $field, $value);
			else
				$sql = sprintf ("`%s` = '%s'", $field, $value);
		}
		array_push ($fields, $sql);
	}
	
	return implode (", ", $fields);
}

/**
 * Formats an array of values into a SQL where clause string.
 *
 * This function is useful to generate a WHERE clause for a SQL sentence from
 * a list of values. Example code:
<code>
$values = array ();
$values['name'] = "Name";
$values['description'] = "Long description";
$values['limit'] = $config['block_size']; // Assume it's 20
$sql = 'SELECT * FROM table WHERE '.format_array_to_where_clause_sql ($values);
echo $sql;
</code>
 * Will return:
 * <code>
 * SELECT * FROM table WHERE `name` = "Name" AND `description` = "Long description" LIMIT 20
 * </code>
 *
 * @param array Values to be formatted in an array indexed by the field name.
 * There are special parameters such as 'limit' and 'offset' that will be used
 * as ORDER, LIMIT and OFFSET clauses respectively. Since LIMIT and OFFSET are
 * numerics, ORDER can receive a field name or a SQL function and a the ASC or
 * DESC clause. Examples:
<code>
$values = array ();
$values['value'] = 10;
$sql = 'SELECT * FROM table WHERE '.format_array_to_where_clause_sql ($values);
// SELECT * FROM table WHERE VALUE = 10

$values = array ();
$values['value'] = 10;
$values['order'] = 'name DESC';
$sql = 'SELECT * FROM table WHERE '.format_array_to_where_clause_sql ($values);
// SELECT * FROM table WHERE VALUE = 10 ORDER BY name DESC

</code>
 * @param string Join operator. AND by default.
 * @param string A prefix to be added to the string. It's useful when limit and
 * offset could be given to avoid this cases:
<code>
$values = array ();
$values['limit'] = 10;
$values['offset'] = 20;
$sql = 'SELECT * FROM table WHERE '.format_array_to_where_clause_sql ($values);
// Wrong SQL: SELECT * FROM table WHERE LIMIT 10 OFFSET 20

$values = array ();
$values['limit'] = 10;
$values['offset'] = 20;
$sql = 'SELECT * FROM table WHERE '.format_array_to_where_clause_sql ($values, 'AND', 'WHERE');
// Good SQL: SELECT * FROM table LIMIT 10 OFFSET 20

$values = array ();
$values['value'] = 5;
$values['limit'] = 10;
$values['offset'] = 20;
$sql = 'SELECT * FROM table WHERE '.format_array_to_where_clause_sql ($values, 'AND', 'WHERE');
// Good SQL: SELECT * FROM table WHERE value = 5 LIMIT 10 OFFSET 20
</code>
 *
 * @return string Values joined into an SQL string that can fits into the WHERE
 * clause of an SQL sentence.
 */
function format_array_to_where_clause_sql ($values, $join = 'AND', $prefix = false) {
	
	$fields = array ();
	
	if (! is_array ($values)) {
		return '';
	}
	
	$query = '';
	$limit = '';
	$offset = '';
	$order = '';
	$group = '';
	if (isset ($values['limit'])) {
		$limit = sprintf (' LIMIT %d', $values['limit']);
		unset ($values['limit']);
	}
	
	if (isset ($values['offset'])) {
		$offset = sprintf (' OFFSET %d', $values['offset']);
		unset ($values['offset']);
	}

	if (isset ($values['order'])) {
		if (is_array($values['order'])) {
			if (!isset($values['order']['order'])) {
				$orderTexts = array();
				foreach ($values['order'] as $orderItem) {	
					$orderTexts[] = $orderItem['field'] . ' ' . $orderItem['order'];
				}
				$order = ' ORDER BY ' . implode(', ', $orderTexts);
			}
			else {
				$order = sprintf (' ORDER BY %s %s', $values['order']['field'], $values['order']['order']);
			}
		}
		else {
			$order = sprintf (' ORDER BY %s', $values['order']);
		}
		unset ($values['order']);
	}
	
	if (isset ($values['group'])) {
		$group = sprintf (' GROUP BY %s', $values['group']);
		unset ($values['group']);
	}
	
	$i = 1;
	$max = count ($values);
	foreach ($values as $field => $value) {
		if (is_numeric ($field)) {
			/* User provide the exact operation to do */
			$query .= $value;
			
			if ($i < $max) {
				$query .= ' '.$join.' ';
			}
			$i++;
			continue;
		}
		
		if ($field[0] != "`") {
			$field = "`".$field."`";
		}
		
		if (is_null ($value)) {
			$query .= sprintf ("%s IS NULL", $field);
		}
		elseif (is_int ($value) || is_bool ($value)) {
			$query .= sprintf ("%s = %d", $field, $value);
		}
		else if (is_float ($value) || is_double ($value)) {
			$query .= sprintf ("%s = %f", $field, $value);
		}
		elseif (is_array ($value)) {
			$query .= sprintf ('%s IN ("%s")', $field, implode ('", "', $value));
		}
		else {
			if ($value[0] == ">"){
				$value = substr($value,1,strlen($value)-1);
				$query .= sprintf ("%s > '%s'", $field, $value);
			}
			else if ($value[0] == "<"){
				if ($value[1] == ">"){
					$value = substr($value,2,strlen($value)-2);
					$query .= sprintf ("%s <> '%s'", $field, $value);
				}	
				else {
					$value = substr($value,1,strlen($value)-1);
					$query .= sprintf ("%s < '%s'", $field, $value);
				}
			} 
			else {
				$query .= sprintf ("%s = '%s'", $field, $value);
			}
		}
		
		if ($i < $max) {
			$query .= ' '.$join.' ';
		}
		$i++;
	}
	
	return (! empty ($query) ? $prefix: '').$query.$group.$order.$limit.$offset;
}

/** 
 * Get the status of an agent module.
 * 
 * @param int Id agent module to check.
 * 
 * @return int Module status. Value 4 means that some alerts assigned to the
 * module were fired.
 */
function get_agentmodule_status ($id_agentmodule = 0) {
	$current_timestamp = get_system_time ();

	$times_fired = get_db_value ('SUM(times_fired)', 'talert_template_modules', 'id_agent_module', $id_agentmodule);
	if ($times_fired > 0) {
		return 4; // Alert fired
	}

	$status_row = get_db_row ("tagente_estado", "id_agente_modulo", $id_agentmodule);
	
	return $status_row['estado'];
}

/** 
 * Get the last status of an agent module.
 * 
 * @param int Id agent module to check.
 * 
 * @return int Module last status. 
 */
function get_agentmodule_last_status ($id_agentmodule = 0) {
	$status_row = get_db_row ("tagente_estado", "id_agente_modulo", $id_agentmodule);
	
	return $status_row['last_status'];
}

/** 
 * Get the worst status of all modules of a given agent.
 * 
 * @param int Id agent to check.
 * 
 * @return int Worst status of an agent for all of its modules.
 * The value -1 is returned in case the agent has exceed its interval.
 */
function get_agent_status ($id_agent = 0) {
	$modules = get_agent_modules ($id_agent, 'id_agente_modulo', array('disabled' => 0), true, false);

	$modules_status = array();
	$modules_async = 0;
	foreach($modules as $module) {
		$modules_status[] = get_agentmodule_status($module);
		
		$module_type = get_agentmodule_type($module);
		if(($module_type >= 21 && $module_type <= 23) || $module_type == 100) {
			$modules_async++;
		}
	}

	// If all the modules are asynchronous or keep alive, the group cannot be unknown
	if($modules_async < count($modules)) {
		$time = get_system_time ();
		$status = get_db_value_filter ('COUNT(*)',
			'tagente',
			array ('id_agente' => (int) $id_agent,
				'UNIX_TIMESTAMP(ultimo_contacto) + intervalo * 2 > '.$time,
				'UNIX_TIMESTAMP(ultimo_contacto_remoto) + intervalo * 2 > '.$time));
		if (! $status)
			return -1;
	}

	// Status is 0 for normal, 1 for critical, 2 for warning and 3 for unknown. 4 for alert fired
	// Checking if any module has alert fired (4)
	if(is_int(array_search(4,$modules_status))){
		return 4;
	}	
	// Checking if any module has critical status (1)
	elseif(is_int(array_search(1,$modules_status))){
		return 1;
	}	
	// Checking if any module has warning status (2)
	elseif(is_int(array_search(2,$modules_status))){
		return 2;
	}
	// Checking if any module has unknown status (3)
	elseif(is_int(array_search(3,$modules_status))){
		return 3;
	}
	else {
		return 0;
	}
	
}

function get_group_status ($id_group = 0) {	
	$agents = get_group_agents($id_group);
	
	$agents_status = array();
	foreach($agents as $key => $agent){
		$agents_status[] = get_agent_status($key);
	}
	
	$childrens = get_childrens($id_group);
	
	foreach($childrens as $key => $child){
		$agents_status[] = get_group_status($key);
	}
	
	// Status is 0 for normal, 1 for critical, 2 for warning and 3/-1 for unknown. 4 for fired alerts
	
	// Checking if any agent has fired alert (4)
	if(is_int(array_search(4,$agents_status))){
		return 4;
	}
	// Checking if any agent has critical status (1)
	elseif(is_int(array_search(1,$agents_status))){
		return 1;
	}
	// Checking if any agent has warning status (2)
	elseif(is_int(array_search(2,$agents_status))){
		return 2;
	}
	// Checking if any agent has unknown status (-1)
	elseif(is_int(array_search(-1,$agents_status))){
		return -1;
	}
	// Checking if any agents module has unknown status (3)
	elseif(is_int(array_search(3,$agents_status))){
		return 3;
	}
	else {
		return 0;
	}
		
	return $status;
}

/** 
 * Get the worst status of all modules of the agents of a group.
 * 
 * @param int Id module to check.
 * 
 * @return int Worst status of a module for all of its agents.
 * The value -1 is returned in case the agent has exceed its interval. <-- DISABLED
 */
function get_module_status ($id_module = 0) {
	$time = get_system_time ();
	/*$status = get_db_value_filter ('COUNT(*)',
		'tagente',
		array ('id_agente' => (int) $id_agent,
			'UNIX_TIMESTAMP(ultimo_contacto) + intervalo * 2 > '.$time,
			'UNIX_TIMESTAMP(ultimo_contacto_remoto) + intervalo * 2 > '.$time));
	if (! $status)
		return -1;*/
	
	$status = get_db_sql ("SELECT estado
			FROM tagente_estado, tagente_modulo 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0 
			AND tagente_modulo.delete_pending = 0 
			AND tagente_modulo.id_agente_modulo = ".$id_module);
	
	// TODO: Check any alert for that agent who has recept alerts fired
	
	return $status;
}

/** 
 * Get the current value of an agent module.
 * 
 * @param int Agent module id.
 * 
 * @return int a numerically formatted value 
 */
function get_agent_module_last_value ($id_agentmodule) {
	return get_db_value ('datos', 'tagente_estado', 
		'id_agente_modulo', $id_agentmodule);
}

/** 
 * Get the X axis coordinate of a layout item
 * 
 * @param int Id of the layout to get.
 * 
 * @return int The X axis coordinate value.
 */
function get_layoutdata_x ($id_layoutdata) {
	return (float) get_db_value ('pos_x', 'tlayout_data', 'id', (int) $id_layoutdata);
}

/** 
 * Get the Y axis coordinate of a layout item
 * 
 * @param int Id of the layout to get.
 * 
 * @return int The Y axis coordinate value.
 */
function get_layoutdata_y ($id_layoutdata){
	return (float) get_db_value ('pos_y', 'tlayout_data', 'id', (int) $id_layoutdata);
}

/**
 * Get the previous data to the timestamp provided.
 *
 * It's useful to know the first value of a module in an interval, 
 * since it will be the last value in the table which has a timestamp 
 * before the beginning of the interval. All this calculation is due
 * to the data compression algorithm.
 *
 * @param int Agent module id
 * @param int The timestamp to look backwards from and get the data.
 * @param int 1 if the module has a string type.
 *
 * @return mixed The row of tagente_datos of the last period. False if there were no data.
 */
function get_previous_data ($id_agent_module, $utimestamp = 0, $string = 0) {
	if (empty ($utimestamp))
		$utimestamp = time ();
	
	if ($string == 1) {
		$table = 'tagente_datos_string';
	} else {
		$table = 'tagente_datos';
	}

	// 172800 = 60×60×24*2 Search up to 2 days before utimestamp 
	$sql = sprintf ('SELECT * FROM ' . $table . ' WHERE id_agente_modulo = %d 
			AND utimestamp <= %d 
			AND utimestamp >= %d 
			ORDER BY utimestamp DESC',
			$id_agent_module, $utimestamp, $utimestamp - 172800);

	return get_db_row_sql ($sql, true);
}

/**
 * Get the next data to the timestamp provided.
 *
 * @param int Agent module id
 * @param int The timestamp to look backwards from and get the data.
 * @param int 1 if the module has a string type.
 * 
 * @return mixed The row of tagente_datos of the last period. False if there were no data.
 */
function get_next_data ($id_agent_module, $utimestamp = 0, $string = 0) {
	if (empty ($utimestamp))
		$utimestamp = time ();

	if ($string == 1) {
		$table = 'tagente_datos_string';
	} else {
		$table = 'tagente_datos';
	}
	
	$interval = get_module_interval ($id_agent_module);
	$sql = sprintf ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp <= %d 
			AND utimestamp >= %d
			ORDER BY utimestamp ASC',
			$id_agent_module, $utimestamp + $interval, $utimestamp);
	
	return get_db_row_sql ($sql, true);
}

/** 
 * Get all the values of an agent module in a period of time.
 * 
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * 
 * @return array The module value and the timestamp
 */
function get_agentmodule_data ($id_agent_module, $period, $date = 0) {
	if ($date < 1) {
		$date = get_system_time ();
	}
	
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT datos AS data, utimestamp FROM tagente_datos WHERE id_agente_modulo = %d 
					AND utimestamp > %d AND utimestamp <= %d ORDER BY utimestamp ASC",
					$id_agent_module, $datelimit, $date);
	
	$values = get_db_all_rows_sql ($sql, true, false);
		
	if ($values === false) {
		return array ();
	}
	
	$module_name = get_agentmodule_name ($id_agent_module);
	$agent_id = get_agentmodule_agent ($id_agent_module);
	$agent_name = get_agentmodule_agent_name ($id_agent_module);
	
	foreach ($values as $key => $data) {
		$values[$key]["module_name"] = $module_name;
		$values[$key]["agent_id"] = $agent_id;
		$values[$key]["agent_name"] = $agent_name;
	}
	
	return $values;
}
	
/** 
 * Get a translated string
 * 
 * @param string String to translate. It can have special format characters like
 * a printf 
 * @param mixed Optional parameters to be replaced in string. Example:
<code>
echo __('Hello!');
echo __('Hello, %s!', $user);
</code>
 * 
 * @return string The translated string. If not defined, the same string will be returned
 */
function __ ($string /*, variable arguments */) {
	global $l10n;
	
	if ($string == '') {
		return $string;
	}
	
	if (func_num_args () == 1) {
		if (is_null ($l10n))
			return $string;
		return $l10n->translate ($string);
	}
	
	$args = func_get_args ();
	$string = array_shift ($args);
	
	if (is_null ($l10n))
		return vsprintf ($string, $args);
	
	return vsprintf ($l10n->translate ($string), $args);
}

/** 
 * Get the numbers of servers up.
 *
 * This check assumes that server_keepalive should be at least 15 minutes.
 * 
 * @return int The number of servers alive.
 */
function check_server_status () {
	$sql = "SELECT COUNT(id_server) FROM tserver WHERE status = 1 AND keepalive > NOW() - INTERVAL 15 MINUTE";
	$status = (int) get_db_sql ($sql); //Cast as int will assure a number value
	// This function should just ack of server down, not set it down.
	return $status;
}

/** 
 * @deprecated Will show a small HTML table with some compound alert information
 */
function show_alert_row_mini ($id_combined_alert) {
	$color=1;
	$sql = sprintf ("SELECT talert_template_modules.*,tcompound_alert.operation
			FROM talert_template_modules, tcompound_alert
			WHERE tcompound_alert.id_aam = talert_template_modules.id
			AND tcompound_alert.id = %d", $id_combined_alert);
	$result = get_db_all_rows_sql ($sql);
	
	if ($result === false)
		return;

	echo "<table width=400 cellpadding=2 cellspacing=2 class='databox'>";
	echo "<th>".__('Name')."</th>";
	echo "<th>".__('Oper')."</th>";
	/* Translators: Abbrevation for Time threshold */
	echo "<th>".__('Tt')."</th>";
	echo "<th>".__('Firing')."</th>";
	echo "<th>".__('Time')."</th>";
	/* Translators: Abbrevation for Description */
	echo "<th>".__('Desc')."</th>";
	echo "<th>".__('Recovery')."</th>";
	echo "<th>".__('MinMax.Al')."</th>";
	echo "<th>".__('Days')."</th>";
	echo "<th>".__('Fired')."</th>";

	foreach ($result as $row2) {
		if ($color == 1) {
			$tdcolor = "datos";
			$color = 0;
		} else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr>";
		if ($row2["disable"] == 1) {
			$tdcolor = "datos3";
		}
		echo "<td class=$tdcolor>".get_db_sql ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo =".$row2["id_agente_modulo"])."</td>";
		echo "<td class=$tdcolor>".$row2["operation"]."</td>";

		echo "<td class='$tdcolor'>".human_time_description ($row2["time_threshold"])."</td>";

		if ($row2["dis_min"]!=0) {
			$mytempdata = fmod ($row2["dis_min"], 1);
			if ($mytempdata == 0) {
				$mymin = intval ($row2["dis_min"]);
			} else {
				$mymin = format_for_graph ($row2["dis_min"]);
			}
		} else {
			$mymin = 0;
		}

		if ($row2["dis_max"]!=0) {
			$mytempdata = fmod ($row2["dis_max"], 1);
			if ($mytempdata == 0) {
				$mymax = intval ($row2["dis_max"]);
			} else {
				$mymax = format_for_graph ($row2["dis_max"]);
			}
		} else {
			$mymax = 0;
		}

		if (($mymin == 0) && ($mymax == 0)) {
			$mymin = __('N/A');
			$mymax = $mymin;
		}

		// We have alert text ?
		if ($row2["alert_text"]!= "") {
			echo "<td class='$tdcolor'>".__('Text')."</td>";
		} else {
			echo "<td class='$tdcolor'>".$mymin."/".$mymax."</td>";
		}

		// Alert times
		echo "<td class='$tdcolor'>";
		echo get_alert_times ($row2);

		// Description
		echo "</td><td class='$tdcolor'>".substr ($row2["descripcion"],0,20);

		// Has recovery notify activated ?
		if ($row2["recovery_notify"] > 0) {
			$recovery_notify = __('Yes');
		} else {
			$recovery_notify = __('No');
		}
		echo "</td><td class='$tdcolor'>".$recovery_notify;

		// calculare firing conditions
		if ($row2["alert_text"] != ""){
			$firing_cond = __('Text')."(".substr ($row2["alert_text"],0,8).")";
		} else {
			$firing_cond = $row2["min_alerts"]." / ".$row2["max_alerts"];
		}
		echo "</td><td class='$tdcolor'>".$firing_cond;

		// calculate days
		$firing_days = get_alert_days ( $row2 );
		echo "</td><td class='$tdcolor'>".$firing_days;

		// Fired ?
		if ($row2["times_fired"]>0) {
			echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_red.png' title='".__('Alert fired')."'></td>";
		} else {
			echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_green.png' title='".__('Alert not fired')."'></td>";
		}
	}
	echo "</table>";
}

/** 
 * @deprecated use get_server_info instead 
 * Get statistical information for a given server
 *
 * @param int Server id to get status.
 *
 * @return array Server info array
*/
function server_status ($id_server) {
	$serverinfo = get_server_info ($id_server);
	return $serverinfo[$id_server];
}

/**
 * Subfunciton for less typing
 * @ignore
 */
function temp_sql_delete ($table, $row, $value) {
	global $error; //Globalize the errors variable
	
	$result = process_sql_delete ($table, $row.' = '.$value);
	if ($result === false) {
		$error = true;
	}
}


/**
 * Delete an agent from the database.
 *
 * @param mixed An array of agents ids or a single integer id to be erased
 * @param bool Disable the ACL checking, for default false.
 *
 * @return bool False if error, true if success.
*/
function delete_agent ($id_agents, $disableACL = false) {
	global $config;
	
	$error = false;
	
	//Convert single values to an array
	if (! is_array ($id_agents))
		$id_agents = (array) $id_agents;

	//Start transaction
	process_sql_begin ();

	foreach ($id_agents as $id_agent) {
		$id_agent = (int) $id_agent; //Cast as integer
		if ($id_agent < 1)
			continue;
		
		$agent_name = get_agent_name($id_agent, "");
	
		/* Check for deletion permissions */
		$id_group = get_agent_group ($id_agent);
		if ((! give_acl ($config['id_user'], $id_group, "AW")) && !$disableACL) {
			process_sql_rollback ();
			return false;
		}
		
		//A variable where we store that long subquery thing for
		//modules
		$where_modules = "ANY(SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ".$id_agent.")";	
		
		//IP address
		$sql = sprintf ("SELECT id_ag FROM taddress_agent, taddress
			WHERE taddress_agent.id_a = taddress.id_a
			AND id_agent = %d",
			$id_agent);
		$addresses = get_db_all_rows_sql ($sql);
		
		if ($addresses === false)
			$addresses = array ();
		foreach ($addresses as $address) {
			temp_sql_delete ("taddress_agent", "id_ag", $address["id_ag"]);
		}
		
		// We cannot delete tagente_datos and tagente_datos_string here
		// because it's a huge ammount of time. tagente_module has a special
		// field to mark for delete each module of agent deleted and in 
		// daily maintance process, all data for that modules are deleted
		
		//Alert
		temp_sql_delete ("talert_compound", "id_agent", $id_agent);
		temp_sql_delete ("talert_template_modules", "id_agent_module", $where_modules);
	
		//Events (up/down monitors)
		// Dont delete here, could be very time-exausting, let the daily script 
		// delete them after XXX days
		// temp_sql_delete ("tevento", "id_agente", $id_agent);

		//Graphs, layouts & reports
		temp_sql_delete ("tgraph_source", "id_agent_module", $where_modules);
		temp_sql_delete ("tlayout_data", "id_agente_modulo", $where_modules);
		temp_sql_delete ("treport_content", "id_agent_module", $where_modules);
		
		//Planned Downtime
		temp_sql_delete ("tplanned_downtime_agents", "id_agent", $id_agent);
		
		//The status of the module
		temp_sql_delete ("tagente_estado", "id_agente", $id_agent);
		
		//The actual modules, don't put anything based on
		// DONT Delete this, just mark for deletion 
		// temp_sql_delete ("tagente_modulo", "id_agente", $id_agent);
		
		process_sql_update ('tagente_modulo',
			array ('delete_pending' => 1, 'disabled' => 1),
			'id_agente = '. $id_agent);
		
		// Access entries
		// Dont delete here, this records are deleted in daily script
		// temp_sql_delete ("tagent_access", "id_agent", $id_agent);

		// Delete agent policies
		enterprise_hook('delete_agent_policies', array($id_agent));

		// tagente_datos_inc
		// Dont delete here, this records are deleted later, in database script
		// temp_sql_delete ("tagente_datos_inc", "id_agente_modulo", $where_modules);

		// Delete remote configuration
		if (isset ($config["remote_config"])) {
			$agent_md5 = md5 ($agent_name, FALSE);
			
			if (file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5")) {
				// Agent remote configuration editor
				$file_name = $config["remote_config"]."/conf/".$agent_md5.".conf";
				@unlink ($file_name);
				
				$file_name = $config["remote_config"]."/md5/".$agent_md5.".md5";
				@unlink ($file_name);
			}
		}
		
		//And at long last, the agent
		temp_sql_delete ("tagente", "id_agente", $id_agent);

		audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "Agent management",
		"Deleted agent '$agent_name'");

		
		/* Break the loop on error */
		if ($error)
			break;
	}
	
	if ($error) {
		process_sql_rollback ();
		return false;
	} else {
		process_sql_commit ();
		return true;
	}
}


/**
 * This function gets the agent group for a given agent module
 *
 * @param int The agent module id
 *
 * @return int The group id
 */
function get_agentmodule_group ($id_module) {
	$agent = (int) get_agentmodule_agent ((int) $id_module);
	return (int) get_agent_group ($agent);
}

/**
 * This function gets the group for a given agent
 *
 * @param int The agent id
 *
 * @return int The group id
 */
function get_agent_group ($id_agent) {
	return (int) get_db_value ('id_grupo', 'tagente', 'id_agente', (int) $id_agent);
}

/**
 * This function gets the modulegroup for a given group
 *
 * @param int The group id
 *
 * @return int The modulegroup id
 */
function get_agentmodule_modulegroup ($id_module) {
	return (int) get_db_value ('id_module_group', 'tagente_modulo', 'id_agente_modulo', (int) $id_module);
}

/**
 * This function gets the group name for a given group id
 *
 * @param int The group id
 * @param boolean $returnAllGroup Flag the return group, by default false.
 *
 * @return string The group name
 */
function get_group_name ($id_group, $returnAllGroup = false) {
	if($id_group > 0)
		return (string) get_db_value ('nombre', 'tgrupo', 'id_grupo', (int) $id_group);
	elseif($returnAllGroup)
		return "All";
}

/**
 * Gets all module groups. (General, Networking, System).
 *
 * Module groups are merely for sorting frontend
 *
 * @return array All module groups
 */
function get_modulegroups () {
	$result = get_db_all_fields_in_table ("tmodule_group");
	$return = array ();
	
	if (empty ($result)) {
		return $return;
	}
	
	foreach ($result as $modulegroup) {
		$return[$modulegroup["id_mg"]] = $modulegroup["name"];
	}
	
	return $return;
}

/**
 * Gets a modulegroup name based on the id
 *
 * @param int The id of the modulegroup
 *
 * @return string The modulegroup name
 */	
function get_modulegroup_name ($modulegroup_id) {
	if($modulegroup_id == 0)
		return false;
	else
		return (string) get_db_value ('name', 'tmodule_group', 'id_mg', (int) $modulegroup_id);
}

/**
 * Inserts strings into database
 *
 * The number of values should be the same or a positive integer multiple as the number of rows
 * If you have an associate array (eg. array ("row1" => "value1")) you can use this function with ($table, array_keys ($array), $array) in it's options
 * All arrays and values should have been cleaned before passing. It's not neccessary to add quotes.
 *
 * @param string Table to insert into
 * @param mixed A single value or array of values to insert (can be a multiple amount of rows)
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function process_sql_insert ($table, $values) {
	 //Empty rows or values not processed
	if (empty ($values))
		return false;
	
	$values = (array) $values;
		
	$query = sprintf ("INSERT INTO `%s` ", $table);
	$fields = array ();
	$values_str = '';
	$i = 1;
	$max = count ($values);
	foreach ($values as $field => $value) { //Add the correct escaping to values
		if ($field[0] != "`") {
			$field = "`".$field."`";
		}
		
		array_push ($fields, $field);
		
		if (is_null ($value)) {
			$values_str .= "NULL";
		} elseif (is_int ($value) || is_bool ($value)) {
			$values_str .= sprintf ("%d", $value);
		} else if (is_float ($value) || is_double ($value)) {
			$values_str .= sprintf ("%f", $value);
		} else {
			$values_str .= sprintf ("'%s'", $value);
		}
		
		if ($i < $max) {
			$values_str .= ",";
		}
		$i++;
	}
	
	$query .= '('.implode (', ', $fields).')';
	
	$query .= ' VALUES ('.$values_str.')';
	
	return process_sql ($query, 'insert_id');
}

/**
 * Updates a database record.
 *
 * All values should be cleaned before passing. Quoting isn't necessary.
 * Examples:
 *
 * <code>
 * process_sql_update ('table', array ('field' => 1), array ('id' => $id));
 * process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name));
 * process_sql_update ('table', array ('field' => 1), array ('id' => $id, 'name' => $name), 'OR');
 * process_sql_update ('table', array ('field' => 2), 'id in (1, 2, 3) OR id > 10');
 * </code>
 *
 * @param string Table to insert into
 * @param array An associative array of values to update
 * @param mixed An associative array of field and value matches. Will be joined
 * with operator specified by $where_join. A custom string can also be provided.
 * If nothing is provided, the update will affect all rows.
 * @param string When a $where parameter is given, this will work as the glue
 * between the fields. "AND" operator will be use by default. Other values might
 * be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function process_sql_update ($table, $values, $where = false, $where_join = 'AND') {
	$query = sprintf ("UPDATE `%s` SET %s",
		$table,
		format_array_to_update_sql ($values));
	
	if ($where) {
		if (is_string ($where)) {
			// No clean, the caller should make sure all input is clean, this is a raw function
			$query .= " WHERE ".$where;
		} else if (is_array ($where)) {
			$query .= format_array_to_where_clause_sql ($where, $where_join, ' WHERE ');
		}
	}
	
	return process_sql ($query);
}

/**
 * Delete database records.
 *
 * All values should be cleaned before passing. Quoting isn't necessary.
 * Examples:
 *
 * <code> 
 * process_sql_delete ('table', array ('id' => 1));
 * // DELETE FROM table WHERE id = 1
 * process_sql_delete ('table', array ('id' => 1, 'name' => 'example'));
 * // DELETE FROM table WHERE id = 1 AND name = 'example'
 * process_sql_delete ('table', array ('id' => 1, 'name' => 'example'), 'OR');
 * // DELETE FROM table WHERE id = 1 OR name = 'example'
 * process_sql_delete ('table', 'id in (1, 2, 3) OR id > 10');
 * // DELETE FROM table WHERE id in (1, 2, 3) OR id > 10
 * </code>
 *
 * @param string Table to insert into
 * @param array An associative array of values to update
 * @param mixed An associative array of field and value matches. Will be joined
 * with operator specified by $where_join. A custom string can also be provided.
 * If nothing is provided, the update will affect all rows.
 * @param string When a $where parameter is given, this will work as the glue
 * between the fields. "AND" operator will be use by default. Other values might
 * be "OR", "AND NOT", "XOR"
 *
 * @return mixed False in case of error or invalid values passed. Affected rows otherwise
 */
function process_sql_delete ($table, $where, $where_join = 'AND') {
	if (empty ($where))
		/* Should avoid any mistake that lead to deleting all data */
		return false;
	
	$query = sprintf ("DELETE FROM `%s` WHERE ", $table);
	
	if ($where) {
		if (is_string ($where)) {
			/* FIXME: Should we clean the string for sanity? 
			 Who cares if this is deleting data... */
			$query .= $where;
		} else if (is_array ($where)) {
			$query .= format_array_to_where_clause_sql ($where, $where_join);
		}
	}
	
	return process_sql ($query);
}

/**
 * Starts a database transaction.
 */
function process_sql_begin () {
	mysql_query ('SET AUTOCOMMIT = 0');
	mysql_query ('START TRANSACTION');
}

/**
 * Commits a database transaction.
 */
function process_sql_commit () {
	mysql_query ('COMMIT');
	mysql_query ('SET AUTOCOMMIT = 0');
}

/**
 * Rollbacks a database transaction.
 */
function process_sql_rollback () {
	mysql_query ('ROLLBACK');
	mysql_query ('SET AUTOCOMMIT = 0');
}

/** 
 * Get all the users belonging to a group.
 * 
 * @param int $id_group The group id to look for
 * 
 * @return array An array with all the users or an empty array
 */
function get_group_users ($id_group, $filter = false) {
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_grupo'] = $id_group;
	$resulta = array();
	$resulta = get_db_all_rows_filter ("tusuario_perfil", $filter);
	
	// The users of the group All (0) will be also returned
	$filter['id_grupo'] = 0;
	$resultb = array();
	$resultb = get_db_all_rows_filter ("tusuario_perfil", $filter);
	
	if($resulta == false && $resultb == false)
		$result = false;
	elseif($resulta == false)
		$result = $resultb;
	elseif($resultb == false)
		$result = $resulta;
	else
		$result = array_merge($resulta,$resultb);

	if ($result === false)
		return array ();
	//This removes stale users from the list. This can happen if switched to another auth scheme
	//(internal users still exist) or external auth has users removed/inactivated from the list (eg. LDAP)
	$retval = array ();
	foreach ($result as $key => $user) {
		if (!is_user ($user)) {
			unset ($result[$key]);
		} else {
			array_push ($retval, get_user_info ($user));
		}
	}
	
	return $retval;
}

/**
 * Prints a database debug table with all the queries done in the page loading.
 * 
 * This functions does nothing if the config['debug'] flag is not set.
 */
function print_database_debug () {
	global $config;
	
	if (! isset ($config['debug']))
		return '';
	
	echo '<div class="database_debug_title">'.__('Database debug').'</div>';
	
	$table->id = 'database_debug';
	$table->cellpadding = '0';
	$table->width = '95%';
	$table->align = array ();
	$table->align[1] = 'left';
	$table->size = array ();
	$table->size[0] = '40px';
	$table->size[2] = '30%';
	$table->size[3] = '40px';
	$table->size[4] = '40px';
	$table->size[5] = '40px';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = '#';
	$table->head[1] = __('SQL sentence');
	$table->head[2] = __('Result');
	$table->head[3] = __('Rows');
	$table->head[4] = __('Saved');
	$table->head[5] = __('Time (ms)');
	
	if (! isset ($config['db_debug']))
		$config['db_debug'] = array ();
	$i = 1;
	foreach ($config['db_debug'] as $debug) {
		$data = array ();
		
		$data[0] = $i++;
		$data[1] = $debug['sql'];
		$data[2] = (empty ($debug['result']) ? __('OK') : $debug['result']);
		$data[3] = $debug['affected'];
		$data[4] = $debug['saved'];
		$data[5] = (isset ($debug['extra']['time']) ? format_numeric ($debug['extra']['time'] * 1000, 0) : '');
		
		array_push ($table->data, $data);
		
		if (($i % 100) == 0) {
			print_table ($table);
			$table->data = array ();
		}
	}
	
	print_table ($table);
}


/** 
 * Return access to a specific agent by a specific user
 * 
 * @param int Agent id.
 * @param string Access mode to be checked. Default AR (Agent reading)
 * @param string User id. Current user by default
 *
 * @return bool Access to that agent (false not, true yes)
 */
function user_access_to_agent ($id_agent, $mode = "AR", $id_user = false) {
	if (empty ($id_agent))
		return false;
	
	if ($id_user == false) {
		global $config;
		$id_user = $config['id_user'];
	}
	
	$id_group = (int) get_db_value ('id_grupo', 'tagente', 'id_agente', (int) $id_agent);
	return (bool) give_acl ($id_user, $id_group, $mode);
}
?>
