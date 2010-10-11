<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function getPandoraDiagnostic(&$systemInfo) {
	global $config;
	global $build_version;
	global $pandora_version;
	
	$systemInfo["Pandora FMS Build"] = $build_version;
	$systemInfo["Pandora FMS Version"] = $pandora_version;
	$systemInfo["Homedir"] = $config["homedir"];
	$systemInfo["HomeUrl"] = $config["homeurl"];
	
	$systemInfo["PHP Version"] = phpversion();
	
	$systemInfo['tagente'] = get_db_sql("SELECT COUNT(*) FROM tagente");
	$systemInfo['tagent_access'] = get_db_sql("SELECT COUNT(*) FROM tagent_access");
	$systemInfo['tagente_datos'] = get_db_sql("SELECT COUNT(*) FROM tagente_datos");
	$systemInfo['tagente_datos_string'] = get_db_sql("SELECT COUNT(*) FROM tagente_datos_string");
	$systemInfo['tagente_estado'] = get_db_sql("SELECT COUNT(*) FROM tagente_estado");
	$systemInfo['tagente_modulo'] = get_db_sql("SELECT COUNT(*) FROM tagente_modulo");
	$systemInfo['talert_actions'] = get_db_sql("SELECT COUNT(*) FROM talert_actions");
	$systemInfo['talert_commands'] = get_db_sql("SELECT COUNT(*) FROM tagente");
	$systemInfo['talert_template_modules'] = get_db_sql("SELECT COUNT(*) FROM talert_template_modules");
	$systemInfo['tlayout'] = get_db_sql("SELECT COUNT(*) FROM tlayout");
	if($config['enterprise_installed'])
		$systemInfo['tlocal_component'] = get_db_sql("SELECT COUNT(*) FROM tlocal_component");
	$systemInfo['tserver'] = get_db_sql("SELECT COUNT(*) FROM tserver");
	$systemInfo['treport'] = get_db_sql("SELECT COUNT(*) FROM treport");
	$systemInfo['ttrap'] = get_db_sql("SELECT COUNT(*) FROM ttrap");
	$systemInfo['tusuario'] = get_db_sql("SELECT COUNT(*) FROM tusuario");
	$systemInfo['tsesion'] = get_db_sql("SELECT COUNT(*) FROM tsesion");

	$systemInfo['db_scheme_version'] = get_db_sql("SELECT `value` FROM tconfig WHERE `token` = 'db_scheme_version'");
	$systemInfo['db_scheme_build'] = get_db_sql("SELECT `value` FROM tconfig WHERE `token` = 'db_scheme_build'");
	$systemInfo['enterprise_installed'] = get_db_sql("SELECT `value` FROM tconfig WHERE `token` = 'enterprise_installed'");
	$systemInfo['db_maintance'] = date ("Y/m/d H:i:s", get_db_sql ("SELECT `value` FROM tconfig WHERE `token` = 'db_maintance'"));
	$systemInfo['customer_key'] = get_db_sql("SELECT value FROM tupdate_settings WHERE `key` = 'customer_key';");
	$systemInfo['updating_code_path'] = get_db_sql("SELECT value FROM tupdate_settings WHERE `key` = 'updating_code_path'");
	$systemInfo['keygen_path'] = get_db_sql("SELECT value FROM tupdate_settings WHERE `key` = 'keygen_path'");
	$systemInfo['current_update'] = get_db_sql("SELECT value FROM tupdate_settings WHERE `key` = 'current_update'");
}

function getSystemInfo(&$systemInfo, $script = false) {
	$systemInfo['system_name'] = php_uname('s');
	$systemInfo['system_host'] = php_uname('n');
	$systemInfo['system_release'] = php_uname('r');
	$systemInfo['system_version'] = php_uname('v');
	$systemInfo['system_machine'] = php_uname('m');
	if (!$script) {
		$systemInfo['apache_version'] = apache_get_version();
		$systemInfo['apache_modules'] = apache_get_modules();
	}
	
//	$systemInfo['php_load_extensions'] = get_loaded_extensions();
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
	
	$result = shell_exec('du -h /var/log/pandora | cut -d"/" -f1');
	$systemInfo['size_var_log_pandora'] = $result;
	
	$result = shell_exec('date');
	$systemInfo['date'] = $result;
}

function getLastLinesLog($file, $numLines = 2000) {
	$result = shell_exec('tail -n ' . $numLines . ' ' . $file);
	
	return $result;
}

function show_logfile($file_name, $numLines) {
	global $config;

	if (!file_exists($file_name)){
		echo "<h2 class=error>".__("Cannot find file"). "(".$file_name;
		echo ")</h2>";
	} 
	else {
		echo "<h2>" . $file_name . "</h2>";
		echo "<textarea style='width: 95%; height: 200px;' name='$file_name'>";
		echo shell_exec('tail -n ' . $numLines . '  ' . $file_name);
		echo "</textarea>";
	}
}

function getLinesLog($file, $numLines = 2000) {
	
}

function getLastLog($show = true, $numLines = 2000) {
	global $config;
	
	show_logfile($config["homedir"]."/pandora_console.log", $numLines);
	show_logfile("/var/log/pandora/pandora_server.log", $numLines);
	show_logfile("/var/log/pandora/pandora_server.error", $numLines);
}

function mainSystemInfo() {
	global $config;

    if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	    audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to access Setup Management");
	    require ("general/noaccess.php");
	    return;
    }
    
    debugPrint($_POST);
    
    $show = (bool) get_parameter('show');
    $save = (bool) get_parameter('save');
    $pandora_diag = (bool) get_parameter('pandora_diag', 0);
    $system_info = (bool) get_parameter('system_info', 0);
    $log_info = (bool) get_parameter('log_info', 0);
    $log_num_lines = (int) get_parameter('log_num_lines', 2000);
    
    print_page_header (__("System Info"), "images/extensions.png", false, "", true, "" );
    
    echo '<div class="notify">';
   	echo __("This extension can run as PHP script in a shell for extract more information, but it must be run as root or across sudo. For example: <i>sudo php /var/www/pandora_console/extensions/system_info.php -d -s -c</i>");
    echo '</div>';
    
    echo "<form method='post'>";
    $table = null;
    $table->width = '80%';
    $table->align = array();
    $table->align[1] = 'right';
    $table->data[0][0] = __('Pandora Diagnostic info');
    $table->data[0][1] = print_checkbox('pandora_diag', 1, $pandora_diag, true);
    $table->data[1][0] = __('System info');
    $table->data[1][1] = print_checkbox('system_info', 1, $system_info, true);
	$table->data[2][0] = __('Log Info');
    $table->data[2][1] = print_checkbox('log_info', 1, $log_info, true);
	$table->data[3][0] = __('Number lines of log');
    $table->data[3][1] = print_input_text('log_num_lines', $log_num_lines, __('Number lines of log'), 5, 10, true);
    print_table($table);
    echo "<div style='width: " . $table->width . "; text-align: right;'>";
   	print_submit_button(__('Show'), 'show', false, 'class="sub next"');
   	echo "&nbsp;";
   	print_submit_button(__('Save'), 'save', false, 'class="sub wand"');
   	echo "</div>";
    echo "</form>";
    
    echo "<p>" . __('This tool is used just to view your Pandora FMS system logfiles directly from console') . "</p>";
    
//    $systemInfo = array();
    
//    if ($pandora_diag) {
//    	getPandoraDiagnostic($systemInfo);
//    }
//    if ($system_info) {
//    	getSystemInfo(&$systemInfo);
//    }
//    
//    if ($show) {
//    	$table = null;
//    	$table->width = '90%';    	
//    	$table->head = array();
//    	$table->head[0] = __('Info');
//    	$table->head[1] = __('Value');
//    	
//    	$table->valign = array();
//    	$table->valign[0] = 'top';
//    	
//    	$table->data = array();
//    	foreach ($systemInfo as $name => $info) {
//    		$row = array();
//    		
//    		switch ($name) {
//    			case 'apache_modules':
//    				foreach ($info as $name => $module) {
//    					$row = array();
//    					
//    					$row[] = 'apache_module';
//    					$row[] = $module;
//    					
//    					$table->data[] = $row;
//    				}
//    				break;
//    			case 'php_ini':
//    				foreach ($info as $name => $ini) {
//	    				$row = array();
//	    				
//	    				$row[] = $name;
//	    				$row[] = __('Global value: ') . $ini['global_value'] . __(' Local value: ') . $ini['local_value']; 
//	    				
//	    				$table->data[] = $row;
//    				}
//    				break;
//    			case 'php_load_extensions':
//    				foreach ($info as $name => $extension) {
//	    				$row = array();
//	    				
//	    				$row[] = $name;
//	    				$row[] = $extension;
//	    				
//    					$table->data[] = $row;
//    				}
//    				break;
//    			case 'disk':
//    				foreach ($info as $entry) {
//    					$row = array();
//	    				
//	    				$row[] = 'system_disk';
//	    				$row[] = 'Filesystem: ' . $entry['Filesystem'] . '<br />' .
//	    					'Size: ' . $entry['Size'] . '<br />' .
//	    					'Used: ' . $entry['Used'] . '<br />' .
//	    					'Use%: ' . $entry['Use%'] . '<br />' .
//	    					'Avail: ' . $entry['Avail'] . '<br />' .
//	    					'Mounted on: ' . $entry['Mounted_on'] . '<br />';
//    					$table->data[] = $row;
//    				}
//    				break;
//    			default:
//					$row[] = $name;
//		    		$row[] = $info;
//    	    		$table->data[] = $row;
//    				break;
//    		}
//    	}
//    	
//    	print_table($table);
//    	
//        if ($log_info) {
//    		getLastLog($show, $log_num_lines);
//    	}
//    }
}

function consoleMode() {
	//Execution across the shell
	$dir = dirname($_SERVER['PHP_SELF']);
	if (file_exists($dir . "/../include/config.php"))
		include $dir . "/../include/config.php";
		
	global $config;
		
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
				echo getLastLinesLog("/var/log/pandora/pandora_server.log", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'pandora_server.error' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog("/var/log/pandora/pandora_server.error", $lines);
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
				echo getLastLinesLog("/var/log/pandora/pandora_server.error", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
			
			$file = fopen($tempDir . 'pandora_server.log' . $lines, 'w');
			if ($file !== false) {
				ob_start();
				echo getLastLinesLog("/var/log/pandora/pandora_server.log", $lines);
				$output = ob_get_clean();
				fwrite($file, $output);
				fclose($file);
			}
		}
		echo 'tar zcvf ' . $tempDirSystem . '/' . $nameDir . '.tar.gz ' . $tempDir . '*' . "\n";
		$result = shell_exec('tar zcvf ' . $tempDirSystem . '/' . $nameDir . '.tar.gz ' . $tempDir . '*');
		
		echo "You find the result file in " . $tempDirSystem . '/' . $nameDir . ".tar.gz\n";
	}
}

if (!isset($argv)) {
	//Execution across the browser
	add_extension_godmode_function('mainSystemInfo');
	add_godmode_menu_option(__('System Info'), 'PM', 'glog');
}
else {
	consoleMode();
}
?>