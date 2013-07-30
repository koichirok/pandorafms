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

/**
 * @package Include
 * @subpackage Constants
 */

/* Enterprise hook constant */
define ('ENTERPRISE_NOT_HOOK', -1);


/**/
define('DATE_FORMAT', 'Y/m/d');
define('DATE_FORMAT_JS', 'yy/mm/d');
define('TIME_FORMAT', 'H:i:s');
define('TIME_FORMAT_JS', 'HH:mm:ss');

/* Events state constants */
define ('EVENT_NEW', 0);
define ('EVENT_VALIDATE', 1);
define ('EVENT_PROCESS', 2);



/* Agents disabled status */
define ('AGENT_ENABLED',0);
define ('AGENT_DISABLED',1);



/* Error report codes */
define ('NOERR',11111);
define ('ERR_GENERIC',-10000);
define ('ERR_EXIST',-20000);
define ('ERR_INCOMPLETE', -30000);
define ('ERR_DB', -40000);
define ('ERR_DB_HOST', -40001);
define ('ERR_DB_DB', -40002);
define ('ERR_FILE', -50000);
define ('ERR_NOCHANGES', -60000);
define ('ERR_NODATA', -70000);
define ('ERR_CONNECTION', -80000);
define ('ERR_DISABLED', -90000);
define ('ERR_WRONG', -100000);
define ('ERR_WRONG_NAME', -100001);
define ('ERR_WRONG_PARAMETERS', -100002);
define ('ERR_ACL', -110000);

/* Event status code */
define ('EVENT_STATUS_NEW',0);
define ('EVENT_STATUS_INPROCESS',2);
define ('EVENT_STATUS_VALIDATED',1);

/* Seconds in a time unit constants */
define('SECONDS_1MINUTE', 60);
define('SECONDS_2MINUTES', 120);
define('SECONDS_5MINUTES', 300);
define('SECONDS_10MINUTES', 600);
define('SECONDS_15MINUTES', 900);
define('SECONDS_30MINUTES', 1800);
define('SECONDS_1HOUR', 3600);
define('SECONDS_2HOUR', 7200);
define('SECONDS_3HOUR', 10800);
define('SECONDS_5HOUR', 18000);
define('SECONDS_6HOURS', 21600);
define('SECONDS_12HOURS', 43200);
define('SECONDS_1DAY', 86400);
define('SECONDS_2DAY', 172800);
define('SECONDS_4DAY', 345600);
define('SECONDS_5DAY', 432000);
define('SECONDS_1WEEK', 604800);
define('SECONDS_10DAY', 864000);
define('SECONDS_2WEEK', 1209600);
define('SECONDS_15DAYS', 1296000);
define('SECONDS_1MONTH', 2592000);
define('SECONDS_2MONTHS', 5184000);
define('SECONDS_3MONTHS', 7776000);
define('SECONDS_6MONTHS', 15552000);
define('SECONDS_1YEAR', 31104000);
define('SECONDS_2YEARS', 62208000);
define('SECONDS_3YEARS', 93312000);



/* Separator constats */
define('SEPARATOR_COLUMN', ';');
define('SEPARATOR_ROW', chr(10)); //chr(10) = '\n'
define('SEPARATOR_COLUMN_CSV', "#");
define('SEPARATOR_ROW_CSV', "@\n");



/* Backup paths */
switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		define ('BACKUP_DIR', 'attachment/backups');
		define ('BACKUP_FULLPATH', $config['homedir'] . '/' . BACKUP_DIR);
		break;
	case "oracle":
		define ('BACKUP_DIR', 'DATA_PUMP_DIR');
		define ('BACKUP_FULLPATH', 'DATA_PUMP_DIR');
		break;
}



/* Color constants */
define('COL_CRITICAL','#FF4040');
define('COL_WARNING','#F2D400');
define('COL_WARNING_DARK','#FFB900');
define('COL_NORMAL','#6EB432');
define('COL_NOTINIT','#3BA0FF');
define('COL_UNKNOWN','#AAAAAA');
define('COL_ALERTFIRED','#FF8800');
define('COL_MINOR','#FF92E9');
define('COL_MAJOR','#C97A4A');
define('COL_INFORMATIONAL','#E4E4E4');
define('COL_MAINTENANCE','#3BA0FF');



/* The styles */
/* Size of text in characters for truncate */
define('GENERIC_SIZE_TEXT', 25);



/* Agent module status */
define('AGENT_MODULE_STATUS_CRITICAL_BAD', 1);
define('AGENT_MODULE_STATUS_CRITICAL_ALERT', 100);
define('AGENT_MODULE_STATUS_NO_DATA', 4);
define('AGENT_MODULE_STATUS_NORMAL', 0);
define('AGENT_MODULE_STATUS_NOT_NORMAL', 6);
define('AGENT_MODULE_STATUS_WARNING', 2);
define('AGENT_MODULE_STATUS_UNKNOWN', 3);
define('AGENT_MODULE_STATUS_NOT_INIT', 5);

/* Agent module status */
define('AGENT_STATUS_ALL', -1);
define('AGENT_STATUS_CRITICAL', 1);
define('AGENT_STATUS_NORMAL', 0);
define('AGENT_STATUS_NOT_INIT', 5);
define('AGENT_STATUS_NOT_NORMAL', 6);
define('AGENT_STATUS_UNKNOWN', 3);
define('AGENT_STATUS_ALERT_FIRED', 4);
define('AGENT_STATUS_WARNING', 2);

/* Visual maps contants */
//The items kind
define('STATIC_GRAPH', 0);
define('PERCENTILE_BAR', 3);
define('MODULE_GRAPH', 1);
define('SIMPLE_VALUE', 2);
define('LABEL', 4);
define('ICON', 5);
define('SIMPLE_VALUE_MAX', 6);
define('SIMPLE_VALUE_MIN', 7);
define('SIMPLE_VALUE_AVG', 8);
define('PERCENTILE_BUBBLE', 9);
define('SERVICE', 10); //Enterprise Item.
//Some styles
define('MIN_WIDTH',300);
define('MIN_HEIGHT',120);
define('MIN_WIDTH_CAPTION',420);
//The process for simple value
define('PROCESS_VALUE_NONE', 0);
define('PROCESS_VALUE_MIN', 1);
define('PROCESS_VALUE_MAX', 2);
define('PROCESS_VALUE_AVG', 3);
//Status
define('VISUAL_MAP_STATUS_CRITICAL_BAD', 1);
define('VISUAL_MAP_STATUS_CRITICAL_ALERT', 4);
define('VISUAL_MAP_STATUS_NORMAL', 0);
define('VISUAL_MAP_STATUS_WARNING', 2);
define('VISUAL_MAP_STATUS_UNKNOWN', 3);
define('VISUAL_MAP_STATUS_WARNING_ALERT', 10);



/* Service constants */
//Status
define('SERVICE_STATUS_UNKNOWN', -1);
define('SERVICE_STATUS_NORMAL', 0);
define('SERVICE_STATUS_CRITICAL', 1);
define('SERVICE_STATUS_WARNING', 2);
//Default weights
define('SERVICE_WEIGHT_CRITICAL', 1);
define('SERVICE_WEIGHT_WARNING', 0.5);
define('SERVICE_ELEMENT_WEIGHT_CRITICAL', 1);
define('SERVICE_ELEMENT_WEIGHT_WARNING', 0.5);
define('SERVICE_ELEMENT_WEIGHT_OK', 0);
define('SERVICE_ELEMENT_WEIGHT_UNKNOWN', 0);



/* Status images */
//For modules
define ('STATUS_MODULE_OK', 'module_ok.png');
define ('STATUS_MODULE_CRITICAL', 'module_critical.png');
define ('STATUS_MODULE_WARNING', 'module_warning.png');
define ('STATUS_MODULE_NO_DATA', 'module_no_data.png');
define ('STATUS_MODULE_UNKNOWN', 'module_unknown.png');
//For agents
define ('STATUS_AGENT_CRITICAL', 'agent_critical.png');
define ('STATUS_AGENT_WARNING', 'agent_warning.png');
define ('STATUS_AGENT_DOWN', 'agent_down.png');
define ('STATUS_AGENT_UNKNOWN', 'agent_unknown.png');
define ('STATUS_AGENT_OK', 'agent_ok.png');
define ('STATUS_AGENT_NO_DATA', 'agent_no_data.png');
define ('STATUS_AGENT_NO_MONITORS', 'agent_no_monitors.png');
define ('STATUS_AGENT_NOT_INIT', 'agent_notinit.png');
//For alerts
define ('STATUS_ALERT_FIRED', 'alert_fired.png');
define ('STATUS_ALERT_NOT_FIRED', 'alert_not_fired.png');
define ('STATUS_ALERT_DISABLED', 'alert_disabled.png');
//For servers
define ('STATUS_SERVER_OK', 'server_ok.png');
define ('STATUS_SERVER_DOWN', 'server_down.png');



/* Events criticity */
define ('EVENT_CRIT_MAINTENANCE', 0);
define ('EVENT_CRIT_INFORMATIONAL', 1);
define ('EVENT_CRIT_NORMAL', 2);
define ('EVENT_CRIT_MINOR', 5);
define ('EVENT_CRIT_WARNING', 3);
define ('EVENT_CRIT_MAJOR', 6);
define ('EVENT_CRIT_CRITICAL', 4);
define ('EVENT_CRIT_WARNING_OR_CRITICAL', 34);
define ('EVENT_CRIT_NOT_NORMAL', 20);

/* Id Module (more use in component)*/
define ('MODULE_DATA', 1);
define ('MODULE_NETWORK', 2);
define ('MODULE_SNMP', 2);
define ('MODULE_PLUGIN', 4);
define ('MODULE_PREDICTION', 5);
define ('MODULE_WMI', 6);
define ('MODULE_WEB', 7);

/* Type of Modules of Prediction */
define ('MODULE_PREDICTION_SERVICE', 2);
define ('MODULE_PREDICTION_SYNTHETIC', 3);
define ('MODULE_PREDICTION_NETFLOW', 4);

/* SNMP CONSTANTS */
define ('SNMP_DIR_MIBS', "attachment/mibs");

/* PASSWORD POLICIES */
define('PASSSWORD_POLICIES_OK', 0);
define('PASSSWORD_POLICIES_FIRST_CHANGE', 1);
define('PASSSWORD_POLICIES_EXPIRED', 2);

/* SERVER TYPES */
define ('SERVER_TYPE_DATA', 0);
define ('SERVER_TYPE_NETWORK', 1);
define ('SERVER_TYPE_SNMP', 2);
define ('SERVER_TYPE_RECON', 3);
define ('SERVER_TYPE_PLUGIN', 4);
define ('SERVER_TYPE_PREDICTION', 5);
define ('SERVER_TYPE_WMI', 6);
define ('SERVER_TYPE_EXPORT', 7);
define ('SERVER_TYPE_INVENTORY', 8);
define ('SERVER_TYPE_WEB', 9);
define ('SERVER_TYPE_EVENT', 10);
define ('SERVER_TYPE_ENTERPRISE_ICMP', 11);
define ('SERVER_TYPE_ENTERPRISE_SNMP', 12);

/* REPORTS */
define ('REPORT_TOP_N_MAX', 1);
define ('REPORT_TOP_N_MIN', 2);
define ('REPORT_TOP_N_AVG', 0);

define ('REPORT_TOP_N_ONLY_GRAPHS', 2);
define ('REPORT_TOP_N_SHOW_TABLE_GRAPS', 1);
define ('REPORT_TOP_N_ONLY_TABLE', 0);

define ('REPORT_EXCEPTION_CONDITION_EVERYTHING', 0);
define ('REPORT_EXCEPTION_CONDITION_GE', 1);
define ('REPORT_EXCEPTION_CONDITION_LE', 5);
define ('REPORT_EXCEPTION_CONDITION_L', 2);
define ('REPORT_EXCEPTION_CONDITION_G', 6);
define ('REPORT_EXCEPTION_CONDITION_E', 7);
define ('REPORT_EXCEPTION_CONDITION_NE', 8);
define ('REPORT_EXCEPTION_CONDITION_OK', 3);
define ('REPORT_EXCEPTION_CONDITION_NOT_OK', 4);

/* POLICIES */

define("POLICY_UPDATED", 0);
define("POLICY_PENDING_DATABASE", 1);
define("POLICY_PENDING_ALL", 2);

define("STATUS_IN_QUEUE_OUT", 0);
define("STATUS_IN_QUEUE_IN", 1);
define("STATUS_IN_QUEUE_APPLYING", 2);

define("MODULE_UNLINKED", 0);
define("MODULE_LINKED", 1);
define("MODULE_PENDING_UNLINK", 10);
define("MODULE_PENDING_LINK", 11);

?>
