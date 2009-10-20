<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Pandora FMS - The Flexible Monitoring System - Console error</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">
</head>
<body>

<div id="main" style='float:left; margin-left: 100px'>
<div align='center'>
<div id='login_f'>
	<h1 id="log_f" class="error">Bad permission for include/config.php</h1>
	<div>
		<img src="images/pandora_logo.png" border="0"></a><br>
	</div>
	<div class="msg"><br><br>
	For security reasons, <i>config.php</i> must have restrictive permissions, and "other" users 
	should not read it or write to it. It should be written only for owner 
	(usually www-data or http daemon user), normal operation is not possible until you change 
	permissions for <i>include/config.php</i> file. Please do it, it's for your security.
	</div>
</div>
</div>
</div>
</body>
</html>
