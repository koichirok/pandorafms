<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, U

// Load global vars
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access to Database Purge Section");
	include ("general/noaccess.php");
	return;
}


if (isset ($_POST["agent"])){
	$id_agent = get_parameter_post ("agent");
} else
	$id_agent = -1;

echo '<h2>'.__('Database Maintenance').' &gt;'.__('Database purge')."</h2>";
echo "<img src='reporting/fgraph.php?tipo=db_agente_purge&id=$id_agent'>";
echo "<br><br>";
echo '<h3>'.__('Get data from agent').'</h3>';

// All data (now)
$purge_all = date ("Y-m-d H:i:s", time ());
	
require("godmode/db/times_incl.php");

$datos_rango3=0;
$datos_rango2=0;
$datos_rango1=0;
$datos_rango0=0; 
$datos_rango00=0; 
$datos_rango11=0; 
$datos_total=0;

# ADQUIRE DATA PASSED AS FORM PARAMETERS
# ======================================
# Purge data using dates
	

# Purge data using dates
if (isset($_POST["purgedb"])) {
	$from_date = get_parameter_post ("date_purge");
	if (isset($id_agent)){
		if ($id_agent != -1) {
			echo __('Purge task launched for agent id ').$id_agent." / ".$from_date;
			echo "<h3>".__('Please be patient. This operation can be very long in time (5-10 minutes)')."<br>",__('while deleting data for ').__('Agent')."</h3>";
			if ($id_agent == 0) {
				$sql="SELECT * FROM tagente_modulo";
			} else {
				$sql=sprintf("SELECT * FROM tagente_modulo WHERE id_agente = %d",$id_agent);
			}
			$result=get_db_all_rows_sql($sql);
			foreach ($result as $row) {
				echo __('Deleting records for module ').dame_nombre_modulo_agentemodulo($row["id_agente_modulo"]);
				flush();
				//ob_flush();
				echo "<br>";
				$sql = sprintf("DELETE FROM `tagente_datos` WHERE `id_agente_modulo` = '%d' AND `timestamp` < '%s'",$row["id_agente_modulo"],$from_date);
				process_sql ($sql);
				$sql = sprintf("DELETE FROM `tagente_datos_inc` WHERE `id_agente_modulo` = '%d' AND `timestamp` < '%s'",$row["id_agente_modulo"],$from_date);
				process_sql ($sql);
				$sql = sprintf("DELETE FROM `tagente_datos_string` WHERE `id_agente_modulo` = '%d' AND `timestamp` < '%s'",$row["id_agente_modulo"],$from_date);
				process_sql ($sql);
			}
		} else {
			echo __('Deleting records for module ').__('All agents');
			flush();
			//ob_flush();
			$query = sprintf("DELETE FROM `tagente_datos` WHERE `timestamp` < '%s'",$from_date);
			process_sql ($query);
			$query = sprintf("DELETE FROM `tagente_datos_inc` WHERE `timestamp` < '%s'",$from_date);
			process_sql ($query);
			$query = sprintf("DELETE FROM `tagente_datos_string` WHERE `timestamp` < '%s'",$from_date);
			process_sql ($query);
		}
		echo "<br><br>";
	}
}

# Select Agent for further operations.
?>
<form action='index.php?sec=gdbman&sec2=godmode/db/db_purge' method='post'>
<table class='databox'>
<tr><td class='datos'>
<select name='agent' class='w130'>

<?php
if (isset($_POST["agent"]) and ($id_agent > 0))
	echo "<option value='".$_POST["agent"]."'>".dame_nombre_agente($_POST["agent"]);
if (isset($_POST["agent"]) and ($id_agent == 0)){
	echo "<option value=0>".__('All agents');
echo "<option value=-1>".__('Choose agent');
} else {
	echo "<option value=-1>".__('Choose agent');
	echo "<option value=0>".__('All agents');
}
$result_t=mysql_query("SELECT * FROM tagente");
while ($row=mysql_fetch_array($result_t)){	
	echo "<option value='".$row["id_agente"]."'>".$row["nombre"];
}
?>
</select>
<a href="#" class="tip">&nbsp;<span><?php echo $help_label["db_purge0"] ?></span></a>
<td><input class='sub upd' type='submit' name='purgedb_ag' value='<?php echo __('Get data') ?>'>
<a href="#" class="tip">&nbsp;<span><?php echo $help_label["db_purge1"] ?></span></a>
</table><br>

<?php	
# End of get parameters block

if (isset($_POST["agent"]) and ($id_agent !=-1)){
	echo "<h3>".__('Data from agent ').dame_nombre_agente ($id_agent).__(' in the Database')."</h3>";
	
	$sql = "SELECT id_agente_modulo FROM tagente_modulo";
	if ($id_agent != 0) {
		$sql .= sprintf(" WHERE id_agente = '%d'",$id_agent);		
	}
	
	$datos_rango00 += get_db_sql (sprintf("SELECT COUNT(*) FROM `tagente_datos` WHERE `id_agente_modulo` = ANY(%s) AND `timestamp` > '%s'",$sql,$d1));
	$datos_rango0 += get_db_sql (sprintf("SELECT COUNT(*) FROM `tagente_datos` WHERE `id_agente_modulo` = ANY(%s) AND `timestamp` > '%s'",$sql,$d3));
	$datos_rango1 += get_db_sql (sprintf("SELECT COUNT(*) FROM `tagente_datos` WHERE `id_agente_modulo` = ANY(%s) AND `timestamp` > '%s'",$sql,$week));
	$datos_rango11 += get_db_sql (sprintf("SELECT COUNT(*) FROM `tagente_datos` WHERE `id_agente_modulo` = ANY(%s) AND `timestamp` > '%s'",$sql,$week2));
	$datos_rango2 += get_db_sql (sprintf("SELECT COUNT(*) FROM `tagente_datos` WHERE `id_agente_modulo` = ANY(%s) AND `timestamp` > '%s'",$sql,$month));		
	$datos_rango3 += get_db_sql (sprintf("SELECT COUNT(*) FROM `tagente_datos` WHERE `id_agente_modulo` = ANY(%s) AND `timestamp` > '%s'",$sql,$month3));
	$datos_total += get_db_sql (sprintf("SELECT COUNT(*) FROM `tagente_datos` WHERE `id_agente_modulo` = ANY(%s)",$sql));
		
}
?>

<table width='300' border='0' class='databox' cellspacing='4' cellpadding='4'>
<tr><td class=datos>
<?php echo __('Packets three months old')?>
</td>
<td class=datos>
<?php echo $datos_rango3; ?>
</td>

<tr><td class=datos2>
<?php echo __('Packets one month old')?>
</td>
<td class=datos2>
<?php echo $datos_rango2; ?>
</td>

<tr><td class=datos>
<?php echo __('Packets two weeks old')?>
</td>
<td class=datos>
<?php echo $datos_rango11; ?>
</td>

<tr><td class=datos2>
<?php echo __('Packets one week old')?>
</td>
<td class=datos2>
<?php echo $datos_rango1; ?>
</td>

<tr><td class=datos>
<?php echo __('Packets three days old')?>
</td>
<td class=datos>
<?php echo $datos_rango0; ?>
</td>

<tr><td class=datos2>
<?php echo __('Packets one day old')?>
</td>
<td class=datos2>
<?php echo $datos_rango00; ?>
</td>	
<tr><td class=datos>
<b><?php echo __('Total packets')?></b>
</td>
<td class=datos>
<b><?php echo $datos_total; ?></b>
</td>
</tr>
</table>
<br>
<h3><?php echo __('Purge data') ?></h3>
<table width='300' border='0' class='databox' cellspacing='4' cellpadding='4'>
<tr><td>
<select name="date_purge" width="255px">
<option value="<?php echo $month3 ?>"><?php echo __('Purge data over 90 days') ?>
<option value="<?php echo $month ?>"><?php echo __('Purge data over 30 days') ?>
<option value="<?php echo $week2 ?>"><?php echo __('Purge data over 14 days') ?>
<option value="<?php echo $week ?>"><?php echo __('Purge data over 7 days') ?>
<option value="<?php echo $d3 ?>"><?php echo __('Purge data over 3 days') ?>
<option value="<?php echo $d1 ?>"><?php echo __('Purge data over 1 day') ?>
</select>

<td><input class="sub wand" type="submit" name="purgedb" value="<?php echo __('Do it!') ?>" onClick="if (!confirm('<?php  echo __('Are you sure?') ?>')) return false;">
</table>
</form>
