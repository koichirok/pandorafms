<?php


/* Change to E_ALL for development/debugging */
error_reporting (E_ALL);

/* Database backend, not really tested with other backends, so it's 
 not functional right now */
define ('DB_BACKEND', 'mysql');

if (! extension_loaded ('mysql'))
	die ('Your PHP installation appears to be missing the MySQL extension which is required.');

require_once ('lib/libupdate_manager.php');

function get_user_key () {
	/* We only want to know this for statistics records.
	   Feel free to disable if you want. We don't want to hide anything.
	 */
	$user_key = get_db_value ('COUNT(`id_agente`)', 'tagente', 'disabled', 0);
	
	return $user_key;
}

flush ();
?>
