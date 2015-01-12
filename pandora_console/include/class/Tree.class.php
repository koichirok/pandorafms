<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

class Tree {
	protected $type = null;
	protected $tree = array();
	protected $filter = array();
	protected $root = null;
	protected $childrenMethod = "on_demand";
	protected $countModuleStatusMethod = "on_demand";
	protected $countAgentStatusMethod = "on_demand";

	protected $userGroups;
	
	public function  __construct($type, $root = null,
		$childrenMethod = "on_demand",
		$countModuleStatusMethod = "on_demand",
		$countAgentStatusMethod = "on_demand") {
		
		$this->type = $type;
		$this->root = $root;
		$this->childrenMethod = $childrenMethod;
		$this->countModuleStatusMethod = $countModuleStatusMethod;
		$this->countAgentStatusMethod = $countAgentStatusMethod;
		
		$userGroups = users_get_groups();

		if (empty($userGroups))
			$this->userGroups = false;
		else
			$this->userGroups = $userGroups;

		global $config;
		include_once($config['homedir']."/include/functions_servers.php");
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function setFilter($filter) {
		$this->filter = $filter;
	}

	protected function processModule (&$module) {
		global $config;

		$module['type'] = 'module';
		$module['id'] = (int) $module['id_agente_modulo'];
		$module['name'] = $module['nombre'];
		$module['id_module_type'] = (int) $module['id_tipo_modulo'];
		$module['server_type'] = (int) $module['id_modulo'];
		// $module['icon'] = modules_get_type_icon($module['id_tipo_modulo']);

		if (!isset($module['status']))
			$module['status'] = modules_get_status($module['id']);
		if (!isset($module['value']))
			$module['value'] = modules_get_last_value($module['id']);

		// Status
		switch ($module['status']) {
			case AGENT_MODULE_STATUS_CRITICAL_ALERT:
				$module['alert'] = 1;
			case AGENT_MODULE_STATUS_CRITICAL_BAD:
				$statusType = STATUS_MODULE_CRITICAL_BALL;
				$statusTitle = __('CRITICAL');
				$module['statusText'] = "critical";
				break;
			case AGENT_MODULE_STATUS_WARNING_ALERT:
				$module['alert'] = 1;
			case AGENT_MODULE_STATUS_WARNING:
				$statusType = STATUS_MODULE_WARNING_BALL;
				$statusTitle = __('WARNING');
				$module['statusText'] = "warning";
				break;
			case AGENT_MODULE_STATUS_UNKNOWN:
				$statusType = STATUS_MODULE_UNKNOWN_BALL;
				$statusTitle = __('UNKNOWN');
				$module['statusText'] = "unknown";
				break;
			case AGENT_MODULE_STATUS_NO_DATA:
			case AGENT_MODULE_STATUS_NOT_INIT:
				$statusType = STATUS_MODULE_NO_DATA_BALL;
				$statusTitle = __('NO DATA');
				$module['statusText'] = "not_init";
				break;
			case AGENT_MODULE_STATUS_NORMAL_ALERT:
				$module['alert'] = 1;
			case AGENT_MODULE_STATUS_NORMAL:
			default:
				$statusType = STATUS_MODULE_OK_BALL;
				$statusTitle = __('NORMAL');
				$module['statusText'] = "ok";
				break;
		}

		if ($statusType !== STATUS_MODULE_UNKNOWN_BALL
				&& $statusType !== STATUS_MODULE_NO_DATA_BALL) {
			if (is_numeric($module["value"])) {
				$statusTitle .= " : " . format_for_graph($module["value"]);
			}
			else {
				$statusTitle .= " : " . substr(io_safe_output($module["value"]),0,42);
			}
		}
		
		$module['statusImageHTML'] = ui_print_status_image($statusType, $statusTitle, true);

		// HTML of the server type image
		$module['serverTypeHTML'] = servers_show_type($module['server_type']);

		// Link to the Module graph
		$graphType = return_graphtype($module['id']);
		$winHandle = dechex(crc32($module['id'] . $module['name']));
		
		$moduleGraphURL = $config['homeurl'] .
			"/operation/agentes/stat_win.php?" .
			"type=$graphType&" .
			"period=86400&" .
			"id=" . $module['id'] . "&" .
			"label=" . rawurlencode(urlencode(base64_encode($module['name']))) . "&" .
			"refresh=600";

		$module['moduleGraph'] = array(
				'url' => $moduleGraphURL,
				'handle' => $winHandle
			);
	}

	protected function processModules ($modules_aux, &$modules) {
		$counters = false;

		if (!empty($modules_aux)) {
			$counters = array(
					'critical' => 0,
					'warning' => 0,
					'ok' => 0,
					'not_init' => 0,
					'unknown' => 0,
					'alerts' => 0
				);

			foreach ($modules_aux as $module) {
				$this->processModule($module);
				$modules[] = $module;

				if (isset($counters[$module['statusText']]))
					$counters[$module['statusText']]++;
				if ($module['alert'])
					$counters['alerts']++;
			}
		}
		return $counters;
	}

	protected function getModules ($parent = 0, $filter = array()) {
		$modules = array();

		$modules_aux = agents_get_modules($parent,
			array('id_agente_modulo', 'nombre', 'id_tipo_modulo', 'id_modulo'), $filter);
		
		if (empty($modules_aux))
			$modules_aux = array();
		
		// Process the modules
		$this->processModules($modules_aux, $modules);

		return $modules;
	}
	
	protected function processAgent (&$agent, $modulesFilter = array(), $searchChildren = true) {
		$agent['type'] = 'agent';
		$agent['id'] = (int) $agent['id_agente'];
		$agent['name'] = $agent['nombre'];
		
		// Counters
		$agent['counters'] = array();

		if (isset($agent['unknown_count']))
			$agent['counters']['unknown'] = $agent['unknown_count'];
		else
			$agent['counters']['unknown'] = agents_monitor_unknown($agent['id']);

		if (isset($agent['critical_count']))
			$agent['counters']['critical'] = $agent['critical_count'];
		else
			$agent['counters']['critical'] = agents_monitor_critical($agent['id']);

		if (isset($agent['warning_count']))
			$agent['counters']['warning'] = $agent['warning_count'];
		else
			$agent['counters']['warning'] = agents_monitor_warning($agent['id']);

		if (isset($agent['notinit_count']))
			$agent['counters']['not_init'] = $agent['notinit_count'];
		else
			$agent['counters']['not_init'] = agents_monitor_notinit($agent['id']);

		if (isset($agent['normal_count']))
			$agent['counters']['ok'] = $agent['normal_count'];
		else
			$agent['counters']['ok'] = agents_monitor_ok($agent['id']);

		if (isset($agent['total_count']))
			$agent['counters']['total'] = $agent['total_count'];
		else
			$agent['counters']['total'] = agents_monitor_total($agent['id']);

		if (isset($agent['fired_count']))
			$agent['counters']['alerts'] = $agent['fired_count'];
		else
			$agent['counters']['alerts'] = agents_get_alerts_fired($agent['id']);

		// Status image
		$agent['statusImageHTML'] = agents_tree_view_status_img_ball(
				$agent['counters']['critical'],
				$agent['counters']['warning'],
				$agent['counters']['unknown'],
				$agent['counters']['total'],
				$agent['counters']['not_init']);

		// Alerts fired image
		$agent["alertImageHTML"] = agents_tree_view_alert_img_ball($agent['counters']['alerts']);

		// Status
		$agent['statusRaw'] = agents_get_status($agent['id']);
		switch ($agent['statusRaw']) {
			case AGENT_STATUS_NORMAL:
				$agent['status'] = "ok";
				break;
			case AGENT_STATUS_WARNING:
				$agent['status'] = "warning";
				break;
			case AGENT_STATUS_CRITICAL:
				$agent['status'] = "critical";
				break;
			case AGENT_STATUS_UNKNOWN:
				$agent['status'] = "unknown";
				break;
			case AGENT_STATUS_NOT_INIT:
				$agent['status'] = "not_init";
				break;
			default:
				$agent['status'] = "none";
				break;
		}
		
		// Children
		$agent['children'] = array();
		if ($agent['counters']['total'] > 0) {
			switch ($this->childrenMethod) {
				case 'on_demand':
					$agent['searchChildren'] = 1;
					break;
				case 'live':
					$agent['searchChildren'] = 0;

					if ($searchChildren)
						$agent['children'] = $this->getModules($agent['id'], $modulesFilter);
					break;
			}
		}
		else {
			switch ($this->childrenMethod) {
				case 'on_demand':
					$agent['searchChildren'] = 0;
					break;
				case 'live':
					$agent['searchChildren'] = 0;
					break;
			}
		}
	}

	protected function processAgents (&$agents, $modulesFilter = array()) {
		if (!empty($agents)) {
			foreach ($agents as $iterator => $agent) {
				$this->processAgent($agents[$iterator], $modulesFilter);
			}
		}
	}

	protected function getAgents ($parent = 0, $parentType = '') {
		switch ($parentType) {
			case 'group':
				// ACL Groups
				if (isset($this->userGroups) && $this->userGroups === false)
					return array();

				if (!empty($this->userGroups) && !empty($parent)) {
					if (!isset($this->userGroups[$parent]))
						return array();
				}
				$filter = array();
				$filter['id_grupo'] = $parent;
				if (isset($this->filter['status']) && $this->filter['status'] != -1)
					$filter['status'] = $this->filter['status'];
				if (!empty($this->filter['searchAgent']))
					$filter['nombre'] = "%" . $this->filter['searchAgent'] . "%";
				
				$agents = agents_get_agents($filter, array('id_agente', 'nombre'));
				if (empty($agents)) {
					$agents = array();
				}
				break;
			default:
				return array();
				break;
		}
		
		$this->processAgents($agents);
		
		return $agents;
	}
	
	protected function getGroupsRecursive($parent, $limit = null, $get_agents = true) {
		$filter = array();
		$filter['parent'] = $parent;
		
		// if (!empty($this->filter['search'])) {
		// 	$filter['nombre'] = "%" . $this->filter['search'] . "%";
		// }
		// ACL groups
		if (isset($this->userGroups) && $this->userGroups === false)
			return array();

		if (!empty($this->userGroups))
			$filter['id_grupo'] = array_keys($this->userGroups);
		
		// First filter by name and father
		$groups = db_get_all_rows_filter('tgrupo', $filter, array('id_grupo', 'nombre', 'icon'));
		if (empty($groups))
			$groups = array();
		
		// Filter by status
		// $filter_status = AGENT_STATUS_ALL;
		// if (!empty($this->filter['status'])) {
		// 	$filter_status = $this->filter['status'];
		// }
		
		foreach ($groups as $iterator => $group) {
			// Counters
			$group_stats = reporting_get_group_stats($group['id_grupo']);

			$groups[$iterator]['counters'] = array();
			if (!empty($group_stats)) {
				$groups[$iterator]['counters']['unknown'] = $group_stats['agents_unknown'];
				$groups[$iterator]['counters']['critical'] = $group_stats['agent_critical'];
				$groups[$iterator]['counters']['warning'] = $group_stats['agent_warning'];
				$groups[$iterator]['counters']['not_init'] = $group_stats['agent_not_init'];
				$groups[$iterator]['counters']['ok'] = $group_stats['agent_ok'];
				$groups[$iterator]['counters']['total'] = $group_stats['total_agents'];
			}

			$groups[$iterator]['status'] = $group_stats['status'];
			$groups[$iterator]['icon'] = !empty($group['icon']) ? $group['icon'] . '.png' : 'without_group.png';
			
			// // Filter by status
			// if ($filter_status != AGENT_STATUS_ALL) {
			// 	$remove_group = true;
			// 	switch ($filter_status) {
			// 		case AGENT_STATUS_NORMAL:
			// 			if ($groups[$iterator]['status'] === "ok")
			// 				$remove_group = false;
			// 			break;
			// 		case AGENT_STATUS_WARNING:
			// 			if ($groups[$iterator]['status'] === "warning")
			// 				$remove_group = false;
			// 			break;
			// 		case AGENT_STATUS_CRITICAL:
			// 			if ($groups[$iterator]['status'] === "critical")
			// 				$remove_group = false;
			// 			break;
			// 		case AGENT_STATUS_UNKNOWN:
			// 			if ($groups[$iterator]['status'] === "unknown")
			// 				$remove_group = false;
			// 			break;
			// 		case AGENT_STATUS_NOT_INIT:
			// 			if ($groups[$iterator]['status'] === "not_init")
			// 				$remove_group = false;
			// 			break;
			// 	}
				
			// 	if ($remove_group) {
			// 		unset($groups[$iterator]);
			// 		continue;
			// 	}
			// }
			
			if (is_null($limit)) {
				$groups[$iterator]['children'] =
					$this->getGroupsRecursive($group['id_grupo']);
			}
			else if ($limit >= 1) {
				$groups[$iterator]['children'] =
					$this->getGroupsRecursive($group['id_grupo'], ($limit - 1));
			}

			switch ($this->countAgentStatusMethod) {
				case 'on_demand':
					$groups[$iterator]['searchCounters'] = 1;
					break;
				case 'live':
					$groups[$iterator]['searchCounters'] = 0;
					break;
			}
			switch ($this->childrenMethod) {
				case 'on_demand':
					// if (!empty($groups[$iterator]['children'])) {
					// 	$groups[$iterator]['searchChildren'] = 1;
					// }
					// else {
					// 	$groups[$iterator]['searchChildren'] = 0;
					// }
					$groups[$iterator]['searchChildren'] = 0;
					break;
				case 'live':
					$groups[$iterator]['searchChildren'] = 0;
					break;
			}
			
			$groups[$iterator]['type'] = 'group';
			$groups[$iterator]['name'] = $groups[$iterator]['nombre'];
			$groups[$iterator]['id'] = $groups[$iterator]['id_grupo'];
		}
		
		if (!empty($parent) && $get_agents) {
			$agents = $this->getAgents($parent, 'group');

			if (!empty($agents))
				$groups = array_merge($groups, $agents);
		}

		return $groups;
	}
	
	public function getData() {
		switch ($this->type) {
			case 'os':
				$this->getDataOS();
				break;
			case 'group':
				$this->getDataGroup();
				break;
			case 'module_group':
				$this->getDataModuleGroup();
				break;
			case 'module':
				$this->getDataModules();
				break;
			case 'tag':
				$this->getDataTag();
				break;
			default:
				$this->getDataExtended();
		}
	}

	protected function getDataExtended () {
		// Override this method to add new types
	}
	
	private function getDataGroup() {
		// Get the parent
		if (empty($this->root))
			$parent = 0;
		else
			$parent = $this->root;

		$groups = $this->getGroupsRecursive($parent);

		if (empty($groups))
			$groups = array();

		$this->tree = $groups;
	}

	private function getDataModules() {
		// ACL Group
		if (isset($this->userGroups) && $this->userGroups === false)
			return array();

		$group_acl =  "";
		if (!empty($this->userGroups)) {
			$user_groups_str = implode(",", array_keys($this->userGroups));
			$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
		}

		// Agent name filter
		$agent_search = "";
		if (!empty($this->filter['searchAgent'])) {
			$agent_search = " AND ta.nombre LIKE '%".$this->filter['searchAgent']."%' ";
		}
		
		// Module name filter
		$module_search = "";
		if (!empty($this->filter['searchModule'])) {
			$module_search = " AND tam.nombre LIKE '%".$this->filter['searchModule']."%' ";
		}
		
		$sql = "SELECT tam.id_agente_modulo, tam.nombre AS module_name,
					tam.id_tipo_modulo, tam.id_modulo,
					ta.id_agente, ta.nombre AS agent_name, ta.fired_count,
					ta.normal_count, ta.warning_count, ta.critical_count,
					ta.unknown_count, ta.notinit_count, ta.total_count,
					tae.estado, tae.datos
				FROM tagente_modulo AS tam
				INNER JOIN tagente AS ta
					ON ta.id_agente = tam.id_agente
						AND ta.disabled = 0
				INNER JOIN tagente_estado AS tae
					ON tae.id_agente_modulo = tam.id_agente_modulo
				WHERE tam.disabled = 0
					$agent_search
					$module_search
					$group_acl
				ORDER BY tam.nombre ASC, ta.nombre ASC";
		$data = db_process_sql($sql);

		if (empty($data)) {
			$data = array();
		}

		$modules = array();
		$actual_module_root = array(
				'name' => '',
				'children' => array(),
				'counters' => array()
			);
		foreach ($data as $key => $value) {
			$agent = array();
			$agent['id_agente'] = (int) $value['id_agente'];
			$agent['nombre'] = $value['agent_name'];

			$this->processAgent(&$agent, array(), false);

			$module = array();
			$module['id_agente_modulo'] = (int) $value['id_agente_modulo'];
			$module['nombre'] = $value['module_name'];
			$module['id_tipo_modulo'] = (int) $value['id_tipo_modulo'];
			$module['server_type'] = (int) $value['id_modulo'];
			$module['status'] = (int) $value['estado'];
			$module['value'] = $value['data'];

			$this->processModule($module);

			$agent['children'] = array($module);

			if ($actual_module_root['name'] == $module['name']) {
				$actual_module_root['children'][] = $agent;

				// Increase counters
				$actual_module_root['counters']['total']++;

				if (isset($actual_module_root['counters'][$agent['status']]))
					$actual_module_root['counters'][$agent['status']]++;
			}
			else {
				if (!empty($actual_module_root['name']))
					$modules[] = $actual_module_root;

				$actual_module_root = array();
				$actual_module_root['name'] = $module['name'];
				$actual_module_root['children'] = array($agent);

				// Initialize counters
				$actual_module_root['counters'] = array();
				$actual_module_root['counters']['total'] = 0;
				$actual_module_root['counters']['alerts'] = 0;
				$actual_module_root['counters']['critical'] = 0;
				$actual_module_root['counters']['warning'] = 0;
				$actual_module_root['counters']['unknown'] = 0;
				$actual_module_root['counters']['not_init'] = 0;
				$actual_module_root['counters']['ok'] = 0;

				// Increase counters
				$actual_module_root['counters']['total']++;

				if (isset($actual_module_root['counters'][$agent['status']]))
					$actual_module_root['counters'][$agent['status']]++;
			}
		}
		if (!empty($actual_module_root['name'])) {
			$modules[] = $actual_module_root;
		}

		$this->tree = $modules;
	}

	private function getDataModuleGroup() {
		// ACL Group
		if (isset($this->userGroups) && $this->userGroups === false)
			return array();

		$group_acl =  "";
		if (!empty($this->userGroups)) {
			$user_groups_str = implode(",", array_keys($this->userGroups));
			$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
		}

		// Agent name filter
		$agent_search = "";
		if (!empty($this->filter['searchAgent'])) {
			$agent_search = " AND ta.nombre LIKE '%".$this->filter['searchAgent']."%' ";
		}
		
		// Module name filter
		$module_search = "";
		if (!empty($this->filter['searchModule'])) {
			$module_search = " AND tam.nombre LIKE '%".$this->filter['searchModule']."%' ";
		}
		
		$sql = "SELECT tam.id_agente_modulo, tam.nombre AS module_name,
					tam.id_tipo_modulo, tam.id_modulo,
					ta.id_agente, ta.nombre AS agent_name, ta.fired_count,
					ta.normal_count, ta.warning_count, ta.critical_count,
					ta.unknown_count, ta.notinit_count, ta.total_count,
					tmg.id_mg, tmg.name AS module_group_name,
					tae.estado, tae.datos
				FROM tagente_modulo AS tam
				INNER JOIN tagente AS ta
					ON ta.id_agente = tam.id_agente
						AND ta.disabled = 0
				INNER JOIN tagente_estado AS tae
					ON tae.id_agente_modulo = tam.id_agente_modulo
				LEFT JOIN tmodule_group AS tmg
					ON tmg.id_mg = tam.id_module_group
				WHERE tam.disabled = 0
					$agent_search
					$module_search
					$group_acl
				ORDER BY tmg.name ASC, tmg.id_mg ASC, ta.nombre ASC, tam.nombre ASC";
		$data = db_process_sql($sql);

		if (empty($data)) {
			$data = array();
		}

		$nodes = array();
		$actual_module_group_root = array(
				'id' => -1,
				'name' => '',
				'children' => array(),
				'counters' => array()
			);
		$actual_agent = array();
		foreach ($data as $key => $value) {

			// Module
			$module = array();
			$module['id_agente_modulo'] = (int) $value['id_agente_modulo'];
			$module['nombre'] = $value['module_name'];
			$module['id_tipo_modulo'] = (int) $value['id_tipo_modulo'];
			$module['id_module_group'] = (int) $value['id_mg'];
			$module['server_type'] = (int) $value['id_modulo'];
			$module['status'] = (int) $value['estado'];
			$module['value'] = $value['data'];

			$this->processModule($module);

			// Module group
			if ($actual_module_group_root['id'] === $module['id_module_group']) {
				// Agent
				if (empty($actual_agent) || $actual_agent['id'] !== (int)$value['id_agente']) {
					// Add the last agent to the agent module
					if (!empty($actual_agent))
						$actual_module_group_root['children'][] = $actual_agent;

					// Create the new agent
					$actual_agent = array();
					$actual_agent['id_agente'] = (int) $value['id_agente'];
					$actual_agent['nombre'] = $value['agent_name'];
					$actual_agent['children'] = array();

					$this->processAgent(&$actual_agent, array(), false);

					// Add the module to the agent
					$actual_agent['children'][] = $module;

					// Increase counters
					$actual_module_group_root['counters']['total']++;

					if (isset($actual_module_group_root['counters'][$actual_agent['status']]))
						$actual_module_group_root['counters'][$actual_agent['status']]++;
				}
				else {
					$actual_agent['children'][] = $module;
				}
			}
			else {
				// The first iteration doesn't enter here
				if ($actual_module_group_root['id'] !== -1) {
					// Add the agent to the module group
					$actual_module_group_root['children'][] = $actual_agent;
					// Add the module group to the branch
					$nodes[] = $actual_module_group_root;
				}

				// Create the new agent
				$actual_agent = array();
				$actual_agent['id_agente'] = (int) $value['id_agente'];
				$actual_agent['nombre'] = $value['agent_name'];
				$actual_agent['children'] = array();

				$this->processAgent(&$actual_agent, array(), false);

				// Add the module to the agent
				$actual_agent['children'][] = $module;

				// Create new module group
				$actual_module_group_root = array();
				$actual_module_group_root['id'] = $module['id_module_group'];
				$actual_module_group_root['type'] = $this->type;

				if (!empty($value['module_group_name'])) {
					$actual_module_group_root['name'] = $value['module_group_name'];
				}
				else {
					$actual_module_group_root['name'] = __('Not assigned');
				}
				
				// Initialize counters
				$actual_module_group_root['counters'] = array();
				$actual_module_group_root['counters']['total'] = 0;
				$actual_module_group_root['counters']['alerts'] = 0;
				$actual_module_group_root['counters']['critical'] = 0;
				$actual_module_group_root['counters']['warning'] = 0;
				$actual_module_group_root['counters']['unknown'] = 0;
				$actual_module_group_root['counters']['not_init'] = 0;
				$actual_module_group_root['counters']['ok'] = 0;

				// Increase counters
				$actual_module_group_root['counters']['total']++;

				if (isset($actual_module_group_root['counters'][$actual_agent['status']]))
					$actual_module_group_root['counters'][$actual_agent['status']]++;
			}
		}
		// If there is an agent and a module group opened and not saved
		if ($actual_module_group_root['id'] !== -1) {
			// Add the last agent to the module group
			$actual_module_group_root['children'][] = $actual_agent;
			// Add the last module group to the branch
			$nodes[] = $actual_module_group_root;
		}

		$this->tree = $nodes;
	}
	
	private function getDataOS() {
		// ACL Group
		if (isset($this->userGroups) && $this->userGroups === false)
			return array();

		$group_acl =  "";
		if (!empty($this->userGroups)) {
			$user_groups_str = implode(",", array_keys($this->userGroups));
			$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
		}

		// Agent name filter
		$agent_search = "";
		if (!empty($this->filter['searchAgent'])) {
			$agent_search = " AND ta.nombre LIKE '%".$this->filter['searchAgent']."%' ";
		}

		// Module name filter
		$module_search = "";
		if (!empty($this->filter['searchModule'])) {
			$module_search = " AND tam.nombre LIKE '%".$this->filter['searchModule']."%' ";
		}

		$sql = "SELECT tam.id_agente_modulo, tam.nombre AS module_name,
					tam.id_tipo_modulo, tam.id_modulo,
					ta.id_agente, ta.nombre AS agent_name, ta.fired_count,
					ta.normal_count, ta.warning_count, ta.critical_count,
					ta.unknown_count, ta.notinit_count, ta.total_count,
					tos.id_os, tos.name AS os_name, tos.icon_name AS os_icon,
					tae.estado, tae.datos
				FROM tagente_modulo AS tam
				INNER JOIN tagente AS ta
					ON ta.id_agente = tam.id_agente
						AND ta.disabled = 0
				INNER JOIN tagente_estado AS tae
					ON tae.id_agente_modulo = tam.id_agente_modulo
				LEFT JOIN tconfig_os AS tos
					ON tos.id_os = ta.id_os
				WHERE tam.disabled = 0
					$agent_search
					$module_search
					$group_acl
				ORDER BY tos.icon_name ASC, tos.id_os ASC, ta.nombre ASC, tam.nombre";
		$data = db_process_sql($sql);

		if (empty($data)) {
			$data = array();
		}

		$nodes = array();
		$actual_os_root = array(
				'id' => -1,
				'name' => '',
				'icon' => '',
				'children' => array(),
				'counters' => array()
			);
		$actual_agent = array();
		foreach ($data as $key => $value) {

			// Module
			$module = array();
			$module['id_agente_modulo'] = (int) $value['id_agente_modulo'];
			$module['nombre'] = $value['module_name'];
			$module['id_tipo_modulo'] = (int) $value['id_tipo_modulo'];
			$module['server_type'] = (int) $value['id_modulo'];
			$module['status'] = (int) $value['estado'];
			$module['value'] = $value['datos'];

			$this->processModule($module);

			// OS item
			if ($actual_os_root['id'] === (int)$value['id_os']) {
				// Agent
				if (empty($actual_agent) || $actual_agent['id'] !== (int)$value['id_agente']) {
					// Add the last agent to the os item
					if (!empty($actual_agent))
						$actual_os_root['children'][] = $actual_agent;

					// Create the new agent
					$actual_agent = array();
					$actual_agent['id_agente'] = (int) $value['id_agente'];
					$actual_agent['nombre'] = $value['agent_name'];
					$actual_agent['children'] = array();

					$this->processAgent(&$actual_agent, array(), false);

					// Add the module to the agent
					$actual_agent['children'][] = $module;

					// Increase counters
					$actual_os_root['counters']['total']++;

					if (isset($actual_os_root['counters'][$actual_agent['status']]))
						$actual_os_root['counters'][$actual_agent['status']]++;
				}
				else {
					// Add the module to the agent
					$actual_agent['children'][] = $module;
				}
			}
			else {
				// The first iteration doesn't enter here
				if ($actual_os_root['id'] !== -1) {
					// Add the agent to the os item
					$actual_os_root['children'][] = $actual_agent;
					// Add the os the branch
					$nodes[] = $actual_os_root;
				}

				// Create new os item
				$actual_os_root = array();
				$actual_os_root['id'] = (int) $value['id_os'];
				$actual_os_root['type'] = $this->type;

				if (!empty($actual_os_root['id'])) {
					$actual_os_root['name'] = $value['os_name'];
					$actual_os_root['icon'] = $value['os_icon'];
				}
				else {
					$actual_os_root['name'] = __('None');
					$actual_os_root['icon'] = 'so_other.png';
				}
				
				// Create the new agent
				$actual_agent = array();
				$actual_agent['id_agente'] = (int) $value['id_agente'];
				$actual_agent['nombre'] = $value['agent_name'];
				$actual_agent['children'] = array();

				$this->processAgent(&$actual_agent, array(), false);

				// Add the module to the agent
				$actual_agent['children'][] = $module;

				// Initialize counters
				$actual_os_root['counters'] = array();
				$actual_os_root['counters']['total'] = 0;
				$actual_os_root['counters']['alerts'] = 0;
				$actual_os_root['counters']['critical'] = 0;
				$actual_os_root['counters']['warning'] = 0;
				$actual_os_root['counters']['unknown'] = 0;
				$actual_os_root['counters']['not_init'] = 0;
				$actual_os_root['counters']['ok'] = 0;

				// Increase counters
				$actual_os_root['counters']['total']++;

				if (isset($actual_os_root['counters'][$actual_agent['status']]))
					$actual_os_root['counters'][$actual_agent['status']]++;
			}
		}
		// If there is an agent and an os item opened and not saved
		if ($actual_os_root['id'] !== -1) {
			// Add the last agent to the os item
			$actual_os_root['children'][] = $actual_agent;
			// Add the last os to the branch
			$nodes[] = $actual_os_root;
		}

		$this->tree = $nodes;
	}
	
	private function getDataTag() {
		// ACL Group
		if (isset($this->userGroups) && $this->userGroups === false)
			return array();

		$group_acl =  "";
		if (!empty($this->userGroups)) {
			$user_groups_str = implode(",", array_keys($this->userGroups));
			$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
		}

		// Agent name filter
		$agent_search = "";
		if (!empty($this->filter['searchAgent'])) {
			$agent_search = " AND ta.nombre LIKE '%".$this->filter['searchAgent']."%' ";
		}
		
		// Module name filter
		$module_search = "";
		if (!empty($this->filter['searchModule'])) {
			$module_search = " AND tam.nombre LIKE '%".$this->filter['searchModule']."%' ";
		}
		
		$sql = "SELECT tam.id_agente_modulo, tam.nombre AS module_name,
					tam.id_tipo_modulo, tam.id_modulo,
					ta.id_agente, ta.nombre AS agent_name, ta.fired_count,
					ta.normal_count, ta.warning_count, ta.critical_count,
					ta.unknown_count, ta.notinit_count, ta.total_count,
					tt.id_tag, tt.name AS tag_name,
					tae.estado, tae.estado
				FROM tagente_modulo AS tam
				INNER JOIN tagente AS ta
					ON ta.id_agente = tam.id_agente
						AND ta.disabled = 0
				INNER JOIN tagente_estado AS tae
					ON tae.id_agente_modulo = tam.id_agente_modulo
				INNER JOIN ttag_module AS ttm
					ON ttm.id_agente_modulo = tam.id_agente_modulo
				INNER JOIN ttag AS tt
					ON tt.id_tag = ttm.id_tag
				WHERE tam.disabled = 0
					$agent_search
					$module_search
					$group_acl
				ORDER BY tt.name ASC, tt.id_tag ASC, ta.nombre ASC, tam.nombre ASC";
		$data = db_process_sql($sql);

		if (empty($data)) {
			$data = array();
		}

		$nodes = array();
		$actual_tag_root = array(
				'id' => null,
				'name' => '',
				'children' => array(),
				'counters' => array()
			);
		$actual_agent = array();
		foreach ($data as $key => $value) {

			// Module
			$module = array();
			$module['id_agente_modulo'] = (int) $value['id_agente_modulo'];
			$module['nombre'] = $value['module_name'];
			$module['id_tipo_modulo'] = (int) $value['id_tipo_modulo'];
			$module['server_type'] = (int) $value['id_modulo'];
			$module['status'] = (int) $value['estado'];
			$module['value'] = $value['datos'];

			$this->processModule($module);

			// Tag
			if ($actual_tag_root['id'] === (int)$value['id_tag']) {
				// Agent
				if (empty($actual_agent) || $actual_agent['id'] !== (int)$value['id_agente']) {
					// Add the last agent to the tag
					if (!empty($actual_agent))
						$actual_tag_root['children'][] = $actual_agent;

					// Create the new agent
					$actual_agent = array();
					$actual_agent['id_agente'] = (int) $value['id_agente'];
					$actual_agent['nombre'] = $value['agent_name'];
					$actual_agent['children'] = array();

					$this->processAgent(&$actual_agent, array(), false);

					// Add the module to the agent
					$actual_agent['children'][] = $module;

					// Increase counters
					$actual_tag_root['counters']['total']++;

					if (isset($actual_tag_root['counters'][$actual_agent['status']]))
						$actual_tag_root['counters'][$actual_agent['status']]++;
				}
				else {
					$actual_agent['children'][] = $module;
				}
			}
			else {
				// The first iteration doesn't enter here
				if ($actual_tag_root['id'] !== null) {
					// Add the agent to the tag
					$actual_tag_root['children'][] = $actual_agent;
					// Add the tag to the branch
					$nodes[] = $actual_tag_root;
				}

				// Create the new agent
				$actual_agent = array();
				$actual_agent['id_agente'] = (int) $value['id_agente'];
				$actual_agent['nombre'] = $value['agent_name'];
				$actual_agent['children'] = array();

				$this->processAgent(&$actual_agent, array(), false);

				// Add the module to the agent
				$actual_agent['children'][] = $module;

				// Create new tag
				$actual_tag_root = array();
				$actual_tag_root['id'] = (int) $value['id_tag'];
				$actual_tag_root['name'] = $value['tag_name'];
				$actual_tag_root['type'] = $this->type;

				// Initialize counters
				$actual_tag_root['counters'] = array();
				$actual_tag_root['counters']['total'] = 0;
				$actual_tag_root['counters']['alerts'] = 0;
				$actual_tag_root['counters']['critical'] = 0;
				$actual_tag_root['counters']['warning'] = 0;
				$actual_tag_root['counters']['unknown'] = 0;
				$actual_tag_root['counters']['not_init'] = 0;
				$actual_tag_root['counters']['ok'] = 0;

				// Increase counters
				$actual_tag_root['counters']['total']++;

				if (isset($actual_tag_root['counters'][$actual_agent['status']]))
					$actual_tag_root['counters'][$actual_agent['status']]++;
			}
		}
		// If there is an agent and a tag opened and not saved
		if ($actual_tag_root['id'] !== null) {
			// Add the last agent to the tag
			$actual_tag_root['children'][] = $actual_agent;
			// Add the last tag to the branch
			$nodes[] = $actual_tag_root;
		}

		$this->tree = $nodes;
	}
	
	public function getJSON() {
		$this->getData();
		
		return json_encode($this->tree);
	}
	
	public function getArray() {
		$this->getData();
		
		return $this->tree;
	}
}
?>
