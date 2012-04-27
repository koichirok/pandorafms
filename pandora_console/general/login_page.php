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

$url = '?login=1';
//These variables come from index.php
if (!empty ($page) && !empty ($sec)) {
	foreach ($_GET as $key => $value) {
		$url .= '&amp;'.safe_url_extraclean($key).'='.safe_url_extraclean($value);
	}
}
echo '<img src="images/login_background.png" id="login_body">';

echo '<div class="databox_login" id="login">';

//	echo "<h1>Put here your custom welcome message</h1>";

//echo '<br /><br /><br />';
echo '
	<div id="login_in">
		<form method="post" action="index.php'.$url.'">';

//TODO: Put branding in variables (external file) or database
/* CUSTOM BRANDING STARTS HERE */

// Replace the following with your own URL and logo.
// A mashup of the Pandora FMS logo and your companies highly preferred
echo '&nbsp;&nbsp;<a href="http://pandorafms.org" title="Go to pandorafms.org...">';
if (defined ('PANDORA_ENTERPRISE')){
	html_print_image ("images/pandora_login_enterprise.png", false, array ("alt" => "logo", "border" => 0));
}
else {
	html_print_image ("images/pandora_login.png", false, array ("alt" => "logo", "border" => 0));	
}
echo '</a>';
		
// This prints the current pandora console version.
// For stable/live function it might be wise to comment it out

/* CUSTOM BRANDING ENDS HERE */


echo '<div style="text-align: center; height: 5px !important;">&nbsp;</div>'; 

if (!empty ($page) && !empty ($sec)) {
	foreach ($_POST as $key => $value) {
		html_print_input_hidden ($key, $value);
	}
}

echo '<br />'.html_print_input_text_extended ("nick", '', "nick", '', '', '' , false, '', 'class="login"', true).
   '<br>
		<br />'.html_print_input_text_extended ("pass", '', "pass", '', '', '' ,false, '', 'class="login"', true, true).
   '<br>';
	echo '<div style="float: right; margin-top: -70px; margin-right: 25px">';
	html_print_input_image ("Login", "images/login_botton.png", 'Login');
	echo '</div>';

echo '</form>
	</div>
</div>';

echo '<div id="bottom_logo">';
if (defined('PANDORA_ENTERPRISE')) 
	echo html_print_image('images/bottom_logo_enterprise.png', true, array ("alt" => "logo", "border" => 0));
else
	echo html_print_image('images/bottom_logo.png', true, array ("alt" => "logo", "border" => 0));
echo '</div>';
echo '<div id="ver_num">' . $pandora_version.(($develop_bypass == 1) ? ' '.__('Build').' '.$build_version : '') . '</div>';


if (isset ($login_failed)) {

	echo '<div id="login_failed" title="Login failed" style="">';

		echo '<div style="position:absolute; top:0px; text-align: center; left:0%; right:0%; height:100px; width:330px; margin: 0 auto; ">';
		
			echo '<div id="error_login" style="margin-top: 20px">';
			echo '<strong style="font-size: 10pt">' . $config["auth_error"] . '</strong>';
			echo '</div>';
	
			echo '<div id="error_login_icon">';
			echo html_print_image('images/error_login.png', true, array("alt" => __('Login failed'), "border" => 0));
			echo '</div>';
	  
			echo '<div style="position:absolute; margin: 0 auto; top: 70px; left: 35%; ">';	  
				html_print_submit_button("Ok", 'hide-login-error', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only" style="width:100px;"');  
			echo '</div>';
			
		echo '</div>';
			
	echo '</div>';	
}

ui_require_css_file ('dialog');
ui_require_jquery_file ('ui.core');
ui_require_jquery_file ('ui.dialog');
ui_require_jquery_file ('ui.draggable');

?>

<?php 
if (!isset ($login_failed)) {
?>
	<!--[if IE]>
	<div id="dialog" title="Pandora FMS Web browser advise" style="-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=50)'; filter: alpha(opacity=50); background:url(images/advise_navigator_background.png) no-repeat center bottom">

		<div style="position:absolute; top:20%; text-align: center; left:0%; right:0%; width:590px;">
			  <img src="images/error.png">
			<?php	  
			  echo __("In order to have the best user experience with Pandora FMS, we <b>strongly recommend</b> to use") . "<br>";
			  echo __("<a href='http://www.mozilla.org/en-US/firefox/fx/'>Mozilla Firefox</a> or <a href='https://www.google.com/chrome'>Google Chrome</a> browsers.") . "<br>"; 
			?>
			  <div style="position: absolute; top:200%; left:20%;">
			  <a href="http://www.mozilla.org/en-US/firefox/fx/"><img alt="Mozilla Firefox" title="Mozilla Firefox" src="images/mozilla_firefox.png"></a>
			  </div>
			  <div style="position: absolute; top:195%; right:20%;">
			  <a href="https://www.google.com/chrome"><img alt="Google Chrome" title="Google Chrome" src="images/google_chrome.png"></a>
			  </div>

			<div style="position: absolute; top:180px; right:43%;">	  
			<?php html_print_submit_button("Ok",'hide-advise',false,'class="sub" style="width:100px;"'); ?>	  
			</div>
		 </div> 
	</div>
	<![endif]-->
<?php
}
?>


<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$(document).ready (function () {		
	$(function() {
		$( "#dialog" ).dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 300,
				width: 600,
				overlay: {
							opacity: 0.5,
							background: "black"
						},
				bgiframe: jQuery.browser.msie
			});
	});
	
	$("#submit-hide-advise").click (function () {
		$("#dialog" ).dialog('close')
	});
	
	$(function() {
		$( "#login_failed" ).dialog({
				resizable: true,
				draggable: true,
				modal: true,
				height: 150,
				width: 350,
				overlay: {
							opacity: 0.5,
							background: "black"
						},
				bgiframe: jQuery.browser.msie
			});
	});	
	
	$("#submit-hide-login-error").click (function () {
		$("#login_failed" ).dialog('close')
	});	
	
});

document.getElementById('nick').focus();

/* ]]> */
</script>
