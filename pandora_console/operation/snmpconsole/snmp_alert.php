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

if (give_acl($id_user, 0, "LW")==1) {
	// Variable init
	$view_alert=1;
	$alert_add = 0;
	$alert_update=0;
	$alert_submit=0;

	$id_as = "";
	$id_alert = "";
	$nombre_alerta = "";
	$alert_type = "";
	$agent = "";
	$description = "";
	$oid = "";
	$custom_oid = "";
	$time_threshold = "";
	$al_field1 = "";
	$al_field2 = "";
	$al_field3 = "";
	$last_fired = "";
	$max_alerts = "";
	$min_alerts = "";
    $priority = "";

	// Alert Delete
	// =============
	if (isset($_GET["delete_alert"])){ // Delete alert
		$alert_delete = $_GET["delete_alert"];
		$sql1='DELETE FROM talert_snmp WHERE id_as = '.$alert_delete;
		$result=mysql_query($sql1);
		if (!$result)
			echo "<h3 class='error'>".$lang_label["delete_alert_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["delete_alert_ok"]."</h3>";
	}
	// Alert submit (for insert or update)
	if (isset($_GET["submit"])){
		$alert_submit=1;
		$create = entrada_limpia($_POST["create"]);
		$update = entrada_limpia($_POST["update"]);
		$id_as = entrada_limpia($_POST["id_as"]);
		$max = entrada_limpia($_POST["max"]);
		$min = entrada_limpia($_POST["min"]);
		$time = entrada_limpia($_POST["time"]);
		$description = entrada_limpia($_POST["description"]);
		$oid = entrada_limpia($_POST["oid"]);
		$agent = entrada_limpia($_POST["agent"]);
		$custom = entrada_limpia($_POST["custom"]);
		$alert_id = entrada_limpia($_POST["alert_id"]);
		$alert_type = entrada_limpia($_POST["alert_type"]);
		$field1 = entrada_limpia($_POST["field1"]);
		$field2 = entrada_limpia($_POST["field2"]);
		$field3 = entrada_limpia($_POST["field3"]);
        $priority = get_parameter ("priority",0);
		
		if ($create == 1){
			$sql = "INSERT INTO talert_snmp (id_alert,al_field1,al_field2,al_field3,description,alert_type,agent,custom_oid,oid,time_threshold,max_alerts,min_alerts, priority) VALUES ($alert_id,'$field1','$field2','$field3','$description', $alert_type, '$agent', '$custom', '$oid', $time, $max, $min, $priority)";
			$result=mysql_query($sql);
			if (!$result)
				echo "<h3 class='error'>".$lang_label["create_alert_no"]."</h3>";
			else
				echo "<h3 class='suc'>".$lang_label["create_alert_ok"]."</h3>";
		} else { 
			$sql = "UPDATE talert_snmp set priority = $priority, id_alert= $alert_id, al_field1 = '$field1', al_field2 = '$field2', al_field3 = '$field3', description = '$description', alert_type = $alert_type, agent = '$agent', custom_oid = '$custom', oid = '$oid', time_threshold = $time, max_alerts = '$max', min_alerts = '$min' WHERE id_as = $id_as";
			$result=mysql_query($sql);
			if (!$result)
				echo "<h3 class='error'>".$lang_label["update_alert_no"]."</h3>";
			else
				echo "<h3 class='suc'>".$lang_label["create_alert_ok"]."</h3>";
		}


	}
	// Alert update: (first, load data used in form), later use insert/add form
	// ============
	if (isset($_GET["update_alert"])){
		$alert_update = $_GET["update_alert"];
		$sql1='SELECT * FROM talert_snmp WHERE id_as = '.$alert_update;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$id_as = $row["id_as"];
			$id_alert = $row["id_alert"];
			$nombre_alerta = dame_nombre_alerta($id_alert);
			$alert_type = $row["alert_type"];
			$agent = $row["agent"];
			$description = $row["description"];
			$oid = $row["oid"];
			$custom_oid = $row["custom_oid"];
			$time_threshold = $row["time_threshold"];
			$al_field1 = $row["al_field1"];
			$al_field2 = $row["al_field2"];
			$al_field3 = $row["al_field3"];
			$last_fired = $row["last_fired"];
			$max_alerts = $row["max_alerts"];
			$min_alerts = $row["min_alerts"];
            $priority = $row["priority"];
		}
	}
	if (isset($_POST["add_alert"])){
		$alert_add = 1;
	}
	echo "<h2>Pandora SNMP &gt; ";
	// Add alert form
	if (($alert_update != 0) || ($alert_add == 1)) {
	
		if ($alert_update != 0) {
			echo $lang_label["update_alert"]."</h2>";
		} else {
			echo $lang_label["create_alert"]."</h2>";
		}
		echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert&submit=1">';
		echo '<input type="hidden" name="id_as" value="'.$id_as.'">'; // if known, if add will be undetermined (0).
		echo '<table cellpadding="4" cellspacing="4" width="650" class="databox_color">';
		// Alert
		echo '<tr><td class=datos>'.$lang_label["alert"].'<td class=datos><select name="alert_id">';
		if ($alert_update != 0) { // calculate first item
			$sql0='SELECT * FROM talerta WHERE id_alerta = '.$id_alert;
			$result0=mysql_query($sql0);
			$row0=mysql_fetch_array($result0);
			echo "<option value='".$row0["id_alerta"]."'>".$row0["nombre"]."</option>";
		}
		$sql1='SELECT * FROM talerta';
		$result1=mysql_query($sql1);
		while ($row1=mysql_fetch_array($result1)){
			echo "<option value='".$row1["id_alerta"]."'>".$row1["nombre"]."</option>";
		}
		echo "</select>";
		// Alert type		
		echo '<tr><td class="datos2">'.$lang_label["alert_type"];
		echo '<td class="datos2"><select name="alert_type">';
		if ($alert_type == 0) {
			echo '
			<option value=0>OID</option>
			<option value=1>CustomOID/Value</option>
			<option value=2>SNMPAgent</option>';
		
		} elseif ($alert_type == 1) {
			echo '
			<option value=1>CustomOID/Value</option>
			<option value=0>OID</option>
			<option value=2>SNMPAgent</option>';
		} else {
			echo '
			<option value=2>SNMPAgent</option>
			<option value=0>OID</option>
			<option value=1>CustomOID/Value</option>';
		}
		echo '</select></td></tr>';
		// Description
		echo '<tr><td class=datos>'.$lang_label["description"].'</td>';
		echo '<td class=datos><input type="text" size=60 name="description" value="'.$description.'">';

		// OID
		echo '<tr><td class="datos2">'.$lang_label["OID"].'</td>';
		echo '<td  class="datos2"><input type="text" size=30 name="oid" value="'.$oid.'">';

		// OID Custom
		echo '<tr><td class=datos>'.$lang_label["customvalue"].'</td>';
		echo '<td class=datos><input type="text" size=30 name="custom" value="'.$custom_oid.'">';

		// SNMP Agent
		echo '<tr><td class="datos2">'.$lang_label["SNMP_agent"].' IP</td>';
		echo '<td class="datos2"><input type="text" size=30 name="agent" value="'.$agent.'">';
		
		// Alert fields
		echo '<tr><td class=datos>'.$lang_label["field1"].'</td>';
		echo '<td class=datos><input type="text" size=30 name="field1" value="'.$al_field1.'"></td>';
		echo '<tr><td class="datos2">'.$lang_label["field2"].'</td>';
		echo '<td class="datos2"><input type="text" size=40 name="field2" value="'.$al_field2.'"></td>';
		echo '<tr><td class=datos valign="top">'.$lang_label["field3"];
		echo '<td class=datos><textarea rows=4 style="width:400px" name="field3">'.$al_field3.'</textarea>';
		
		// Max / Min alerts
		echo '<tr>
		<td class="datos2">'.$lang_label["min_alerts"].'</td>';
		echo '<td class="datos2"><input type="text" size=3 name="min" value="'.$min_alerts.'"></td>';
		echo '<tr>
		<td class="datos">'.$lang_label["max_alerts"].'</td>';
		echo '<td class=datos><input type="text" size=3 name="max" value="'.$max_alerts.'"></td>';

        // Time THreshold
		echo '<tr>
		<td class="datos2">'.$lang_label["time_threshold"].'</td>';
		echo '<td class="datos2">';
        echo '<select name="time" style="margin-right: 60px;">';
        if ($time_threshold != ""){ 
            echo "<option value='".$time_threshold."'>".human_time_description($time_threshold)."</option>";
        }
        echo '
        <option value=300>5 Min.</option>
        <option value=600>10 Min.</option>
        <option value=900>15 Min.</option>
        <option value=1800>30 Min.</option>
        <option value=3600>1 Hour</option>
        <option value=7200>2 Hour</option>
        <option value=18000>5 Hour</option>
        <option value=43200>12 Hour</option>
        <option value=86400>1 Day</option>
        <option value=604800>1 Week</option>
        <option value=-1>Other value</option>
        </select>';

        // Priority
        echo '<tr><td class="datos">'.lang_string("Priority");
        echo '<td class="datos">';
        echo form_priority ($priority);

		echo '</tr></table>';
		echo '<table cellpadding="4" cellspacing="4" width="650">
		<tr><td align="right">';
		// Update or Add button
		if ($alert_update != 0) {
			echo '<input name="uptbutton" type="submit" class="sub upd" value="'.$lang_label["update"].'">';
			echo "<input type='hidden' name='update' value='1'>";
			echo "<input type='hidden' name='create' value='0'>";
		} else {
			echo '<input name="createbutton" type="submit" class="sub next" value="'.$lang_label["create"].'">';
			echo "<input type='hidden' name='update' value='0'>";
			echo "<input type='hidden' name='create' value='1'>";
		}
		// Endtable
		echo "</td></tr></table>";
		$view_alert =0; // Do not show alert list
	}

	if ($view_alert == 1) { // View alerts defined on SNMP traps
		
		$sql1='SELECT * FROM talert_snmp';
		$result=mysql_query($sql1);
		
		echo $lang_label["snmp_assigned_alerts"]."</h2>";
		if (mysql_num_rows($result)){

			echo '<table cellpadding="4" cellspacing="4" width="750" class="databox">';
			echo '<tr><th>'.$lang_label["alert"]."</th>";
			echo '<th width=75>'.$lang_label["alert_type"]."</th>";	
			echo '<th>'.$lang_label["SNMP_agent"]."</th>";
			echo '<th>'.$lang_label["OID"]."</th>";
			echo '<th>'.$lang_label["customvalue"]."</th>";
			echo '<th>'.$lang_label["description"]."</th>";
			echo '<th>'.$lang_label["times_fired"]."</th>";
			echo '<th>'.$lang_label["last_fired"]."</th>";
			echo '<th width="50">'.$lang_label["action"]."</th>";
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
				$id_as = $row["id_as"];
				$id_alert = $row["id_alert"];
				$nombre_alerta = dame_nombre_alerta($id_alert);
				$alert_type = $row["alert_type"];
				$agent = $row["agent"];
				$description = $row["description"];
				$oid = $row["oid"];
				$custom_oid = $row["custom_oid"];
				$time_threshold = $row["time_threshold"];
				$al_field1 = $row["al_field1"];
				$al_field2 = $row["al_field2"];
				$al_field3 = $row["al_field3"];
				$last_fired = $row["last_fired"];
				$times_fired = $row["times_fired"];
				$max_alerts = $row["max_alerts"];
				$min_alerts = $row["min_alerts"];
				
				echo "<tr><td class='$tdcolor'>";
				echo $nombre_alerta;
				echo "</td><td class='$tdcolor'>";
				if ($alert_type == 0) {
					$tipo_alerta = $lang_label["OID"];
				} elseif ($alert_type == 1) {
					$tipo_alerta = $lang_label["customvalue"];
				} elseif ($alert_type == 2) {
					$tipo_alerta = $lang_label["SNMP_agent"];
				} else {
					$tipo_alerta = "N/A";
				}
				echo $tipo_alerta;
				echo "</td><td class='$tdcolor'>";
				if ($alert_type == 2) {
					echo $agent;
				} else { 
					echo "N/A";
				}
	
				echo "</td><td class='$tdcolor'>";
				if ($alert_type == 0) {
					echo $oid;
				} else { 
					echo "N/A";
				}
				
				echo "</td><td class='$tdcolor'>";
				if ($alert_type == 1) {
					echo $custom_oid;
				} else { 
					echo "N/A";
				}
				
				echo "</td><td class='$tdcolor'>";
				echo $description;
				
				echo "</td><td class='$tdcolor'>";
				echo $times_fired;
				echo "</td><td class='$tdcolor'>";
				if ($last_fired != "0000-00-00 00:00:00")
					echo $last_fired;
				else
					echo $lang_label["never"];
				echo "</td><td class='$tdcolor'>";
				echo "<a href='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert&delete_alert=".$id_as."'>
				<img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></b></a> &nbsp; ";
				echo "<a href='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert&update_alert=".$id_as."'>
				<img src='images/config.png' border=0 alt='".$lang_label["update"]."'></b></a></td></tr>";
			}
			echo "</table>";
			echo "<table width='750px'>";
			echo "<tr><td align='right'>";
			echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert">';
			echo '<input name="add_alert" type="submit" class="sub next" value="'.$lang_label["create"].'">';
			echo "</form>";
			echo "</td></tr></table>";
		} else {
			echo "<div class='nf'>".$lang_label["no_snmp_alert"]."</div>";
			echo "<br>";
			echo '<form name="agente" method="post" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_alert">';
			echo '<input name="add_alert" type="submit" class="sub next" value="'.$lang_label["create"].'">';
			echo "</form>";
		} // End of view snmp alert
	}
} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access SNMP Alert Management");
		require ("general/noaccess.php");
}

?>
<tr>
</table>