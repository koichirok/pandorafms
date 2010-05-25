package PandoraFMS::DB;
##########################################################################
# Database Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2010 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use strict;
use warnings;
use DBI;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 
		db_connect
		db_disconnect
		db_do
		db_insert
		get_agent_id
		get_agent_name
		get_module_name
		get_db_rows
		get_db_single_row
		get_db_value
		get_group_name
		get_module_id
		get_nc_profile_name
		get_server_id
		get_group_id
		get_os_id
		get_template_id
		get_template_module_id
		is_group_disabled
	);

##########################################################################
## Connect to the DB.
##########################################################################
sub db_connect ($$$$$$) {
	my ($rdbms, $db_name, $db_host, $db_port, $db_user, $db_pass) = @_;

	if ($rdbms eq 'mysql') {
		
		# Connect to MySQL
		my $dbh = DBI->connect("DBI:mysql:$db_name:$db_host:3306", $db_user, $db_pass, { RaiseError => 1, AutoCommit => 1 });
		return undef unless defined ($dbh);
		
		# Enable auto reconnect
		$dbh->{'mysql_auto_reconnect'} = 1;

		return $dbh;
	}
	
	return undef;
}

##########################################################################
## Disconnect from the DB. 
##########################################################################
sub db_disconnect ($) {
	my $dbh = shift;

	$dbh->disconnect();
}

##########################################################################
## Return agent ID given the agent name.
##########################################################################
sub get_agent_id ($$) {
	my ($dbh, $agent_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_agente FROM tagente WHERE nombre = ? OR direccion = ?", $agent_name, $agent_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return server ID given the name of server.
##########################################################################
sub get_server_id ($$$) {
	my ($dbh, $server_name, $server_type) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_server FROM tserver
					WHERE name = ? AND server_type = ?",
					$server_name, $server_type);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return group ID given the group name.
##########################################################################
sub get_group_id ($$) {
	my ($dbh, $group_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_grupo FROM tgrupo WHERE nombre = ?", $group_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return OS ID given the OS name.
##########################################################################
sub get_os_id ($$) {
	my ($dbh, $os_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_os FROM tconfig_os WHERE name = ?", $os_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## SUB dame_agente_nombre (id_agente)
## Return agent name, given "id_agente"
##########################################################################
sub get_agent_name ($$) {
	my ($dbh, $agent_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tagente WHERE id_agente = ?", $agent_id);
}

##########################################################################
## SUB get_module_name(module_id)
## Return the module name, given "module_id"
##########################################################################
sub get_module_name ($$) {
	my ($dbh, $module_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ?", $module_id);
}

##########################################################################
## Return module id given the module name and agent id.
##########################################################################
sub get_agent_module_id ($$$) {
	my ($dbh, $module_name, $agent_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id_agente_modulo FROM tagente_modulo WHERE nombre = ? AND id_agente = ?", $module_name, $agent_id);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return template id given the template name.
##########################################################################
sub get_template_id ($$) {
	my ($dbh, $template_name) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id FROM talert_templates WHERE name = ?", $template_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return the module template id given the module id and the template id.
##########################################################################
sub get_template_module_id ($$$) {
	my ($dbh, $module_id, $template_id) = @_;
	
	my $rc = get_db_value ($dbh, "SELECT id FROM talert_template_modules WHERE id_agent_module = ? AND id_alert_template = ?", $module_id, $template_id);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Returns true if the given group is disabled, false otherwise.
##########################################################################
sub is_group_disabled ($$) {
	my ($dbh, $group_id) = @_;

	return get_db_value ($dbh, "SELECT disabled FROM tgrupo WHERE id_grupo = ?", $group_id);
}

##########################################################################
## Return module ID given the module name.
##########################################################################
sub get_module_id ($$) {
	my ($dbh, $module_name) = @_;

	my $rc = get_db_value ($dbh, "SELECT id_tipo FROM ttipo_modulo WHERE nombre = ?", $module_name);
	return defined ($rc) ? $rc : -1;
}

##########################################################################
## Return a network component's profile name given its ID.
##########################################################################
sub get_nc_profile_name ($$) {
	my ($dbh, $nc_id) = @_;
	
	return get_db_value ($dbh, "SELECT * FROM tnetwork_profile WHERE id_np = ?", $nc_id);
}

##########################################################################
## Return a group's name given its ID.
##########################################################################
sub get_group_name ($$) {
	my ($dbh, $group_id) = @_;
	
	return get_db_value ($dbh, "SELECT nombre FROM tgrupo WHERE id_grupo = ?", $group_id);
}

##########################################################################
## Get a single column returned by an SQL query as a hash reference.
##########################################################################
sub get_db_value ($$;@) {
		my ($dbh, $query, @values) = @_;

		# Cache statements
		my $sth = $dbh->prepare_cached($query);
		$sth->execute(@values);

		# No results
		if ($sth->rows == 0) {
			$sth->finish();
			return undef;
		}
		
		my $row = $sth->fetchrow_arrayref();
		$sth->finish();
		return $row->[0];
}

##########################################################################
## Get a single row returned by an SQL query as a hash reference. Returns
## -1 on error.
##########################################################################
sub get_db_single_row ($$;@) {
		my ($dbh, $query, @values) = @_;

		# Cache statements
		my $sth = $dbh->prepare_cached($query);

		$sth->execute(@values);

		# No results
		if ($sth->rows == 0) {
			$sth->finish();
			return undef;
		}
		
		my $row = $sth->fetchrow_hashref();
		$sth->finish();
		return $row;
}

##########################################################################
## Get all rows returned by an SQL query as a hash reference array.
##########################################################################
sub get_db_rows ($$;@) {
		my ($dbh, $query, @values) = @_;
		my @rows;

		# Cache statements
		my $sth = $dbh->prepare_cached($query);

		$sth->execute(@values);

		# Save returned rows
		while (my $row = $sth->fetchrow_hashref()) {
			push (@rows, $row);
		}

		$sth->finish();
		return @rows;
}

##########################################################################
## SQL insert. Returns the ID of the inserted row.
##########################################################################
sub db_insert ($$;@) {
	my ($dbh, $query, @values) = @_;

	$dbh->do($query, undef, @values);
	return $dbh->{'mysql_insertid'};
}

##########################################################################
## Generic SQL sentence. 
##########################################################################
sub db_do ($$;@) {
	my ($dbh, $query, @values) = @_;

	#DBI->trace( 3, '/tmp/dbitrace.log' );

	$dbh->do($query, undef, @values);
}

# End of function declaration
# End of defined Code

1;
__END__
