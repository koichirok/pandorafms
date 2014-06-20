<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

if (is_ajax()) {

	$generate_info = (bool) get_parameter("generate_info");
	if ($generate_info) {
		$pandora_diag = (bool) get_parameter("pandora_diag");
		$system_info = (bool) get_parameter("system_info");
		$log_info = (bool) get_parameter("log_info");

		$checks = array();
		$checks['pandora_diagnostic'] = $pandora_diag;
		$checks['system_info'] = $system_info;
		$checks['log_info'] = $log_info;
		$result = generate_info($checks);

		echo json_encode($result);
		return;
	}

	return;
}

function getPandoraDiagnostic(&$systemInfo) {
	global $config;
	global $build_version;
	global $pandora_version;
	
	$systemInfo["Pandora FMS Build"] = $build_version;
	$systemInfo["Pandora FMS Version"] = $pandora_version;
	$systemInfo["Homedir"] = $config["homedir"];
	$systemInfo["HomeUrl"] = $config["homeurl"];
	
	$systemInfo["PHP Version"] = phpversion();
	
	$systemInfo['tagente'] = db_get_sql("SELECT COUNT(*) FROM tagente");
	$systemInfo['tagent_access'] = db_get_sql("SELECT COUNT(*) FROM tagent_access");
	$systemInfo['tagente_datos'] = db_get_sql("SELECT COUNT(*) FROM tagente_datos");
	$systemInfo['tagente_datos_string'] = db_get_sql("SELECT COUNT(*) FROM tagente_datos_string");
	$systemInfo['tagente_estado'] = db_get_sql("SELECT COUNT(*) FROM tagente_estado");
	$systemInfo['tagente_modulo'] = db_get_sql("SELECT COUNT(*) FROM tagente_modulo");
	$systemInfo['talert_actions'] = db_get_sql("SELECT COUNT(*) FROM talert_actions");
	$systemInfo['talert_commands'] = db_get_sql("SELECT COUNT(*) FROM tagente");
	$systemInfo['talert_template_modules'] = db_get_sql("SELECT COUNT(*) FROM talert_template_modules");
	$systemInfo['tlayout'] = db_get_sql("SELECT COUNT(*) FROM tlayout");
	if ($config['enterprise_installed'])
		$systemInfo['tlocal_component'] = db_get_sql("SELECT COUNT(*) FROM tlocal_component");
	$systemInfo['tserver'] = db_get_sql("SELECT COUNT(*) FROM tserver");
	$systemInfo['treport'] = db_get_sql("SELECT COUNT(*) FROM treport");
	$systemInfo['ttrap'] = db_get_sql("SELECT COUNT(*) FROM ttrap");
	$systemInfo['tusuario'] = db_get_sql("SELECT COUNT(*) FROM tusuario");
	$systemInfo['tsesion'] = db_get_sql("SELECT COUNT(*) FROM tsesion");
	
	switch ($config["dbtype"]) {
		case "mysql":
			$systemInfo['db_scheme_version'] = db_get_sql("SELECT `value` FROM tconfig WHERE `token` = 'db_scheme_version'");
			$systemInfo['db_scheme_build'] = db_get_sql("SELECT `value` FROM tconfig WHERE `token` = 'db_scheme_build'");
			$systemInfo['enterprise_installed'] = db_get_sql("SELECT `value` FROM tconfig WHERE `token` = 'enterprise_installed'");
			$systemInfo['db_maintance'] = date ("Y/m/d H:i:s", db_get_sql ("SELECT `value` FROM tconfig WHERE `token` = 'db_maintance'"));
			$systemInfo['customer_key'] = db_get_sql("SELECT value FROM tupdate_settings WHERE `key` = 'customer_key';");
			$systemInfo['updating_code_path'] = db_get_sql("SELECT value FROM tupdate_settings WHERE `key` = 'updating_code_path'");
			$systemInfo['current_update'] = db_get_sql("SELECT value FROM tupdate_settings WHERE `key` = 'current_update'");
			break;
		case "postgresql":
			$systemInfo['db_scheme_version'] = db_get_sql("SELECT \"value\" FROM tconfig WHERE \"token\" = 'db_scheme_version'");
			$systemInfo['db_scheme_build'] = db_get_sql("SELECT \"value\" FROM tconfig WHERE \"token\" = 'db_scheme_build'");
			$systemInfo['enterprise_installed'] = db_get_sql("SELECT \"value\" FROM tconfig WHERE \"token\" = 'enterprise_installed'");
			$systemInfo['db_maintance'] = date ("Y/m/d H:i:s", db_get_sql ("SELECT \"value\" FROM tconfig WHERE \"token\" = 'db_maintance'"));
			$systemInfo['customer_key'] = db_get_sql("SELECT value FROM tupdate_settings WHERE \"key\" = 'customer_key';");
			$systemInfo['updating_code_path'] = db_get_sql("SELECT value FROM tupdate_settings WHERE \"key\" = 'updating_code_path'");
			$systemInfo['current_update'] = db_get_sql("SELECT value FROM tupdate_settings WHERE \"key\" = 'current_update'");
			break;
		case "oracle":
			$systemInfo['db_scheme_version'] = db_get_sql("SELECT value FROM tconfig WHERE token = 'db_scheme_version'");
			$systemInfo['db_scheme_build'] = db_get_sql("SELECT value FROM tconfig WHERE token = 'db_scheme_build'");
			$systemInfo['enterprise_installed'] = db_get_sql("SELECT value FROM tconfig WHERE token = 'enterprise_installed'");
			$systemInfo['db_maintance'] = db_get_sql ("SELECT value FROM tconfig WHERE token = 'db_maintance'");
			$systemInfo['customer_key'] = db_get_sql("SELECT value FROM tupdate_settings WHERE key = 'customer_key';");
			$systemInfo['updating_code_path'] = db_get_sql("SELECT value FROM tupdate_settings WHERE key = 'updating_code_path'");
			$systemInfo['current_update'] = db_get_sql("SELECT value FROM tupdate_settings WHERE key = 'current_update'");
			break;
	}
}

function getSystemInfo(&$systemInfo, $script = false) {
	global $config;

	$systemInfo['system_name'] = php_uname('s');
	$systemInfo['system_host'] = php_uname('n');
	$systemInfo['system_release'] = php_uname('r');
	$systemInfo['system_version'] = php_uname('v');
	$systemInfo['system_machine'] = php_uname('m');
	if (!$script) {
		$systemInfo['apache_version'] = apache_get_version();
		$systemInfo['apache_modules'] = apache_get_modules();
	}
	
	$systemInfo['php_ini'] = ini_get_all();
	$systemInfo['phpversion'] = phpversion();
	foreach (get_loaded_extensions() as $module) {
		$systemInfo['php_load_extensions'][$module] = phpversion($module);
	}
	
	$result = shell_exec('df -h | tail --lines=+2');
	$temp = explode("\n", $result);
	$disk = array();
	foreach($temp as $line) {
		$line = preg_replace('/[ ][ ]*/', " ", $line);
		$temp2 = explode(' ', $line);
		if (count($temp2) < 5) {
			break;
		}
		$info = array(
			'Filesystem' => $temp2[0],
			'Size' => $temp2[1],
			'Used' => $temp2[2],
			'Use%' => $temp2[3],
			'Avail' => $temp2[4],
			'Mounted_on' => $temp2[5]
			);
		$disk[] = $info;
	}
	
	$systemInfo['disk'] = $disk;
	
	$result = shell_exec('uptime');
	preg_match('/.* load average: (.*)/', $result, $matches);
	
	$systemInfo['load_average'] = $matches[1];
	
	$result = shell_exec('ps -Ao cmd | tail --lines=+2');
	$temp = explode("\n", $result);
	foreach ($temp as $line) {
		if ($line != '') {
			$process[] = $line;
		} 
	}
	$systemInfo['process'] = $process;

	$logs_directory = (!empty($config["server_log_dir"])) ? io_safe_output($config["server_log_dir"]) : "/var/log/pandora";
	
	$result = shell_exec('du -h ' . $logs_directory . ' | cut -d"/" -f1');
	$systemInfo['size_var_log_pandora'] = $result;
	
	$result = shell_exec('date');
	$systemInfo['date'] = $result;
}

function getLastLinesLog($file, $numLines = 2000) {
	$result = shell_exec('tail -n ' . $numLines . ' ' . $file);
	
	return $result;
}

function show_logfile($file_name, $numLines = 2000) {
	global $config;
	
	if (!file_exists($file_name)) {
		echo "<h2 class=error>" . __("Cannot find file") . "(" . $file_name .
			")</h2>";
	} 
	else {
		if (!is_readable($file_name)) {
			echo "<h2 class=error>" . __("Cannot read file") . "(" . $file_name .
				")</h2>";
		}
		else {
			echo "<h2>" . $file_name . "</h2>";
			echo "<textarea style='width: 98%; height: 200px;' name='$file_name'>";
			echo shell_exec('tail -n ' . $numLines . ' ' . $file_name);
			echo "</textarea>";
		}
	}
}

function logFilesLines($file_name, $numLines) {
	global $config;
	
	if (!file_exists($file_name)) {
		return '';
	} 
	else {
		if (!is_readable($file_name)) {
			return '';
		}
		else {
			return shell_exec('tail -n ' . $numLines . ' ' . $file_name);
		}
	}
}

function getLastLog($numLines = 2000) {
	global $config;

	$logs_directory = (!empty($config["server_log_dir"])) ? io_safe_output($config["server_log_dir"]) : "/var/log/pandora";
	
	show_logfile($config["homedir"]."/pandora_console.log", $numLines);
	show_logfile($logs_directory."/pandora_server.log", $numLines);
	show_logfile($logs_directory."/pandora_server.error", $numLines);
	show_logfile("/etc/mysql/my.cnf", $numLines);
	show_logfile($config["homedir"]."/include/config.php", $numLines);
	show_logfile("/etc/pandora/pandora_server.conf", $numLines);
	show_logfile("/var/log/syslog", $numLines);
}

function show_array($title, $anchor, $array = array()) {
	
	$table = new StdClass();
	$table->width = '98%';
	$table->class = "databox";
	$table->head = array();
	$table->head[0] = $title;
	$table->data = array();

	foreach ($array as $index => $item) {
		if (!is_array($item)) {
			$row = array();
			$row[] = $index;
			$row[] = $item;
			$table->data[] = $row;
		}
		else {
			foreach ($item as $index2 => $item2) {
				if (!is_array($item2)) {
					$row = array();
					$row[] = $index;
					$row[] = $index2;
					$row[] = $item2;
					$table->data[] = $row;
				}
				else {
					foreach ($item2 as $index3 => $item3) {
						$row = array();
						$row[] = $index;
						$row[] = $index2;
						$row[] = $index3;
						$row[] = $item3;
						$table->data[] = $row;
					}
				}
			}
		}
	}
	
	echo "<h1><a name='" . $anchor . "'>" . $title . "</a></h1>";
	
	html_print_table($table);
}

function generate_info($checks) {
	global $config;

	$pandora_diag = isset($checks['pandora_diagnostic']) ? $checks['pandora_diagnostic'] : false;
	$system_info = isset($checks['system_info']) ? $checks['system_info'] : false;
	$log_info = isset($checks['log_info']) ? $checks['log_info'] : false;

	$tempDirSystem = sys_get_temp_dir();
	$nameDir = 'dir_' . uniqid();
	$tempDir = $tempDirSystem . '/' . $nameDir . '/';
	mkdir($tempDir);
	
	$zipArchive = $config['attachment_store'] . '/last_info.zip';
	@unlink($zipArchive);
	
	$url_zip = ui_get_full_url(false);
	
	$url = $url_zip . 'attachment/last_info.zip';
	
	$result = array();
	$result['success'] = false;
	$result['url'] = $url;
	$result['location'] = $zipArchive;

	$some_check = $log_info || $system_info || $pandora_diag;

	$zip = new ZipArchive;

	$zip_openned = $zip->open($zipArchive, ZIPARCHIVE::CREATE) === true;
	
	if ($some_check && $zip_openned) {
		if ($pandora_diag) {
			$systemInfo = array();
			getPandoraDiagnostic($systemInfo);
			
			$file = fopen($tempDir . 'pandora_diagnostic.txt', 'w');
			
			if ($file !== false) {
				ob_start();
				foreach ($systemInfo as $index => $item) {
					if (is_array($item)) {
						foreach ($item as $secondIndex => $secondItem) {
							echo $index. ";" . $secondIndex . ";" . $secondItem . "\n";
						}
					}
					else {
						echo $index . ";" . $item . "\n";
					}
				}
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$zip->addFile($tempDir . 'pandora_diagnostic.txt', 'pandora_diagnostic.txt');
		}
		
		if ($system_info) {
			$info = array();
			getSystemInfo($info);
			
			$file = fopen($tempDir . 'system_info.txt', 'w');
			
			if ($file !== false) {
				ob_start();
				foreach ($info as $index => $item) {
					if (is_array($item)) {
						foreach ($item as $secondIndex => $secondItem) {
							echo $index. ";" . $secondIndex . ";" . $secondItem . "\n";
						}
					}
					else {
						echo $index . ";" . $item . "\n";
					}
				}
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$zip->addFile($tempDir . 'system_info.txt', 'system_info.txt');
		}

		$server_logs_directory = (!empty($config["server_log_dir"])) ? io_safe_output($config["server_log_dir"]) : "/var/log/pandora";
		
		if ($log_info) {
			file_put_contents($tempDir . 'pandora_console.log.lines_' . $log_num_lines, getLastLinesLog($config["homedir"]."/pandora_console.log", $log_num_lines));
			$zip->addFile($tempDir . 'pandora_console.log.lines_' . $log_num_lines, 'pandora_console.log.lines_' . $log_num_lines);
			file_put_contents($tempDir . 'pandora_server.log.lines_' . $log_num_lines, getLastLinesLog($server_logs_directory."/pandora_server.log", $log_num_lines));
			$zip->addFile($tempDir . 'pandora_server.log.lines_' . $log_num_lines, 'pandora_server.log.lines_' . $log_num_lines);
			file_put_contents($tempDir . 'pandora_server.error.lines_' . $log_num_lines, getLastLinesLog($server_logs_directory."/pandora_server.error", $log_num_lines));
			$zip->addFile($tempDir . 'pandora_server.error.lines_' . $log_num_lines, 'pandora_server.error.lines_' . $log_num_lines);
			file_put_contents($tempDir . 'my.cnf.lines_' . $log_num_lines, getLastLinesLog("/etc/mysql/my.cnf", $log_num_lines));
			$zip->addFile($tempDir . 'my.cnf.lines_' . $log_num_lines, 'my.cnf.lines_' . $log_num_lines);
			file_put_contents($tempDir . 'config.php.lines_' . $log_num_lines, getLastLinesLog($config["homedir"]."/include/config.php", $log_num_lines));
			$zip->addFile($tempDir . 'config.php.lines_' . $log_num_lines, 'config.php.lines_' . $log_num_lines);
			file_put_contents($tempDir . 'pandora_server.conf.lines_' . $log_num_lines, getLastLinesLog("/etc/pandora/pandora_server.conf", $log_num_lines));
			$zip->addFile($tempDir . 'pandora_server.conf.lines_' . $log_num_lines, 'pandora_server.conf.lines_' . $log_num_lines);
			file_put_contents($tempDir . 'syslog.lines_' . $log_num_lines, getLastLinesLog("/var/log/syslog", $log_num_lines));
			$zip->addFile($tempDir . 'syslog.lines_' . $log_num_lines, 'syslog.lines_' . $log_num_lines);
		}
		
		$zip->close();

		$result['date'] = ui_print_timestamp(filectime($zipArchive), true);
		$result['success'] = true;
	}
	elseif (!$some_check) {
		$result['message'] = __('No options selected');
	}
	elseif (!$zip_openned) {
		$result['message'] = __('There was an error with the zip file');
	}

	return $result;
}

function mainSystemInfo() {
	global $config;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		
		return;
	}
	
	$show = (bool) get_parameter('show');
	$generate = (bool) get_parameter('generate');
	$pandora_diag = (bool) get_parameter('pandora_diag', 0);
	$system_info = (bool) get_parameter('system_info', 0);
	$log_info = (bool) get_parameter('log_info', 0);
	$log_num_lines = (int) get_parameter('log_num_lines', 2000);
	
	ui_print_page_header (__("System Info"), "images/extensions.png", false, "", true, "" );
	
	echo '<div class="notify">';
	echo __("This extension can run as PHP script in a shell for extract more information, but it must be run as root or across sudo. For example: <i>sudo php /var/www/pandora_console/extensions/system_info.php -d -s -c</i>");
	echo '</div>';
	
	$table = null;
	$table->width = '99%';
	$table->align = array();
	$table->align[1] = 'right';
	if ($pandora_diag) {
		$table->data[0][0] = '<a href="#diag_info">' .
			__('Pandora Diagnostic info') . "</a>";
	}
	else {
		$table->data[0][0] = __('Pandora Diagnostic info');
	}
	$table->data[0][1] = html_print_checkbox('pandora_diag', 1, $pandora_diag, true);
	if ($system_info) {
		$table->data[1][0] = '<a href="#system_info">' . __('System info') . '</a>';
	}
	else {
		$table->data[1][0] = __('System info');
	}
	$table->data[1][1] = html_print_checkbox('system_info', 1, $system_info, true);
	if ($log_info) {
		$table->data[2][0] = '<a href="#log_info">' . __('Log Info') . '</a>';
	}
	else {
		$table->data[2][0] = __('Log Info');
	}
	$table->data[2][1] = html_print_checkbox('log_info', 1, $log_info, true);
	$table->data[3][0] = __('Number lines of log');
	$table->data[3][1] = html_print_input_text('log_num_lines', $log_num_lines, __('Number lines of log'), 5, 10, true);
	

	$default_location = $config['attachment_store'] . '/last_info.zip';
	$file_exists = file_exists($default_location) && is_readable($default_location);

	$table_file = new StdClass();
	$table_file->id = "table_file";
	$table_file->width = '99%';
	$table_file->style = array();
	$table_file->style[0] = "font-weight: bold";
	$table_file->data = array();

	$display_file_link = $file_exists ? "" : "style=\"display: none;\"";
	$url = ui_get_full_url(false) . "attachment/last_info.zip";
	$file_link = "<a href=\"$url\" $display_file_link>";
	$file_link .= html_print_image('images/file.png', true, array('title' => __('Download'))); // Download image
	$file_link .= "</a>";

	$data = array();
	$data[0] = __('File');
	$data['cell-link'] = $file_link;
	$table_file->data['row_link'] = $data;

	$data = array();
	$data[0] = __('Created');
	$data['cell-date'] = $file_exists ? ui_print_timestamp(filectime($default_location), true) : '';
	$table_file->data['row_date'] = $data;

	$data = array();
	$data[0] = __('Location');
	$data['cell-location'] = $file_exists ? $default_location : '';
	$table_file->data['row_location'] = $data;

	echo "<form method='post' action='index.php?extension_in_menu=gsetup&sec=gextensions&sec2=extensions/system_info'>";
	
	html_print_table($table);
	
	$display_table_file = $file_exists ? "" : "style=\"display: none;\"";
	echo "<div id=\"table_file_container\" $display_table_file>";
	html_print_table($table_file);
	echo "</div>";

	echo "<br>";
	echo "<div style='width: " . $table->width . "; text-align: right;'>";
	html_print_submit_button(__('Generate file'), 'generate', false, 'class="sub next"');
	html_print_image('images/spinner.gif', false, array('id' => 'spinner_img', 'title' => __('Loading'), 'style' => 'display: none;'));
	echo "</div>";
	echo "</form>";

	if ($show) {
		if ($pandora_diag) {
			$info = array();
			getPandoraDiagnostic($info);
			show_array(__('Pandora Diagnostic info'), 'diag_info', $info);
		}
		
		if ($system_info) {
			$info = array();
			getSystemInfo($info);
			show_array(__('System info'), 'system_info', $info);
		}
		
		if ($log_info) {
			echo "<h1><a name='log_info'>" . __('Log Info') . "</a></h1>";
			getLastLog($log_num_lines);
		}
	}
	elseif ($generate) {
		$checks = array();
		$checks['pandora_diagnostic'] = $pandora_diag;
		$checks['system_info'] = $system_info;
		$checks['log_info'] = $log_info;
		$result = generate_info($checks);

		if ($result['success']) {
			echo '<b>' . __('File') . ':</b> ' . $result['url'] . '<br />';
			echo '<b>' . __('Location') . ':</b> ' . $result['location'];
		}
		elseif (isset($result['message'])) {
			echo $result['message'];
		}
		else {
			echo __('Error');
		}
	}

?>
<script type="text/javascript">

	$("#submit-generate").click(function(e) {
		e.preventDefault();

		if ($("#checkbox-pandora_diag").prop('checked')
				|| $("#checkbox-system_info").prop('checked')
				|| $("#checkbox-log_info").prop('checked')) {
			generate_info();
		} else {
			alert('<?php echo __('At least one option must be selected'); ?>');
		}
			
	});

	function generate_info () {
		$("#submit-generate").hide();
		$("#spinner_img").show();

		console.log($("#checkbox-pandora_diag").prop('checked'));
		console.log($("#checkbox-system_info").prop('checked'));
		console.log($("#checkbox-log_info").prop('checked'));

		$.ajax({
			url: 'ajax.php',
			type: 'POST',
			dataType: 'json',
			data: {
				page: <?php echo json_encode(EXTENSIONS_DIR) ?> + '/system_info',
				generate_info: 1,
				pandora_diag: Number($("#checkbox-pandora_diag").prop('checked')),
				system_info: Number($("#checkbox-system_info").prop('checked')),
				log_info: Number($("#checkbox-log_info").prop('checked'))
			},
			complete: function() {
				$("#spinner_img").hide();
				$("#submit-generate").show();
			},
			success: function(data) {

				if (data.success) {
					$("#table_file-row_link-cell-link").find("a").prop("href", data.url).show();
					$("#table_file-row_date-cell-date").html(data.date);
					$("#table_file-row_location-cell-location").html(data.location);
					$("#table_file_container").slideDown();
				}
				else {
					$("#table_file-row_link-cell-link").find("a").prop("href", "");
					$("#table_file-row_date-cell-date").html("");
					$("#table_file-row_location-cell-location").html("");
					$("#table_file_container").slideUp();
					alert(data.message);
				}
			},
			error: function(xhr, textStatus, errorThrown) {
				$("#table_file-row_link-cell-link").find("a").prop("href", "");
				$("#table_file-row_date-cell-date").html("");
				$("#table_file-row_location-cell-location").html("");
				$("#table_file_container").slideUp();
				alert('<?php echo __('Error'); ?>');
			}
		});
	}
</script>
<?php
}

function consoleMode() {
	//Execution across the shell
	global $config;
	global $argv;
	
	$tempDirSystem = sys_get_temp_dir();
	$nameDir = 'dir_' . uniqid();
	$tempDir = $tempDirSystem . '/' . $nameDir . '/';
	
	$result = mkdir($tempDir);
	
	if ($result == false) {
		echo "Error in creation of temp dir.";
		return;
	}
	
	$pandoraDiag = false;
	$pandoraSystemInfo = false;
	$pandoraConfFiles = false;
	
	if ((array_search('-h', $argv) !== false)
		|| (array_search('--help', $argv) !== false)) {
		echo "Usage is:\n" .
			"\t-h --help : show this help\n" .
			"\t-d --pandora_diagnostic : generate pandora diagnostic data\n" .
			"\t-s --system_info : generate system info data\n" .
			"\t-c --conf_files : generate conf\n";
	}
	else {
		$index = array_search('-d', $argv);
		if ($index === false) {
			$index = array_search('--pandora_diagnostic', $argv);
		}
		if ($index !== false) {
			$pandoraDiag = true;
		}
		
		$index = array_search('-s', $argv);
		if ($index === false) {
			$index = array_search('--system_info', $argv);
		}
		if ($index !== false) {
			$pandoraSystemInfo = true;
		}
		
		$index = array_search('-c', $argv);
		if ($index === false) {
			$index = array_search('--conf_files', $argv);
		}
		if ($index !== false) {
			$pandoraConfFiles = true;
		}
		
		if ($pandoraDiag) {
			$systemInfo = array();
			getPandoraDiagnostic($systemInfo);
			
			$file = fopen($tempDir . 'pandora_diagnostic.txt', 'w');
			
			if ($file !== false) {
				ob_start();
				foreach ($systemInfo as $index => $item) {
					if (is_array($item)) {
						foreach ($item as $secondIndex => $secondItem) {
							echo $index. ";" . $secondIndex . ";" . $secondItem . "\n";
						}
					}
					else {
						echo $index . ";" . $item . "\n";
					}
				}
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
		}
		
		if ($pandoraSystemInfo) {
			$systemInfo = array();
			getSystemInfo($systemInfo, true);
			
			$file = fopen($tempDir . 'system_info.txt', 'w');
			
			if ($file !== false) {
				ob_start();
				foreach ($systemInfo as $index => $item) {
					if (is_array($item)) {
						foreach ($item as $secondIndex => $secondItem) {
							if (is_array($secondItem)) {
								foreach ($secondItem as $thirdIndex => $thirdItem) {
									echo $index. ";" . $secondIndex . ";" . $thirdIndex . ";" . $thirdItem . "\n";
								}
							}
							else {
								echo $index. ";" . $secondIndex . ";" . $secondItem . "\n";
							}
						}
					}
					else {
						echo $index . ";" . $item . "\n";
					}
				}
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
		}
		
		if ($pandoraConfFiles) {
			$lines = 2000;

			$system_logs_directory = (!empty($config["server_log_dir"])) ? io_safe_output($config["server_log_dir"]) : "/var/log/pandora";
			
			$file = fopen($tempDir . 'pandora_console.log' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog($config["homedir"]."/pandora_console.log", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'pandora_server.log' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog($system_logs_directory."/pandora_server.log", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'pandora_server.error' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog($system_logs_directory."/pandora_server.error", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'my.cnf', 'w');
			if ($file !== false) {
				ob_start();
				echo file_get_contents('/etc/mysql/my.cnf');
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'my.cnf', 'w');
			if ($file !== false) {
				ob_start();
				echo file_get_contents($config["homedir"]."/include/config.php");
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'pandora_server.conf', 'w');
			if ($file !== false) {
				ob_start();
				echo file_get_contents("/etc/pandora/pandora_server.conf");
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'syslog' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog("/var/log/syslog", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'pandora_server.error' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog($system_logs_directory."/pandora_server.error", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'pandora_server.log' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog($system_logs_directory."/pandora_server.log", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
		}
		echo 'tar zcvf ' . $tempDirSystem . '/' . $nameDir . '.tar.gz ' . $tempDir . '*' . "\n";
		$result = shell_exec('tar zcvf ' . $tempDirSystem . '/' . $nameDir . '.tar.gz ' . $tempDir . '*');
		
		//TODO Delete the temp directory
		
		echo "You find the result file in " . $tempDirSystem . '/' . $nameDir . ".tar.gz\n";
	}
}

if (!isset($argv)) {
	//Execution across the browser
	extensions_add_godmode_function('mainSystemInfo');
	extensions_add_godmode_menu_option(__('System Info'), 'PM', 'gsetup', null, "v1r1");
}
else {
	$dir = dirname($_SERVER['PHP_SELF']);
	$dir = str_replace("\\", "/", $dir); // Windows compatibility
	if (file_exists($dir . "/../include/config.php"))
		include $dir . "/../include/config.php";
	
	consoleMode();
}
?>
