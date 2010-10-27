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


global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	pandora_audit("ACL Violation", "Trying to access Group Management2");
	require ("general/noaccess.php");
	return;
}

// Init vars
$icon = "";
$name = "";
$id_parent = 0;
$alerts_disabled = 0;
$custom_id = "";
$propagate = 0;

$create_group = (bool) get_parameter ('create_group');
$id_group = (int) get_parameter ('id_group');

if ($id_group) {
	$group = get_db_row ('tgrupo', 'id_grupo', $id_group);
	if ($group) {
		$name = $group["nombre"];
		$icon = $group["icon"].'.png';
		$alerts_disabled = $group["disabled"];
		$id_parent = $group["parent"];
		$custom_id = $group["custom_id"];
		$propagate = $group["propagate"];
	} else {
		echo "<h3 class='error'>".__('There was a problem loading group')."</h3>";
		echo "</table>"; 
		include ("general/footer.php");
		exit;
	}
}

// Header
if ($id_group) {
	print_page_header (__("Update group"), "images/god1.png", false, "", true, "");
} else {
	print_page_header (__("Create group"), "images/god1.png", false, "", true, "");
}

$table->width = '450px';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 35, 100, true);

$table->data[1][0] = __('Icon');
$files = list_files ('images/groups_small/', "png", 1, 0);
$table->data[1][1] = print_select ($files, 'icon', $icon, '', 'None', '', true);
$table->data[1][1] .= ' <span id="icon_preview">';
if ($icon) {
	$table->data[1][1] .= '<img src="images/groups_small/'.$icon.'" />';
}
$table->data[1][1] .= '</span>';

$table->data[2][0] = __('Parent');
$sql = 'SELECT id_grupo, nombre FROM tgrupo ';
if ($id_group)
	$sql .= sprintf ('WHERE id_grupo != %d', $id_group);
$groups = get_user_groups();
if ($id_group) {
	$table->data[2][1] = print_select_groups(false, "AR", true, 'id_parent', $id_parent,
		'', '', '', true, false, true, '', false, false, false, $id_group);
}
else {
	$table->data[2][1] = print_select_groups(false, "AR", true, 'id_parent', $id_parent, '', '', '', true);
}
$table->data[2][1] .= ' <span id="parent_preview">';
$table->data[2][1] .= '<img src="images/groups_small/'.get_group_icon ($id_parent).'.png" />';
$table->data[2][1] .= '</span>';

$table->data[3][0] = __('Alerts');
$table->data[3][1] = print_checkbox ('alerts_enabled', 1, ! $alerts_disabled, true);

$table->data[4][0] = __('Propagate ACL') . print_help_tip (__("Propagate the same ACL security into the child subgroups."), true);
$table->data[4][1] = print_checkbox('propagate', 1, $propagate, true);

$table->data[5][0] = __('Custom ID');
$table->data[5][1] = print_input_text ('custom_id', $custom_id, '', 16, 255, true);

echo '<form name="grupo" method="post" action="index.php?sec=gagente&sec2=godmode/groups/group_list">';
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_group) {
	print_input_hidden ('update_group', 1);
	print_input_hidden ('id_group', $id_group);
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_group', 1);
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';
?>
<script language="javascript" type="text/javascript">
function icon_changed () {
	var inputs = [];
	var data = this.value;
	$('#icon_preview').fadeOut ('normal', function () {
		$('#icon_preview').empty ();
		if (data != "") {
			$('#icon_preview').append ($('<img />').attr ('src', 'images/groups_small/'+data));
		}
		$('#icon_preview').fadeIn ();
	});
}

function parent_changed () {
	var inputs = [];
	inputs.push ("get_group_json=1");
	inputs.push ("id_group=" + this.value);
	inputs.push ("page=godmode/groups/group_list");
	jQuery.ajax ({
		data: inputs.join ("&"),
		type: 'GET',
		url: action="ajax.php",
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			var data_ = data;
			$('#parent_preview').fadeOut ('normal', function () {
				$('#parent_preview').empty ();
				if (data_ != null) {
					$('#parent_preview').append ($('<img />').attr ('src', 'images/groups_small/'+data['icon']+'.png'));
				}
				$('#parent_preview').fadeIn ();
			});
		}
	});
}

$(document).ready (function () {
	$('#icon').change (icon_changed);
	$('#id_parent').change (parent_changed);
}); 
</script>
