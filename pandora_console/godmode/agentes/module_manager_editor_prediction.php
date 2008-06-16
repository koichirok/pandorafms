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

		if ($tbl_disabled == 1){
			$disabled_status = 'checked="ckecked"';
		} else {
			$disabled_status = NULL;
		}
	}
}

echo "<h3>".lang_string ("module_assignment")." - ".lang_string("Prediction server module")."</h3>";
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'">';
// Whether in update or insert mode
if ($update_module_id == NULL){
	echo "<input type='hidden' name='insert_module' value='1'>";
} else {
	echo "<input type='hidden' name='update_module' value='1'>";
}

//id_agente_module
echo "<input type='hidden' name='id_agente_modulo'' value='".$update_module_id."'>";

// id_modulo 5 - Prediction
echo "<input type='hidden' name='form_id_modulo' value='5'>";

// name / disabled
echo '<table width="600" cellpadding="4" cellspacing="4" class="databox_color">';
echo '<tr>';
echo '<td class="datos2">'.lang_string ("module_name")."</td>";
echo '<td class="datos2"><input type="text" name="form_name" size="35" value="'.$form_name.'"></td>';
echo '<td class="datos2">'.lang_string ("disabled")."</td>";
echo '<td class="datos2"><input type="checkbox" name="form_disabled" value="1" "'.$disabled_status.'"></td>';
echo '</tr>';


echo '<tr>';
echo '<td class="datos">'.lang_string ("source_module")."</td>";
echo '<td class="datos" colspan=3>';
echo '<select name="form_id_prediction_module">';
$sql1='SELECT id_agente_modulo, tagente_modulo.nombre, tagente.id_agente, tagente.id_grupo, tagente.nombre FROM tagente_modulo, tagente WHERE id_tipo_modulo NOT IN (100,24,19,20,21,22,23,17,10,2) AND tagente.id_agente = tagente_modulo.id_agente ORDER BY tagente.nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	if (give_acl($config["id_user"], $row[3], "AR"))
    	echo "<option value='".$row[0]."'>".$row[1]. " / ". $row[4];
}
echo "</select>";
echo "</tr>";

echo "<tr>";
echo '<td class="datos2">'.lang_string ("module_type")."</td>";
echo '<td class="datos2">';
if ($update_module_id != NULL){
	echo "<span class='redi'>Not available in edition mode</span>";
	echo "<input type='hidden' name='form_id_tipo_modulo' value='".$form_id_tipo_modulo."'>";
} else {
	echo '<select name="form_id_tipo_modulo">';
	$sql1='SELECT id_tipo, nombre FROM ttipo_modulo WHERE id_tipo IN (1,2) ORDER BY nombre';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_tipo"]."'>".$row["nombre"]."</option>";
	}
	echo '</select>';
}

echo '<td class="datos2">'.lang_string ("interval")."</td>";
echo '<td class="datos2"><input type="text" name="form_interval" size="5" value="'.$form_interval.'"></td></tr>';

// Post process / Export server
echo '<tr>';
echo '<td class="datos">'.lang_string ("module_group")."</td>";
echo '<td class="datos"><select name="form_id_module_group">';
if ($form_id_module_group != 0){
    echo "<option value='".$form_id_module_group."'>".dame_nombre_grupomodulo($form_id_module_group)."</option>";
}
$sql1='SELECT * FROM tmodule_group';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_mg"]."'>".$row["name"]."</option>";
}
echo '</select>';

echo '<td class="datos">'.lang_string ("export_server")."</td>";
echo '<td class="datos"><select name="form_id_export">';
if ($form_id_export != 0){
    echo "<option value='".$form_id_export."'>".dame_nombre_servidorexportacion($form_id_export)."</option>";
}
echo "<option value='0'>".lang_string("None")."</option>";
$sql1='SELECT id, name FROM tserver_export ORDER BY name;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id"]."'>".$row["name"]."</option>";
}
echo '</select>';
echo '</tr>';

// Description
echo '<tr>';
echo '<td valign="top" class="datos">'.lang_string ("description")."</td>";
echo '<td valign="top" class="datos" colspan="3"><textarea name="form_description" cols="65" rows="2">'.$form_description.'</textarea>';
echo '</tr>';
echo '</table>';

// Submit
echo '<table width="680" cellpadding="4" cellspacing="4">';
echo '<td valign="top" align="right">';
if ($update_module_id == NULL){
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.lang_string ("create").'">';
} else {
	echo '<input name="updbutton" type="submit" class="sub wand" value="'.lang_string ("update").'">';
}
echo '</table>';

?>
