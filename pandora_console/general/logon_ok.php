<?php

// Pandora FMS
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas, info@artica.es
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

	require("include/functions_reporting.php");
	
	echo "<div class='jus'>";
	$nick = $_SESSION['id_usuario'];
	echo "<h1>" . $lang_label["welcome_title"] . "</h1>";
	echo "<p>";
	echo $lang_label["main_text"];
	echo "</p>";

	// Private messages pending to read !

	$sql='SELECT COUNT(*) FROM tmensajes WHERE id_usuario_destino="'.$nick.'" 
	AND estado="FALSE";';
	$resultado = mysql_query ($sql);
	$row = mysql_fetch_array ($resultado);
	if ($row["COUNT(*)"] != 0){
		echo "<h2>". $lang_label["new_message_bra"] . ' 
		<a href="index.php?sec=messages&sec2=operation/messages/message">'
		.$row["COUNT(*)"] . ' <img src="images/email.png" border="0">'
		.$lang_label["new_message_ket"] . '</a></h2>';
	}

	echo "<table width=95%>";
	echo "<tr><td valign='top'>";

	// Site news !
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	echo '<h2>' . $lang_label["site_news"] . '</h2>';
	$sql_news = "SELECT * FROM tnews ORDER by timestamp DESC LIMIT 3";
	if ($result_news = mysql_query ($sql_news)){
		echo '<table cellpadding="4" cellspacing="4" width="270" class="databox">';
		while ($row = mysql_fetch_array ($result_news)) {
			echo '<tr>';
			echo "<th><b>".$row["subject"]."</b>";
			echo "<tr><td>".$lang_label["by"]." <b>".$row["author"]. "</b> ".$lang_label["at"]." <i>".$row["timestamp"]."</i>";
			echo '<tr><td class=datos>';
			echo clean_output_breaks($row["text"]);
		}
		echo "</table>";
	}
	echo "<td align='center'>";
	// Site stats
	// Summary
	// ~~~~~~~~~~~~~~~
	$data = general_stats($id_user,0);
	$monitor_checks = $data[0];
	$monitor_ok = $data[1];
	$monitor_bad = $data[2];
	$monitor_unknown = $data[3];
	$monitor_alert = $data[4];
	$total_agents = $data[5];
	$data_checks = $data[6];
	$data_unknown = $data[7];
	$data_alert = $data[8];
	$data_alert_total = $data[9];
	$monitor_alert_total = $data[10];
    $data_not_init = $data[11];
    $monitor_not_init = $data[12];
	// Calculate global indicators

    $total_checks = $data_checks + $monitor_checks;
    $notinit_percentage = (($data_not_init + $monitor_not_init) / ($total_checks / 100));
    $module_sanity = format_numeric (100 - $notinit_percentage);
    $total_alerts = $data_alert + $monitor_alert;
    $total_fired_alerts = $monitor_alert_total+$data_alert_total;
	if ( $total_fired_alerts > 0)
    	$alert_level = format_numeric (100 - ($total_alerts / ($total_fired_alerts / 100)));
	else
		$alert_level = 100;
    
    if ($monitor_checks > 0){
        $monitor_health = format_numeric (  100- (($monitor_bad + $monitor_unknown) / ($monitor_checks/100)) , 1);
    } else 
        $monitor_health = 100;
    if ($data_checks > 0){
        $data_health = format_numeric ( (($data_checks -($data_unknown + $data_alert)) / $data_checks ) * 100,1);;
    } else
        $data_health = 100;
    if (($data_checks != 0) OR ($data_checks != 0)){
        $global_health = format_numeric ((($data_health * $data_checks) + ($monitor_health * $monitor_checks)) / $total_checks);
    } else
        $global_health = 100;

    echo "<table class='databox' celldpadding=4 cellspacing=4 width=250>";
    //echo "<h2>".$lang_label["tactical_indicator"]."</h2>";
    //echo "<img src='reporting/fgraph.php?tipo=odo_tactic&value1=$global_health&value2=$data_health&value3=$monitor_health'>";
    
    echo "<tr><td colspan='2'>".lang_string("Monitor health")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$monitor_health' title='$monitor_health % ".lang_string("of monitors UP")."'>";

    echo "<tr><td colspan='2'>".lang_string("Data health")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$data_health' title='$data_health % ".lang_string("of modules with updated data")."'>";

    echo "<tr><td colspan='2'>".lang_string("Global health")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$global_health' title='$global_health % ".lang_string("of modules with good data")."'>";

    echo "<tr><td colspan='2'>".lang_string("Module sanity")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$module_sanity ' title='$module_sanity % ".lang_string("of well initialized modules")."'>";
   

    echo "<tr><td colspan='2'>".lang_string("Alert level")."</th>";
    echo "<tr><td colspan='2'><img src='reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=$alert_level' title='$alert_level % ".lang_string("of non-fired alerts")."'>";
    echo "</table>";

	$query1 = "SELECT COUNT(id_usuario) FROM tusuario";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	$users_defined = $row[0];
	
	echo "<table class='databox' celldpadding=4 cellspacing=4 width=250>";
	echo "<th colspan=2>".$lang_label["Pandora_FMS_summary"]."</th>";
	echo "<tr><td class='datos2'><b>"."Total agents"."</b></td>";
	echo "<td class='datos2' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$total_agents."</td>";
	echo "</tr><tr><td class='datos'><b>"."Total checks"."</b></td>";
	echo "<td class='datos' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$total_checks."</td>";	
	echo "</tr><tr><td class='datos2'><b>"."Monitor BAD"."</b></td>";
	echo "<td class='datos2' style='font: bold 2em Arial, Sans-serif; color: #f00;'>";
	if ($monitor_bad > 0)
		echo $monitor_bad;
	else
		echo "-";
	echo "</td></tr><tr><td class='datos'><b>"."Alerts defined"."</b></td>";
	echo "<td class='datos' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$total_alerts."</td>";
	echo "</tr><tr><td class='datos2'><b>"."Total users"."</b></td>";
	echo "<td class='datos2' style='font: bold 2em Arial, Sans-serif; color: #000;'>".$users_defined."</td>";
	echo "</tr></table>";

	echo "</table>";

	echo "<div id='activity'>";
	// Show last activity from this user
	echo "<h2>" . $lang_label["user_last_activity"] . "</h2>";
	// Show table header
	echo '<table cellpadding="4" cellspacing="4" width="700" class="databox"><tr>'; 
	echo '<th>' . $lang_label["user"] . '</th>';
	echo '<th>' . $lang_label["action"] . '</th>';
	echo '<th width="130px">' . $lang_label["date"] . '</th>';
	echo '<th>' . $lang_label["src_address"] . '</th>';
	echo '<th width="200px">' . $lang_label["comments"] . '</th></tr>';

	// Skip offset records
	$query1="SELECT * FROM tsesion WHERE (TO_DAYS(fecha) > TO_DAYS(NOW()) - 7) 
	AND ID_usuario = '" . $nick . "' ORDER BY fecha DESC limit 15";

	$result = mysql_query ($query1);
	$contador = 5; // Max items
	$color = 1;
	while (($row = mysql_fetch_array ($result)) and ($contador > 0)) {
		
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		} else {
			$tdcolor = "datos2";
			$color = 1;
		}
		
		$usuario = $row["ID_usuario"];
		echo '<tr>';
		echo '<td class="' . $tdcolor . 'f9"><b>' . $usuario . '</b></td>';
		echo '<td class="' . $tdcolor . 'f9">' . $row["accion"]. '</td>';
		echo '<td class="' . $tdcolor . 'f9">' . $row["fecha"]. '</td>';
		echo '<td class="' . $tdcolor . 'f9">' . $row["IP_origen"]. '</td>';
		echo '<td class="' . $tdcolor . 'f9">' . $row["descripcion"]. '</td>';
		echo '</tr>';
		
		$contador--;
	}

	echo "</table>";
	echo "</div>"; // activity

	echo '</div>'; // class "jus"
?>
