<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once ('../include/functions_visual_map.php');

class Visualmap {
	private $correct_acl = false;
	private $acl = "RR";
	
	private $id = 0;
	private $visual_map = null;
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL($this->acl)) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	private function getFilters() {
		$system = System::getInstance();
		
		$this->id = (int)$system->getRequest('id', 0);
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->getFilters();
			
			$this->visualmap = db_get_row('tlayout',
				'id', $this->id);
			
			$this->show_visualmap();
		}
	}
	
	private function show_fail_acl() {
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$home = new Home();
		$home->show($error);
	}
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();
		
		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'render_map':
					$map_id = $system->getRequest('map_id', '0');
					$width = $system->getRequest('width', '400');
					visual_map_print_visual_map($map_id, false, true, $width);
					exit;
			}
		}
	 }
	
	private function show_visualmap() {
		$ui = Ui::getInstance();
		$system = System::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(
			sprintf("%s",
			$this->visualmap['name']),
			$ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Back'),
					'href' => 'index.php?page=visualmaps')));
		$ui->showFooter(false);
		$ui->beginContent();

		ob_start();
		$rendered_map = '<div id="rendered_visual_map">';
		$rendered_map .= html_print_image('images/spinner.gif', true);
		$rendered_map .= '</div>';
		ob_clean();
		
		$ui->contentAddHtml($rendered_map);
		$ui->contentAddHtml("<script type=\"text/javascript\">
				function ajax_load_map() {
					//$('#rendered_visual_map').html('<img src=\"images/spinner.gif\">);
					
					var map_max_width = window.innerWidth * 0.90;
					var map_max_height = (window.innerHeight - 47) * 0.90;

					var original_width = " . $this->visualmap['width'] . ";
					var original_height = " . $this->visualmap['height'] . ";
					
					var map_width = map_max_width;
					var map_height = original_height / (original_width / map_width);
					
					if(map_height > map_max_height) {
						map_height = map_max_height;
						map_width = original_width / (original_height / map_height);
					}

					postvars = {};
					postvars[\"action\"] = \"ajax\";
					postvars[\"parameter1\"] = \"visualmap\";
					postvars[\"parameter2\"] = \"render_map\";
					postvars[\"map_id\"] = \"" . $this->id . "\";
					postvars[\"width\"] = map_width;
					
					$.post(\"index.php\",
						postvars,
						function (data) {
							$('#rendered_visual_map').html(data);
						},
						\"html\");
				}
				
				ajax_load_map();
				
				$( window ).resize(function() {
					ajax_load_map();
				});
			</script>");
		$ui->endContent();
		$ui->showPage();
	}
}
?>