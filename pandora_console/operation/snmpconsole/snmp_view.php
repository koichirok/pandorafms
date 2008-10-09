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

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SNMP Console");
	require ("general/noaccess.php");
	return;
}
?>

<script language="JavaScript" type="text/javascript">
<!--
function CheckAll() {
	for (var i = 0; i < document.eventtable.elements.length; i++) {
		var e = document.eventtable.elements[i];
		if (e.type == 'checkbox' && e.name != 'allbox')
			e.checked = 1;
	}
}

function OpConfirm (text, conf) {
	for (var i = 0; i < document.pageform.elements.length; i++) {
		var e = document.pageform.elements[i];
		if (e.type == 'checkbox' && e.name != 'allbox' && e.checked == 1 ) {
			if (conf) {
				return confirm(text);
			} else {
				return 1;
			}
		}
	}
	return false;
}
//-->
</script>

<?php
// Var init
$accion = "";
$offset_counter =0;

// OPERATIONS

// Delete SNMP Trap entryEvent (only incident management access).
if (isset($_GET["delete"])){
	$id_trap = $_GET["delete"];
	if (give_acl ($config['id_user'], 0, "IM")) {
		$sql2="DELETE FROM ttrap WHERE id_trap =".$id_trap;
		$result2=mysql_query($sql2);
		if ($result) { echo "<h3 class='suc'>".__('Event successfully deleted')."</h3>";}
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to delete event ID".$id_trap);
	}
}

// Check Event (only incident write access).
if (isset($_GET["check"])){
	$id_trap = $_GET["check"];
	if (give_acl ($config['id_user'], 0, "IW")) {
		$sql2="UPDATE ttrap set status=1, id_usuario = '".$config['id_user']."' WHERE id_trap = ".$id_trap;
		$result2=mysql_query($sql2);
		if ($result2) { echo "<h3 class='suc'>".__('Event successfully validated')."</h3>";}

	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to checkout SNMP Trap ID".$id_trap);
	}
}

// Mass-process DELETE
if (isset($_POST["deletebt"])){
	$count=0;
	if (give_acl ($config['id_user'], 0, "IW")) {
		while ($count <= $config["block_size"]) {
			if (isset($_POST["snmptrapid".$count])){
				$trap_id = $_POST["snmptrapid".$count];
				mysql_query("DELETE FROM ttrap WHERE id_trap =".$trap_id);
			}
			$count++;
		}
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to mass-delete SNMP Trap ID");
	}
}

// Mass-process UPDATE
if (isset($_POST["updatebt"])){
	$count=0;
	if (give_acl ($config['id_user'], 0, "IW")) {
		while ($count <= $config["block_size"]){
			if (isset($_POST["snmptrapid".$count])){
				$id_trap = $_POST["snmptrapid".$count];
				$sql2="UPDATE ttrap SET status=1, id_usuario = '".$config['id_user']."' WHERE status = 0 and id_trap = ".$id_trap;
				$result2=mysql_query($sql2);
			}
			$count++;
		}
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to mass-validate SNMP Trap ID");
	}
}
echo "<h2>Pandora SNMP &gt; ";
echo __('SNMP console')."</h2>";

$offset = get_parameter ('offset');

$sql2="SELECT * FROM ttrap ORDER BY timestamp DESC";
$result2=mysql_query($sql2);

if (mysql_num_rows ($result2) == 0) {
	echo "<div class='nf'>".__('There are no SNMP traps in database')."</div>";
	return;
}

echo "<table border=0 width=600><tr>";
echo "<td class='f9' style='padding-left: 30px;'>";
echo "<img src='images/pixel_green.png' width=20 height=20> - ".__('Validated event');
echo "<br>";
echo "<img src='images/pixel_red.png' width=20 height=20> - ".__('Not validated event');
//echo "<br>";
//echo "<img src='images/pixel_yellow.png' width=20 height=35> - ".__('Alert');
echo "</td>";
echo "<td class='f9' style='padding-left: 20px;'>";  
echo "<img src='images/ok.png'> - ".__('Validate event');
echo "<br>"; 
echo "<img src='images/cross.png '> - ".__('Delete event');
echo "</td>";
echo "</tr></table>";
echo "<br>";

// Prepare index for pagination
$trap_list[]="";

while ($row2=mysql_fetch_array($result2)){ // Jump offset records
	$trap_list[]=$row2["id_trap"];
}

$total_traps = count ($trap_list);
pagination ($total_traps, "index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view", $offset);

echo "<br>";
echo "<table cellpadding='4' cellspacing='4' width='735' class='databox'>";
echo "<tr>";
echo "<th>".__('Status')."</th>";
echo "<th>".__('SNMP Agent')."</th>";
echo "<th>".__('OID')."</th>";
echo "<th>".__('Value')."</th>";
echo "<th>".__('Custom')."</th>";
echo "<th>".__('User ID')."</th>";
echo "<th width ='130px'>".__('Timestamp')."</th>";
echo "<th>".__('Alert')."</th>";
echo "<th>".__('Action')."</th>";
echo "<th class='p10'>";
echo "<label for='checkbox' class='p21'>".__('All')." </label>";
echo '<input type="checkbox" class="chk" name="allbox" onclick="CheckAll();">
</th>';
echo "<form name='eventtable' method='POST' action='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&refr=60&offset=".$offset."'>";	

$id_trap = 0;
$color = 0;
if ($offset != 0)
	$offset_limit = $offset + 1;
else
	$offset_limit = $offset;
// Skip offset records
for ($a=$offset_limit;$a < ($config["block_size"] + $offset + 1);$a++){
	if (isset($trap_list[$a])){
		$id_trap = $trap_list[$a];
		$sql="SELECT * FROM ttrap WHERE id_trap = $id_trap";
		if ($result=mysql_query($sql)){
			$trap=mysql_fetch_array($result);
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			$offset_counter++;
			echo "<tr>";
			echo "<td class='$tdcolor' align='center'>";
			if ($trap["status"] == 0){
				echo "<img src='images/pixel_red.png' width=20 height=20>";
			}
			else {
				echo "<img src='images/pixel_green.png' width=20 height=20>";
			}
			echo "</td>";


			// Agent ID
			$agent = get_db_row ('tagente', 'direccion', $trap['source']);
			if ($agent) {
				echo "<td class='$tdcolor'>
				<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$agent["id_agente"]."'>
				<strong>".$agent['nombre']."</strong></a></td>";
			} else {
				echo "<td class='$tdcolor'>".$trap["source"]."</td>";
			}
	
			// OID
			echo "<td class='$tdcolor'>".$trap["oid"];
			
			
			// Value
			$value = substr ($trap["value"], 0, 15);
			if (strlen ($trap["value"]) > 15)
				$value .= "...";
			echo "<td title='".$trap["value"]."' class='$tdcolor'>".$value."</td>";

			// CUSTOM
			$custom = substr ($trap["value_custom"], 0, 15);
			if (strlen ($trap["value_custom"]) > 15)
				$custom .= "...";
			echo "<td title='".$trap["value_custom"]."' class='$tdcolor'>".$custom."</td>";

			// User 
			echo "<td class='$tdcolor'>";
			if ($trap["status"])
				echo "<a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$trap["id_usuario"]."'><a href='#' class='tip'>&nbsp;<span>".dame_nombre_real($trap["id_usuario"])."</span></a>".substr($trap["id_usuario"],0,8)."</a>";
			echo "</td>";

			// Timestamp
			echo "<td class='$tdcolor'>".$trap["timestamp"]."</td>";

			// Alerted ?
			echo "<td class='$tdcolor' align='center'>";
			if ($trap["alerted"])
				echo "<img src='images/pixel_yellow.png' width=40 height=18 border=0>";
			echo "</td>";

			// Delete and ACK
			echo "<td class='$tdcolor' align='center'>";
			if ($trap["status"] == 0 && give_acl ($config['id_user'],"0","IW"))
				echo "<a href='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&check=".$trap["id_trap"]."'><img src='images/ok.png' border='0'></a>";
			if (give_acl ($config['id_user'], "0", "IM"))
				echo "<a href='index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&delete=".$trap["id_trap"]."&refr=60&offset=".$offset."'><img src='images/cross.png' border=0></a>";
			echo "</td>";

			echo "<td class='$tdcolor' align='center'>";
			echo "<input type='checkbox' class='chk' name='snmptrapid".$offset_counter."' value='".$trap["id_trap"]."'>";
			echo "</td></tr>";
		}
	}
}
echo "</table>";
$offset_counter = 0;
echo "<table width='750px'><tr><td align='right'>";

echo "<input class='sub ok' type='submit' name='updatebt' value='".__('Validate')."'> ";
if (give_acl ($config['id_user'], 0, "IM")) {
	echo "<input class='sub delete' type='submit' name='deletebt' value='".__('Delete')."'>";
}
echo "</form></td></tr></table>";
?>
