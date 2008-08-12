<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

echo "<h2>".__('Events')." &gt; ";
echo __('Events statistics')."</h2>";
echo "<br><br>";
echo "<table width=95%>";
echo "<tr><td valign='top'>";
echo "<h3>".__('Event graph')."</h3>";
echo '<img src="reporting/fgraph.php?tipo=total_events&width=300&height=200" border=0>';
echo "<td valign='top'>";
echo "<h3>".__('Event graph by user')."</h3>";
echo '<img src="reporting/fgraph.php?tipo=user_events&width=300&height=200" border=0>';
echo "<tr><td>";
echo "<h3>".__('Event graph by group')."</h3>";
echo '<img src="reporting/fgraph.php?tipo=group_events&width=300&height=200" border=0>';
echo "</table>";
?>
