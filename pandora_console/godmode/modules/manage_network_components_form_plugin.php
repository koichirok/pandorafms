<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Copyright (c) 2008 Esteban Sanche, http://www.artica.es
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
require_once ('include/config.php');

check_login ();

echo '<h2>'.__('Module component management').'</h2>';
echo '<h4>'.__('Plugin component').'</h4>';

$data = array ();
$data[0] = __('Plugin');
$data[1] = print_select_from_sql ('SELECT id, name FROM tplugin ORDER BY name',
	'id_plugin', $id_plugin, '', __('None'), 0, true, false, false);
$table->colspan['plugin_1'][1] = 3;

push_table_row ($data, 'plugin_1');

$data = array ();
$data[0] = __('Username');
$data[1] = print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = _('Password');
$data[3] = print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_row ($data, 'plugin_2');

$data = array ();
$data[0] = __('Plugin parameters');
$data[0] .= print_help_icon ('plugin_parameters', true);
$data[1] = print_input_text ('plugin_parameter', $plugin_parameter, '', 30, 60, true);
$table->colspan['plugin_3'][1] = 3;

push_table_row ($data, 'plugin_3');

?>

