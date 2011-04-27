<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

if (file_exists("images/noaccess.png")){
	ui_print_page_header (__('You don\'t have access to this page'), "", false, "", true);
} else {
	echo "<br><br><center><h3>".__('You don\'t have access to this page')."</h3></center>";
}
?>

<div id="noaccess">
	<div align='center'>

<?php
	if (file_exists("images/noaccess.png")){
		echo html_print_image('images/noaccess.png', true, array("alt" => __('No access')));
	}
?>
		<div>&nbsp;</div>
		<div class="msg" style='width: 400px'><?php echo __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');?></div>
	</div>
</div>


<!-- Container div. ENDS HERE -->
