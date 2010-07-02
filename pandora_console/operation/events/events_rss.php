<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

ini_set ('display_errors', 0); //Don't display other errors, messes up XML

require_once "../../include/config.php";
require_once "../../include/functions.php";
require_once "../../include/functions_db.php";
require_once "../../include/functions_api.php";


$ipOrigin = $_SERVER['REMOTE_ADDR'];

// Uncoment this to activate ACL on RSS Events
if (!isInACL($ipOrigin))
    exit;

header("Content-Type: application/xml; charset=UTF-8"); //Send header before starting to output

function rss_error_handler ($errno, $errstr, $errfile, $errline) {
	global $config;

	$base = 'http'.(isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': '').'://'.$_SERVER['HTTP_HOST'];
	$url = $base.$config["homeurl"];
	$selfurl = $base.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];

	$rss_feed = '<?xml version="1.0" encoding="utf-8" ?>'; //' Fixes certain highlighters freaking out on the PHP closing tag
	$rss_feed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'; 
	$rss_feed .= '<channel><title>Pandora RSS Feed</title><description>Latest events on Pandora</description>';
	$rss_feed .= '<lastBuildDate>'.date (DATE_RFC822, 0).'</lastBuildDate>';
	$rss_feed .= '<link>'.$url.'</link>'; //Link back to the main Pandora page
	$rss_feed .= '<atom:link href="'.safe_input ($selfurl).'" rel="self" type="application/rss+xml" />'; //Alternative for Atom feeds. It's the same.

	$rss_feed .= '<item><guid>'.$url.'/index.php?sec=eventos&sec2=operation/events/events</guid><title>Error creating feed</title>';
	$rss_feed .= '<description>There was an error creating the feed: '.$errno.' - '.$errstr.' in '.$errfile.' on line '.$errline.'</description>';
	$rss_feed .= '<link>'.$url.'/index.php?sec=eventos&sec2=operation/events/events</link></item>';
	
	exit ($rss_feed); //Exit by displaying the feed
}

set_error_handler ('rss_error_handler', E_ALL); //Errors output as RSS

$ev_group = get_parameter ("ev_group", 0); // group
$search = get_parameter ("search", ""); // free search
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 0); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1);
$id_event = (int) get_parameter ("id_event", -1); //This will allow to select only 1 event (eg. RSS)
$event_view_hr = (int) get_parameter ("event_view_hr", 0);

$sql_post = "";

if ($event_view_hr > 0) {
	$unixtime = (int) (get_system_time () - ($event_view_hr * 3600)); //Put hours in seconds
	$sql_post .= " AND `tevento`.`utimestamp` > ".$unixtime;
}
if ($ev_group > 1)
	$sql_post .= " AND `tevento`.`id_grupo` = $ev_group";
if ($status >= 0)
	$sql_post .= " AND `tevento`.`estado` = ".$status;
if ($search != "")
	$sql_post .= " AND `tevento`.`evento` LIKE '%$search%'";
if ($event_type != ""){
	// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
	// for the user so for him is presented only "warning, critical and normal"
	if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal"){
		$sql_post .= " AND `tevento`.`event_type` LIKE '%$event_type%' ";
	}
	elseif ($event_type == "not_normal"){
		$sql_post .= " AND `tevento`.`event_type` LIKE '%warning%' OR `tevento`.`event_type` LIKE '%critical%' OR `tevento`.`event_type` LIKE '%unknown%' ";
	}
	else
		$sql_post .= " AND `tevento`.`event_type` = '".$event_type."'";
}
if ($severity != -1)
	$sql_post .= " AND `tevento`.`criticity` >= ".$severity;
if ($id_agent != -1)
	$sql_post .= " AND `tevento`.`id_agente` = ".$id_agent;
if ($id_event != -1)
	$sql_post .= " AND id_evento = ".$id_event;
		
$sql="SELECT `tevento`.`id_evento` AS event_id,
	`tevento`.`id_agente` AS id_agent,
	`tevento`.`id_usuario` AS validated_by,
	`tevento`.`estado` AS validated,
	`tevento`.`evento` AS event_descr,
	`tevento`.`utimestamp` AS unix_timestamp,
	`tevento`.`event_type` AS event_type 
	FROM tevento
	WHERE 1 = 1".$sql_post."
	ORDER BY utimestamp DESC LIMIT 0 , 30";

$result= get_db_all_rows_sql ($sql);

$base = 'http'.(isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': '').'://'.$_SERVER['HTTP_HOST'];
$url = $base.$config["homeurl"];
$selfurl = $base.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];

if (empty ($result)) {
	$lastbuild = 0; //Last build in 1970
} else {
	$lastbuild = (int) $result[0]['unix_timestamp'];
}

$rss_feed = '<?xml version="1.0" encoding="utf-8" ?>'; // ' <?php ' -- Fixes highlighters thinking that the closing tag is PHP
$rss_feed .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'; 
$rss_feed .= '<channel><title>Pandora RSS Feed</title><description>Latest events on Pandora</description>';
$rss_feed .= '<lastBuildDate>'.date (DATE_RFC822, $lastbuild).'</lastBuildDate>'; //Last build date is the last event - that way readers won't mark it as having new posts
$rss_feed .= '<link>'.$url.'</link>'; //Link back to the main Pandora page
$rss_feed .= '<atom:link href="'.safe_input ($selfurl).'" rel="self" type="application/rss+xml" />'; //Alternative for Atom feeds. It's the same.

if (empty ($result)) {
	$result = array();
	$rss_feed .= '<item><guid>'.safe_input ($url.'/index.php?sec=eventos&sec2=operation/events/events').'</guid><title>No results</title>';
	$rss_feed .= '<description>There are no results. Click on the link to see all Pending events</description>';
	$rss_feed .= '<link>'.safe_input ($url.'/index.php?sec=eventos&sec2=operation/events/events').'</link></item>';
}

foreach ($result as $row) {
	if ($row["event_type"] == "system") {
		$agent_name = __('System');
	}
	elseif ($row["id_agent"] > 0) {
		// Agent name
		$agent_name = get_agent_name ($row["id_agent"]);
	}
	else {
		$agent_name = __('Alert').__('SNMP');
	}
	
//This is mandatory
	$rss_feed .= '<item><guid>';
	$rss_feed .= safe_input ($url . "/index.php?sec=eventos&sec2=operation/events/events&id_event=" . $row['event_id']);
	$rss_feed .= '</guid><title>';
	$rss_feed .= $agent_name;
	$rss_feed .= '</title><description>';
	$rss_feed .= safe_input ($row['event_descr']);
	if($row['validated'] == 1) {
		$rss_feed .= safe_input ('<br /><br />').'Validated by ' . safe_input ($row['validated_by']);
	}
	$rss_feed .= '</description><link>';
	$rss_feed .= safe_input ($url . "/index.php?sec=eventos&sec2=operation/events/events&id_event=" . $row["event_id"]);
	$rss_feed .= '</link>';

//The rest is optional
	$rss_feed .= '<pubDate>' . date(DATE_RFC822, $row['unix_timestamp']) . '</pubDate>';
	
//This is mandatory again
	$rss_feed .= '</item>';
}

$rss_feed .= "</channel></rss>";

echo $rss_feed;
?>
