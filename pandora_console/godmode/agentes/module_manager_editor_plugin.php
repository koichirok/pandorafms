<?PHP
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation, version 2.


// General startup for established session
global $config;
check_login();

// Specific ACL check
if (give_acl($config["id_user"], 0, "AW")!=1) {
    audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
}

echo "<h3>".lang_string ("module_assigment")." - ".lang_string("Plugin server module")."</h3>";
echo '<table width="680" cellpadding="4" cellspacing="4" class="databox_color">';
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'">';
echo '<input type="hidden" name="insert_module" value=1>';

// id_modulo 4 - PLugin
echo "<input type='hidden' name='form_id_modulo' value='4'>";

// Name / IP_target
echo '<tr>';
echo '<td class="datos2">'.lang_string ("module_name")."</td>";
echo '<td class="datos2"><input type="text" name="form_name" size="20" value="'.$form_name.'"></td>';
echo '<td class="datos2">'.lang_string ("disabled")."</td>";
echo '<td class="datos2"><input type="checkbox" name="form_disabled" value=1></td>';
echo "</tr>";

// Ip target, Plugin Parameter
echo "<tr>";
echo '<td class="datos">'.lang_string ("ip_target")."</td>";
echo '<td class="datos"><input type="text" name="form_ip_target" size="20" value="'.$form_ip_target.'"></td>';
echo '<td class="datos">'.lang_string ("plugin")."</td>";
echo '<td class="datos">';
echo '<select name="form_id_plugin">';
$sql1='SELECT id, name FROM tplugin ORDER BY name;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id"]."'>".$row["name"]."</option>";
}
echo "</select>";
echo '</tr>';

echo '</tr><tr>';
echo '<td class="datos2">'.lang_string ("Plugin parameters")."</td>";
echo '<td class="datos2" colspan=3><input type="text" name="form_plugin_parameter" size="40" value="'.$form_plugin_parameter.'"></td>';

// username / password
echo '<tr>';
echo '<td class="datos">'.lang_string ("Username")."</td>";
echo '<td class="datos"><input type="text" name="form_plugin_user" size="10" value="'.$form_plugin_user.'"></td>'; 
echo '<td class="datos">'.lang_string ("Password")."</td>";
echo '<td class="datos"><input type="password" name="form_plugin_pass" size="10" value="'.$form_plugin_pass.'"></td>'; 
echo '</tr>';


// module type / max timeout
echo '</tr><tr>';
echo '<td class="datos2">'.lang_string ("module_type")."</td>";
echo '<td class="datos2">';
echo '<select name="form_id_tipo_modulo">';
$sql1='SELECT id_tipo, nombre FROM ttipo_modulo WHERE categoria IN (0,1,2,9) ORDER BY nombre;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_tipo"]."'>".$row["nombre"]."</option>";
}
echo "</select>";
echo '<td class="datos2">'.lang_string ("max_timeout")."</td>";
echo '<td class="datos2"><input type="text" name="form_max_timeout" size="5" value="'.$form_max_timeout.'"></td></tr>';

// Interval & id_module_group
echo '<tr>';
echo '<td class="datos">'.lang_string ("interval")."</td>";
echo '<td class="datos"><input type="text" name="form_interval" size="5" value="'.$form_interval.'"></td>';
echo '<td class="datos">'.lang_string ("module_group")."</td>";
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
echo '<td class="datos2">'.lang_string ("min_value")."</td>";
echo '<td class="datos2"><input type="text" name="form_minvalue" size="5" value="'.$form_minvalue.'"></td>';
echo '<td class="datos2">'.lang_string ("max_value")."</td>";
echo '<td class="datos2"><input type="text" name="form_maxvalue" size="5" value="'.$form_maxvalue.'"></td>';
echo '</tr>';

// Post process / Export server
echo "<tr>";
echo '<td class="datos">'.lang_string ("post_process")."</td>";
echo '<td class="datos"><input type="text" name="form_post_process" size="5" value="'.$form_post_process.'">';
pandora_help("postprocess");
echo "</td>";
echo '<td class="datos">'.lang_string ("export_server")."</td>";
echo '<td class="datos"><select name="form_id_export">';
echo "<option value='0'>".lang_string("None")."</option>";
$sql1='SELECT id, name FROM tserver_export ORDER BY name;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id"]."'>".$row["name"]."</option>";
}
echo "</select>";
echo '</tr>';

// Description
echo '</tr><tr>';
echo '<td valign="top" class="datos2">'.lang_string ("description")."</td>";
echo '<td valign="top" class="datos2" colspan=3><textarea name="form_description" cols=65 rows=2>'.$form_interval.'</textarea>';
echo "</table>";

// SUbmit
echo '<table width="680" cellpadding="4" cellspacing="4">';
echo '<td valign="top" align="right">';
echo '<input name="crtbutton" type="submit" class="sub wand" value="'.lang_string ("create").'">';
echo "</table>";


?>