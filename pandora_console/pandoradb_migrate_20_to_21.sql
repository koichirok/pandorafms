ALTER TABLE tagente ADD `custom_id` varchar(255) default '';
ALTER TABLE tagente_modulo ADD `custom_id` varchar(255) default '';
ALTER TABLE tgrupo ADD `custom_id` varchar(255) default '';

ALTER TABLE `tagente_datos` DROP INDEX `data_index2`;
ALTER TABLE `tagente_datos` DROP `timestamp`, DROP `id_agente`;
ALTER TABLE `tagente_datos_inc` DROP `timestamp`;
ALTER TABLE `tagente_datos_string` DROP `timestamp`, DROP `id_agente`;
ALTER TABLE `tagente_estado` DROP `cambio`;
ALTER TABLE  `tagente_estado` ADD  `status_changes` TINYINT( 4 ) NOT  
NULL DEFAULT  '0', ADD  `last_status` TINYINT( 4 ) NOT NULL DEFAULT   
'0';
ALTER TABLE  `tagente_estado` ADD INDEX (  `current_interval` );
ALTER TABLE  `tagente_estado` ADD INDEX (  `running_by` );
ALTER TABLE  `tagente_estado` ADD INDEX (  `last_execution_try` );

ALTER TABLE  `tagente_modulo`  ADD `min_warning` double(18,2) default 0;
ALTER TABLE  `tagente_modulo`  ADD `max_warning` double(18,2) default 0;
ALTER TABLE  `tagente_modulo`  ADD `min_critical` double(18,2) default 0;
ALTER TABLE  `tagente_modulo`  ADD `max_critical` double(18,2) default 0;
ALTER TABLE  `tagente_modulo`  ADD `history_data` tinyint(1) unsigned default '1';

ALTER TABLE  `tagente_modulo`  ADD `min_ff_event` int(4) unsigned default '0';
ALTER TABLE  `tagente_modulo` ADD `delete_pending` int(1) unsigned default 0;

ALTER TABLE  `tagente_modulo` DROP INDEX  `tam_plugin`;
ALTER TABLE  `tagente_modulo` DROP PRIMARY KEY , ADD PRIMARY KEY  
(  `id_agente_modulo` );

ALTER TABLE `tagent_access` DROP `timestamp`;

ALTER TABLE `tlayout_data` ADD `id_agent` int(10) unsigned NOT NULL default 0;

CREATE TABLE  IF NOT EXISTS `talert_commands` (
   `id` int(10) unsigned NOT NULL auto_increment,
   `name` varchar(100) NOT NULL default '',
   `command` varchar(500) default '',
   `description` varchar(255) default '',
   `internal` tinyint(1) default 0,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE  IF NOT EXISTS `talert_actions` (
   `id` int(10) unsigned NOT NULL auto_increment,
   `name` varchar(255) default '',
   `id_alert_command` int(10) unsigned NOT NULL,
   `field1` varchar(255) NOT NULL default '',
   `field2` varchar(255) default '',
   `field3` varchar(255) default '',
   PRIMARY KEY  (`id`),
   FOREIGN KEY (`id_alert_command`) REFERENCES talert_commands(`id`)
     ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `talert_templates` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) default '',
  `description` mediumtext default '',
  `id_alert_action` int(10) unsigned NULL,
  `field1` varchar(255) default '',
  `field2` varchar(255) default '',
  `field3` mediumtext NOT NULL,
  `type` ENUM ('regex', 'max_min', 'max', 'min', 'equal', 'not_equal'),
  `value` varchar(255) default '',
  `matches_value` tinyint(1) default 0,
  `max_value` double(18,2) default NULL,
  `min_value` double(18,2) default NULL,
  `time_threshold` int(10) NOT NULL default '0',
  `max_alerts` int(4) unsigned NOT NULL default '1',
  `min_alerts` int(4) unsigned NOT NULL default '0',
  `time_from` time default '00:00:00',
  `time_to` time default '00:00:00',
  `monday` tinyint(1) default 1,
  `tuesday` tinyint(1) default 1,
  `wednesday` tinyint(1) default 1,
  `thursday` tinyint(1) default 1,
  `friday` tinyint(1) default 1,
  `saturday` tinyint(1) default 1,
  `sunday` tinyint(1) default 1,
  `recovery_notify` tinyint(1) default '0',
  `field2_recovery` varchar(255) NOT NULL default '',
  `field3_recovery` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `talert_template_modules` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_agent_module` int(10) unsigned NOT NULL,
  `id_alert_template` int(10) unsigned NOT NULL,
  `internal_counter` int(4) default '0',
  `last_fired` bigint(20) NOT NULL default '0',
  `last_reference` bigint(20) NOT NULL default '0',
  `times_fired` int(3) NOT NULL default '0',
  `disabled` tinyint(1) default '0',
  `priority` tinyint(4) default '0',
  `force_execution` tinyint(1) default '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_agent_module`) REFERENCES tagente_modulo(`id_agente_modulo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_alert_template`) REFERENCES talert_templates(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE (`id_agent_module`, `id_alert_template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `talert_template_module_actions` (
   `id_alert_template_module` int(10) unsigned NOT NULL,
   `id_alert_action` int(10) unsigned NOT NULL,
   `fires_min` int(3) unsigned default 0,
   `fires_max` int(3) unsigned default 0,
   FOREIGN KEY (`id_alert_template_module`) REFERENCES  
talert_template_modules(`id`)
     ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (`id_alert_action`) REFERENCES talert_actions(`id`)
     ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- If you have custom stuff here, please make sure you manually  
-- migrate it.

-- DROP TABLE  `talerta`

INSERT INTO `talert_commands` VALUES (1,'Compound only', 'Internal  
type', 'This alert will not be executed individually', 1);

INSERT INTO `talert_commands` VALUES (2,'eMail','Internal type', 'This  
alert send an email using internal Pandora FMS Server SMTP  
capabilities (defined in each server, using:\r\n_field1_ as  
destination email address, and\r\n_field2_ as subject for message. \r 
\n_field3_ as text of message.', 1);

INSERT INTO `talert_commands` VALUES (3,'Internal Audit','Internal  
type','This alert save alert in Pandora interal audit system. Fields  
are static and only _field1_ is used.', 1);
INSERT INTO `talert_commands` VALUES (4,'Pandora FMS Event','Internal  
type','This alert create an special event into Pandora FMS event  
manager.', 1);

INSERT INTO `talert_commands` VALUES (5,'Pandora FMS Alertlog','echo  
_timestamp_ pandora _agent_ _data_ _field1_ _field2_ >> /var/log/ 
pandora/pandora_alert.log','This is a default alert to write alerts in  
a standard ASCII  plaintext log file in /var/log/pandora/ 
pandora_alert.log\r\n', 0);

INSERT INTO `talert_commands` VALUES (6,'SNMP Trap','/usr/bin/snmptrap  
-v 1 -c trap_public 192.168.0.4 1.1.1.1.1.1.1.1 _agent_  
_field1_','Send a SNMPTRAP to 192.168.0.4. Please review config and  
adapt to your needs, this is only a sample, not functional itself.', 0);

INSERT INTO `talert_commands` VALUES (7,'Syslog','logger -p  
daemon.alert Pandora Alert _agent_ _data_ _field1_ _field2_','Uses  
field1 and field2 to generate Syslog alert in facility daemon with  
"alert" level.', 0);

INSERT INTO `talert_commands` VALUES (8,'Sound Alert','/usr/bin/play / 
usr/share/sounds/alarm.wav','', 0);

INSERT INTO `talert_commands` VALUES (9,'Jabber Alert','echo _field3_  
| sendxmpp -r _field1_ --chatroom _field2_','Send jabber alert to chat  
room in a predefined server (configure first .sendxmpprc file). Uses  
field3 as text message, field1 as useralias for source message, and  
field2 for chatroom name', 0);

ALTER TABLE  `tnetwork_component` ADD  `history_data` TINYINT( 1 )  
UNSIGNED NOT NULL DEFAULT  '1', ADD  `min_warning` DOUBLE( 18, 2 ) NOT  
NULL DEFAULT  '0', ADD  `max_warning` DOUBLE( 18, 2 ) NOT NULL  
DEFAULT  '0', ADD  `min_critical` DOUBLE( 18, 2 ) NOT NULL DEFAULT   
'0', ADD  `max_critical` DOUBLE( 18, 2 ) NOT NULL DEFAULT  '0', ADD   
`min_ff_event` INT( 4 ) UNSIGNED NOT NULL DEFAULT  '0';

ALTER TABLE  `tusuario` CHANGE  `nombre_real`  `fullname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE  `tusuario` CHANGE  `comentarios`  `comments` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE  `tusuario` CHANGE  `id_usuario`  `id_user` VARCHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '0';
ALTER TABLE  `tusuario` CHANGE  `fecha_registro`  `last_connect` BIGINT( 20 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tusuario` ADD UNIQUE (`id_user`);
ALTER TABLE  `tusuario` ADD  `registered` BIGINT( 20 ) NOT NULL DEFAULT  '0' AFTER  `last_connect` ;
ALTER TABLE  `tusuario` ADD  `firstname` VARCHAR( 255 ) NOT NULL AFTER  `fullname`;
ALTER TABLE  `tusuario` ADD  `lastname` VARCHAR( 255 ) NOT NULL AFTER  `firstname`;
ALTER TABLE  `tusuario` ADD  `middlename` VARCHAR( 255 ) NOT NULL AFTER  `lastname`;
ALTER TABLE  `tusuario` CHANGE  `direccion`  `email` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE  `tusuario` CHANGE  `telefono`  `phone` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;
ALTER TABLE  `tusuario` CHANGE  `nivel`  `is_admin` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0';
