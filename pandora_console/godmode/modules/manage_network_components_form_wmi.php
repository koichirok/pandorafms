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


// Load global variables
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

// Update an existing component
if ($id) {
	$component = get_network_component ($id);
	if ($component === false)
		return;
	$name = $component["name"];
	$type = $component["type"];
	$description = $component["description"];
	$modulo_max = $component["max"];
	$modulo_min = $component["min"];
	$module_interval = $component["module_interval"];
	$tcp_port = $component["tcp_port"];
	$tcp_rcv = $component["tcp_rcv"];
	$tcp_send = $component["tcp_send"];
	$snmp_community = $component["snmp_community"];
	$snmp_oid = $component["snmp_oid"];
	$id_module_group = $component["id_module_group"];
	$id_group = $component["id_group"];
	$plugin_user = $component["plugin_user"];
	$plugin_pass = $component["plugin_pass"];
	$plugin_parameter = $component["plugin_parameter"];
	$max_timeout = $component["max_timeout"];
} else {
	$name = "";
	$snmp_oid = "";
	$description = "";
	$id_group = 1;
	$oid = "";
	$modulo_max = "0";
	$modulo_min = "0";
	$module_interval = "300";
	$tcp_port = "";
	$tcp_rcv = "";
	$tcp_send = "";
	$snmp_community = "";
	$id_module_group = "";
	$id_group = "";
	$type = 0;
	$plugin_user = "Administrator";
	$plugin_pass = "";
	$plugin_parameter = "";
	$max_timeout = 10;
}

echo '<h2>' . __('WMI component management') . '</h2>';
echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_components">';

echo '<table width="95%" cellspacing="4" cellpadding="4" class="databox_color">';
echo '<tr>';

// Name
echo '<tr><td class="datos2">' . __('Module name') . '</td>';
echo '<td class="datos2"><input type="text" name="name" size="25" value="' . $name . '"></td>';

// Type
echo '<td class="datos2">' . __('Module type') . '</td>';
echo '<td class="datos2">';
echo '<select name="tipo">';
echo '<option value="' . $type . '">' . get_moduletype_name ($type);
$result = mysql_query('SELECT id_tipo, nombre FROM ttipo_modulo WHERE categoria IN (0,1,2) ORDER BY nombre;');
while ($component = mysql_fetch_array($result)){
	echo '<option value="' . $component['id_tipo'] . '">' . $component['nombre'] . '</option>';
}
echo '</select>';
echo '</td></tr>';
echo '<tr>';

// Component group
echo '<td class="datos">' . __('Group') . '</td>';
echo '<td class="datos">';
print_select (get_network_component_groups (),
	'id_group', $id_group, '', '', '', false, false, false);

// Module group
echo '<td class="datos">' . __('Module group') . '</td>';
echo '<td class="datos">';
echo '<select name="id_module_group">';
if ($id) {
	echo '<option value="' . $id_module_group . '">' . get_modulegroup_name($id_module_group);
}
$result = mysql_query('SELECT * FROM tmodule_group');
while ($component = mysql_fetch_array($result))
	echo '<option value="' . $component['id_mg'] . '">' . $component['name'] . '</option>';
echo '</select>';
echo '<tr>';

// Interval
echo '<td class="datos2">' . __('Module Interval') . '</td>';
echo '<td class="datos2">';
echo '<input type="text" name="module_interval" size="5" value="'.$module_interval.'">';

// Timeout
echo '<td class="datos2">' . __('Max. timeout') . '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="max_timeout" size="5" value="' . $max_timeout . '">';
echo '</td></tr>';

// WMI Query
echo '<tr><td class="datos">' . __('WMI Query') ;
print_help_icon("wmiquery");
echo '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="snmp_oid" size="25" value="' . $snmp_oid . '">';
echo '</td>';

// Key string
echo '<td class="datos">' . __('Key string');
print_help_icon("wmikey");
echo '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="snmp_community" size="25" value="' . $snmp_community . '">';
echo '</td></tr>';

// Field
echo '<td class="datos2">' . __('Field number');
print_help_icon("wmifield");
echo '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="tcp_port" size="5" value="' . $tcp_port . '">';
echo '</td>';

// Namespace
echo '<td class="datos2">' . __('Namespace');
print_help_icon("wminamespace");
echo '</td>';
echo '<td class="datos2">';
echo	'<input type="text" name="tcp_send" size="25" value="' . $tcp_send . '">';
echo '</td></tr>';

// Username
echo '<tr><td class="datos">' . __('Username') . '</td>';
echo '<td class="datos">';
echo 	'<input type="text" name="plugin_user" size="25" value="' . $plugin_user . '">';
echo '</td>';

// Password
echo '<td class="datos">' . __('Password') . '</td>';
echo '<td class="datos">';
echo 	'<input type="password" name="plugin_pass" size="25" value="' . $plugin_pass . '">';
echo '</td></tr>';

// Min data
echo '<tr><td class="datos2">' . __('Minimum Data') . '</td>';
echo '<td class="datos2">';
echo '<input type="text" name="modulo_min" size="5" value="' . $modulo_min . '">';
echo '</td>';
echo '<td class="datos2">' . __('Maximum Data') . '</td>';
echo '<td class="datos2">';

// Max data
echo '<input type="text" name="modulo_max" size="5" value="' . $modulo_max . '">';
echo '</td></tr>';

// Comments
echo '<tr><td class="datos">'.__('Comments') . '</td>';
echo '<td class="datos" colspan=3>';
echo '<textarea name="descripcion" cols=70 rows=2>';
echo $description;
echo '</textarea>';
echo '</td></tr>';
echo '</table>';

print_input_hidden ('id_modulo', $id_component_type);

// Update/Add buttons
echo '<div class="action-buttons" style="width: 95%">';
if ($id) {
	print_input_hidden ('update_component', 1);
	print_input_hidden ('id', $id);
	print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_component', 1);
	print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';

?>
