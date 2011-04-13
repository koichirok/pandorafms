package PandoraFMS::ProducerConsumerServer;
##########################################################################
# Pandora FMS generic server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2009 Artica Soluciones Tecnologicas S.L
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

use threads;
use threads::shared;
use Thread::Semaphore;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Server;

# inherits from PandoraFMS::Server
our @ISA = qw(PandoraFMS::Server);

########################################################################################
# ProducerConsumerServer class constructor.
########################################################################################
sub new ($$$$$;$) {
	my ($class, $config, $server_type, $producer,
	    $consumer, $dbh) = @_;

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, $server_type, $dbh);

	# Set producer/consumer functions
	$self->{'_producer'} = $producer;
	$self->{'_consumer'} = $consumer;

    bless $self, $class;
    return $self;
}

########################################################################################
# Get producer function.
########################################################################################
sub getProducer ($) {
	my $self = shift;
	
	return $self->{'_producer'};
}

########################################################################################
# Get consumer function.
########################################################################################
sub getConsumer ($) {
	my $self = shift;
	
	return $self->{'_consumer'};
}

###############################################################################
# Run.
###############################################################################
sub run ($$$$$) {
	my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;

	# Update server status and set server ID
	$self->update ();
	$self->setServerID ();

	# Launch consumer threads
	for (1..$self->getNumThreads ()) {
		my $thr = threads->create (\&PandoraFMS::ProducerConsumerServer::data_consumer, $self,
		              $task_queue, $pending_tasks, $sem, $task_sem);
		return unless defined ($thr);
		$self->addThread ($thr->tid ());
	}

	# Launch producer thread
	my $thr = threads->create (\&PandoraFMS::ProducerConsumerServer::data_producer, $self,
	              $task_queue, $pending_tasks, $sem, $task_sem);
	return unless defined ($thr);
	$self->addThread ($thr->tid ());
}

###############################################################################
# Queue pending tasks.
###############################################################################
sub data_producer ($$$$$) {
	my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;
	my $pa_config = $self->getConfig ();

	eval {
		# Connect to the DB
		my $dbh = db_connect ($pa_config->{'dbengine'}, $pa_config->{'dbname'}, $pa_config->{'dbhost'}, 3306,
							  $pa_config->{'dbuser'}, $pa_config->{'dbpass'});
		$self->setDBH ($dbh);

		while (1) {

			# Get pending tasks
			my @tasks = &{$self->{'_producer'}}($self);
			
			# Update queue size for statistics
			$self->setQueueSize (scalar @{$task_queue});

			foreach my $task (@tasks) {
				$sem->down;
				
				if (defined $pending_tasks->{$task}) {
					$sem->up;
					next;
				}
					
				# Queue task and signal consumers
				$pending_tasks->{$task} = 0;
				push (@{$task_queue}, $task);
				$task_sem->up;
				
				$sem->up;
			}

			threads->yield;
			sleep ($pa_config->{'server_threshold'});
		}
	};
	
	if ($@) {
		$self->setErrStr ($@);
	}
}

###############################################################################
# Execute pending tasks.
###############################################################################
sub data_consumer ($$$$$) {
	my ($self, $task_queue, $pending_tasks, $sem, $task_sem) = @_;
	my $pa_config = $self->getConfig ();

	eval {
		# Connect to the DB
		my $dbh = db_connect ($pa_config->{'dbengine'}, $pa_config->{'dbname'}, $pa_config->{'dbhost'}, 3306,
							  $pa_config->{'dbuser'}, $pa_config->{'dbpass'});
		$self->setDBH ($dbh);

		while (1) {

			# Wait for data
			$task_sem->down;

			$sem->down;
			my $task = shift (@{$task_queue});
			$sem->up;

			# Execute task
			&{$self->{'_consumer'}}($self, $task);

			# Update task status
			$sem->down;
			delete ($pending_tasks->{$task});
			$sem->up;

			threads->yield;
		}
	};

	if ($@) {
		$self->setErrStr ($@);
	}
}

1;
__END__
