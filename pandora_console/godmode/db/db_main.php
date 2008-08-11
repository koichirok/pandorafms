<?php 

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, U

// Load global vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Database Management");
	require ("general/noaccess.php");
	return;
}
// Todo for a good DB maintenance 
/* 
	- Delete too on datos_string and and datos_inc tables 
	
	- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
	  onto a small chunk of interpolated data (1 month - 600 registers)
	
	- A more powerful selection (by Agent, by Module, etc).
*/
?>
<h2><?php echo __('dbmain_title') ?> &gt;
<?php echo __('current_dbsetup') ?></h2>
<table width=550 cellspacing=3 cellpadding=3 border=0>
<tr><td>
<i><?php echo __('days_compact'); ?>:</i>&nbsp;<b><?php echo __('days_compact'); ?></b><br><br>
<i><?php echo __('days_purge'); ?>:</i>&nbsp;<b><?php echo __('days_purge'); ?></b><br><br>
<tr><td>
<div align='justify'>
<?php echo __('dbsetup_info'); ?>
</div><br>
<img src="reporting/fgraph.php?tipo=db_agente_purge&id=-1">
</table>
