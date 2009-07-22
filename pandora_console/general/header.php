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

require_once ("include/functions_messages.php");

?>
<table width="100%" cellpadding="0" cellspacing="0" style="margin:0px; padding:0px;" border="0">
	<tr>
		<td><div id="pandora_logo_header"></div></td>
		<td width="20%">
			<img src="images/user_<?=((is_user_admin ($config["id_user"]) == 1) ? 'suit' : 'green' );?>.png" class="bot" alt="user" />
			<a href="index.php?sec=usuarios&sec2=operation/users/user_edit" class="white"><?=__('You are');?> [<b><?=$config["id_user"];?></b>]</a>
			<?php
			$msg_cnt = get_message_count ($config["id_user"]);
			if ($msg_cnt > 0) {
				echo '<div id="dialog_messages" style="display: none"></div>';
	
				require_css_file ('dialog');
				require_jquery_file ('ui.core');
				require_jquery_file ('ui.dialog');
				echo '<a href="ajax.php?page=operation/messages/message" id="show_messages_dialog">';
				print_image ("images/email.png", false,
				array ("title" => __('You have %d unread message(s)', $msg_cnt), "id" => "yougotmail", "class" => "bot"));
				echo '</a>';
			}
			?>
			<br />
			<br />
			<a class="white_bold" href="index.php?bye=bye"><img src="images/log-out.png" alt="<?=__('Logout');?>" class="bot" /> <?=__('Logout');?></a>
		</td>
		<td width="20%">
			<a class="white_bold" href="index.php?sec=main"><img src="images/information.png" alt="info" class="bot" /><?=__('General information');?></a>
			<br />
			<br />
			<a class="white_bold" href="index.php?sec=estado_server&sec2=operation/servers/view_server&refr=60">
			<?php
			$servers["all"] = (int) get_db_value ('COUNT(id_server)','tserver');
			$servers["up"] = (int) check_server_status ();
			$servers["down"] = $servers["all"] - $servers["up"];
			if ($servers["up"] == 0) {
				//All Servers down or no servers at all
				echo '<img src="images/cross.png" alt="cross" class="bot" />&nbsp;'.__('All systems').': '.__('Down');
			}
			elseif ($servers["down"] != 0) {
				//Some servers down
				echo '<img src="images/error.png" alt="error" class="bot" />&nbsp;'.$servers["down"].' '.__('servers down');
			}
			else {
				//All servers up
				echo '<img src="images/ok.png" alt="ok" class="bot" />&nbsp;'.__('All systems').': '.__('Ready');
			}
			unset ($servers); // Since this is the header, we don't like to trickle down variables.
			?> 
			</a>
		</td>
		<td width="20%">
			<?php
			// Autorefresh
			$ignored_params = array ('agent_config' => false, 'code' => false);
			if ($config["refr"]) {
				$ignored_params['refr'] = 0;
				echo '<a id="autorefresh" class="white_grey_bold" href="'.get_url_refresh ($ignored_params).'"><img src="images/page_refresh.png" class="bot" alt="lightning" />&nbsp;'. __('Autorefresh');
				echo ' (<span id="refrcounter">'.date ("i:s", $config["refr"]).'</span>)';
				echo '</a>';
			}
			else {	
				$ignored_params['refr'] = '';
				echo '<a id="autorefresh" class="white_bold" href="'.get_url_refresh ($ignored_params).'"><img src="images/page_refresh.png" class="bot" alt="lightning" />&nbsp;'.__('Autorefresh').'</a>';
				$values = array (
					'5' => '5 '.__('seconds'),
					'10' => '10 '.__('seconds'),
					'15' => '15 '.__('seconds'),
					'30' => '30 '.__('seconds'),
					'60' => '1 '.__('minute'),
					'120' => '2 '.__('minutes'),
					'300' => '5 '.__('minutes'),
					'900' => '15 '.__('minutes'),
					'1800' => '30 '.__('minutes'),
					'3600' => '1 '.__('hour'));
				echo '<span id="combo_refr" style="display: none">';
				print_select ($values, 'ref', '', '', __('Select'), '0', false, false, false);
				unset ($values);
				echo '</span>';
			}
			?>
			<br />
			<br />
			<a class="white_bold" href="index.php?sec=eventos&sec2=operation/events/events&refr=5">
				<img src="images/lightning_go.png" alt="lightning_go" class="bot" /> <?=__('Events');?>
			</a>
		</td>
		<td width="20%">
			<div id="head_r"><span id="logo_text1">Pandora</span> <span id="logo_text2">FMS</span></div>
			<?php
			if (file_exists (ENTERPRISE_DIR."/load_enterprise.php")) 
				echo '<div id="logo_text3">Enterprise</div>';
			else
				echo '<div id="logo_text3">OpenSource</div>';
			?>
		</td>
	</tr>
	<tr>
		<td colspan="5" style="padding: 0; height: 20px;">
			<form method="get" style="" valign="middle">
				<script type="text/javascript" language="javascript">
				var fieldKeyWordEmpty = true;
				</script>
				<input type="text" id="keywords" name="keywords"
					<?php
					if (!isset($config['search_keywords']))
						echo "value='" . __("Enter keywords to search") . "'";
					else if (strlen($config['search_keywords']) == 0)
						echo "value='" . __("Enter keywords to search") . "'";
					else echo "value='" . $config['search_keywords'] . "'";
					?>
					onfocus="javascript: if (fieldKeyWordEmpty) $('#keywords').val('');"
					size="119" style="background: white url('images/lupa_15x15.png') no-repeat left; padding: 0; padding-left:15px; margin: 0; width: 70%; margin-left: 2px;" />
				<?php
				if (isset($config['search_category']))
					$selected = $config['search_category'];
				else
					$selected = 'all';
				$values = array (
					//'adsf' => ((strlen(__("Search")) > 12) ?  (substr(__("0123456789AB"), 0, 12) . "&hellip;") : __("0123456789AB")), 
					'all' => ((strlen(__("All")) > 12) ?  (substr(__("All"), 0, 12) . "&hellip;") : __("All")),
					'users' => ((strlen(__("Users")) > 12) ?  (substr(__("Users"), 0, 12) . "&hellip;") : __("Users")),
					'alerts' => ((strlen(__("Alerts")) > 12) ?  (substr(__("Alerts"), 0, 12) . "&hellip;") : __("Alerts")),
					'reports' => ((strlen(__("Reports")) > 12) ?  (substr(__("Reports"), 0, 12) . "&hellip;") : __("Reports"))
				);
				
				//INI SECURITY ACL
				if (check_acl ($config['id_user'], 0, "AW") || check_acl ($config['id_user'], 0, "AR")) {
					$values['agents'] = ((strlen(__("Agents")) > 12) ?  (substr(__("Agents"), 0, 12) . "&hellip;") : __("Agents"));
					$values['graphs'] = ((strlen(__("Graphs")) > 12) ?  (substr(__("Graphs"), 0, 12) . "&hellip;") : __("Graphs"));
				}
				if (give_acl ($config["id_user"], 0, "AR"))
					$values['maps'] = ((strlen(__("Maps")) > 12) ?  (substr(__("Maps"), 0, 12) . "&hellip;") : __("Maps"));;
				//END SECURITY ACL
				
				print_select_style ($values, 'search_category', $selected, "min-width: 12em;");
				?>
				<input id="submit-srcbutton" name="head_search_keywords"
					value="<?=((strlen(__("Search")) > 12) ?  (substr(__("0123456789ABCD"), 0, 12) . "&hellip;") : __("Search")); ?>"
					class="sub" type="submit" style="min-width: 12em; height: 23px;" />
			</form>
		</td>
	</tr>
</table>
<?php
require_jquery_file ('countdown');
?>

<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
<?php if ($msg_cnt > 0): ?>
	$("#yougotmail").pulsate ();
<?php endif; ?>
<?php if ($config["refr"]): ?>
	t = new Date();
	t.setTime (t.getTime () + <?php echo $config["refr"] * 1000; ?>);
	$("#refrcounter").countdown ({until: t, 
		layout: '%M%nn%M:%S%nn%S',
		labels: ['', '', '', '', '', '', ''],
		onExpiry: function () {
				$(this).text ("...");
			}
		});
<?php else: ?>
	$("a#autorefresh").click (function () {
		var a = this;
		
		$(this).hide ().unbind ("click");
		$("#combo_refr").show ();
		$("select#ref").change (function () {
			href = $(a).attr ("href");
			$(document).attr ("location", href + this.value);
		});
		
		return false;
	});
<?php endif; ?>
});
/* ]]> */
</script>
