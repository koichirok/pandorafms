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
<script language="JavaScript" type="text/javascript">
<!--
function type_change()
{
	// type 1-4 - Generic_xxxxxx
	if ((document.modulo.tipo.value > 0) && (document.modulo.tipo.value < 5)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#ddd";
		document.modulo.ip_target.disabled=true;
		document.modulo.modulo_max.style.background="#fff";
		document.modulo.modulo_max.disabled=false;
		document.modulo.modulo_min.style.background="#fff";
		document.modulo.modulo_min.disabled=false;
	}
	// type 15-18- SNMP
	if ((document.modulo.tipo.value > 14) && (document.modulo.tipo.value < 19 )){
		document.modulo.snmp_oid.style.background="#fff";
		document.modulo.snmp_oid.style.disabled=false;
		document.modulo.snmp_community.style.background="#fff";
		document.modulo.snmp_community.disabled=false;
		document.modulo.combo_snmp_oid.style.background="#fff";
		document.modulo.combo_snmp_oid.disabled=false;
		document.modulo.oid.disabled=false;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		if (document.modulo.tipo.value == 18) {
			document.modulo.modulo_max.style.background="#ddd";
			document.modulo.modulo_max.disabled=true;
			document.modulo.modulo_min.style.background="#ddd";
			document.modulo.modulo_min.disabled=true;
		} else {
			document.modulo.modulo_max.style.background="#fff";
			document.modulo.modulo_max.disabled=false;
			document.modulo.modulo_min.style.background="#fff";
			document.modulo.modulo_min.disabled=false;
		}
	}
	// type 6-7 - ICMP
	if ((document.modulo.tipo.value == 6) || (document.modulo.tipo.value == 7)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.combo_snmp_oid.style.background="#ddd";
		document.modulo.combo_snmp_oid.disabled=true;
		document.modulo.oid.disabled=true;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#fff";
		document.modulo.modulo_max.disabled=false;
		document.modulo.modulo_min.style.background="#fff";
		document.modulo.modulo_min.disabled=false;
	}
	// type 8-11 - TCP
	if ((document.modulo.tipo.value > 7) && (document.modulo.tipo.value < 12)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;	
		document.modulo.tcp_send.style.background="#fff";
		document.modulo.tcp_send.disabled=false;
		document.modulo.tcp_rcv.style.background="#fff";
		document.modulo.tcp_rcv.disabled=false;
		document.modulo.tcp_port.style.background="#fff";
		document.modulo.tcp_port.disabled=false;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#ddd";
		document.modulo.modulo_max.disabled=true;
		document.modulo.modulo_min.style.background="#ddd";
		document.modulo.modulo_min.disabled=true;
	}
	// type 12 - UDP
	if (document.modulo.tipo.value == 12){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.tcp_send.style.background="#fff";
		document.modulo.tcp_send.disabled=false;
		document.modulo.tcp_rcv.style.background="#fff";
		document.modulo.tcp_rcv.disabled=false;
		document.modulo.tcp_port.style.background="#fff";
		document.modulo.tcp_port.disabled=false;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#ddd";
		document.modulo.modulo_max.disabled=true;
		document.modulo.modulo_min.style.background="#ddd";
		document.modulo.modulo_min.disabled=true;
	}
}
//-->
</script>
<?PHP
// Load global vars
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["update"])){ // Edit mode
	$id_nc = entrada_limpia ($_GET["id_nc"]);
	$sql1 = "SELECT * FROM tnetwork_component where id_nc = $id_nc ORDER BY name";
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	$name = $row["name"];
	$type = $row["type"];
	$description = $row["description"];
	$modulo_max = $row["max"];
	$modulo_min = $row["min"];
	$module_interval = $row["module_interval"];
	$tcp_port = $row["tcp_port"];
	$tcp_rcv = $row["tcp_rcv"];
	$tcp_send = $row["tcp_send"];
	$snmp_community = $row["snmp_community"];
	$snmp_oid = $row["snmp_oid"];
	$id_module_group = $row["id_module_group"];
	$id_group = $row["id_group"];
} elseif (isset($_GET["create"])){
	$id_nc = -1;
	$name = "";
	$snmp_oid = "";
	$description = "";
	$id_group = 1;
	$oid = "";
	$modulo_max = "0";
	$modulo_min = "0";
	$module_interval = "0";
	$tcp_port = "";
	$tcp_rcv = "";
	$tcp_send = "";
	$snmp_community = "public";
	$id_module_group = "";
	$id_group = "";
	$type = 0;
}

echo "<h2>".__('Module component management')."</h2>";
echo '<table width="700" cellspacing="4" cellpadding="4" class="databox_color">';

// Different Form url if it's a create or if it's a update form
if ($id_nc != -1) {
	echo "<form name='modulo' method='post' action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&update=1&id_nc=$id_nc'>";
} else {
	echo "<form name='modulo' method='post' action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&create=1'>";
}
echo "<tr>";
echo '<tr><td class="datos2">'.__('Module name');
echo "<td class='datos2'><input type='text' name='name' size='25' value='$name'>";

//-- Module type combobox
echo "<td class='datos2'>".__('Module type')."</td>";
echo "<td class='datos2'>";
echo '<select name="tipo" onChange="type_change()">';
$sql1="SELECT id_tipo, nombre FROM ttipo_modulo WHERE id_tipo != '$type' ORDER BY nombre";
$result=mysql_query($sql1);
echo "<option value='$type'>". get_moduletype_name ($type);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_tipo"]."'>".$row["nombre"]."</option>";
}
echo "</select>";

echo "</td></tr>";
echo "<tr>";
echo "<td class='datos'>".__('Group')."</td>";
echo "<td class='datos'>";
echo "<select name='id_group'>";
echo "<option value='$id_group'>".give_network_component_group_name($id_group)."</option>";
$sql1 = "SELECT * FROM tnetwork_component_group where id_sg != '$id_group'";
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["id_sg"]."'>".give_network_component_group_name($row["id_sg"])."</option>";
echo "</select>";


echo "<td class='datos'>".__('Module group')."</td>";
echo '<td class="datos">';
echo '<select name="id_module_group">';
if ($id_nc != -1 )
	echo "<option value='".$id_module_group."'>".dame_nombre_grupomodulo($id_module_group);
$sql1='SELECT * FROM tmodule_group';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result))
	echo "<option value='".$row["id_mg"]."'>".$row["name"]."</option>";
echo "</select>";

echo "<tr>";
echo '<td class="datos2">'.__('Module Interval');
echo '<td class="datos2">';
echo '<input type="text" name="module_interval" size="5" value="'.$module_interval.'">';
?>

<td class="datos2"><?php echo __('TCP port') ?></td>
<td class="datos2">
<input type="text" name="tcp_port" size="5" value="<?php echo $tcp_port ?>">
</td></tr>
<tr><td class="datos"><?php echo __('SNMP OID') ?><?php pandora_help("snmpoid"); ?></td>
<td class="datos">
<input type="text" name="snmp_oid" size="25" value="<?php echo $snmp_oid ?>">
</td>
<td class="datos"><?php echo __('SNMP Community') ?></td>
<td class="datos">
<input type="text" name="snmp_community" size="25" value="<?php echo $snmp_community ?>">
</td></tr>
<tr><td class="datos2t"><?php echo __('TCP send') ?></td>
<td class="datos2">
<textarea name="tcp_send" cols="20" rows="2"><?php echo $tcp_send ?></textarea>
</td>
<td class="datos2t"><?php echo __('TCP receive') ?></td>
<td class="datos2">
<textarea name="tcp_rcv" cols="20" rows="2"><?php echo $tcp_rcv ?></textarea>
</td></tr>
<tr><td class="datos"><?php echo __('Minimum Data') ?></td>
<td class="datos">
<input type="text" name="modulo_min" size="5" value="<?php echo $modulo_min ?>">
</td>
<td class="datos"><?php echo __('Maximum Data') ?></td>
<td class="datos">
<input type="text" name="modulo_max" size="5" value="<?php echo $modulo_max ?>">
</td></tr>
<?PHP

echo '<tr><td class="datos2t">'.__('Comments');
echo '<td class="datos2" colspan=3>';
echo '<textarea name="descripcion" cols=70 rows=2>';
echo $description;
echo "</textarea>";
echo "</td></tr>";
echo "</table>";

// Module type, hidden
echo '<input type="hidden" name="id_modulo" value="2">';

echo "<table width='700px'>";
echo "</tr><td align='right'>";
if ($id_nc != "-1")
	echo '<input name="updbutton" type="submit" class="sub upd" value="'.__('Update').'">';
else
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Add').'">';
echo "</form>";
echo "</td></tr></table>";

?>
