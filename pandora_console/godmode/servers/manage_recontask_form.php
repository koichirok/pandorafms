<?PHP
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

if ((comprueba_login() != 0) || (give_acl($id_user, 0, "PM")!=1)) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
	"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["update"])){ // Edit mode
	$id_rt = $_GET["update"];
	$query="SELECT * FROM trecon_task where id_rt = $id_rt";
	$result=mysql_query($query);
	$row=mysql_fetch_array($result);
	$name = $row["name"];
	$network = $row["subnet"];
	$id_recon_server = $row["id_recon_server"];
	$description = $row["description"];
	$type = $row["type"];
	$interval = $row["interval_sweep"];
	$id_group = $row["id_group"];
	$create_incident = $row["create_incident"];
	$id_network_profile = $row["id_network_profile"];
	$id_os = $row["id_os"];
	
} elseif (isset($_GET["create"])){
	$id_rt = -1;
	$name = "";
	$network = "";
	$description = "";
	$id_recon_server = 0;
	$type = 1;
	$interval = 43200;
	$id_group = 1;
	$create_incident = 1;
	$id_network_profile = 1;
	$id_os = 10; // Other
}

echo '<h2>'.$lang_label["view_servers"].' &gt; ';
echo $lang_label["manage_recontask"];
pandora_help ("recontask");
echo '</h2>';
echo '<table width="700" cellspacing="4" cellpadding="4" class="databox_color">';

// Different Form url if it's a create or if it's a update form
if ($id_rt != -1)
	echo "<form name='modulo' method='post' action='index.php?sec=gservers&sec2=godmode/servers/manage_recontask&update=$id_rt'>";
else
	echo "<form name='modulo' method='post' action='index.php?sec=gservers&sec2=godmode/servers/manage_recontask&create=1'>";

// Name
echo '<tr><td class="datos2">'.$lang_label["task_name"];
echo "<td class='datos2'><input type='text' name='name' size='25' value='$name'>";

// Recon server
echo "<td class='datos2'>".$lang_label["recon_server"];
echo '<a href="#" class="tip">&nbsp;<span>'.$lang_label["recon_server_help"].'</span></a>';
echo "<td class='datos2'>";
echo '<select name="id_recon_server">';
echo "<option value='$id_recon_server'>" . give_server_name($id_recon_server);
$sql1="SELECT id_server, name FROM tserver WHERE recon_server = 1 ORDER BY name ";
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_server"]."'>".$row["name"]."</option>";
}
echo "</select>";

// Network 
echo "<tr>";
echo '<td class="datos">'.$lang_label["network"].'</td>';
echo '<td class="datos">';
echo '<input type="text" name="network" size="25" value="'.$network.'"></td>';

// Interval
echo '<td class="datos">'.$lang_label["interval"].'</td>';
echo '<td class="datos">';
echo "<select name='interval'>";
if ($interval != 0){
	if ($interval < 43200)
		echo "<option value='$interval'>".($interval / 3600).$lang_label["hours"]."</option>";
	else
		echo "<option value='$interval'>".($interval / 86400).$lang_label["days"]."</option>";
}
echo "<option value='3600'>1 ".$lang_label["hour"]."</option>";
echo "<option value='7200'>2 ".$lang_label["hours"]."</option>";
echo "<option value='21600'>6 ".$lang_label["hours"]."</option>";
echo "<option value='43200'>1/2 ".$lang_label["day"]."</option>";
echo "<option value='86400'>1 ".$lang_label["day"]."</option>";
echo "<option value='432000'>5 ".$lang_label["days"]."</option>";
echo "<option value='604800'>1 ".$lang_label["week"]."</option>";
echo "<option value='1209600'>2 ".$lang_label["week"]."</option>";
echo "<option value='2592000'>1 ".$lang_label["month"]."</option>";
echo "</select>";

// Network profile
echo "<tr>";
echo "<td class='datos2'>".lang_string ("network_profile") . "</td>";
echo "<td class='datos2'>";
echo "<select name='id_network_profile'>";
echo "<option value='$id_network_profile'>".give_network_profile_name($id_network_profile);
$sql1 = "SELECT * FROM tnetwork_profile where id_np != '$id_network_profile'";
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["id_np"]."'>".$row["name"]."</option>";
echo "</select></td>";

// OS
echo "<td class='datos2'>". lang_string ("OS") . "</td>";
echo "<td class='datos2'>";
echo "<select name='id_os'>";
if ($id_os != 0)
	echo "<option value='$id_os'>".get_db_sql ("SELECT name FROM tconfig_os WHERE id_os = $id_os");
	echo "<option value=-1>". lang_string ("Any");
$sql1 = "SELECT * FROM tconfig_os ORDER BY name";
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["id_os"]."'>".$row["name"]."</option>";
echo "</select></td>";

// Group
echo "<tr>";
echo "<td class='datos'>".$lang_label["group"]."</td>";
echo "<td class='datos'>";
echo "<select name='id_group'>";
echo "<option value='$id_group'>".dame_nombre_grupo($id_group)."</option>";
$sql1 = "SELECT * FROM tgrupo where id_grupo != $id_group";
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["id_grupo"]."'>".$row["nombre"]."</option>";
echo "</select></td>";

// Incident
echo "<tr>";
echo "<td class='datos2'>".$lang_label["incident"]."</td>";
echo "<td class='datos2'>";
echo "<select name='create_incident'>";
if ($create_incident == 1){
	echo "<option value='1'>".$lang_label["yes"]."</option>";
	echo "<option value='0'>".$lang_label["no"]."</option>";
}
else {
	echo "<option value='0'>".$lang_label["no"]."</option>";
	echo "<option value='1'>".$lang_label["yes"]."</option>";
}
echo "</select></td>";
echo "<td class='datos2' colspan=2> </td></tr>";

// Comments
echo '<tr><td class="datost">'.$lang_label["comments"];
echo '<td class="datos" colspan=3>';
echo '<textarea name="description" cols=70 rows=2>';
echo $description;
echo "</textarea>";
echo "</td></tr>";
echo "</table>";

echo "<table cellpadding='4' cellspacing='4' width='700'>";
echo "<td align='right'>";
if ($id_rt != "-1")
	echo '<input name="updbutton" type="submit" class="sub upd" value="'.$lang_label["update"].'">';
else
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.$lang_label["add"].'">';
echo "</form>";
echo "</table>";

?>
