<?php

// Pandora FMS 
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2008 Artica Soluciones Tecnoloicas S.L, info@artica.es

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

comprueba_login();

$id_user = $_SESSION["id_usuario"];
$origen = get_parameter ("origen", -1);
$id_group = get_parameter ("id_group", -1);
$update_agent = get_parameter ("update_agent", -1);
$update_group = get_parameter ("update_group", -1);

if ( (give_acl($id_user, 0, "LM")==0) AND (give_acl($id_user, 0, "AW")==0) ){
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Config Management Admin section");
	require ("general/noaccess.php");
}
		
// Operations
// ---------------
if ((isset($_GET["operacion"])) AND ($update_agent == -1) AND ($update_group == -1) ) {

	// DATA COPY
	// ---------
	if (isset($_POST["copy"])) {
		echo "<h2>".$lang_label["datacopy"]."</h2>";
		// Initial checkings

		// if selected more than 0 agents
		$destino = $_POST["destino"];
		if (count($destino) <= 0){
			echo "<h3 class='error'>ERROR: ".$lang_label["noagents_cp"]."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}

		// Source
		$id_origen = $_POST["origen"];
	
		// Copy files
		for ($a=0;$a <count($destino); $a++){ 
			// For every agent in destination

			$id_agente = $destino[$a];
			$agent_name_src = dame_nombre_agente($id_origen);
			$agent_name_dst = dame_nombre_agente($id_agente);
			echo "<br><br>".$lang_label["copyage"]."<b> [".$agent_name_src."] -> [".$agent_name_dst."]</b>";
			
			$source = $config["remote_config"]."/".md5($agent_name_src);
			$destination = $config["remote_config"]."/".md5($agent_name_dst);
			copy  ( $source.".md5", $destination.".md5" );
			copy  ( $source.".conf", $destination.".conf" );			
		} // for each destination agent
	} //end if copy modules or alerts


	// ============	
	// Form view
	// ============
	} else { 
		
		// title
		echo '<h2>'.lang_string ("agent_conf"). '&gt;'. lang_string ("config_manage").'</h2>';
		echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/manage_config_remote&operacion=1">';
		echo "<table width='650' border='0' cellspacing='4' cellpadding='4' class='databox'>";
		
		// Source group
		echo '<tr><td class="datost"><b>'. lang_string ("Source group"). '</b><br><br>';
		echo '<select name="id_group" style="width:200px">';
		if ($id_group != 0)
			echo "<option value=$id_group>".dame_nombre_grupo ($id_group);
		echo "<option value=0>".lang_string ("All");
		list_group ($config["id_user"]);
		echo '</select>';
		echo '&nbsp;&nbsp;';
		echo '<input type=submit name="update_group" class="sub upd"  value="'.lang_string("Filter").'">';
		echo '<br><br>';

		// Source agent
		echo '<b>'. lang_string ("source_agent").'</b><br><br>';

		// Show combo with SOURCE agents
		if ($id_group != 0)
			$sql1 = "SELECT * FROM tagente WHERE id_grupo = $id_group ORDER BY nombre ";
		else
			$sql1 = 'SELECT * FROM tagente ORDER BY nombre';
		echo '<select name="origen" style="width:200px">';			
		if (($update_agent != 1) AND ($origen != -1))
			echo "<option value=".$_POST["origen"].">".dame_nombre_agente($origen)."</option>";
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if (give_acl ($config["id_user"], $row["id_grupo"], "AR")){
				if ( $origen != $row["id_agente"])
					echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
			}
		}
		echo '</select>';

		echo '&nbsp;&nbsp;';
		echo '<input type=submit name="update_agent" class="sub upd" value="'.lang_string ("get_info").'">';
		echo '<br><br>';

		// Destination agent
		echo '<tr><td class="datost">';
		echo '<b>'.$lang_label["toagent"].'</b><br><br>';
		echo "<select name=destino[] size=10 multiple=yes style='width: 250px;'>";
		$sql1='SELECT * FROM tagente ORDER BY nombre';
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if (give_acl ($config["id_user"], $row["id_grupo"], "AW"))
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
		echo '</select>';
		
		// Form buttons
		echo '<td align="right" class="datosb">';
		echo '<input type="submit" name="copy" class="sub next" value="'.lang_string ("Replicate configuration").'" onClick="if (!confirm("'.lang_string ("are_you_sure").'")) return false;>';
		echo '<tr><td colspan=2>';
		echo '</div></td></tr>';
		echo '</table>';

	}

?>
