<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



if (isset($_SERVER['REQUEST_TIME'])) {
	$time = $_SERVER['REQUEST_TIME'];
} else {
	$time = get_system_time ();
}

$license_file = '/general/license/pandora_info_'.$config["language"].'.html';
if (! file_exists ($config["homedir"] . $license_file)) {
	$license_file = '/general/license/pandora_info_en.html';
}

echo '<a class="white_bold" target="_blank" href="' . $config["homeurl"] . $license_file. '">';
echo sprintf(__('Pandora FMS %s - Build %s', $pandora_version, $build_version));
echo '</a><br />';
echo '<a class="white">'. __('Page generated at') . ' '. print_timestamp ($time, true, array ("prominent" => "timestamp")); //Always use timestamp here
echo '</a>';
if (isset ($config['debug'])) {
	echo ' - Saved '.format_numeric ($sql_cache["saved"]).' Queries';
}

?>
