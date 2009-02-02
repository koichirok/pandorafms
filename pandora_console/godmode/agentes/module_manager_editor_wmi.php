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

// General startup for established session
if (!isset ($id_agente)) {
	die ("Not Authorized");
}

$extra_title = __('WMI server module');

$data = array ();
$data[0] = __('Using module component').' ';
$data[0] .= pandora_help ('network_component', true);

if (empty ($update_module_id)) {
	$data[1] = print_select_from_sql ('SELECT id_nc, name FROM tnetwork_component WHERE id_modulo = 6',
		'network_component', '', '', '---'.__('Manual setup').'---', 0, true);
	$data[1] .= ' <span id="component_loading" class="invisible">';
	$data[1] .= '<img src="images/spinner.gif" />';
	$data[1] .= '</span>';
} else {
	/* TODO: Print network component if available */
	$data[1] = 'TODO';
}
$table_simple->colspan['module_component'][1] = 3;
$table_simple->rowstyle['module_component'] = 'background-color: #D4DDC6';

prepend_table_simple ($data, 'module_component');

$data = array ();
$data[0] = __('Target IP');
$data[1] = print_input_text ('ip_target', $ip_target, '', 15, 60, true);
$data[2] = _('Namespace');
$data[2] .= pandora_help ('wminamespace', true);
$data[3] = print_input_text ('tcp_send', $tcp_send, '', 5, 20, true);

push_table_simple ($data, 'target_ip');

$data = array ();
$data[0] = __('Username');
$data[1] = print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = _('Password');
$data[3] = print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_simple ($data, 'user_pass');

$data = array ();
$data[0] = __('WMI Query');
$data[0] .= pandora_help ('wmiquery', true);
$data[1] = print_input_text ('snmp_oid', $snmp_oid, '', 35, 60, true);
$table_simple->colspan['wmi_query'][1] = 3;

push_table_simple ($data, 'wmi_query');

$data = array ();
$data[0] = __('Key string');
$data[0] .= pandora_help ('wmikey', true);
$data[1] = print_input_text ('snmp_community', $snmp_community, '', 20, 60, true);
$data[2] = __('Field number');
$data[2] .= pandora_help ('wmifield', true);
$data[3] = print_input_text ('tcp_port', $tcp_port, '', 5, 15, true);

push_table_simple ($data, 'key_field');
?>
