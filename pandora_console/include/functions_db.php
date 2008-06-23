<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/** 
 * Check if login session variables are set.
 *
 * It will stop the execution if those variables were not set
 * 
 * @return 0 on success
 */
function check_login () { 
	global $config;
	if (!isset($config["homedir"])){
		// No exists $config. Exit inmediatly
		include ("general/noaccess.php");
		exit;
	}
	if ((isset($_SESSION["id_usuario"])) AND ($_SESSION["id_usuario"] != "")) { 
		$id = $_SESSION["id_usuario"];
		$query1="SELECT id_usuario FROM tusuario WHERE id_usuario= '$id'";
		$resq1 = mysql_query($query1);
		$rowdup = mysql_fetch_array($resq1);
		$nombre = $rowdup[0];
		if ( $id == $nombre ){
			return 0;
		}
	}
	audit_db("N/A", getenv("REMOTE_ADDR"), "No session", "Trying to access without a valid session");
	include ($config["homedir"]."/general/noaccess.php");
	exit;
}

/** 
 * Check access privileges to resources
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
 * @param id_user User id 
 * @param id_group Agents group id
 * @param access Access privilege
 * 
 * @return 1 if the user has privileges, 0 if not.
 */
function give_acl ($id_user, $id_group, $access) {
	// IF user is level = 1 then always return 1
	// Access can be:
	/*	
		IR - Incident Read
		IW - Incident Write
		IM - Incident Management
		AR - Agent Read
		AW - Agent Write
		LW - Alert Write
		UM - User Management
		DM - DB Management
		LM - Alert Management
		PM - Pandora Management
	*/
	
	// Conexion con la base Datos 
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id_user."'";
	$res=mysql_query($query1);
	$row=mysql_fetch_array($res);
	if ($row["nivel"] == 1)
		return 1;
	if ($id_group == 0) // Group doesnt matter, any group, for check permission to do at least an action in a group
		$query1="SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."'";	// GroupID = 0, group doesnt matter (use with caution!)
	else
		$query1="SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."' and ( id_grupo =".$id_group." OR id_grupo = 1)";	// GroupID = 1 ALL groups      
	$resq1=mysql_query($query1);  
	$result = 0; 
	while ($rowdup=mysql_fetch_array($resq1)){
		$id_perfil=$rowdup["id_perfil"];
		// For each profile for this pair of group and user do...
		$query2="SELECT * FROM tperfil WHERE id_perfil = ".$id_perfil;    
		$resq2=mysql_query($query2);  
		if ($rowq2=mysql_fetch_array($resq2)){
			switch ($access) {
			case "IR":
				$result = $result + $rowq2["incident_view"];
				
				break;
			case "IW":
				$result = $result + $rowq2["incident_edit"];
				
				break;
			case "IM":
				$result = $result + $rowq2["incident_management"];
				
				break;
			case "AR":
				$result = $result + $rowq2["agent_view"];
				
				break;
			case "AW":
				$result = $result + $rowq2["agent_edit"];
				
				break;
			case "LW":
				$result = $result + $rowq2["alert_edit"];
				
				break;
			case "LM":
				$result = $result + $rowq2["alert_management"];
				
				break;
			case "PM":
				$result = $result + $rowq2["pandora_management"];
				
				break;
			case "DM":
				$result = $result + $rowq2["db_management"];
				
				break;
			case "UM":
				$result = $result + $rowq2["user_management"];
				
				break;
			}
		} 
	}
	if ($result > 1)
		$result = 1;
        return $result; 
} 

/** 
 * Adds an audit log entry.
 * 
 * @param id User id
 * @param ip Client IP
 * @param accion Action description
 * @param descripcion Long action description
 */
function audit_db ($id, $ip, $accion, $descripcion){
	require("config.php");
	$today=date('Y-m-d H:i:s');
	$utimestamp = time();
	$sql1='INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion, utimestamp) VALUES ("'.$id.'","'.$accion.'","'.$today.'","'.$ip.'","'.$descripcion.'", '.$utimestamp.')';
	$result=mysql_query($sql1);
}

/**
 * Log in a user into Pandora.
 *
 * @param id_user User id
 * @param ip Client user IP address.
 */
function logon_db ($id_user, $ip) {
	require  ("config.php");
	audit_db ($id_user, $ip, "Logon", "Logged in");
	// Update last registry of user to get last logon
	$sql = 'UPDATE tusuario fecha_registro = $today WHERE id_usuario = "$id_user"';
	$result = mysql_query ($sql);
}

/**
 * Log out a user into Pandora.
 *
 * @param id_user User id
 * @param ip Client user IP address.
 */
function logoff_db ($id_user, $ip) {
	require ("config.php");
	audit_db ($id_user, $ip, "Logoff", "Logged out");
}

/**
 * Get profile name from id.
 * 
 * @param id_profile Id profile in tperfil
 * 
 * @return Profile name of the given id
 */
function dame_perfil ($id_profile) {
	return (string) get_db_value ('name', 'tperfil', 'id_perfil', (int) $id_profile);
}

/** 
 * Get disabled field of a group
 * 
 * @param id_group Group id
 * 
 * @return Disabled field of given group
 */
function give_disabled_group ($id_group) {
	return (bool) get_db_value ('disabled', 'tgrupo', 'id_grupo', (int) $id_group);
}

/**
 * Get all the agents in a group.
 *
 * @param id_group Group id
 * @param disabled Add disabled agents to agents. Default: False.
 *
 * @return An array with all agents in the group.
 */
function get_agents_in_group ($id_group, $disabled = false) {
	echo "GROUP: ".$id_group;
	/* 'All' group must return all agents */
	if ($id_group == 1) {
		if ($disabled)
			return get_db_all_rows_in_table ('tagente');
		return get_db_all_rows_field_filter ('tagente', 'disabled', 0);
	}
	if ($disabled)
		return get_db_all_rows_field_filter ('tagente', 'id_grupo', (int) $id_group);
	$sql = sprintf ('SELECT * FROM tagente 
			WHERE id_grupo = %d AND disabled = 0',
			$id_group);
	return get_db_all_rows_sqlfree ($sql);
}

/**
 * Get all the modules in an agent.
 *
 * @param $id_agent Agent id
 *
 * @return An array with all modules in the agent.
 */
function get_modules_in_agent ($id_agent) {
	return get_db_all_rows_field_filter ('tagente_modulo', 'id_agente', (int) $id_agent);
}

/**
 * Get all the simple alerts of an agent.
 *
 * @param $id_agent Agent id
 *
 * @return An array with all simple alerts defined for an agent.
 */
function get_simple_alerts_in_agent ($id_agent) {
	$sql = sprintf ('SELECT talerta_agente_modulo.*
			FROM talerta_agente_modulo, tagente_modulo
			WHERE talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.id_agente = %d', $id_agent);
	return get_db_all_rows_sqlfree ($sql);
}

/**
 * Get all the combined alerts of an agent.
 *
 * @param $id_agent Agent id
 *
 * @return An array with all combined alerts defined for an agent.
 */
function get_combined_alerts_in_agent ($id_agent) {
	return get_db_all_rows_field_filter ('talerta_agente_modulo', 'id_agent', (int) $id_agent);
}

/**
 * Get all the alerts of an agent, simple and combined.
 *
 * @param $id_agent Agent id
 *
 * @return An array with all alerts defined for an agent.
 */
function get_alerts_in_agent ($id_agent) {
	$simple_alerts = get_simple_alerts_in_agent ($id_agent);
	$combined_alerts = get_combined_alerts_in_agent ($id_agent);
	
	return array_merge ($simple_alerts, $combined_alerts);
}

/**
 * Get a list of the reports the user can view.
 *
 * A user can view a report by two ways:
 *  - The user created the report (id_user field in treport)
 *  - The report is not private and the user has reading privileges on 
 *    the group associated to the report
 *
 * @param $id_user User id
 *
 * @return An array with all the reports the user can view.
 */
function get_reports ($id_user) {
	$user_reports = array ();
	$all_reports = get_db_all_rows_in_table ('treport');
	if (sizeof ($all_reports) == 0) {
		return $user_reports;
	}
	foreach ($all_reports as $report) {
		/* The report is private and it does not belong to the user */
		if ($report['private'] && $report['id_user'] != $id_user)
			continue;
		/* Check ACL privileges on report group */
		if (! give_acl ($id_user, $report['id_group'], 'AR'))
			continue;
		array_push ($user_reports, $report);
	}
	return $user_reports;
}

/** 
 * Get group name from group.
 * 
 * @param id_group Id group to get the name.
 * 
 * @return The name of the given group
 */
function dame_grupo ($id_group) {
	return (string) get_db_value ('nombre', 'tgrupo', 'id_grupo', (int) $id_group);
}

/** 
 * Get group icon from group.
 * 
 * @param id_group Id group to get the icon
 * 
 * @return Icon path of the given group
 */
function dame_grupo_icono ($id_group) {
	return (string) get_db_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
}

/** 
 * Get agent id from an agent name.
 * 
 * @param agent_name Agent name to get its id.
 * 
 * @return Id from the agent of the given name.
 */
function dame_agente_id ($agent_name) {
	return (int) get_db_value ('id_agente', 'tagente', 'nombre', $agent_name);
}

/** 
 * Get user id of a note.
 * 
 * @param id_note Note id.
 * 
 * @return User id of the given note.
 */
function give_note_author ($id_note) {
	return (int) get_db_value ('id_usuario', 'tnota', 'id_nota', (int) $id_note);
}

/** 
 * Get description of an event.
 * 
 * @param id_event Event id.
 * 
 * @return Description of the given event.
 */
function return_event_description ($id_event) {
	return (string) get_db_value ('evento', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Get group id of an event.
 * 
 * @param id_event Event id
 * 
 * @return Group id of the given event.
 */
function gime_idgroup_from_idevent ($id_event) {
	return (int) get_db_value ('id_grupo', 'tevento', 'id_evento', (int) $id_event);
}

/** 
 * Get name of an agent.
 * 
 * @param id_agente Agent id.
 * 
 * @return Name of the given agent.
 */
function dame_nombre_agente ($id_agente) {
	return (string) get_db_value ('nombre', 'tagente', 'id_agente', (int) $id_agente);
}

/** 
 * Get password of an user.
 * 
 * @param id_usuario User id.
 * 
 * @return Password of an user.
 */
function get_user_password ($id_usuario) {
	return (string) get_db_value ('password', 'tusuario', 'id_usuario', (int) $id_usuario);
}

/** 
 * Get name of an alert
 * 
 * @param id_alert Alert id.
 * 
 * @return Name of the alert.
 */
function dame_nombre_alerta ($id_alert) {
	return (string) get_db_value ('nombre', 'talerta', 'id_alerta', (int) $id_alert);
}

/** 
 * Get name of a module group.
 * 
 * @param id_module_group Module group id.
 * 
 * @return Name of the given module group.
 */
function dame_nombre_grupomodulo ($id_module_group) {
	return (string) get_db_value ('name', 'tmodule_group', 'id_mg', (int) $id_module_group);
}

/** 
 * Get the name of an exporting server
 * 
 * @param id_server Server id
 * 
 * @return The name of given server.
 */
function dame_nombre_servidorexportacion ($id_server) {
	return (string) get_db_value ('name', 'tserver_export', 'id', (int) $id_server);
}

/** 
 * Get the name of a plugin
 * 
 * @param id_plugin Plugin id.
 * 
 * @return The name of the given plugin
 */
function dame_nombre_pluginid ($id_plugin) {
	return (string) get_db_value ('name', 'tplugin', 'id', (int) $id_plugin);
}

/** 
 * Get the name of a module type
 * 
 * @param id_type Type id
 * 
 * @return The name of the given type.
 */
function giveme_module_type ($id_type) {
	return (string) get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}

/** 
 * Get agent name of an agent module.
 * 
 * @param id_agente_modulo Agent module id.
 * 
 * @return The name of the given agent module.
 */
function dame_nombre_agente_agentemodulo ($id_agente_modulo) {
	$id_agent = get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
	if ($id_agent)
		return dame_nombre_agente ($id_agent);
	return '';
}

/** 
 * Get the module name of an agent module.
 * 
 * @param id_agente_modulo Agent module id.
 * 
 * @return Name of the given agent module.
 */
function dame_nombre_modulo_agentemodulo ($id_agente_modulo) {
	return (string) get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}

/** 
 * Get the module type of an agent module.
 * 
 * @param id_agente_modulo Agent module id.
 * 
 * @return Module type of the given agent module.
 */
function dame_id_tipo_modulo_agentemodulo ($id_agente_modulo) {
	return (int) get_db_value ('id_tipo_modulo', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}

/** 
 * Get the real name of an user.
 * 
 * @param id_user User id
 * 
 * @return Real name of given user.
 */
function dame_nombre_real ($id_user) {
	return (string) get_db_value ('nombre_real', 'tusuario', 'id_usuario', (int) $id_user);
}

/**
 * Get all the times a monitor went down during a period.
 * 
 * @param $id_agent_module Agent module of the monitor.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The number of times a monitor went down.
 */
function get_monitor_downs_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT COUNT(*) FROM tevento WHERE
			event_type = "monitor_down" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	 
	return get_db_sql ($sql);
}

/**
 * Get the last time a monitor went down during a period.
 * 
 * @param $id_agent_module Agent module of the monitor.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The last time a monitor went down.
 */
function get_monitor_last_down_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT MAX(timestamp) FROM tevento WHERE
			event_type = "monitor_down" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	
	return get_db_sql ($sql);
}

/**
 * Get all the times an alerts fired during a period.
 * 
 * @param $id_agent_module Agent module of the alert.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The number of times an alert fired.
 */
function get_alert_fires_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT COUNT(*) FROM tevento WHERE
			event_type = "alert_fired" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	return (int) get_db_sql ($sql);
}

/**
 * Get the last time an alert fired during a period.
 * 
 * @param $id_agent_module Agent module of the monitor.
 * @param $period Period timed to check from date
 * @param $date Date to check (now by default)
 *
 * @return The last time an alert fired.
 */
function get_alert_last_fire_timestamp_in_period ($id_agent_module, $period, $date = 0) {
	if (!$date)
		$date = time ();
	$datelimit = $date - $period;
	$sql = sprintf ('SELECT MAX(timestamp) FROM tevento WHERE
			event_type = "alert_fired" 
			AND id_agentmodule = %d
			AND utimestamp > %d AND utimestamp <= %d',
			$id_agent_module, $datelimit, $date);
	return get_db_sql ($sql);
}

/** 
 * Get the author of an incident.
 * 
 * @param id_incident Incident id.
 * 
 * @return The author of an incident
 */
function give_incident_author ($id_incident) {
	return (string) get_db_value ('id_usuario', 'tincidencia', 'id_incidencia', (int) $id_incident);
}

/** 
 * Get the server name.
 * 
 * @param id_server Server id.
 * 
 * @return Name of the given server
 */
function give_server_name ($id_server) {
	return (string) get_db_value ('name', 'tserver', 'id_server', $id_server);
}

/** 
 * Get the module type name.
 * 
 * @param id_type Type id
 * 
 * @return Name of the given type.
 */
function dame_nombre_tipo_modulo ($id_type) {
	return (string) get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', $id_type);
} 

/** 
 * Get group name from the id
 * 
 * @param id_group Group id
 * 
 * @return The name of the given group
 */
function dame_nombre_grupo ($id_group) {
	return (string) get_db_value ('nombre', 'tgrupo', 'id_grupo', $id_group);
} 

/** 
 * Get group id of an agent.
 * 
 * @param id_agent Agent id
 * 
 * @return Group of the given agent
 */
function dame_id_grupo ($id_agent) {
	return (int) get_db_value ('id_grupo', 'tagente', 'id_agente', $id_agent);
}

/** 
 * Get the number of notes in a incident.
 * 
 * @param id_incident Incident id
 * 
 * @return The number of notes in given incident.
 */
function dame_numero_notas ($id_incident) {
	return (int) get_db_value ('COUNT(*)', 'tnota_inc', 'id_incidencia', $id_incident);
}

/** 
 * Get the number of pandora data in the database.
 * 
 * @return 
 */
function dame_numero_datos () {
	return (int) get_db_sql ('SELECT COUNT(*) FROM tagente_datos');
}

/** 
 * Get the data value of a agent module of string type.
 * 
 * @param id Agent module string id
 * 
 * @return Data value of the agent module.
 */
function dame_generic_string_data ($id) {
	return (string) get_db_value ('datos', 'tagente_datos_string', 'id_tagente_datos_string', $id);
}

/** 
 * Delete an incident of the database.
 * 
 * @param id_inc Incident id
 */
function borrar_incidencia ($id_inc) {
	require ("config.php");
	$sql = "DELETE FROM tincidencia WHERE id_incidencia = ".$id_inc;
	mysql_query ($sql);
	$sql = "SELECT * FROM tnota_inc WHERE id_incidencia = ".$id_inc;
	$res2 = mysql_query ($sql);
	while ($row2 = mysql_fetch_array ($res2)) {
		// Delete all note ID related in table
		$sql = "DELETE FROM tnota WHERE id_nota = ".$row2["id_nota"];
		mysql_query ($sql);
	}
	$sql = "DELETE FROM tnota_inc WHERE id_incidencia = ".$id_inc;
	mysql_query ($sql);
	// Delete attachments
	$sql = "SELECT * FROM tattachment WHERE id_incidencia = ".$id_inc;
	$result = mysql_query ($sql);
	while ($row = mysql_fetch_array ($result)) {
		// Unlink all attached files for this incident
		$file_id = $row["id_attachment"];
		$filename = $row["filename"];
		unlink ($attachment_store."attachment/pand".$file_id."_".$filename);
	}
	$sql = "DELETE FROM tattachment WHERE id_incidencia = ".$id_inc;
	mysql_query ($sql);
}

/** 
 * Get the operating system name.
 * 
 * @param id_os Operating system id.
 * 
 * @return Name of the given operating system.
 */
function dame_so_name ($id_os) {
	return (string) get_db_value ('name', 'tconfig_os', 'id_os', $id_os);
}

/** 
 * Update user last login timestamp.
 * 
 * @param id_user User id
 */
function update_user_contact ($id_user) {
	$sql = "UPDATE tusuario set fecha_registro = NOW() WHERE id_usuario = '".$id_user."'";
	mysql_query ($sql);
}

/** 
 * Get the icon of an operating system.
 *
 * The path of the icons is 'images/' which must be append by the
 * caller (including slash and filename extension .png)
 * 
 * @param id_os Operating system id
 * 
 * @return Icon filename of the operating system
 */
function dame_so_icon ($id_os) {
	return (string) get_db_value ('icon_name', 'tconfig_os', 'id_os', $id_os);
}

/** 
 * Get the user email
 * 
 * @param id_user User id.
 * 
 * @return Get the email address of an user
 */
function dame_email ($id_user) {
	return (string) get_db_value ('direccion', 'tusuario', 'id_usuario', $id_user);
}

/** 
 * Checks if a user is administrator.
 * 
 * @param id_user User id.
 * 
 * @return True is the user is admin
 */
function dame_admin ($id_user) {
	$level = get_db_value ('nivel', 'tusuario', 'id_usuario', $id_user);
	if ($level)
		return true;
	return false;
}

/** 
 * WARNING: This is a deprectad function and must not be used
 */
function comprueba_login() { 
	return check_login ();
}

/** 
 * Check if an agent has alerts fired.
 * 
 * @param id_agent Agent id.
 * 
 * @return True if the agent has fired alerts.
 */
function check_alert_fired ($id_agent) {
	$sql = "SELECT COUNT(*) FROM talerta_agente_modulo, tagente_modulo
		WHERE talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND times_fired > 0 AND id_agente = ".$id_agent;
	
	$value = get_db_sql ($sql);
	if ($value > 0)
		return true;
	return false;
}

/** 
 * Check is a user exists in the system
 * 
 * @param id_user User id.
 * 
 * @return True if the user exists.
 */
function existe ($id_user) {
	$user = get_db_row ('tusuario', 'id_usuario', $id_user);
	if (! $user)
		return false;
	return true;
}

/** 
 * Insert a event in the event log system.
 * 
 * @param evento 
 * @param id_grupo 
 * @param id_agente 
 * @param status 
 * @param id_usuario 
 * @param event_type 
 * @param priority 
 * @param id_agent_module 
 * @param id_aam 
 */
function event_insert ($evento, $id_grupo, $id_agente, $status = 0,
			$id_usuario = '', $event_type = "unknown", $priority = 0,
			$id_agent_module = 0, $id_aam = 0) {
	$sql = 'INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, 
		estado, utimestamp, id_usuario, event_type, criticity, id_agentmodule, id_alert_am) 
		VALUES ('.$id_agente.','.$id_grupo.',"'.$evento.'",NOW(),'.$status.
		', '.$utimestamp.', "'.$id_usuario.'", "'.$event_type.'", '.$priority.
		', '.$id_agent_module.', '.$id_aam.')';

	mysql_query ($sql);
}

/** 
 * Get the interval value of an agent module.
 *
 * If the module interval is not set, the agent interval is returned
 * 
 * @param id_agent_module Id agent module to get the interval value.
 * 
 * @return 
 */
function get_module_interval ($id_agent_module) {
	$interval = (int) get_db_value ('module_interval', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
	if ($interval)
		return $interval;
	$id_agent = get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', (int) $id_agent_module);
	
	return (int) give_agentinterval ($id_agent);
}

/** 
 * Get the interval of an agent.
 * 
 * @param id_agent Agent id.
 * 
 * @return The interval value of a given agent
 */
function give_agentinterval ($id_agent) {
	return (int) get_db_value ('intervalo', 'tagente', 'id_agente', $id_agent);
}

/** 
 * Get the flag value of an agent module.
 * 
 * @param id_agent_module Agent module id.
 * 
 * @return The flag value of an agent module.
 */
function give_agentmodule_flag ($id_agent_module) {
	return get_db_value ('flag', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

/** 
 * Prints a list of <options> HTML tags with the groups the user has
 * reading privileges.
 * 
 * @param id_user User id
 * @param show_all Flag to show all the groups or not. True by default.
 * 
 * @return An array with all the groups
 */
function list_group ($id_user, $show_all = 1){
	$mis_grupos = array (); // Define array mis_grupos to put here all groups with Agent Read permission
	$sql = 'SELECT id_grupo, nombre FROM tgrupo';
	$result = mysql_query ($sql);
	while ($row = mysql_fetch_array ($result)) {
		if ($row["id_grupo"] != 0) {
			if (give_acl($id_user,$row["id_grupo"], "AR") == 1) {
				if (($row["id_grupo"] != 1) || ($show_all == 1)) {
					//Put in  an array all the groups the user belongs to
					array_push ($mis_grupos, $row["id_grupo"]);
					echo "<option value='".$row["id_grupo"]."'>".
					$row["nombre"]."</option>";
				}
			}
		}
	}
	return ($mis_grupos);
}

/** 
 * Get a list of the groups a user has reading privileges.
 * 
 * @param id_user User id
 * 
 * @return A list of the groups the user has reading privileges.
 */
function list_group2 ($id_user) {
	$mis_grupos[]=""; // Define array mis_grupos to put here all groups with Agent Read permission
	$sql = 'SELECT id_grupo FROM tgrupo';
	$result = mysql_query ($sql);
	while ($row = mysql_fetch_array ($result)) {
		if (give_acl ($id_user, $row["id_grupo"], "AR") == 1) {
			$mis_grupos[]=$row["id_grupo"]; //Put in  an array all the groups the user belongs
		}
	}
	return ($mis_grupos);
}

/** 
 * Get group icon
 *
 * The path of the icons is 'images/' or 'images/group_small/', which
 * must be append by the caller (including slash and filename
 * extension .png)
 * 
 * @param id_group Group id
 * 
 * @return Icon filename of the given group
 */
function show_icon_group ($id_group) {
	return (string) get_db_value ('icon', 'tgrupo', 'id_grupo', $id_group);
}

/** 
 * Get module type icon.
 *
 * The path of the icons is 'images/', which must be append by the
 * caller (including final slash).
 * 
 * @param id_tipo Module type id
 * 
 * @return Icon filename of the given group
 */
function show_icon_type ($id_type) { 
	return (string) get_db_value ('icon', 'ttipo_modulo', 'id_tipo', $id_type);
}

/**
 * Return a string containing image tag for a given target id (server)
 *
 * @param int Server type id
 * @return string Fully formatted  IMG HTML tag with icon
 */
function show_server_type ($id){ 
	global $config;
	switch ($id) {
	case 1:
		return '<img src="images/data.png" title="Pandora FMS Data server">';
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
	default: return "--";
	}
}

/** 
 * Get a module category name
 * 
 * @param id_category Id category
 * 
 * @return Name of the given category
 */
function give_modulecategory_name ($id_category) {
	switch ($id_category) {
	case 0: 
		return lang_string ("cat_0");
		break;
	case 1: 
		return lang_string ("cat_1");
		break;
	case 2: 
		return lang_string ("cat_2");
		break;
	case 3: 
		return lang_string ("cat_3");
		break;
	}
	return lang_string ("unknown");
}

/** 
 * Get a network component group name
 * 
 * @param id_network_component_group Id network component group.
 * 
 * @return Name of the given network component group
 */
function give_network_component_group_name ($id_network_component_group) {
	return (string) get_db_value ('name', 'tnetwork_component_group', 'id_sg', $id_network_component_group);
}

/** 
 * Get a network profile name.
 * 
 * @param id_network_profile Id network profile
 * 
 * @return Name of the given network profile.
 */
function give_network_profile_name ($id_network_profile) {
	return (string) get_db_value ('name', 'tnetwork_profile', 'id_np', $id_network_profile);
}

/** 
 * Assign an IP address to an agent.
 * 
 * @param id_agent Agent id
 * @param ip_address IP address to assign
 */
function agent_add_address ($id_agent, $ip_address) {
	$address_exist = 0;
	$id_address =-1;
	$address_attached = 0;

	// Check if already is attached to agent
	$sql = "SELECT * FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND ip = '$ip_address'
		AND id_agent = $id_agent";
	$current_address = get_db_row_sql ($sql);
	if ($current_address)
		return;
	
	// Look for a record with this IP Address
	$id_address = (int) get_db_value ('id_a', 'taddress', 'ip', $ip_address);
	
	if (! $id_address) {
		// Create IP address in tadress table
		$sql = "INSERT INTO taddress (ip) VALUES ('$ip_address')";
		mysql_query ($sql);
		$id_address = mysql_insert_id ();
	}
	
	// Add address to agent
	$sql = "INSERT INTO taddress_agent
			(id_a, id_agent) VALUES
			($id_address, $id_agent)";
	mysql_query ($sql);
}

/** 
 * Unassign an IP address from an agent.
 * 
 * @param id_agent Agent id
 * @param ip_address IP address to unassign
 */
function agent_delete_address ($id_agent, $ip_address) {
	$address_exist = 0;
	$id_address =-1;
	$sql = "SELECT * FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND ip = '$ip_address'
		AND id_agent = $id_agent";
	if ($resq1 = mysql_query ($sql)) {
		$rowdup = mysql_fetch_array($resq1);
		$id_ag = $rowdup["id_ag"];
		$id_a = $rowdup["id_a"];
		$sql = "DELETE FROM taddress_agent WHERE id_ag = $id_ag";	
		mysql_query ($sql);
	}
	// Need to change main address ? 
	if (give_agent_address ($id_agent) == $ip_address) {
		$new_ip = give_agent_address_from_list ($id_agent);
		// Change main address in agent to whis one
		$query = "UPDATE tagente 
			(direccion) VALUES
			($new_ip)
			WHERE id_agente = $id_agent ";
		mysql_query ($query);
	}
}

/** 
 * Get address of an agent.
 * 
 * @param id_agent Agent id
 * 
 * @return The address of the given agent 
 */
function give_agent_address ($id_agent) {
	return (string) get_db_value ('direccion', 'tagente', 'id_agente', $id_agent);
}

/** 
 * Get IP address of an agent from address list
 * 
 * @param id_agent Agent id
 * 
 * @return The IP address of the given agent.
 */
function give_agent_address_from_list ($id_agent){
	$sql = "SELECT ip FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND id_agent = $id_agent";
	return (string) get_db_sql ($sql);
}

/** 
 * Get agent id from an agent module.
 * 
 * @param id_agent_module Id of the agent module.
 * 
 * @return The agent if of the given module.
 */
function give_agent_id_from_module_id ($id_agent_module) {
	return (int) get_db_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}

/** 
 * Get the first value of the first row of a table in the database.
 * 
 * @param field Field name to get
 * @param table Table to retrieve the data
 * @param field_search Field to filter elements
 * @param condition Condition the field must have.
 * 
 * @return 
 */
function get_db_value ($field, $table, $field_search, $condition){
	if (is_int ($condition)) {
		$sql = sprintf ('SELECT %s FROM %s WHERE %s = %d', $field, $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ('SELECT %s FROM %s WHERE %s = %f', $field, $table, $field_search, $condition);
	} else {
		$sql = sprintf ('SELECT %s FROM %s WHERE %s = "%s"', $field, $table, $field_search, $condition);
	}
	$sql .= ' LIMIT 1';
	
	$result = mysql_query ($sql);
	if (! $result) {
		echo '<strong>Error:</strong> get_db_value("'.$sql.'") :'. mysql_error ().'<br />';
		return NULL;
	}
	if ($row = mysql_fetch_array ($result))
		return $row[0];
	
	return NULL;
}

/** 
 * Get the first row of an SQL database query.
 * 
 * @param sql SQL select statement to execute.
 * 
 * @return The first row of the result.
 */
function get_db_row_sql ($sql) {
	$result = mysql_query ($sql);
	if (! $result) {
		echo '<strong>Error:</strong> get_db_row("'.$sql.'") :'. mysql_error ().'<br />';
		return NULL;
	}
	if ($row = mysql_fetch_array ($result))
		return $row;
	
	return NULL;
}

/** 
 * Get the first row of a database query into a table.
 *
 * The SQL statement executed would be something like:
 * "SELECT * FROM $table WHERE $field_search = $condition"
 *
 * @param table Table to get the row
 * @param field_search Field to filter elementes
 * @param condition Condition the field must have.
 * 
 * @return The first row of a database query.
 */
function get_db_row ($table, $field_search, $condition) {
	global $config;
	
	if (is_int ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %d', $table, $field_search, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %f', $table, $field_search, $condition);
	} else {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = "%s"', $table, $field_search, $condition);
	}
	$sql .= ' LIMIT 1';
	
	return get_db_row_sql ($sql);
}

/** 
 * Get a single field in the databse from a SQL query.
 *
 * @param sql SQL statement to execute
 * @param field Field number to get, beggining by 0. Default: 0
 * 
 * @return The selected field of the first row in a select statement.
 */
function get_db_sql ($sql, $field = 0) {
	global $config;
	
	$result = mysql_query ($sql);
	if (! $result) {
		echo '<strong>Error:</strong> get_db_sql ("'.$sql.'") :'. mysql_error ().'<br />';
		return NULL;
	}
	if ($row = mysql_fetch_array ($result))
		return $row[$field];
	
	return NULL;
}

/**
 * Get all the result rows using an SQL statement.
 * 
 * @param $sql SQL statement to execute.
 *
 * @return A matrix with all the values returned from the SQL statement
 */
function get_db_all_rows_sqlfree ($sql) {
	global $config;
	$retval = array ();
	$result = mysql_query ($sql);
	
	if (! $result) {
		echo mysql_error ();
		return array();
	}
	while ($row = mysql_fetch_array ($result)) {
		array_push ($retval, $row);
	}
	
	return $retval;
}

/**
 * Get all the rows in a table of the database.
 * 
 * @param $table Database table name.
 *
 * @return A matrix with all the values in the table
 */
function get_db_all_rows_in_table ($table) {
	return get_db_all_rows_sqlfree ('SELECT * FROM '.$table);
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param $table Database table name.
 * @param $field Field of the table.
 * @param $condition Condition the field must have to be selected.
 *
 * @return A matrix with all the values in the table that matches the condition in the field
 */
function get_db_all_rows_field_filter ($table, $field, $condition) {
	if (is_int ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %d', $table, $field, $condition);
	} else if (is_float ($condition) || is_double ($condition)) {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = %f', $table, $field, $condition);
	} else {
		$sql = sprintf ('SELECT * FROM %s WHERE %s = "%s"', $table, $field, $condition);
	}
	
	return get_db_all_rows_sqlfree ($sql);
}

/**
 * Get all the rows in a table of the databes filtering from a field.
 * 
 * @param $table Database table name.
 * @param $field Field of the table.
 * @param $condition Condition the field must have to be selected.
 *
 * @return A matrix with all the values in the table that matches the condition in the field
 */
function get_db_all_fields_in_table ($table, $field) {
	return get_db_all_rows_sqlfree ('SELECT '.$field.' FROM '. $table);
}

/** 
 * Get the status of an alert assigned to an agent module.
 * 
 * @param id_agentmodule Id agent module to check.
 * 
 * @return True if there were alerts fired.
 */
function return_status_agent_module ($id_agentmodule = 0){
	$query1 = "SELECT estado FROM tagente_estado WHERE id_agente_modulo = " . $id_agentmodule; 
	$resq1 = mysql_query ($query1);
	if ($resq1 != 0) {
		$rowdup = mysql_fetch_array($resq1);
		if ($rowdup[0] == 100) {
			// We need to check if there are any alert on this item
			$query2 = "SELECT SUM(times_fired) FROM talerta_agente_modulo WHERE id_agente_modulo = " . $id_agentmodule;
			$resq2 = mysql_query($query2);
			if ($resq2 != 0) {
		                $rowdup2 = mysql_fetch_array ($resq2);
				if ($rowdup2[0] > 0){
					return false;
				}
			}
			// No alerts fired for this agent module
			return true;
		} elseif ($rowdup[0] == 0) // 0 is ok for estado field
			return true;
		return false;
	}

	return true;
}

/** 
 * Get the status of a layout.
 *
 * It gets all the data of the contained elements (including nested
 * layouts), and makes an AND operation to be sure that all the items
 * are OK. If any of them is down, then result is down (0)
 * 
 * @param id_layout Id of the layout
 * 
 * @return The status of the given layout.
 */
function return_status_layout ($id_layout = 0) {
	$temp_status = 0;
	$temp_total = 0;
	$sql = "SELECT * FROM tlayout_data WHERE id_layout = $id_layout";
	$res = mysql_query ($sql);
	while ($row = mysql_fetch_array ($res)) {
		$id_agentmodule = $row["id_agente_modulo"];
		$type = $row["type"];
		$parent_item = $row["parent_item"];
		$link_layout = $row["id_layout_linked"];
		if (($link_layout != 0) && ($id_agentmodule == 0)) {
			$temp_status += return_status_layout ($link_layout);
			$temp_total++;
		} else {
			$temp_status += return_status_agent_module ($id_agentmodule);
			$temp_total++;
		}
	}
	if ($temp_status == $temp_total)
		return 1;
	return 0;
}

/** 
 * Get the current value of an agent module.
 * 
 * @param id_agentmodule 
 * 
 * @return 
 */
function return_value_agent_module ($id_agentmodule) {
	return format_numeric (get_db_value ('datos', 'tagente_estado', 'id_agente_modulo', $id_agentmodule));
}

/** 
 * Get the X axis coordinate of a layout item
 * 
 * @param id_layoutdata Id of the layout to get.
 * 
 * @return The X axis coordinate value.
 */
function return_coordinate_X_layoutdata ($id_layoutdata) {
	return (float) get_db_value ('pos_x', 'tlayout_data', 'id', $id_layoutdata);
}

/** 
 * Get the X axis coordinate of a layout item
 * 
 * @param id_layoutdata Id of the layout to get.
 * 
 * @return The X axis coordinate value.
 */
function return_coordinate_y_layoutdata ($id_layoutdata){
	return (float) get_db_value ('pos_y', 'tlayout_data', 'id', $id_layoutdata);
}

/**
 * Get the previous data to the timestamp provided.
 *
 * It's useful to know the first value of a module in an interval, 
 * since it will be the last value in the table which has a timestamp 
 * before the beginning of the interval. All this calculation is due
 * to the data compression algorithm.
 *
 * @param $id_agent_module Agent module id
 * @param $utimestamp The timestamp to look backwards from and get the data.
 *
 * @return The row of tagente_datos of the last period. NULL if there were no data.
 */
function get_previous_data ($id_agent_module, $utimestamp) {
	$interval = get_module_interval ($id_agent_module);
	$sql = sprintf ('SELECT * FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp <= %d 
			AND utimestamp > %d
			ORDER BY utimestamp DESC LIMIT 1',
			$id_agent_module, $utimestamp, $utimestamp - $interval);
	
	return get_db_row_sql ($sql);
}

/** 
 * Get the average value of an agent module in a period of time.
 * 
 * @param id_agent_module Agent module id
 * @param period Period of time to check (in seconds)
 * @param date Top date to check the values. Default current time.
 * 
 * @return The average module value in the interval.
 */
function return_moduledata_avg_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT SUM(datos), COUNT(*) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC",
			$id_agent_module, $datelimit, $date);
	$values = get_db_row_sql ($sql);
	$sum = (float) $values[0];
	$total = (int) $values[1];
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return ($previous_data['datos'] + $sum) / ($total + 1);
	if ($total > 0)
		return $sum / $total;
	else
		return 0;
}

/** 
 * Get the maximum value of an agent module in a period of time.
 * 
 * @param id_agent_module Agent module id to get the maximum value.
 * @param period Period of time to check (in seconds)
 * @param date Top date to check the values. Default current time.
 * 
 * @return The maximum module value in the interval.
 */
function return_moduledata_max_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MAX(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d  AND utimestamp <= %d",
			$id_agent_module, $datelimit, $date);
	$max = (float) get_db_sql ($sql);
	
	/* Get also the previous report before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return max ($previous_data['datos'], $max);
	
	return max ($previous_data, $max);
}

/** 
 * Get the minimum value of an agent module in a period of time.
 * 
 * @param id_agent_module Agent module id to get the minimum value.
 * @param period Period of time to check (in seconds)
 * @param date Top date to check the values. Default current time.
 * 
 * @return The minimum module value of the module
 */
function return_moduledata_min_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period;
	
	$sql = sprintf ("SELECT MIN(datos) FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d",
			$id_agent_module, $datelimit, $date);
	$min = (float) get_db_sql ($sql);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data)
		return min ($previous_data['datos'], $min);
	return $min;
}

/** 
 * Get the sumatory of values of an agent module in a period of time.
 * 
 * @param id_agent_module Agent module id to get the sumatory.
 * @param period Period of time to check (in seconds)
 * @param date Top date to check the values. Default current time.
 * 
 * @return The sumatory of the module values in the interval.
 */
function return_moduledata_sum_value ($id_agent_module, $period, $date = 0) {
	if (! $date)
		$date = time ();
	$datelimit = $date - $period; // limit date
	$module_name = get_db_value ('nombre', 'ttipo_modulo', 'id_tipo', $agent_module['id_tipo_modulo']);
	
	if (is_module_data_string ($module_name)) {
		return lang_string ('wrong_module_type');
	}
	
	// Get the whole interval of data
	$sql = sprintf ('SELECT utimestamp, datos FROM tagente_datos 
			WHERE id_agente_modulo = %d 
			AND utimestamp > %d AND utimestamp <= %d 
			ORDER BY utimestamp ASC',
			$id_agent_module, $datelimit, $date);
	$datas = get_db_all_rows_sqlfree ($sql);
	
	/* Get also the previous data before the selected interval. */
	$previous_data = get_previous_data ($id_agent_module, $datelimit);
	if ($previous_data) {
		/* Add data to the beginning */
		array_unshift ($datas, $previous_data);
	}
	if (sizeof ($datas) == 0) {
		return 0;
	}
	
	$last_data = "";
	$total_badtime = 0;
	$module_interval = get_module_interval ($id_agent_module);
	$timestamp_begin = $datelimit + module_interval;
	$timestamp_end = 0;
	$sum = 0;
	$data_value = 0;
	foreach ($datas as $data) {
		$timestamp_end = $data["utimestamp"];
		$elapsed = $timestamp_end - $timestamp_begin;
		$times = intval ($elapsed / $module_interval);
			
		if (is_module_inc ($module_name)) {
			$data_value = $data['datos'] * $module_interval;
		} else {
			$data_value = $data['datos'];
		}
		
		$sum += $times * $data_value;
		$timestamp_begin = $data["utimestamp"];
	}

	/* The last value must be get from tagente_estado, but
	   it will count only if it's not older than date demanded
	*/
	$timestamp_end = get_db_value ('utimestamp', 'tagente_estado', 'id_agente_modulo', $id_agent_module);
	if ($timestamp_end <= $datelimit) {
		$elapsed = $timestamp_end - $timestamp_begin;
		$times = intval ($elapsed / $module_interval);
		$sum += $times * $previous_data;
	}
	
	return (float) $sum;
}

/** 
 * Get a translated string.
 * 
 * @param string String to translate
 * 
 * @return The translated string. If not defined, the same string will be returned
 */
function lang_string ($string) {
	global $config;
	require ($config["homedir"]."/include/languages/language_".$config["language"].".php");
	if (isset ($lang_label[$string]))
		return $lang_label[$string];
	return $string;
}

/** 
 * Get the numbers of servers up.
 *
 * This check assumes that server_keepalive should be at least 15 minutes.
 * 
 * @return The number of agents alive.
 */
function check_server_status () {
	$sql = "SELECT COUNT(id_server) FROM tserver WHERE status = 1 AND keepalive > NOW() - INTERVAL 15 MINUTE";
	$status = get_db_sql ($sql);
	// Set servers to down
	if ($status == 0){ 
		mysql_query ("UPDATE tserver SET status = 0");
	}
	return $status;
}

/** 
 * 
 * 
 * @param id_combined_alert 
 * 
 * @return 
 */
function show_alert_row_mini ($id_combined_alert) {
	$color=1;
	$sql = "SELECT talerta_agente_modulo.*, tcompound_alert.operation FROM talerta_agente_modulo, tcompound_alert WHERE tcompound_alert.id_aam = talerta_agente_modulo.id_aam AND tcompound_alert.id = ".$id_combined_alert;
	$result = mysql_query ($sql);
	echo "<table width=400 cellpadding=2 cellspacing=2 class='databox'>";
	echo "<th>".lang_string("Name");
	echo "<th>".lang_string("Oper");
	echo "<th>".lang_string("Tt");
	echo "<th>".lang_string("Firing");
	echo "<th>".lang_string("Time");
	echo "<th>".lang_string("Desc");
	echo "<th>".lang_string("Recovery");
	echo "<th>".lang_string("MinMax.Al");
	echo "<th>".lang_string("Days");
	echo "<th>".lang_string("Fired");
	while ($row2 = mysql_fetch_array ($result)) {

		if ($color == 1) {
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr>";    

		if ($row2["disable"] == 1){
			$tdcolor = "datos3";
		}
		echo "<td class=$tdcolor>".get_db_sql("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo =".$row2["id_agente_modulo"]);
		echo "<td class=$tdcolor>".$row2["operation"];

		echo "<td class='$tdcolor'>".human_time_description($row2["time_threshold"]);

		if ($row2["dis_min"]!=0){
			$mytempdata = fmod($row2["dis_min"], 1);
		if ($mytempdata == 0)
			$mymin = intval($row2["dis_min"]);
		else
			$mymin = $row2["dis_min"];
			$mymin = format_for_graph($mymin );
		} else {
			$mymin = 0;
		}

		if ($row2["dis_max"]!=0){
			$mytempdata = fmod($row2["dis_max"], 1);
		if ($mytempdata == 0)
			$mymax = intval($row2["dis_max"]);
		else
			$mymax = $row2["dis_max"];
			$mymax =  format_for_graph($mymax );
		} else {
			$mymax = 0;
		}

		if (($mymin == 0) && ($mymax == 0)){
			$mymin = lang_string ("N/A");
			$mymax = $mymin;
		}

		// We have alert text ?
		if ($row2["alert_text"]!= "") {
			echo "<td class='$tdcolor'>".lang_string ('text')."</td>";
		} else {
			echo "<td class='$tdcolor'>".$mymin."/".$mymax."</td>";
		}

		// Alert times
		echo "<td class='$tdcolor'>";
		echo get_alert_times ($row2);

		// Description
		echo "</td><td class='$tdcolor'>".substr($row2["descripcion"],0,20);

		// Has recovery notify activated ?
		if ($row2["recovery_notify"] > 0)
			$recovery_notify = lang_string ("Yes");
		else
			$recovery_notify = lang_string ("No");

		echo "</td><td class='$tdcolor'>".$recovery_notify;

		// calculare firing conditions
		if ($row2["alert_text"] != ""){
			$firing_cond = lang_string("text")."(".substr($row2["alert_text"],0,8).")";
		} else {
			$firing_cond = $row2["min_alerts"]." / ".$row2["max_alerts"];
		}
		echo "</td><td class='$tdcolor'>".$firing_cond;

		// calculate days
		$firing_days = get_alert_days ( $row2 );
		echo "</td><td class='$tdcolor'>".$firing_days;

		// Fired ?
		if ($row2["times_fired"]>0)
			echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_red.png' title='".lang_string("fired")."'></td>";
		else
			echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_green.png' title='".lang_string ('not_fired')."'></td>";

	}
	echo "</table>";
}

/** 
 * 
 * 
 * @param filter 
 * @param limit 
 * @param width 
 * 
 * @return 
 */
function smal_event_table ($filter = "", $limit = 10, $width = 440) {
	global $config;
	global $lang_label;

	$sql = "SELECT * FROM tevento $filter ORDER BY timestamp DESC LIMIT $limit";
	echo "<table cellpadding='4' cellspacing='4' width='$width' border=0 class='databox'>";
	echo "<tr>";
	echo "<th colspan=6>".lang_string ("Latest events");
	echo "<tr>";
	echo "<th class='datos3 f9'>".lang_string ("St")."</th>";
	echo "<th class='datos3 f9'>".lang_string ("Type")."</th>";
	echo "<th class='datos3 f9'>".lang_string ('event_name')."</th>";
	echo "<th class='datos3 f9'>".lang_string ('agent_name')."</th>";
	echo "<th class='datos3 f9'>".lang_string ('id_user')."</th>";
	echo "<th class='datos3 f9'>".lang_string ('timestamp')."</th>";
	$result = mysql_query ($sql);
	while ($event = mysql_fetch_array ($result)) {
		$id_grupo = $event["id_grupo"];
		if (! give_acl ($config["id_user"], $id_grupo, "AR")) {
			continue;
		}
		
		/* Only incident read access to view data ! */
		switch ($event["criticity"]) {
		case 0: 
			$tdclass = "datos_blue";
			break;
		case 1: 
			$tdclass = "datos_grey";
			break;
		case 2: 
			$tdclass = "datos_green";
			break;
		case 3: 
			$tdclass = "datos_yellow";
			break;
		case 4: 
			$tdclass = "datos_red";
			break;
		default:
			$tdclass = "datos_grey";
		}
		
		$criticity_label = return_priority ($event["criticity"]);
		/* Colored box */
		echo "<tr><td class='$tdclass' title='$criticity_label' align='center'>";
		if ($event["estado"] == 0)
			echo "<img src='images/pixel_red.png' width=20 height=20>";
		else
			echo "<img src='images/pixel_green.png' width=20 height=20>";
	
		/* Event type */
		echo "<td class='".$tdclass."' title='".$event["event_type"]."'>";
		switch ($event["event_type"]) {
		case "unknown": 
			echo "<img src='images/err.png'>";
			break;
		case "alert_recovered": 
			echo "<img src='images/error.png'>";
			break;
		case "alert_manual_validation": 
			echo "<img src='images/eye.png'>";
			break;
		case "monitor_up":
			echo "<img src='images/lightbulb.png'>";
			break;
		case "monitor_down":
			echo "<img src='images/lightbulb_off.png'>";
			break;
		case "alert_fired":
			echo "<img src='images/bell.png'>";
			break;
		case "system";
			echo "<img src='images/cog.png'>";
			break;
		case "recon_host_detected";
			echo "<img src='images/network.png'>";
			break;
		}
	
		// Event description
		echo "<td class='".$tdclass."f9' title='".$event["evento"]."'>";
		echo substr($event["evento"],0,45);
		if (strlen($event["evento"]) > 45)
			echo "..";
		if ($event["id_agente"] > 0) {
			// Agent name
			$agent_name = dame_nombre_agente ($event["id_agente"]);
			echo "<td class='".$tdclass."f9' title='$agent_name'><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$event["id_agente"]."'><b>";
			echo substr ($agent_name, 0, 14);
			if (strlen ($agent_name) > 14)
				echo "..";
			echo "</b></a>";
		
			// for System or SNMP generated alerts
		} else { 
			if ($event["event_type"] == "system") {
				echo "<td class='$tdclass'>".lang_string ("System");
			} else {
				echo "<td class='$tdclass'>".lang_string ("alert")."SNMP";
			}
		}
	
		// User who validated event
		echo "<td class='$tdclass'>";
		if ($event["estado"] != 0)
			echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$event["id_usuario"]."'>".substr($event["id_usuario"],0,8)."<a href='#' class='tip'> <span>".dame_nombre_real ($event["id_usuario"])."</span></a></a>";
	
		// Timestamp
		echo "<td class='".$tdclass."f9' title='".$event["timestamp"]."'>";
		echo human_time_comparation ($event["timestamp"]);
	}
	echo "</table>";
}


/** 
 * Get statistical information for a given server
 * 
 * @param id_server 
 *
 * @return : Serverifo array with following keys:
 	type 			- Type of server (descriptive)
 	modules_total 	- Total of modules for this kind of servers
 	modules			- Modules running on this server
	module_lag		- Nº of modules of time
	lag				- Lag time in sec
*/
function server_status ($id_server) {
	$server = get_db_row_sql ( "SELECT * FROM tserver WHERE id_server = $id_server" );
	$serverinfo = array();
	
	if ($server["network_server"] == 1)
		$serverinfo["type"]="network";
	elseif ($server["data_server"] == 1)
		$serverinfo["type"]="data";
	elseif ($server["plugin_server"] == 1)
		$serverinfo["type"]="plugin";
	elseif ($server["wmi_server"] == 1)
		$serverinfo["type"]="wmi";
	elseif ($server["recon_server"] == 1)
		$serverinfo["type"]="recon";
	elseif ($server["snmp_server"] == 1)
		$serverinfo["type"]="snmp";
	elseif ($server["prediction_server"] == 1)
		$serverinfo["type"]="prediction";

	
	// Get type of modules that runs this server 
	$moduletype = get_db_sql ("SELECT MAX(id_modulo) FROM tagente_estado, tagente_modulo  WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.running_by = $id_server ORDER BY tagente_modulo.id_agente_modulo ");
	
	if ($moduletype != ""){

		$serverinfo["modules_total"] = get_db_sql ("SELECT COUNT(id_agente_modulo) FROM tagente_modulo WHERE tagente_modulo.disabled = 0 AND tagente_modulo.id_modulo = $moduletype");

		$serverinfo["modules"] = get_db_sql ("SELECT COUNT(tagente_estado.running_by) FROM tagente_estado, tagente_modulo WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND  tagente_modulo.disabled = 0 AND tagente_modulo.id_modulo = $moduletype AND tagente_estado.running_by = $id_server");

		$serverinfo["module_lag"] = get_db_sql ("SELECT COUNT(tagente_estado.last_execution_try) FROM tagente_estado, tagente_modulo, tagente WHERE tagente_estado.last_execution_try > 0 AND tagente_estado.running_by=$id_server  AND  tagente_modulo.id_agente = tagente.id_agente AND tagente.disabled = 0 AND tagente_modulo.id_modulo = $moduletype AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND (UNIX_TIMESTAMP() - tagente_estado.last_execution_try - tagente_estado.current_interval < 1200) ");

		// Lag over 1200 secons is not lag, is module without contacting data in several time.or with a 
		// 1200 sec is 20 min
		$serverinfo["lag"] = get_db_sql ("SELECT MAX(tagente_estado.last_execution_try - tagente_estado.current_interval) FROM tagente_estado, tagente_modulo, tagente WHERE tagente_estado.last_execution_try > 0 AND tagente_estado.running_by=$id_server  AND  tagente_modulo.id_agente = tagente.id_agente AND tagente.disabled = 0 AND tagente_modulo.id_modulo = $moduletype AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND (UNIX_TIMESTAMP() - tagente_estado.last_execution_try - tagente_estado.current_interval < 1200) ");

		if ($serverinfo["lag"] == "")
			$serverinfo["lag"] = 0;
		else
			$serverinfo["lag"] = $serverinfo["lag"] ;
	} else {
		$serverinfo["modules_total"] = 0;
		$serverinfo["modules"] = 0;
		$serverinfo["module_lag"] = 0;
		$serverinfo["lag"] = 0;
	}

	$nowtime = time();		
	if ($serverinfo["lag"] != 0){
		$serverinfo["lag"] = $nowtime - $serverinfo["lag"];
	}
	
	return $serverinfo;
}
?>
