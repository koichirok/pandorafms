<?php 

// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require_once ("include/config.php");

check_login (); 

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Database Management Event");
	require ("general/noaccess.php");
	return;
}


require ("godmode/db/times_incl.php");

$datos_rango3 = 0;
$datos_rango2 = 0;
$datos_rango1 = 0;

# ADQUIRE DATA PASSED AS FORM PARAMETERS
# ======================================
# Purge data using dates
# Purge data using dates
if (isset ($_POST["date_purge"])){
	$from_date = get_parameter_post ("date_purge");
	$query = sprintf ("DELETE FROM `tevento` WHERE `timestamp` < '%s'",$from_date);
	(int) $deleted = process_sql ($query);			
}
# End of get parameters block

echo "<h2>".__('dbmain_title')." &gt; ";
echo  __('db_purge_event')."</h2>";

echo "<table cellpadding='4' cellspacing='4' class='databox'>";
echo "<tr><td class='datos'>";
$row = get_db_row_sql ("SELECT COUNT(*) AS total, MIN(timestamp) AS first_date, MAX(timestamp) AS latest_date FROM tevento");

echo "<b>".__('total')."</b>";
echo "<td class='datos'>".$row["total"]." ".__('records')."</td>";

echo "<tr>";	
echo "<td class='datos2'><b>".__('first_date')."</b></td>";
echo "<td class='datos2'>".$row["first_date"]."</td></tr>";


echo "<tr><td class='datos'>";
echo "<b>".__('latest_date')."</b>";
echo "<td class='datos'>".$row["latest_date"]."</td>";
echo "</table>";
?>

<h3><?php echo __('purge_data') ?></h3>
<form name="db_audit" method="post" action="index.php?sec=gdbman&sec2=godmode/db/db_event">
<table width='300' cellpadding='4' cellspacing='4' class='databox'>
<tr><td class='datos'>
<select name="date_purge" width="255px">
<option value="<?php echo $month3 ?>"><?php echo __('purge_event_90day') ?>
<option value="<?php echo $month ?>"><?php echo __('purge_event_30day') ?>
<option value="<?php echo $week2 ?>"><?php echo __('purge_event_14day') ?>
<option value="<?php echo $week ?>"><?php echo __('purge_event_7day') ?>
<option value="<?php echo $d3 ?>"><?php echo __('purge_event_3day') ?>
<option value="<?php echo $d1 ?>"><?php echo __('purge_event_1day') ?>
<option value="<?php echo $all_data ?>"><?php echo __('purge_event_all') ?>
</select>

<td class="datos">
<input class="sub wand" type="submit" name="purgedb" value="<?php echo __('doit') ?>" onClick="if (!confirm('<?php  echo __('are_you_sure') ?>')) return false;">
</table>
</form>
