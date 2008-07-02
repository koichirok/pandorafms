<?php
// Pandora FMS
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas S.L, info@artica.es

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
global $config;
$id_user = $config["id_user"];


if (comprueba_login() != 0) {
        require ("general/noaccess.php");
        exit;
}

if ((give_acl($id_user, 0, "AR")!=1) AND (give_acl($id_user,0,"AW")!=1)) {
        audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
        "Trying to access SLA View");
        require ("general/noaccess.php");
        exit;
}


require ("include/functions_reporting.php");


	echo "<h2>".lang_string("SLA view")."</h2>";
	$id_agent = get_parameter ("id_agente", "0");

	// Get all module from agent
	$sql_t='SELECT * FROM tagente_estado, tagente_modulo WHERE tagente_modulo.disabled = 0 AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_agente='.$id_agent.' AND tagente_estado.estado != 100 AND tagente_estado.utimestamp != 0 ORDER BY tagente_modulo.nombre';
	$result_t=mysql_query($sql_t);
	if (mysql_num_rows ($result_t)) {
		echo "<h3>".lang_string ("Automatic SLA for monitors")."</h3>";
		echo "<table width='750' cellpadding=4 cellspacing=4 class='databox'>";
		echo "<tr><th>X</th>";
        echo "<th>".$lang_label["type"]."</th>
		<th>".$lang_label["module_name"]."</th>
		<th>".$lang_label["SLA"]."</th>
		<th>".$lang_label["status"]."</th>
		<th>".$lang_label["interval"]."</th>
		<th>".$lang_label["last_contact"]."</th>";
		$color=0;
		while ($row_t=mysql_fetch_array($result_t)){
			# For evey module in the status table
			$est_modulo = substr($row_t["nombre"],0,25);
			$est_tipo = dame_nombre_tipo_modulo($row_t["id_tipo_modulo"]);
			$est_description = $row_t["descripcion"];
			$est_timestamp = $row_t["timestamp"];
			$est_estado = $row_t["estado"];
			$est_datos = $row_t["datos"];
			$est_cambio = $row_t["cambio"];
			$est_interval = $row_t["module_interval"];
			if (($est_interval != $intervalo) && ($est_interval > 0)) {
				$temp_interval = $est_interval;
			} else {
				$temp_interval = $intervalo;
			}
			if ($est_estado <>100){ # si no es un modulo de tipo datos
				# Determinamos si se ha caido el agente (tiempo de intervalo * 2 superado)
				if ($color == 1){
					$tdcolor = "datos";
					$color = 0;
				}
				else {
					$tdcolor = "datos2";
					$color = 1;
				}
				$seconds = time() - $row_t["utimestamp"];
				if ($seconds >= ($temp_interval*2)) // If every interval x 2 secs. we get nothing, there's and alert
					$agent_down = 1;
				else
					$agent_down = 0;
			
				echo "<tr><td class='".$tdcolor."'>";

                if (($row_t["id_modulo"] != 1) AND ($row_t["id_tipo_modulo"] < 100)) {
                    if ($row_t["flag"] == 0){
                        echo "<a href='index.php?sec=estado& sec2=operation/agentes/ver_agente& id_agente=".$id_agente."&id_agente_modulo=".$row_t["id_agente_modulo"]."&flag=1& tab=main&refr=60'><img src='images/target.png' border='0'></a>";
                    } else {
                        echo "<a href='index.php?sec=estado& sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&id_agente_modulo=".$row_t["id_agente_modulo"]."&tab=main&refr=60'><img src='images/refresh.png' border='0'></a>";
                    }
                }
				echo "<td class='".$tdcolor."'>";
				echo "<img src='images/".show_icon_type($row_t["id_tipo_modulo"])."' border=0>";	
				echo "<td class='".$tdcolor."' title='".$est_description."'>".$est_modulo."</td>";
				echo "<td class='$tdcolor'>";

				$temp = get_agent_module_sla ($row_t["id_agente_modulo"], $config["sla_period"], 1, 2147483647);
				if ($temp === false)
					echo lang_string("N/A");
				else {
					echo format_numeric($temp)." %</td>";;
				}
				
				

				echo "<td class='".$tdcolor."' align='center'>";
				if ($est_estado == 1){
					if ($est_cambio == 1) 
						echo "<img src='images/pixel_yellow.png' width=40 height=18 title='".$lang_label["yellow_light"]."'>";
					else
						echo "<img src='images/pixel_red.png' width=40 height=18 title='".$lang_label["red_light"]."'>";
				} else
					echo "<img src='images/pixel_green.png' width=40 height=18 title='".$lang_label["green_light"]."'>";

				echo "<td align='center' class='".$tdcolor."'>";
				if ($temp_interval != $intervalo)
					echo $temp_interval."</td>";
				else
					echo "--";
				echo  "<td class='".$tdcolor."f9'>";
				if ($agent_down == 1) { // If agent down, it's shown red and bold
					echo  "<span class='redb'>";
				}
				else {
					echo "<span>";
				}
				if ($row_t["timestamp"]=='0000-00-00 00:00:00') {
					echo $lang_label["never"];
				} else {
					echo human_time_comparation($row_t["timestamp"]);
				}
				echo "</span></td>";
			}
		}
		echo '</table>';

	} 


	// Get all SLA report components
	$sql_t = "SELECT tagente_modulo.id_agente_modulo, sla_max, sla_min, sla_limit, tagente_modulo.id_tipo_modulo, tagente_modulo.nombre, tagente_modulo.descripcion FROM treport_content_sla_combined, tagente_modulo WHERE tagente_modulo.id_agente = $id_agent AND tagente_modulo.id_agente_modulo = treport_content_sla_combined.id_agent_module AND tagente_modulo.id_tipo_modulo IN (1,4,7,8,11,15,16,22,24)";

	$result_t=mysql_query($sql_t);
	if (mysql_num_rows ($result_t)) {
		echo "<h3>".lang_string ("User-defined SLA items")."</h3>";
		echo "<table width='750' cellpadding=4 cellspacing=4 class='databox'>";
		echo "<tr>";
        echo "<th>".$lang_label["type"]."</th>
		<th>".$lang_label["module_name"]."</th>
		<th>".$lang_label["SLA"]."</th>
		<th>".$lang_label["status"]."</th>";
		$color=0;
		while ($row_t=mysql_fetch_array($result_t)){

			# For evey module in the status table
			$id_agent_module = $row_t[0];
			$sla_max = $row_t[1];
			$sla_min = $row_t[2];
			$sla_limit = $row_t[3];
			$id_tipo_modulo = $row_t[4];
			$name = $row_t[5];
			$description = $row_t[6];
			$est_tipo = dame_nombre_tipo_modulo($id_tipo_modulo);
			
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";	
			echo "<td class='".$tdcolor."'>";
			echo "<img src='images/".show_icon_type($id_tipo_modulo)."' border=0>";	
			echo "<td class='".$tdcolor."' title='".$description."'>".$name;
			echo " ($sla_min/$sla_max/$sla_limit) </td>";
			echo "<td class='$tdcolor'>";

			$temp = get_agent_module_sla ($row_t["id_agente_modulo"], $config["sla_period"], $sla_min, $sla_max);
			if ($temp === false){
				echo lang_string("N/A");
				echo "<td class='$tdcolor'>";
			} else {
				echo format_numeric($temp)." %</td>";
				echo "<td class='$tdcolor'>";
				if ($temp > $sla_limit)
					echo "<img src='images/pixel_green.png' width=40 height=18 title='".$lang_label["green_light"]."'>";
				else
					echo "<img src='images/pixel_red.png' width=40 height=18 title='".$lang_label["red_light"]."'>";

			}
		}
		echo '</table>';
	}

?>
