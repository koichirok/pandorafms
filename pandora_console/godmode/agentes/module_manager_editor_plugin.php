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


// General startup for established session
global $config;
check_login();

// get the variable form_moduletype
$form_moduletype = get_parameter_post ("form_moduletype");
// get the module to update
$update_module_id = get_parameter_get ("update_module");
// the variable that checks whether the module is disabled or not must be setcommitedversion
$disabled_status = NULL;

// Specific ACL check
if (give_acl($config["id_user"], 0, "AW")!=1) {
    audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
}

// Check whether we are updataing and get data if so
if ($update_module_id != NULL){
    $row = get_db_row ("tagente_modulo", 'id_agente_modulo', $update_module_id);
    if ($row == 0){
        unmanaged_error("Cannot load tnetwork_component reference from previous page");
    }
	else{
		$id_agente = $row['id_agente'];
		$form_id_tipo_modulo = $row['id_tipo_modulo']; // It doesn't matter
		$form_description = $row['descripcion'];
		$form_name = $row['nombre'];
		$form_minvalue = $row['min'];
		$form_maxvalue = $row['max'];
		$form_interval = $row['module_interval'];
		$form_tcp_port = $row['tcp_port'];
		$form_tcp_send = $row['tcp_send'];
		$form_tcp_rcv = $row['tcp_rcv'];
		$form_snmp_community = $row['snmp_community'];
		$form_snmp_oid = $row['snmp_oid'];
		$form_ip_target = $row['ip_target'];
		$form_id_module_group = $row['id_module_group'];
		$form_flag = $row['flag'];
		$tbl_id_modulo = $row['id_modulo']; // It doesn't matter
		$tbl_disabled = $row['disabled'];
		$form_id_export = $row['id_export'];
		$form_plugin_user = $row['plugin_user'];
		$form_plugin_pass = $row['plugin_pass'];
		$form_plugin_parameter = $row['plugin_parameter'];
		$form_id_plugin = $row['id_plugin'];
		$form_post_process = $row['post_process'];
		$form_prediction_module = $row['prediction_module'];
		$form_max_timeout = $row['max_timeout'];
		$form_custom_id = $row['custom_id'];

		if ($tbl_disabled == 1){
			$disabled_status = 'checked="ckecked"';
		} else {
			$disabled_status = NULL;
		}
	}
}

echo "<h3>".__('Module assignment')." - ".__('Plugin server module')."</h3>";
echo '<table width="680" cellpadding="4" cellspacing="4" class="databox_color">';
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'&form_moduletype='.$form_moduletype.'">';
// Whether in update or insert mode
if ($update_module_id == NULL){
	echo "<input type='hidden' name='insert_module' value='1'>";
} else {
	echo "<input type='hidden' name='update_module' value='1'>";
}

//id_agente_module
echo "<input type='hidden' name='id_agente_modulo'' value='".$update_module_id."'>";

// id_modulo 4 - PLugin
echo "<input type='hidden' name='form_id_modulo' value='4'>";

// Name / IP_target
echo '<tr>';
echo '<td class="datos2">'.__('Module name')."</td>";
echo '<td class="datos2"><input type="text" name="form_name" size="20" value="'.$form_name.'"></td>';
echo '<td class="datos2">'.__('Disabled')."</td>";
echo '<td class="datos2"><input type="checkbox" name="form_disabled" value="1" "'.$disabled_status.'"></td>';
echo '</tr>';

// Ip target, Plugin Parameter
echo "<tr>";
echo '<td class="datos">'.__('Target IP')."</td>";
echo '<td class="datos"><input type="text" name="form_ip_target" size="20" value="'.$form_ip_target.'"></td>';
echo '<td class="datos">'.__('plugin')."</td>";
echo '<td class="datos">';
// Get actual plugin ID and nicename
echo '<select name="form_id_plugin">';
if ($form_id_plugin != 0){
    echo "<option value='".$form_id_plugin."'>".dame_nombre_pluginid($form_id_plugin)."</option>";
}
echo "<option value='0'>".__('None')."</option>";
$sql1='SELECT id, name FROM tplugin ORDER BY name;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id"]."'>".$row["name"]."</option>";
}
echo '</select>';
echo '</tr>';

echo '</tr><tr>';
echo '<td class="datos2">'.__('Plugin parameters');
pandora_help ("plugin_parameters");
echo '</td>';
echo '<td class="datos2"><input type="text" name="form_plugin_parameter" size="30" value="'.$form_plugin_parameter.'"></td>';

echo '<td class="datos2">'.__('Port')."</td>";
echo '<td class="datos2"><input type="text" name="form_tcp_port" size="4" value="'.$form_tcp_port.'"></td>';

// username / password
echo '<tr>';
echo '<td class="datos">'.__('Username')."</td>";
echo '<td class="datos"><input type="text" name="form_plugin_user" size="10" value="'.$form_plugin_user.'"></td>'; 
echo '<td class="datos">'.__('Password')."</td>";
echo '<td class="datos"><input type="password" name="form_plugin_pass" size="10" value="'.$form_plugin_pass.'"></td>'; 
echo '</tr>';

// module type / max timeout
echo '</tr><tr>';
echo '<td class="datos2">'.__('Module type');
pandora_help ("module_type");
echo '</td>';
echo '<td class="datos2">';

if ($update_module_id != NULL){
	echo "<span class='redi'>Not available in edition mode</span>";
	echo "<input type='hidden' name='form_id_tipo_modulo' value='".$form_id_tipo_modulo."'>";
} else {
	echo '<select name="form_id_tipo_modulo">';
	$sql1='SELECT id_tipo, nombre FROM ttipo_modulo WHERE categoria IN (0,1,2,9) ORDER BY nombre;';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_tipo"]."'>".$row["nombre"]."</option>";
	}
	echo '</select>';
}

echo '<td class="datos2">'.__('Max. timeout')."</td>";
echo '<td class="datos2"><input type="text" name="form_max_timeout" size="5" value="'.$form_max_timeout.'"></td></tr>';

// Interval & id_module_group
echo '<tr>';
echo '<td class="datos">'.__('Interval')."</td>";
echo '<td class="datos"><input type="text" name="form_interval" size="5" value="'.$form_interval.'"></td>';
echo '<td class="datos">'.__('Module group')."</td>";
echo '<td class="datos">';
echo '<select name="form_id_module_group">';
if ($form_id_module_group != 0){
    echo "<option value='".$form_id_module_group."'>".dame_nombre_grupomodulo($form_id_module_group)."</option>";
}
$sql1='SELECT * FROM tmodule_group';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_mg"]."'>".$row["name"]."</option>";
}
echo '</select>';

// Max / min value
echo '<tr>';
echo '<td class="datos2">'.__('Min. Value')."</td>";
echo '<td class="datos2"><input type="text" name="form_minvalue" size="5" value="'.$form_minvalue.'"></td>';
echo '<td class="datos2">'.__('Max. Value')."</td>";
echo '<td class="datos2"><input type="text" name="form_maxvalue" size="5" value="'.$form_maxvalue.'"></td>';
echo '</tr>';

// Post process / Export server
echo '<tr>';
echo '<td class="datos">'.__('Post process');
pandora_help ("postprocess");
echo '</td>';
echo '<td class="datos"><input type="text" name="form_post_process" size="5" value="'.$form_post_process.'"></td>';
// Export target is a server where the data will be sent
echo '<td class="datos">'.__('Export target')."</td>";
echo '<td class="datos"><select name="form_id_export">';
if ($form_id_export != 0){
    echo "<option value='".$form_id_export."'>".dame_nombre_servidorexportacion($form_id_export)."</option>";
}
echo "<option value='0'>".__('None')."</option>";
$sql1='SELECT id, name FROM tserver_export ORDER BY name;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id"]."'>".$row["name"]."</option>";
}
echo '</select>';
echo '</tr>';

// Description
echo '<tr>';
echo '<td valign="top" class="datos2">'.__('Description')."</td>";
echo '<td valign="top" class="datos2" colspan="3"><textarea name="form_description" cols="65" rows="2">'.$form_description.'</textarea>';
echo '</tr>';

// Custom ID
echo '<tr>';
echo '<td class="datos">'.__('Custom ID')."</td>";
echo '<td class="datos" colspan="3"><input type="text" name="form_custom_id" size="20" value="'.$form_custom_id.'"></td>';
echo '</tr>';

echo '</table>';

// Submit
echo '<table width="680" cellpadding="4" cellspacing="4">';
echo '<td valign="top" align="right">';
if ($update_module_id == NULL){
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Create').'">';
} else {
	echo '<input name="updbutton" type="submit" class="sub wand" value="'.__('Update').'">';
}
echo '</table>';

?>
