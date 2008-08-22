<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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

<center>
<div class='databox' id='login'>
	<h1 id='log'><?php echo __('Logged Out'); ?></h1>
	<div class='databox' style='width: 400px;'>
		<form method="post" action="index.php?login=1">
		<table cellpadding='4' cellspacing='1' width='400'>
		<tr><td align='left'>
			<a href="index.php">
			<img src="images/pandora_logo.png" border="0" alt="logo"></a><br>
			<?php echo $pandora_version; ?>
		</td><td valign='bottom'>
			<?php echo __('Your session is over. Please close your browser window to close session on Pandora.<br><br>'); ?>
		</td></tr>
		</table>
		</form>
	</div>
	<div id="ip"><?php echo 'IP: <b class="f10">'.$REMOTE_ADDR.'</b>'; ?></div>
</div>
</center>




