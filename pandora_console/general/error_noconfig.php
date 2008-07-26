<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 �rtica Soluciones Tecnol�gicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Pandora FMS - The Free Monitoring System - Console error</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena, Raul Mateos">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">
</head>
<body>
<div align='center'>
<div id='login_f'>
	<h1 id="log_f" class="error">No configuration file found</h1>
	<div>
		<img src="images/pandora_logo.png" border="0"></a><br><font size="1">
		<?php echo $pandora_version; ?>
		</font>
	</div>
	<div class="msg">
	<br><br>Pandora FMS Console cannot find <i>include/config.php</i> or this file has invalid
	permissions and HTTP server cannot read it. Please read documentation to fix this problem.
	</div>
	<div class="msg">
	You can also try to run the <a href="install.php">installation wizard</a> to create one.
	</div>
</div>
</div>
</body>
</html>
