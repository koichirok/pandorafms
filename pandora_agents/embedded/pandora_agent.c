//     Pandora FMS Embedded Agent
//     (c) Artica Soluciones Tecnol�gicas S.L 2011
//     (c) Sancho Lerena <slerena@artica.es>

//     This program is free software; you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation; either version 2 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.


#include <sys/types.h>
#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <signal.h>
#include <errno.h>
#include <dirent.h> 
#include <unistd.h>
#include "pandora_type.h"
#include "pandora_util.h"
#include "pandora_config.h"

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif



int 
main(int argc, char **argv) {
   	DIR						*pDIR=NULL;
   	struct dirent			*pDirEnt=NULL;
	struct pandora_setup	*pandorasetup=NULL;
	struct pandora_module	*list=NULL;
	char					*config_file=NULL;
	char					*fullpath=NULL;
	char					*buffer=NULL;
	long int				id_audit;
	char					c;
	char					*xml_filename=NULL;


	printf ("Pandora FMS Embedded Agent v%s (c) 2011 http://pandorafms.org\n", VERSION);

	config_file = NULL;
	list=NULL;
	
	if (argc < 2 || argc > 3){
		printf ("Syntax is:\n\n    pandora_agent <path_to_pandora_agent.conf> \n\n");
		exit (0);
	}
	
	char *cmd = *argv++;
	config_file = *argv++;

	if (config_file == NULL) {
		printf ("Cannot load configuration file. Exitting \n");
		return -1;
	}
	
	pandorasetup = malloc(sizeof(struct pandora_setup));
	pandorasetup->logfile=NULL;
	pandorasetup->agent_name=NULL;
	pandorasetup->server_ip=NULL;
	pandorasetup->temporal=NULL;
	pandorasetup->sancho_test=NULL;

	// Initialize to default parameters
	init_parameters (pandorasetup);

	// Load config file using first parameter
  	parse_config (pandorasetup, &list, config_file);
	
	asprintf (&buffer,"Starting %s v%s", PACKAGE_NAME, VERSION);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);

	asprintf (&buffer,"Agent name: %s", pandorasetup->agent_name);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);

	asprintf (&buffer,"Server IP: %s", pandorasetup->server_ip);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);

	asprintf (&buffer,"Temporal: %s", pandorasetup->temporal);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);


	while (1){  // Main loop
		xml_filename = pandora_write_xml_disk (pandorasetup, list);
		if (pandorasetup->debug == 1){
			printf ("Debug mode activated. Exiting now! \n");
			exit (0);
		}

	 	tentacle_copy (xml_filename, pandorasetup);

		// Embedded agents Doesnt implement the "buffered" sending, 
		// if it cannot send, just drop the file

		unlink (xml_filename);
		pandora_free(xml_filename);
  		sleep(pandorasetup->interval);
	}
	return (0);
}
