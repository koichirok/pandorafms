--
-- Dumping data for table `talerta`
--

INSERT INTO `talerta` VALUES (1,'eMail','echo _field3_ | sendmail -s _field2_ _field1_','Send email from Pandora Server. mail is a default command on all standard Unix systems, using:\r\n_field1_ as destination email address, and\r\n_field2_ as subject for message. \r\n_field3_ as text of message.');
INSERT INTO `talerta` VALUES (2,'LogFile','echo _timestamp_ pandora _field1_ _field2_  &gt;&gt; /var/log/pandora_alert.log','This is a default alert to write alerts in a standard ASCII  plaintext log file in /var/log/pandora_alert.log\r\n');
INSERT INTO `talerta` VALUES (3,'Internal Audit','','This alert save alert in Pandora interal audit system. Fields are static and only _field1_ is used.');
INSERT INTO `talerta` VALUES (4,'SNMP Trap','/usr/bin/snmptrap -v 1 -c trap_public 192.168.0.4 1.1.1.1.1.1.1.1 _agent_ _field1_','Send a SNMPTRAP to 192.168.0.4. Please review config and adapt to your needs, this is only a sample, not functional itself.');
INSERT INTO `talerta` VALUES (5,'SMS Text','echo _field2_ | mail -s PANDORA_field1_ myuser@smsgateway.com','Send SMS via e-mail gateway. Use field1 for a short SMS text (35 chars) and field 2 for text message (full SMS)');
INSERT INTO `talerta` VALUES (6,'Syslog','logger -p daemon.alert Pandora Alert _agent_ _field1_ _field2_','Uses field1 and field2 to generate Syslog alert in facility daemon with alert level.');
INSERT INTO `talerta` VALUES (7,'Sound Alert','/usr/bin/play /usr/share/sounds/alarm.wav','');
INSERT INTO `talerta` VALUES (8,'Jabber Alert','echo _field3_ | sendxmpp -r _field1_ --chatroom _field2_','Send jabber alert to chat room in a predefined server (configure first .sendxmpprc file). Uses field3 as text message, field1 as useralias for source message, and field2 for chatroom name');
INSERT INTO `talerta` VALUES (9,'Synthetized Speech','flite -t _FIELD2_','Uses commandline voice synthetizer to \"speak\" text given as parameter 1 and 2');


--
-- Dumping data for table `tconfig`
--


/*!40000 ALTER TABLE `tconfig` DISABLE KEYS */;
LOCK TABLES `tconfig` WRITE;
INSERT INTO `tconfig` VALUES 
(1,'language_code','en'),(3,'block_size','20'),(4,'days_purge','60'),(5,'days_compact','15'),(6,'graph_res','5'),(7,'step_compact','1'),(8,'db_scheme_version','1.4'),(9,'db_scheme_build','PD80110'),(12,'bgimage','background4.jpg'),(13,'show_unknown','0'),(14,'show_lastalerts','1'),(15,'style','pandora');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tconfig` ENABLE KEYS */;

--
-- Dumping data for table `tconfig_os`
--


/*!40000 ALTER TABLE `tconfig_os` DISABLE KEYS */;
LOCK TABLES `tconfig_os` WRITE;
INSERT INTO `tconfig_os` VALUES (1,'GNU/Linux','Linux: All versions','so_linux.png'),(2,'Solaris','Sun Solaris','so_solaris.png'),(3,'AIX','IBM AIX','so_aix.png'),(4,'BSD','OpenBSD, FreeBSD and Others','so_bsd.png'),(5,'HP-UX','HP-UX Unix OS','so_hpux.png'),(6,'BeOS','BeOS','so_beos.png'),(7,'Cisco','CISCO IOS','so_cisco.png'),(8,'MacOS','MAC OS','so_mac.png'),(9,'Windows','Microsoft Windows OS','so_win.png'),(10,'Other','Other SO','so_other.png'),(11,'Network','Pandora Network Agent','network.png');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tconfig_os` ENABLE KEYS */;

--
-- Dumping data for table `tgrupo`
--

LOCK TABLES `tgrupo` WRITE;
INSERT INTO `tgrupo` VALUES (1,'All','world',0,0);
INSERT INTO `tgrupo` VALUES (2,'Servers','server_database',0,0);
INSERT INTO `tgrupo` VALUES (3,'IDS','eye',0,0);
INSERT INTO `tgrupo` VALUES (4,'Firewalls','firewall',0,0);
INSERT INTO `tgrupo` VALUES (8,'Databases','database_gear',0,0);
INSERT INTO `tgrupo` VALUES (9,'Comms','transmit',0,0);
INSERT INTO `tgrupo` VALUES (10,'Others','house',0,0);
INSERT INTO `tgrupo` VALUES (11,'Workstations','computer',0,0);
INSERT INTO `tgrupo` VALUES (12,'Applications','applications',0,0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `tgrupo` ENABLE KEYS */;

--
-- Dumping data for table `tlanguage`
--


/*!40000 ALTER TABLE `tlanguage` DISABLE KEYS */;
LOCK TABLES `tlanguage` WRITE;
INSERT INTO `tlanguage` VALUES ('en','English');
/*INSERT INTO `tlanguage` VALUES ('es_es','Espa&ntilde;ol');
INSERT INTO `tlanguage` VALUES ('de','Deutch');
INSERT INTO `tlanguage` VALUES ('fr','Fran&ccedil;ais');
INSERT INTO `tlanguage` VALUES ('pt_br','Portugu&ecirc;s-Brasil'); */

UNLOCK TABLES;
/*!40000 ALTER TABLE `tlanguage` ENABLE KEYS */;

--
-- Dumping data for table `tlink`
--


/*!40000 ALTER TABLE `tlink` DISABLE KEYS */;
LOCK TABLES `tlink` WRITE;
INSERT INTO `tlink` VALUES (0000000001,'GeekTools','www.geektools.com'),(0000000002,'CentralOPS','http://www.centralops.net/'),(0000000003,'Pandora Project','http://pandora.sourceforge.net'),(0000000004,'Babel Project','http://babel.sourceforge.net'),(0000000005,'Google','http://www.google.com'),(0000000006,'ArticaST','http://www.artica.es');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tlink` ENABLE KEYS */;

--
-- Dumping data for table `tmodule_group`
--


/*!40000 ALTER TABLE `tmodule_group` DISABLE KEYS */;
LOCK TABLES `tmodule_group` WRITE;
INSERT INTO `tmodule_group` VALUES (1,'General'),(2,'Networking'),(3,'Application'),(4,'System'),(5,'Miscellaneous');
UNLOCK TABLES;
/*!40000 ALTER TABLE `tmodule_group` ENABLE KEYS */;


--
-- Dumping data for table `torigen`
--
INSERT INTO `torigen` VALUES ('Operating System event'),('IDS events'),('Firewall records'),('Database event'),('Application data'),('Logfiles'),('Other data source'),('Pandora FMS Event'),('User report'),('Unknown source');

--
-- Dumping data for table `ttipo_modulo`
--

INSERT INTO `ttipo_modulo` VALUES 
(1,'generic_data',0,'Generic module to adquire numeric data','mod_data.png'),
(2,'generic_proc',1,'Generic module to adquire boolean data','mod_proc.png'),
(3,'generic_data_string',2,'Generic module to adquire alphanumeric data','mod_string.png'),
(4,'generic_data_inc',0,'Generic module to adquire numeric incremental data','mod_data_inc.png'),

(6,'remote_icmp_proc',4,'Remote ICMP network agent, boolean data','mod_icmp_proc.png'),
(7,'remote_icmp',3,'Remote ICMP network agent (latency)','mod_icmp_data.png'),
(8,'remote_tcp',3,'Remote TCP network agent, numeric data','mod_tcp_data.png'),
(9,'remote_tcp_proc',4,'Remote TCP network agent, boolean data','mod_tcp_proc.png'),
(10,'remote_tcp_string',5,'Remote TCP network agent, alphanumeric data','mod_tcp_string.png'),
(11,'remote_tcp_inc',3,'Remote TCP network agent, incremental data','mod_tcp_inc.png'),
(15,'remote_snmp',3,'Remote SNMP network agent, numeric data','mod_snmp_data.png'),
(16,'remote_snmp_inc',3,'Remote SNMP network agent, incremental data','mod_snmp_inc.png'),
(17,'remote_snmp_string',5,'Remote SNMP network agent, alphanumeric data','mod_snmp_string.png'),
(18,'remote_snmp_proc',4,'Remote SNMP network agent, boolean data','mod_snmp_proc.png'), 

(19,'image_jpg',9,'Image JPG data', 'mod_image_jpg.png'), 
(20,'image_png',9,'Image PNG data', 'mod_image_png.png'), 
(21,'async_proc', 7, 'Asyncronous proc data', 'mod_async_proc.png'), 
(22,'async_data', 6, 'Asyncronous numeric data', 'mod_async_data.png'), 
(23,'async_string', 8, 'Asyncronous string data', 'mod_async_string.png'), 
(24,'predictive', 10, 'Predictive Estimation Data', 'mod_predictive.png'),
(100,'keep_alive',-1,'KeepAlive','mod_keepalive.png');

/* Categoria field is used to segregate several types (plugin, agents, network) on their data
  types, could be used or could be avoided and use directly primary key (id_tipo) */

--
-- Dumping data for table `tusuario`
--

INSERT INTO `tusuario` VALUES ('admin','Default Admin','1da7ee7d45b96d0e1f45ee4ee23da560','Admin Pandora','2007-03-27 18:59:39','admin_pandora@nowhere.net','555-555-555',1);

--
-- Dumping data for table `tusuario_perfil`
--

INSERT INTO `tusuario_perfil` VALUES (1,'admin',5,1,'admin');

--
-- Dumping data for table `tperfil`
--

INSERT INTO `tperfil` VALUES (1,'Operator (Read)',0,1,0,1,0,0,0,0,0,0),(2,'Operator (Write)',1,1,0,1,0,0,0,0,0,0),(3,'Chief Operator',1,1,1,1,0,0,0,0,0,0),(4,'Group coordinator',1,1,1,1,1,1,1,0,0,0),(5,'Pandora Administrator',1,1,1,1,1,1,1,1,1,1);

INSERT INTO `tnews` VALUES (1,'admin','Welcome to Pandora FMS!','This is our new console, a lot of new features has been added from last version. Please read documentation about it and be free to test any option.\r\n\r\nPandora FMS team.','2008-02-26 13:03:20');

INSERT INTO tmodule VALUES (1,'Agent module');
INSERT INTO tmodule VALUES (2,'Network module');
INSERT INTO tmodule VALUES (4,'Plugin module');
INSERT INTO tmodule VALUES (5,'IA module');
INSERT INTO tmodule VALUES (6,'WMI module');

INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (1,'OS Total process','Total process in Operating System (UNIX MIB)',13,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemProcesses.0 ',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (2,'OS CPU Load (1 min)','CPU Load in Operating System (UNIX MIB)',13,15,0,0,180,0,'','','public','UCD-SNMP-MIB::laLoad.1',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (3,'Sysname','Get name of system using SNMP standard MIB',1,17,0,0,900,0,'','','public','.1.3.6.1.2.1.1.1.0',1);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (4,'OS Users','Active users in Operating System (UNIX MIB)',13,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemNumUsers.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (11,'Catalyst CPU Usage (5min)','Cisco Catalyst Switches CPU Usage. Taken from ftp://ftp.cisco.com/pub/mibs/oid/OLD-CISCO-CPU-MIB.oid',2,15,0,0,180,0,'','','public','1.3.6.1.4.1.9.2.1.58',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (22,'HSRP Status','Get status of HSRP',2,18,0,0,180,0,'','','public','1.3.6.1.4.1.9.9.106.1.2.1.1.15.12.106',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (24,'NIC #1 status','Status of NIC#1',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.1',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (25,'NIC #2 status','Status of NIC #2',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.2',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (26,'NIC #3 status','Status of NIC #3',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.3',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (27,'NIC #1 outOctects','Output throughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.1',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (28,'NIC #2 outOctects','Output troughtput on interface #2',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.2',1);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (29,'NIC #3 outOctects','Output troughtput on Interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.3',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (30,'NIC #1 inOctects','Input troughtput on Interface #1',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.1',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (31,'NIC #2 inOctects','Input throughtput for interface #2',10,16,0,0,180,0,'','NULL','public','.1.3.6.1.2.1.2.2.1.10.2',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (32,'NIC #3 inOctects','Input throught on interface #3',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.3',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (34,'Host Alive','Check if host is alive using ICMP ping check.',10,6,0,0,120,0,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (36,'Host Latency','Get host network latency in miliseconds, using ICMP.',10,7,0,0,180,0,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (37,'Check HTTP Server','Test APACHE2 HTTP service remotely (Protocol response, not only openport)',10,9,0,0,300,80,'GET / HTTP/1.0^M^M','HTTP/1.1 200 OK','','',3);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (38,'Check FTP Server','Check FTP protocol, not only check port.',10,9,0,0,300,21,'QUIT','221','','',3);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (39,'Check SSH Server','Checks port 22 is opened',10,9,0,0,300,22,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (40,'Check Telnet server','Check telnet port',10,9,0,0,300,23,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (41,'Check SMTP server','Check if SMTP port it&#039;s open',10,9,0,0,300,25,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (42,'Check POP3 server','Check POP3 port.',10,9,0,0,300,110,'','','','',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (43,'NIC #7 outOctects','Get outcoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.16.7',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (44,'NIC #7 inOctects','Get incoming octects from NIC #7',10,16,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.10.7',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (45,'NIC #4 Status','Get status of NIC #4',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.4',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (46,'NIC #5 Status','Get status of NIC #5',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.5',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (47,'NIC #6 Status','Get status of NIC #6',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.6',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (48,'NIC #7 Status','Get status of NIC #7',10,18,0,0,180,0,'','','public','.1.3.6.1.2.1.2.2.1.8.7',2);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (49,'OS CPU Load (5 min)','CPU load on a 5 min average interval. UCD-SNMP Mib (Usually for all Linux and some UNIX)',13,15,0,0,180,0,'','','public','UCD-SNMP-MIB::laLoad.2',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (50,'System Description','Get system description (all mibs).',1,17,0,0,9000,0,'','','public','SNMPv2-MIB::sysDescr.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (51,'OS Raw Interrupts','Get system raw interrupts from SO',13,16,0,0,180,0,'','','public','UCD-SNMP-MIB::ssRawInterrupts.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (52,'OS IO Signals sent','IO Signals sent by Kernel',13,16,0,0,180,0,'','','public','UCD-SNMP-MIB::ssIOSent.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (53,'System Uptime','Sistem uptime in timeticks',1,15,0,0,180,0,'','','public','HOST-RESOURCES-MIB::hrSystemUptime.0',4);
INSERT INTO `tnetwork_component` (`id_nc`, `name`, `description`, `id_group`, `type`, `max`, `min`, `module_interval`, `tcp_port`, `tcp_send`, `tcp_rcv`, `snmp_community`, `snmp_oid`, `id_module_group`) VALUES (54,'Catalyst Free Mem','Taken from ftp://ftp.cisco.com/pub/mibs/oid/OLD-CISCO-MEMORY-MIB.oid',2,15,0,0,180,0,'','','public','1.3.6.1.4.1.9.2.1.8',4);

--
-- Dumping data for table `tnetwork_component_group`
--

INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (1,'General group',0);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (2,'Cisco MIBs',10);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (5,'UNIX MIBs',12);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (10,'Network Management',0);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (11,'Microsoft Windows MIB',12);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (12,'Operating Systems',0);
INSERT INTO `tnetwork_component_group` (`id_sg`, `name`, `parent`) VALUES (13,'UCD Mibs (Linux, UCD-SNMP)',12);


-- Network profile

INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (1,'Basic Network Monitoring','This includes basic SNMP, ICMP, and TCP checks.');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (2,'Basic Monitoring','Only ICMP check');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (3,'Basic DMZ Server monitoring','This group of network checks, checks for default services located on DMZ servers...');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (4,'Full SNMP Monitoring','');
INSERT INTO `tnetwork_profile` (`id_np`, `name`, `description`) VALUES (5,'Linux Server','Full Monitoring of a Linux server services.');

INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (1,24,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (2,25,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (3,27,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (4,28,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (5,30,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (6,31,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (7,34,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (8,39,1);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (9,34,2);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (10,34,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (11,37,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (12,39,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (13,38,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (14,24,3);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (15,3,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (16,24,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (17,25,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (18,26,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (19,27,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (20,28,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (21,29,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (22,30,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (23,31,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (24,32,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (25,45,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (26,46,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (27,47,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (28,48,4);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (29,3,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (30,50,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (31,53,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (32,24,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (33,30,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (34,27,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (35,34,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (36,1,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (37,2,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (38,49,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (39,4,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (40,51,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (41,52,5);
INSERT INTO `tnetwork_profile_component` (`id_npc`, `id_nc`, `id_np`) VALUES (42,39,5);


