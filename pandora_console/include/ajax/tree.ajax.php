<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Only accesible by ajax
if (is_ajax ()) {
	global $config;
	
	// Login check
	check_login ();
	
	require_once($config['homedir'] . "/include/class/Tree.class.php");
	enterprise_include_once("include/class/Tree.class.php");
	require_once($config['homedir'] . "/include/functions_reporting.php");
	require_once($config['homedir'] . "/include/functions_os.php");
	
	$getChildren = (bool) get_parameter('getChildren', 0);
	$getGroupStatus = (bool) get_parameter('getGroupStatus', 0);
	$get_detail = (bool) get_parameter('getDetail');
	
	if ($getChildren) {
		$type = get_parameter('type', 'group');
		$filter = get_parameter('filter', array('searchAgent' => '', 'statusAgent' => AGENT_STATUS_ALL));
		$id = get_parameter('id', 0);
		$childrenMethod = get_parameter('childrenMethod', 'on_demand');
		$countModuleStatusMethod = get_parameter('countModuleStatusMethod', 'on_demand');
		$countAgentStatusMethod = get_parameter('countAgentStatusMethod', 'live');
		
		if (class_exists('TreeEnterprise')) {
			$tree = new TreeEnterprise($type,
					$id,
					$childrenMethod,
					$countModuleStatusMethod,
					$countAgentStatusMethod
				);
		}
		else {
			$tree = new Tree($type,
					$id,
					$childrenMethod,
					$countModuleStatusMethod,
					$countAgentStatusMethod
				);
		}
		
		$tree->setFilter(array(
				'statusAgent' => $filter['statusAgent'],
				'searchAgent' => $filter['searchAgent'])
			);
		echo json_encode(array('success' => 1, 'tree' => $tree->getArray()));
		return;
	}
	
	if ($getGroupStatus) {
		$id = (int)get_parameter('id', 0);
		$type = get_parameter('type', 'group');
		$id = 0;
		
		$status = array();
		
		switch ($type) {
			case 'group':
				$data = reporting_get_group_stats($id);
				
				$status['unknown'] = $data['agents_unknown'];
				$status['critical'] = $data['agent_critical'];
				$status['warning'] = $data['agent_warning'];
				$status['not_init'] = $data['agent_not_init'];
				$status['ok'] = $data['agent_ok'];
				$status['total'] = $data['total_agents'];
				$status['status'] = $data['status'];
				$status['alert_fired'] = $data['alert_fired'];
				
				echo json_encode($status);
				break;
		}
		return;
	}
	
	if ($get_detail) {
		require_once($config['homedir']."/include/functions_treeview.php");
		
		// Clean the output
		ob_clean();
		
		$id = (int) get_parameter('id');
		$type = (string) get_parameter('type');
		
		$server = array();
		if (defined ('METACONSOLE')) {
			$server_name = (string) get_parameter('server');
			$server = metaconsole_get_connection($server_name);
			metaconsole_connect($server);
		}
		
		switch ($type) {
			case 'agent':
				treeview_printTable($id, $server);
				break;
			case 'module':
				treeview_printModuleTable($id, $server);
				break;
			case 'alert':
				treeview_printAlertsTable($id, $server);
				break;
			default:
				// Nothing
				break;
		}
		
		if (!empty($server) && defined ('METACONSOLE')) {
			metaconsole_restore_db();
		}
		
		return;
	}
	
	return;
}
?>