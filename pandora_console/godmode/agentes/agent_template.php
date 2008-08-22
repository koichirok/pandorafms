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

// Access control
if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	return;
}

// ==========================
// TEMPLATE ASSIGMENT LOGIC
// ==========================
if (isset($_POST["template_id"])) {
	// Take agent data
	$sql = "SELECT * FROM tagente WHERE id_agente = '.$id_agente.'";
	$result = mysql_query ($sql);
	if ($row = mysql_fetch_array ($result)) {
		$intervalo = $row["intervalo"]; 
		$nombre_agente = $row["nombre"];
		$direccion_agente =$row["direccion"];
		$ultima_act = $row["ultimo_contacto"];
		$ultima_act_remota =$row["ultimo_contacto_remoto"];
		$comentarios = $row["comentarios"];
		$id_grupo = $row["id_grupo"];
		$id_os= $row["id_os"];
		$os_version = $row["os_version"];
		$agent_version = $row["agent_version"];
		$disabled= $row["disabled"];
	}

	$id_np = $_POST["template_id"];
	$sql1 = "SELECT * FROM tnetwork_profile_component
		WHERE id_np = $id_np";
	$result = mysql_query ($sql1);
	while ($row = mysql_fetch_array ($result)){
		$sql = "SELECT * FROM tnetwork_component
			WHERE id_nc = ".$row["id_nc"];
		$result2 = mysql_query ($sql);
		while ($row2 = mysql_fetch_array ($result2)) {
			// Insert each module from tnetwork_component into agent
			$module_sql = "INSERT INTO tagente_modulo
			(id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, tcp_port, tcp_send, tcp_rcv, snmp_community, snmp_oid, ip_target, id_module_group, id_modulo, plugin_user, plugin_pass, plugin_parameter, max_timeout)
			VALUES ( $id_agente,
			'".$row2["type"]."',
			'".$row2["description"]."',
			'".$row2["name"]."',
			'".$row2["max"]."',
			'".$row2["min"]."',
			'".$row2["module_interval"]."',
			'".$row2["tcp_port"]."',
			'".$row2["tcp_send"]."',
			'".$row2["tcp_rcv"]."',
			'".$row2["snmp_community"]."',
			'".$row2["snmp_oid"]."',
			'$direccion_agente',
			'".$row2["id_module_group"]."',
			'".$row2["id_modulo"]."',
			'".$row2["plugin_user"]."',
			'".$row2["plugin_pass"]."',
			'".$row2["plugin_parameter"]."',
			'".$row2["max_timeout"]."'
			)";
			mysql_query ($module_sql);
			$id_agente_modulo = mysql_insert_id();
			
			// Create with different estado if proc type or data type
			$id_tipo_modulo = $row2["type"];
			if (($id_tipo_modulo == 2) || 
			($id_tipo_modulo == 6) || 
			($id_tipo_modulo == 9) || 
			($id_tipo_modulo == 12) || 
			($id_tipo_modulo == 18)) {
			
				$sql = "INSERT INTO tagente_estado 
				(id_agente_modulo,datos,timestamp,cambio,estado,id_agente, utimestamp) 
				VALUES (
				$id_agente_modulo, 0,'0000-00-00 00:00:00',0,0,'".$id_agente."',0
				)";
			} else { 
				$sql = "INSERT INTO tagente_estado 
				(id_agente_modulo,datos,timestamp,cambio,estado,id_agente, utimestamp) 
				VALUES (
				$id_agente_modulo, 0,'0000-00-00 00:00:00',0,100,'".$id_agente."',0
				)";
			}
			mysql_query ($sql);
			$sql = "";
		}
	}
	echo "<h3 class='suc'>".__('Modules successfully added ')."</h3>";
}

// Main header

echo "<h2>".__('Agent configuration')." &gt; ".__('Module templates');
echo "</h2>";

// ==========================
// TEMPLATE ASSIGMENT FORM
// ==========================

echo "<h3>".__('Available templates')."</h3>";
echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=template&id_agente=$id_agente'>";

echo "<table width='300' class='databox' cellpadding='4' cellspacing='4'>";
echo "<tr><td>".__('Template')."</td><td valign='middle'>";

echo "<select name='template_id' class='w130'>";
$sql = 'SELECT * FROM tnetwork_profile ORDER BY name';
$result = mysql_query ($sql);
if (mysql_num_rows ($result))
	while ($row = mysql_fetch_array($result))
		echo "<option value='".$row["id_np"]."'>".$row["name"]."</option>";
echo "</select></td>";

echo "<td>";
echo "<input type='submit' class='sub next' name='crt' value='".__('Assign')."'>";
echo "</table>";
echo "</form>";

// ==========================
// MODULE VISUALIZATION TABLE
// ==========================
echo "<h3>".__('Assigned modules')."</h3>";

$sql = 'SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"
	ORDER BY id_module_group, nombre ';
$result = mysql_query($sql);
if ($row = mysql_num_rows ($result)) {
	echo '<table width="700" cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr>';
	echo "<th>".__('Module name')."</th>";
	echo "<th>".__('Type')."</th>";
	echo "<th>".__('Description')."</th>";
	echo "<th width=50>".__('Action')."</th>";
	$color=1;
	$last_modulegroup = "0";
	while ($row = mysql_fetch_array ($result)) {
		if ($color == 1) {
			$tdcolor="datos";
			$color =0;
		} else {
			$tdcolor="datos2";
			$color =1;
		}
		$id_tipo = $row["id_tipo_modulo"];
		$nombre_modulo =$row["nombre"];
		$descripcion = $row["descripcion"];
		echo "<tr><td class='".$tdcolor."_id'>".$nombre_modulo;
		echo "<td class='".$tdcolor."f9'>";
		if ($id_tipo) {
			echo "<img src='images/".show_icon_type($id_tipo)."' border=0>";
		}
		echo "<td class='$tdcolor' title='$descripcion'>".substr($descripcion,0,30)."</td>";
		echo "<td class='$tdcolor'>";
		if ($id_tipo != -1)
			echo "<a href='index.php?sec=gagente&
			tab=module&
			sec2=godmode/agentes/configurar_agente&tab=template&
			id_agente=".$id_agente."&
			delete_module=".$row["id_agente_modulo"]."'>
			<img src='images/cross.png' border=0 alt='".__('Delete')."'>
			</b></a> &nbsp; ";
		echo "<a href='index.php?sec=gagente&
		sec2=godmode/agentes/configurar_agente&
		id_agente=".$id_agente."&
		tab=module&
		update_module=".$row["id_agente_modulo"]."#modules'>
		<img src='images/config.png' border=0 alt='".__('Update')."' onLoad='type_change()'></b></a>";
	}
	echo "</td></tr>";
	echo "</table>";
} else {
	echo "<div class='nf'>No modules</div>";
}

?>
