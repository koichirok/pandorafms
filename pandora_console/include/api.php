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

require_once ("config.php");
require_once("functions_api.php");
enterprise_include_once ('include/functions_enterprise_api.php');

$ipOrigin = $_SERVER['REMOTE_ADDR'];

//Get the parameters and parse if necesary.
$op = get_parameter('op');
$op2 = get_parameter('op2');
$id = get_parameter('id');
$id2 = get_parameter('id2');
$otherSerialize = get_parameter('other');
$otherMode = get_parameter('other_mode', 'url_encode');
$returnType = get_parameter('return_type', 'string');
$password = get_parameter('pass', '');

$other = parseOtherParameter($otherSerialize, $otherMode);

$apiPassword = get_db_value_filter('value', 'tconfig', array('token' => 'api_password'));

$correctLogin = false;
if (!empty($apiPassword)) {
	if ($password === $apiPassword) {
		$correctLogin = true;
	}
}
else {
	if (isInACL($ipOrigin)) {
		$correctLogin = true;
	}
}

if ($correctLogin) {
	if (($op !== 'get') && ($op !== 'set') && ($op !== 'help'))
			returnError('no_set_no_get_no_help', $returnType);
	else {
		if (!function_exists($op.'_'.$op2))
			returnError('no_exist_operation', $returnType);
		else {
			call_user_func($op.'_'.$op2, $id, $id2, $other, $returnType);
		}
	}
}
else {
	echo 'ERROR: Your IP (' . $ipOrigin . ') is not in ACL IP list.';
}
?>
