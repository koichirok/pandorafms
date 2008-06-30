<?PHP

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Copyright (c) 2008 Jorge Gonzalez <jorge.gonzalez@artica.es>
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


// ========================
// AGENT GENERAL DATA FORM 
// ========================
// Load global vars
require("include/config.php");

if (give_acl($id_user, 0, "AW")!=1) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".lang_string ("agent_conf");
if (isset($_GET["create_agent"])){
	$create_agent = 1;
	echo " &gt; ".lang_string ("create_agent");
} else {
	echo " &gt; ".lang_string ("update_agent");
}
echo "</h2>";
echo "<div style='height: 5px'> </div>";

// Agent remote configuration editor
$agent_md5 = md5($nombre_agente, FALSE);
if (isset($_GET["disk_conf"])){
	require ("agent_disk_conf_editor.php");
	exit;
}

// Agent remote configuration DELETE
if (isset($_GET["disk_conf_delete"])){
	$agent_md5 = md5($nombre_agente, FALSE);
	$file_name = $config["remote_config"] . "/" . $agent_md5 . ".conf";
	unlink ($file_name);
	$file_name = $config["remote_config"] . "/" . $agent_md5 . ".md5";
	unlink ($file_name);
}

echo '<form name="conf_agent" method="post" action="index.php?sec=gagente&
sec2=godmode/agentes/configurar_agente">';
echo '<table width="650" id="table-agent-configuration" cellpadding="4" cellspacing="4" class="databox_color">';
echo "<tr>";
echo '<td class="datos"><b>'.lang_string ("agent_name").'</b></td><td class="datos">';
print_input_text ('agente', $nombre_agente, '', 30, 100);

if (isset ($id_agente) && $id_agente != "") {
	echo "
	<a href='index.php?sec=estado&
	sec2=operation/agentes/ver_agente&id_agente=".$id_agente."'>
	<img src='images/lupa.png' border='0' align='middle' alt=''></a>";
} 
// Remote configuration available
if (file_exists ($config["remote_config"] . "/" . $agent_md5 . ".md5")) {
	echo "
	<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=".$id_agente."&disk_conf=" . $agent_md5 . "'>
	<img src='images/application_edit.png' border='0' align='middle' alt=''></a>";	
}

echo '<tr><td class="datos2">';
echo '<b>'.lang_string ("ip_address").'</b>';
echo '<td class="datos2">';
print_input_text ('direccion', $direccion_agente, '', 16, 100);

if ($create_agent != 1) {
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";

	echo '<select name="address_list">';
	$sql1 = "SELECT * FROM taddress, taddress_agent
		WHERE taddress.id_a = taddress_agent.id_a
		AND   taddress_agent.id_agent = $id_agente";
	if ($result=mysql_query($sql1))
		while ($row=mysql_fetch_array($result)){
			echo "<option value='".salida_limpia($row["ip"])."'>".salida_limpia($row["ip"])."&nbsp;&nbsp;";
		}
	echo "</select>";

	echo "<input name='delete_ip' type=checkbox value='1'> ".lang_string ("delete_sel");
	echo "</td>";
}

echo '<tr><td class="datos"><b>'.lang_string ("Parent").'</b>';
echo '<td class="datos">';
print_select_from_sql ('SELECT id_agente, nombre FROM tagente ORDER BY nombre',
				'id_parent', $id_parent, '', 'None', '0');

echo '<tr><td class="datos"><b>'.lang_string ("group").'</b>';
echo '<td class="datos">';
print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre',
			'grupo', $grupo, '', '', '');

echo "<tr><td class='datos2'>";
echo "<b>".lang_string("interval")."</b></td>";
echo '<td class="datos2">';

echo '<input type="text" name="intervalo" size="15" value="'.$intervalo.'"></td>';
echo '<tr><td class="datos"><b>'.lang_string("os").'</b></td>';
echo '<td class="datos">';
print_select_from_sql ('SELECT id_os, name FROM tconfig_os ORDER BY name',
			'id_os', $id_os, '', '', '');

// Network server
echo '<tr><td class="datos2"><b>';
echo lang_string("Network server");
echo '</b></td><td class="datos2">';
$none = '';
$none_value = '';
if ($id_network_server == 0) {
	$none = lang_string ('None');
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE network_server = 1 ORDER BY name',
			'network_server', $id_network_server, '', $none, $none_value);

// Plugin Server
echo '<tr><td class="datos"><b>';
echo lang_string("Plugin server");
echo '</b></td><td class="datos">';
$none_str = lang_string ('None');
$none = '';
$none_value = '';
if ($id_plugin_server == 0) {
	$none = $none_str;
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE plugin_server = 1 ORDER BY name',
			'plugin_server', $id_plugin_server, '', $none, $none_value);

// WMI Server
echo '<tr><td class="datos2"><b>';
echo lang_string("WMI server");
echo '</b></td><td class="datos2">';
$none = '';
$none_value = '';
if ($id_plugin_server == 0) {
	$none = $none_str;
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE wmi_server = 1 ORDER BY name',
			'wmi_server', $id_wmi_server, '', $none, $none_value);

// Prediction Server
echo '<tr><td class="datos"><b>';
echo lang_string("Prediction server");
echo '</b></td><td class="datos">';
$none = '';
$none_value = '';
if ($id_prediction_server == 0) {
	$none = $none_str;
	$none_value = 0;
}
print_select_from_sql ('SELECT id_server, name FROM tserver WHERE prediction_server = 1 ORDER BY name',
			'prediction_server', $id_prediction_server, '', $none, $none_value);

// Description
echo '<tr><td class="datos2"><b>';
echo lang_string ("description");
echo '</b><td class="datos2">';
print_input_text ('comentarios', $comentarios, '', 55, 255);

// Learn mode / Normal mode 
echo '<tr><td class="datos"><b>';
echo lang_string ("module_definition");
echo '</b><td class="datos">';
echo lang_string ("learning_mode");
print_radio_button_extended ("modo", 1, '', $modo, false, '', 'style="margin-right: 40px;"');
echo lang_string ("normal_mode");
print_radio_button_extended ("modo", 0, '', $modo, false, '', 'style="margin-right: 40px;"');

// Status (Disabled / Enabled)
echo '<tr><td class="datos2"><b>'.lang_string("status").'</b>';
echo '<td class="datos2">';
echo lang_string ("disabled");
print_radio_button_extended ("disabled", 1, '', $disabled, false, '', 'style="margin-right: 40px;"');
echo lang_string ("active");
print_radio_button_extended ("disabled", 0, '', $disabled, false, '', 'style="margin-right: 40px;"');

// Remote configuration
echo '<tr><td class="datos"><b>'.lang_string("Remote configuration").'</b>';
echo '<td class="datos">';
$filename = $config["remote_config"] . "/" . $agent_md5 . ".md5";
if (file_exists($filename)){
	echo date("F d Y H:i:s.", fileatime($filename));
	// Delete remote configuration
	echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&disk_conf_delete=1&id_agente=$id_agente'><img src='images/cross.png'></A>";
} else {
	echo '<i>'.lang_string("Not available").'</i>';
}

echo '</table><table width="650"><tr><td  align="right">';
if ($create_agent == 1) {
	print_submit_button (lang_string ('create'), 'crtbutton', false, 'class="sub wand"');
	print_input_hidden ('create_agent', 1);
} else {
	print_submit_button (lang_string ('update'), 'updbutton', false, 'class="sub upd"');
	print_input_hidden ('update_agent', 1);
	print_input_hidden ('id_agente', $id_agente);
}

echo "</td></form></table>";

?>
