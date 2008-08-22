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


// Load global vars
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Network Profile Management");
	require ("general/noaccess.php");
	exit;
}
  
if (isset($_GET["delete"])){ // if delete
	$id_np = entrada_limpia ($_GET["delete"]);
	$sql_delete= "DELETE FROM tnetwork_profile WHERE id_np = ".$id_np;
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".__('Not deleted. Error deleting data')."</h3>";
	else
		echo "<h3 class='suc'>".__('Deleted successfully')."</h3>";
	
	$result=mysql_query($sql_delete);
}
echo "<h2>".__('Module management')." &gt; ";
echo __('Module template management')."</h2>";


$sql1='SELECT * FROM tnetwork_profile ORDER BY name';
$result=mysql_query($sql1);
$color=0;
if (mysql_num_rows($result)) {
	echo "<table cellpadding='4' cellspacing='4' width='650' class='databox'>";
	echo "<th>".__('Name')."</th>";
	echo "<th>".__('Description')."</th>";
	echo "<th>".__('Action')."</th>";
}
while ($row=mysql_fetch_array($result)){
	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
		}
	else {
		$tdcolor = "datos2";
		$color = 1;
	}
	echo "
	<tr>
		<td class='$tdcolor'>
		<b><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np=".$row["id_np"]."'>".$row["name"]."</a></b>
		</td>
		<td class='$tdcolor'>
		".$row["description"]."
		</td>
		<td class='$tdcolor' align='center'>
		<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates&delete=".$row["id_np"]."'
			onClick='if (!confirm(\' ".__('Are you sure?')."\'))
		return false;'>
		<img border='0' src='images/cross.png'></a>
		</td>
	</tr>";

}
if (mysql_num_rows($result)) {
	echo "</table>";
	echo "<table width='650px'>";
} else {
	echo "<div class='nf'>".__('There are no defined network profiles')."</div>";
	echo "<table>";
}

echo "<tr><td align='right'>";
echo "<form method=post action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np=-1'>";
echo "<input type='submit' class='sub next' name='crt' value='".__('Create')."'>";
echo "</form></td></tr></table>";

?>
