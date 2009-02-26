<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandorafms.org for full contribution list

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

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_events.php"); //Event processing functions

check_login ();

if (! give_acl ($config["id_user"], 0, "IR")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$delete = (bool) get_parameter ("delete");
$validate = (bool) get_parameter ("validate");
//Process deletion (pass array or single value)
if ($delete) {
	$eventid = (array) get_parameter ("eventid", -1);
	$return = delete_event ($eventid); //This function handles both single values as well arrays and cleans up before deleting
	print_error_message ($return, __('Events successfully deleted'), __('There was an error deleting events'));
}

//Process validation (pass array or single value)
if ($validate) {
	$eventid = (array) get_parameter ("eventid", -1);
	$return = process_event_validate ($eventid);
	print_error_message ($return, __('Events successfully validated'), __('There was an error validating events'));
}


// ***********************************************************************
// Main code form / page
// ***********************************************************************

$offset = (int) get_parameter ("offset", 0);
$ev_group = (int) get_parameter ("ev_group", 1); //1 = all
$search = preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "%", rawurldecode (get_parameter ("search"))); //Replace all types of &# HTML with % so that it doesn't fail on quotes

$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 0); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -1); //-1 all, 0 system
$id_event = (int) get_parameter ("id_event", -1);
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$groups = get_user_groups ($config["id_user"], "IR");
$event_view_hr = (int) get_parameter ("event_view_hr", $config["event_view_hr"]);
$id_user_ack = get_parameter ("id_user_ack", 0);
$group_rep = (int) get_parameter ("group_rep", 1);

//Group selection
if ($ev_group > 1 && in_array ($ev_group, array_keys ($groups))) {
	//If a group is selected and it's in the groups allowed
	$sql_post = " AND id_grupo = $ev_group";
} elseif (dame_admin ($config["id_user"])) {
	//Do nothing if you're admin, you get full access
	$sql_post = "";
	$groups[0] = __('System Events');
} else {
	//Otherwise select all groups the user has rights to.
	$sql_post = " AND id_grupo IN (".implode (",", array_keys ($groups)).")";
}

if ($status == 1) {
	$sql_post .= " AND estado = 1";
} elseif ($status == 0) {
	$sql_post .= " AND estado = 0";
}

if ($search != "")
	$sql_post .= " AND evento LIKE '%".$search."%'";
if ($event_type != "")
	$sql_post .= " AND event_type = '".$event_type."'";
if ($severity != -1)
	$sql_post .= " AND criticity >= ".$severity;
if ($id_agent != -1)
	$sql_post .= " AND id_agente = ".$id_agent;
if ($id_event != -1)
	$sql_post .= " AND id_evento = ".$id_event;
if ($id_user_ack  != 0)
	$sql_post .= " AND id_usuario == '".$id_user_ack."'";

if ($event_view_hr > 0) {
	$unixtime = get_system_time () - ($event_view_hr * 3600); //Put hours in seconds
	$sql_post .= " AND utimestamp > ".$unixtime;
}

$url = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;search=".rawurlencode($search)."&amp;event_type=".$event_type."&amp;severity=".$severity."&amp;status=".$status."&amp;ev_group=".$ev_group."&amp;refr=".$config["refr"]."&amp;id_agent=".$id_agent."&amp;id_event=".$id_event."&amp;pagination=".$pagination."&amp;group_rep=".$group_rep."&amp;event_view_hr=".$event_view_hr."&amp;id_user_ack=".$id_user_ack;

echo "<h2>".__('Events')." &gt; ".__('Main event view'). "&nbsp;";

if ($config["pure"] == 1) {
	echo '<a target="_top" href="'.$url.'&amp;pure=0">';
	print_image ("images/monitor.png", false, array ("title" => __('Normal screen')));
	echo '</a>';
} else {
	// Fullscreen
	echo '<a target="_top" href="'.$url.'&amp;pure=1">';
	print_image ("images/monitor.png", false, array ("title" => __('Full screen')));
	echo '</a>';
}
echo "</h2>";

//Link to toggle filter
echo '<a href="#" id="tgl_event_control"><b>'.__('Event control filter').'</b>&nbsp;'.print_image ("images/wand.png", true, array ("title" => __('Toggle filter'))).'</a>';

//Start div
echo '<div id="event_control" style="display:none">';

// Table for filter controls
echo '<form method="post" action="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr='.$config["refr"].'&amp;pure='.$config["pure"].'">';
echo '<table style="float:left;" width="550" cellpadding="4" cellspacing="4" class="databox"><tr>';

// Group combo
echo "<td>".__('Group')."</td><td>";
print_select ($groups, 'ev_group', $ev_group, 'javascript:this.form.submit();', '', 0, false, false, false, 'w130');
echo "</td>";

// Event type
echo "<td>".__('Event type')."</td><td>";
print_select (get_event_types (), 'event_type', $event_type, '', __('All'), '');
echo "</td></tr><tr>";

// Severity
echo "<td>".__('Severity')."</td><td>";
print_select (get_priorities (), "severity", $severity, '', __('All'), '-1');
echo '</td>';

// Status
echo "<td>".__('Event status')."</td><td>";
$fields = array ();
$fields[-1] = __('All event');
$fields[1] = __('Only validated');
$fields[0] = __('Only pending');

print_select ($fields, 'status', $status, 'javascript:this.form.submit();', '', '');

//NEW LINE
echo "</td></tr><tr>";

// Free search
echo "<td>".__('Free search')."</td><td>";
print_input_text ('search', $search, '', 15);
echo '</td>';

//Agent search
echo "<td>".__('Agent search')."</td><td>";
$sql = "SELECT DISTINCT(id_agente) AS id_agent FROM tevento WHERE 1=1 ".$sql_post;
$result = get_db_all_rows_sql ($sql);

if ($result === false)
	$result = array();

$agents = array ();
$agents[-1] = __('All');

if (dame_admin ($config["id_user"])) {
	$agents[0] = __('System');
}

foreach ($result as $id_row) {
	$name_for_combo = "";
	if ($id_row["id_agent"] > 0)
		$name_for_combo = mb_substr (get_agent_name ($id_row["id_agent"], "lower"),0,20);
	
	if ($name_for_combo != "")
		$agents[$id_row["id_agent"]] = $name_for_combo;
}

print_select ($agents, 'id_agent', $id_agent, 'javascript:this.form.submit();', '', '');
echo "</td></tr>";

// User selectable block size
echo '<tr><td>';
echo __('Block size for pagination');
echo '</td>';
$lpagination[25] = 25;
$lpagination[50] = 50;
$lpagination[100] = 100;
$lpagination[200] = 200;
$lpagination[500] = 500;

echo "<td>";
print_select ($lpagination, "pagination", $pagination, 'javascript:this.form.submit();', __('Default'), $config["block_size"]);
echo "</td>";

echo "<td>".__('Max. hours old')."</td>";
echo "<td>";
print_input_text ('event_view_hr', $event_view_hr, '', 5);
echo "</td>";


echo "</tr><tr>";
echo "<td>".__('User ack.')."</td>";
echo "<td>";
$users = get_users_info ();
print_select ($users, "id_user_ack", $id_user_ack, 'javascript:this.form.submit();', __('Any'), 0);
echo "</td>";

echo "<td>";
echo __("Repeated");
echo "</td><td>";

$repeated_sel[0] = __("All events");
$repeated_sel[1] = __("Group events");
print_select ($repeated_sel, "group_rep", $group_rep, 'javascript:this.form.submit();');
echo "</td></tr>";

echo '<tr><td colspan="4" style="text-align:right">';
//The buttons
print_submit_button (__('Update'), '', false, 'class="sub upd"');

// CSV
echo '&nbsp;&nbsp;&nbsp;<a href="operation/events/export_csv.php?ev_group='.$ev_group.'&amp;event_type='.$event_type.'&amp;search='.rawurlencode ($search).'&amp;severity='.$severity.'&amp;status='.$status.'&amp;id_agent='.$id_agent.'">';
print_image ("images/disk.png", false, array ("title" => __('Export to CSV file')));
echo '</a>';
// Marquee
echo '&nbsp;<a target="_top" href="operation/events/events_marquee.php">';
print_image ("images/heart.png", false, array ("title" => __('Marquee display')));
echo '</a>';
// RSS
echo '&nbsp;<a target="_top" href="operation/events/events_rss.php?ev_group='.$ev_group.'&amp;event_type='.$event_type.'&amp;search='.rawurlencode ($search).'&amp;severity='.$severity.'&amp;status='.$status.'&amp;id_agent='.$id_agent.'">';
print_image ("images/transmit.png", false, array ("title" => __('RSS Events')));
echo '</a>';

echo "</td></tr></table></form></div>"; //This is the filter div
echo '<div style="width:220px; float:left;">';
print_image ("reporting/fgraph.php?tipo=group_events&width=220&height=180&url=".rawurlencode ($sql_post), false, array ("border" => 0));
echo '</div><div style="clear:both">&nbsp;</div>';

if ($group_rep == 0) {
	$sql = "SELECT * FROM tevento WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC LIMIT ".$offset.",".$pagination;
} else {
	$sql = "SELECT *, COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep FROM tevento WHERE 1=1 ".$sql_post." GROUP BY evento, id_agentmodule ORDER BY timestamp_rep DESC LIMIT ".$offset.",".$pagination;	
} 

$result = get_db_all_rows_sql ($sql);

if ($group_rep == 0) {
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE 1=1 ".$sql_post;
} else {
	$sql = "SELECT COUNT(DISTINCT(evento)) FROM tevento WHERE 1=1 ".$sql_post;
}

$total_events = (int) get_db_sql ($sql);

if (empty ($result)) {
	$result = array ();
}

// Show pagination header
$offset = get_parameter ("offset", 0);
pagination ($total_events, $url."&pure=".$config["pure"], $offset, $pagination);		

// If pure, table width takes more space
if ($config["pure"] != 0) {
	$table->width = 765;
} else {
	$table->width = 750;
}

$table->id = "eventtable";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();

$table->head[0] = '';
$table->align[0] = 'center';

$table->head[1] = __('Type');
$table->headclass[1] = 'f9';
$table->align[1] = 'center';

$table->head[2] = __('Event name');

$table->head[3] = __('Agent name');
$table->align[3] = 'center';

$table->head[4] = __('Source');
$table->align[4] = 'center';

$table->head[5] = __('Group');
$table->align[5] = 'center';

if ($group_rep == 0) {
	$table->head[6] = __('User ID');
} else {
	$table->head[6] = __('Rep');
}
$table->align[6] = 'center';

$table->head[7] = __('Timestamp');
$table->align[7] = 'center';

$table->head[8] = __('Action');
$table->align[8] = 'center';

$table->head[9] = print_checkbox ("allbox", "1", false, true);
$table->align[9] = 'center';

//Arrange data. We already did ACL's in the query
foreach ($result as $row) {
	$data = array ();
	
	//First pass along the class of this row
	$table->rowclass[] = get_priority_class ($row["criticity"]);
	
	// Colored box
	if ($row["estado"] == 0) {
		$data[0] = print_image ("images/pixel_red.png", true, array ("width" => 20, "height" => 20, "title" => get_priority_name ($row["criticity"])));
	} else {
		$data[0] = print_image ("images/pixel_green.png", true, array ("width" => 20, "height" => 20, "title" => get_priority_name ($row["criticity"])));
	}
	
	$data[1] = print_event_type_img ($row["event_type"], true);
	
	// Event description
	$data[2] = '<span title="'.$row["evento"].'" class="f9">';
	$data[2] .= '<a href="'.$url.'&amp;group_rep=0&amp;id_agent='.$row["id_agente"].'&amp;pure='.$config["pure"].'&amp;search='.rawurlencode ($row["evento"]).'">';
	if (strlen ($row["evento"]) > 50) {
		$data[2] .= mb_substr ($row["evento"], 0, 50)."...";
	} else {
		$data[2] .= $row["evento"];
	}
	$data[2] .= '</a></span>';

	if ($row["event_type"] == "system") {
		$data[3] = __('System');
	} elseif ($row["id_agente"] > 0) {
		// Agent name
		$data[3] = print_agent_name ($row["id_agente"], true);
	} else {
		$data[3] = __('Alert').__('SNMP');
	}
	
	$data[4] = '';
	if ($row["id_agentmodule"] != 0) {
		$data[4] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$row["id_agente"].'&amp;tab=data">';
		$data[4] .= print_image ("images/bricks.png", true, array ("border" => 0, "title" => __('Go to data overview')));
		$data[4] .= '</a>&nbsp;';
	}
	if ($row["id_alert_am"] != 0) {
		$data[4] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$row["id_agente"].'&amp;tab=alert">';
		$data[4] .= print_image ("images/bell.png", true, array ("border" => 0, "title" => __('Go to alert overview')));
		$data[4] .= '</a>';
	}
	
	$data[5] = print_group_icon ($row["id_grupo"], true);

	if ($group_rep == 1) {
		$data[6] = $row["event_rep"];	
	} else {
		if (!empty ($row["estado"])) {
			if ($row["id_usuario"] != '0' && $row["id_usuario"] != ''){
			  $data[6] = '<a href="index.php?sec=usuario&amp;sec2=operation/user/user_edit&amp;ver='.$row["id_usuario"].'" title="'.dame_nombre_real ($row["id_usuario"]).'">'.mb_substr ($row["id_usuario"],0,8).'</a>';
			} else {
			  $data[6] = __('System');
			}
		} else {
			$data[6] = '';
		}
	}
	
	//Time	
	
	if ($group_rep == 1) {
		$data[7] = print_timestamp ($row['timestamp_rep'], true);
	} else {
		$data[7] = print_timestamp ($row["timestamp"], true);
	}
	
	//Actions
	$data[8] = '';
	// Validate event
	if (($row["estado"] == 0) and (give_acl ($config["id_user"], $row["id_grupo"], "IW") == 1)) {
		$data[8] .= '<a href="'.$url.'&amp;validate=1&amp;eventid='.$row["id_evento"].'&amp;pure='.$config["pure"].'">';
		$data[8] .= print_image ("images/ok.png", true, array ("border" => 0, "title" => __('Validate event')));
		$data[8] .= '</a>';
	}
	// Delete event
	if (give_acl ($config["id_user"], $row["id_grupo"], "IM") == 1) {
		$data[8] .= '<a href="'.$url.'&amp;delete=1&amp;eventid='.$row["id_evento"].'&amp;pure='.$config["pure"].'">';
		$data[8] .= print_image ("images/cross.png", true, array ("border" => 0, "title" => __('Delete event')));
		$data[8] .= '</a>';
	}
	// Create incident from this event			
	if (give_acl ($config["id_user"], $row["id_grupo"], "IW") == 1) {
		$data[8] .= '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;insert_form&amp;from_event='.$row["id_evento"].'">';
		$data[8] .= print_image ("images/page_lightning.png", true, array ("border" => 0, "title" => __('Create incident from event')));
		$data[8] .= '</a>';
	}
	
	//Checkbox
	$data[9] = print_checkbox_extended ("eventid[]", $row["id_evento"], false, false, false, 'class="chk"', true);
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	echo '<form method="post" action="'.$url.'&amp;pure='.$config["pure"].'">';

	print_table ($table);
	
	echo '<div style="width:750px; text-align:right">';
	if (give_acl ($config["id_user"], 0, "IW") == 1) {
		print_submit_button (__('Validate'), 'validate', false, 'class="sub ok"');
	}
	if (give_acl ($config["id_user"], 0,"IM") == 1) {
		print_submit_button (__('Delete'), 'delete', false, 'class="sub delete"');
	}
	echo '</div></form>';
	if ($config["pure"]== 0) {
		//Print legend
		echo '<div style="padding-left:30px; width:150px; float:left; line-height:17px;">';
		echo '<h3>'.__('Status').'</h3>';
		print_image ("images/pixel_green.png", false, array ("width" => 10, "height" => 10, "title" => __('Validated event')));
		echo ' - '.__('Validated event');
		echo '<br />';
		print_image ("images/pixel_red.png", false, array ("width" => 10, "height" => 10, "title" => __('Event not validated')));
		echo ' - '.__('Event not validated');
		echo '</div><div style="padding-left:30px; width:150px; float:left; line-height:17px;">';
		echo '<h3>'.__('Actions').'</h3>';
		print_image ("images/ok.png", false, array ("title" => __('Validate event')));
		echo ' - '.__('Validate event');
		echo '<br />';
		print_image ("images/cross.png", false, array ("title" => __('Delete event')));
		echo ' - '.__('Delete event');
		echo '<br />';
		print_image ("images/page_lightning.png", false, array ("title" => __('Create incident from event')));
		echo ' - '.__('Create incident from event');
		echo '</div><div style="clear:both;">&nbsp;</div>';
	}
} else {
	echo '<div class="nf">'.__('No events').'</div>';	
}

unset ($table);
?>
<script language="javascript" type="text/javascript">
	/* <![CDATA[ */
	$(document).ready( function() {
		$("INPUT[name='allbox']").click( function() {
			$("INPUT[name='eventid[]']").each( function() {
				$(this).attr('checked', !$(this).attr('checked'));
			});
			return !(this).attr('checked');
		});
		
		$("#tgl_event_control").click( function () {
			$("#event_control").toggle ();	
			return false;				  
		});
	});
	/* ]]> */
</script>
