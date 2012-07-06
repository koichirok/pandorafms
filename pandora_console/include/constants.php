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



/* Seconds in a time unit constants */
define('SECONDS_1MINUTE',60);
define('SECONDS_5MINUTES',300);
define('SECONDS_30MINUTES',1800);
define('SECONDS_1HOUR',3600);
define('SECONDS_6HOURS',21600);
define('SECONDS_12HOURS',43200);
define('SECONDS_1DAY',86400);
define('SECONDS_1WEEK',604800);
define('SECONDS_15DAYS',1296000);
define('SECONDS_1MONTH',2592000);
define('SECONDS_3MONTHS',7776000);
define('SECONDS_6MONTHS',15552000);
define('SECONDS_1YEAR',31104000);
define('SECONDS_2YEARS',62208000);
define('SECONDS_3YEARS',93312000);



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
define('COL_CRITICAL','#cc0000');
define('COL_WARNING','#ffcc00');
define('COL_NORMAL','#8ae234');
define('COL_NOTINIT','#ef2929');
define('COL_UNKNOWN','#aaaaaa');
define('COL_ALERTFIRED','#ff8800');



/* The styles */
/* Size of text in characters for truncate */
define('GENERIC_SIZE_TEXT', 25);



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
define('VISUAL_MAP_STATUS_UNKNOW', 3);



/* Service constants */
//Status
define('SERVICE_STATUS_UNKNOW', -1);
define('SERVICE_STATUS_NORMAL', 0);
define('SERVICE_STATUS_CRITICAL', 1);
define('SERVICE_STATUS_WARNING', 2);
?>