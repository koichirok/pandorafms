<?php
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

if (comprueba_login() != 0) {
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access recon task viewer");
	require ($config["homeurl"]."/general/noaccess.php");
}

if (give_acl($id_user, 0, "AR")==0) {
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access recon task viewer");
	require ($config["homeurl"]."/general/noaccess.php");
}


$modules_server = 0;
$total_modules = 0;
$total_modules_data = 0;


// --------------------------------
// FORCE A RECON TASK
// --------------------------------
if (give_acl($id_user, 0, "PM")==1){
	if (isset($_GET["force"])) {
		$id = entrada_limpia($_GET["force"]);
		$sql = "UPDATE trecon_task set utimestamp = 0, status = -1 WHERE id_rt = $id ";
		$result = mysql_query($sql);
	}
}

$id_server = get_parameter ("server_id", -1);
$sql = "SELECT * FROM tserver WHERE id_server = $id_server";
$result=mysql_query($sql);
$row=mysql_fetch_array($result);
$server_name = $row["name"];
$id_server = $row[0];

echo "<h2>". lang_string ("server_detail") . " - $server_name ";
echo "&nbsp;";
echo "<a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=$id_server'>";
echo "<img src='images/refresh.png'>";
echo "</A>";
echo "</h2>";
// Show network tasks for Recon Server
if ($row["recon_server"]){
	$sql = "SELECT * FROM trecon_task WHERE id_recon_server = $id_server";
	// Connect DataBase
	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		echo "<table cellpadding='4' cellspacing='4' width='760' class='databox'>";
		echo "<tr><th class='datos'>".lang_string ("Force")."</th>";
		echo "<th class='datos'>".$lang_label["task_name"]."</th>";
		echo "<th class='datos'>".$lang_label['interval']."</th>";
		echo "<th class='datos'>".$lang_label['network']."</th>";
		echo "<th class='datos'>".$lang_label['status']."</th>";
		echo "<th class='datos'>".$lang_label['network_profile']."</th>";
		echo "<th class='datos'>".$lang_label['group']."</th>";
		echo "<th class='datos'>".lang_string ("OS") ."</th>";
		echo "<th class='datos'>".$lang_label['progress']."</th>";
		echo "<th class='datos'>".$lang_label['lastupdate']."</th>";
		echo "<th class='datos'></th>";
		$color=1;
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			$id_rt = $row["id_rt"];
			$name = $row["name"];
			$status = $row["status"];
			$utimestamp = $row["utimestamp"];
			$interval = $row["interval_sweep"];
			$create_incident = $row["create_incident"];
			$subnet = $row["subnet"];
			$id_os = $row["id_os"];
			$id_group = $row["id_group"];
			$id_network_profile = $row["id_network_profile"];
			$type = $row["type"];
			echo "<tr>";
			// Name
			echo "<td class='$tdcolor'>";
			echo "<a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=$id_server&force=$id_rt'><img src='images/target.png' border='0'></a>";
			
			echo "<td class='$tdcolor'>";
			echo "<b>$name</b>";
			// Interval
			echo "<td class='$tdcolor'>";
			if ($interval != 0){
				if ($interval < 43200)
					echo "~ ".floor ($interval / 3600)." ".$lang_label["hours"];
				else
					echo "~ ".floor ($interval / 86400)." ".$lang_label["days"];
			} else
				echo $interval;
			
			// Subnet
			echo "<td class='$tdcolor'>";
			echo $subnet;
			
			// status
			echo "<td class='$tdcolor' align='center'>";
			if ($status == -1)
				echo $lang_label["done"];
			else
				echo $lang_label["pending"];
			// Network profile
			echo "<td class='$tdcolor'>";
			echo give_network_profile_name($id_network_profile);
			
			// Group
			echo "<td class='$tdcolor' align='center'>";
			echo "<img class='bot' src='images/groups_small/".show_icon_group($id_group).".png'>";
			
			// OS
			echo "<td class='$tdcolor' align='center'>";
			if ($id_os > 0){
				$icon = get_db_sql ("SELECT icon_name FROM tconfig_os WHERE id_os = $id_os");
				echo "<img src='images/$icon'>";
			}

			// Progress
			echo "<td class='$tdcolor' align='center'>";
			if ($status < 0)
				echo "-";
			else
				echo '<img src="reporting/fgraph.php?tipo=progress&percent='.$status.'&height=20&width=100">';
			
			// Last execution
			echo "<td class='".$tdcolor."f9'>";
			$keepalive = strftime ( "%m/%d/%y %H:%M:%S" , $utimestamp );
			echo substr($keepalive,0,25)."</td>";

			echo "<td class='$tdcolor'>";
			if (give_acl($id_user, 0, "PM")==1){
				echo "<a  href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update=$id_rt'>";
				echo "<img src='images/wrench_orange.png'></a>";
			}	
		}
		echo "</table>";
	} else {
		echo "This server has no recon tasks assigned";
	}
}

?>
