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
require ("include/config.php");

check_login();

if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".__('Pandora Agents')." &gt; ";
echo __('Full list of Monitors')."</h2>";

$ag_freestring = get_parameter ("ag_freestring", "");
$ag_modulename = get_parameter ("ag_modulename", "");
$ag_group = get_parameter ("ag_group", -1);
$offset = get_parameter ("offset", 0);
$status = get_parameter ("status", 0);

$URL = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60";
echo "<form method='post' action='";
if ($ag_group != -1)
	$URL .= "&ag_group=".$ag_group;

// Module name selector
// This code thanks for an idea from Nikum, nikun_h@hotmail.com
if ($ag_modulename != "")
    $URL .= "&ag_modulename=".$ag_modulename;

// Freestring selector
if ($ag_freestring != "")
    $URL .= "&ag_freestring=".$ag_freestring ;

// Status selector
$URL .= "&status=$status";

echo $URL;

// End FORM TAG
echo "'>";

echo "<table cellspacing='4' cellpadding='4' width='600' class='databox'>";
echo "<tr><td valign='middle'>".__('Group')."</td>";
echo "<td valign='middle'>";
echo "<select name='ag_group' onChange='javascript:this.form.submit();' class='w130'>";

if ( $ag_group > 1 ){
	echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group)."</option>";
} 
echo "<option value=1>".dame_nombre_grupo(1)."</option>";
list_group ($config['id_user']);
echo "</select>";
echo "</td>";

echo "<td>";
echo __('Monitor status');
echo "<td>";
echo "<select name='status'>";
if ($status == -1){
	echo "<option value=-1>".__('All')."</option>";
	echo "<option value=0>".__('Monitors down')."</option>";
	echo "<option value=1>".__('Monitors up')."</option>";
	echo "<option value=2>".__('Monitors unknown')."</option>";
} elseif ($status == 0){
	echo "<option value=0>".__('Monitors down')."</option>";
	echo "<option value=-1>".__('All')."</option>";
	echo "<option value=1>".__('Monitors up')."</option>";
	echo "<option value=2>".__('Monitors unknown')."</option>";
} elseif ($status == 2){
	echo "<option value=2>".__('Monitors unknown')."</option>";
	echo "<option value=0>".__('Monitors down')."</option>";
	echo "<option value=-1>".__('All')."</option>";
	echo "<option value=1>".__('Monitors up')."</option>";
} else {
	echo "<option value=1>".__('Monitors up')."</option>";
	echo "<option value=0>".__('Monitors down')."</option>";
	echo "<option value=2>".__('Monitors unknown')."</option>";
	echo "<option value=-1>".__('All')."</option>";
}
echo "</select>";

echo "</tr>";
echo "<tr>";
echo "<td valign='middle'>".__('Module name')."</td>";
echo "<td valign='middle'>
<select name='ag_modulename' onChange='javascript:this.form.submit();'>";
if ( isset($ag_modulename)){
	echo "<option>".$ag_modulename."</option>";
} 
echo "<option>".__('All')."</option>";
$sql='SELECT DISTINCT nombre 
FROM tagente_modulo 
WHERE id_tipo_modulo in (2, 6, 9, 18, 21, 100)';
$result=mysql_query($sql);
while ($row=mysql_fetch_array($result)){
	echo "<option>".$row['0']."</option>";
}
echo "</select>";
echo "<td valign='middle'>";
echo __('Free text');
echo "<td valign='middle'>";
echo "<input type=text name='ag_freestring' size=15 value='$ag_freestring'>";
echo "<td valign='middle'>";
echo "<input name='uptbutton' type='submit' class='sub' value='".__('Show')."'";
echo "</form>";
echo "</table>";

// Begin Build SQL sentences

$SQL_pre = "SELECT tagente_modulo.id_agente_modulo, tagente.nombre, tagente_modulo.nombre, tagente_modulo.descripcion, tagente.id_grupo, tagente.id_agente, tagente_modulo.id_tipo_modulo, tagente_modulo.module_interval, tagente_estado.datos, tagente_estado.utimestamp, tagente_estado.timestamp ";

$SQL_pre_count = "SELECT count(tagente_modulo.id_agente_modulo) ";

$SQL = " FROM tagente, tagente_modulo, tagente_estado WHERE tagente.id_agente = tagente_modulo.id_agente AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 AND tagente_modulo.id_tipo_modulo in (2, 9, 12, 18, 6, 100) AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo ";

// Agent group selector
if ($ag_group > 1)
    $SQL .=" AND tagente.id_grupo = ".$ag_group;
else {
	// User has explicit permission on group 1 ?
	$sql = sprintf ("SELECT COUNT(id_grupo) FROM tusuario_perfil WHERE id_usuario='%s' AND id_grupo = 1", $config['id_user']);
	$all_group = get_db_sql ($sql);
	if ($all_group == 0)
		$SQL .= sprintf (" AND tagente.id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='%s') ", $config['id_user']);
}

// Module name selector
// This code thanks for an idea from Nikum, nikun_h@hotmail.com
if ($ag_modulename != "")
	$SQL .= " AND tagente_modulo.nombre = '$ag_modulename'";

// Freestring selector
if ($ag_freestring != "")
	$SQL .= " AND ( tagente.nombre LIKE '%".$ag_freestring."%' OR tagente_modulo.nombre LIKE '%".$ag_freestring."%' OR tagente_modulo.descripcion LIKE '%".$ag_freestring."%') ";

// Status selector
if ($status == 1)
	$SQL .= " AND tagente_estado.estado = 0 ";
elseif ($status == 0)
	$SQL .= " AND tagente_estado.estado = 1 ";
elseif ($status == 2)
	$SQL .= " AND (UNIX_TIMESTAMP()-tagente_estado.utimestamp ) > (tagente_estado.current_interval * 2)";

// Final order
$SQL .= " ORDER BY tagente.id_grupo, tagente.nombre";

// Build final SQL sentences
$SQL_FINAL = $SQL_pre . $SQL;
$SQL_COUNT = $SQL_pre_count . $SQL;

$counter = get_db_sql ($SQL_COUNT);
if ( $counter > $config["block_size"]) {
	pagination ($counter, $URL, $offset);
	$SQL_FINAL .= " LIMIT $offset , ".$config["block_size"];
}


if ($counter > 0) {
	echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>
	<tr>
	<th>
	<th>".__('Agent')."</th>
	<th>".__('Type')."</th>
	<th>".__('Name')."</th>
	<th>".__('Description')."</th>
	<th>".__('Interval')."</th>
	<th>".__('Status')."</th>
	<th>".__('Timestamp')."</th>";
	$color =1;
	$result=mysql_query($SQL_FINAL);

	while ($data=mysql_fetch_array($result)){ //while there are agents
		if ($color == 1){
			$tdcolor="datos";
			$color =0;
		} else {
			$tdcolor="datos2";
			$color =1;
		}
		if ($data[7] == 0){
			$my_interval = give_agentinterval($data[5]);
		} else {
			$my_interval = $data[7];
		}
		
		if ($status == 2) {
			$seconds = time() - $data[9];
			
			if ($seconds < ($my_interval*2))
				continue;
		}

		echo "<tr><td class='$tdcolor'>";
		echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$data["id_agente"]."&id_agente_modulo=".$data[0]."&flag=1&tab=data&refr=60'>";
		echo "<img src='images/target.png'></a>";
		echo "</td><td class='$tdcolor'>";
		echo "<strong><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$data[5]."'>".strtoupper(substr($data[1],0,21))."</a></strong>";
		echo "</td><td class='$tdcolor'>";
		echo "<img src='images/".show_icon_type($data[6])."' border=0></td>";
		echo "<td class='$tdcolor'>". substr($data[2],0,21). "</td>";
		echo "<td class='".$tdcolor."f9' title='".$data[3]."'>".substr($data[3],0,30)."</td>";
		echo "<td class='$tdcolor' align='center' width=25>";
		echo $my_interval;

		echo "<td class='$tdcolor' align='center' width=20>";
		if ($data[8] > 0){
			echo "<img src='images/pixel_green.png' width=40 height=18 title='".__('Monitor up')."'>";
		} else {
			echo "<img src='images/pixel_red.png' width=40 height=18 title='".__('Monitor down')."'>";
		}

		echo  "<td class='".$tdcolor."f9'>";
		$seconds = time() - $data[9];
		if ($seconds >= ($my_interval*2))
			echo "<span class='redb'>";
		else
		echo "<span>";

		echo  human_time_comparation ($data[10]);
		echo  "</span></td></tr>";
	}
	echo "</table>";
} else {
	echo "<div class='nf'>".__('This group doesn\'t have any monitor')."</div>";
}

echo "<table width=700 border=0>";
echo "<tr>";
echo "<td class='f9'>";
echo "<img src='images/pixel_green.png' width=40 height=18>&nbsp;&nbsp;".__('Monitor up')."</td>";
echo "<td class='f9'";
echo "<img src='images/pixel_red.png' width=40 height=18>&nbsp;&nbsp;".__('Monitor down')."</td>";
echo "</table>";

?>
