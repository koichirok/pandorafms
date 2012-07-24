-- -----------------------------------------------------
-- Table `tnetflow_filter`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_filter` (
  `id_sg`  int(10) unsigned NOT NULL auto_increment,
  `id_name` varchar(600) NOT NULL default '0',
  `id_group` int(10),
  `ip_dst` TEXT NOT NULL,
  `ip_src` TEXT NOT NULL,
  `dst_port` TEXT NOT NULL,
  `src_port` TEXT NOT NULL,
  `advanced_filter` TEXT NOT NULL,
  `filter_args` TEXT NOT NULL,
  `aggregate` varchar(60),
  `output` varchar(60),
PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnetflow_report`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_report` (
  `id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `id_group` int(10),
PRIMARY KEY(`id_report`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tnetflow_report_content`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tnetflow_report_content` (
   	`id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_report` INTEGER UNSIGNED NOT NULL default 0,
    `id_filter` INTEGER UNSIGNED NOT NULL default 0,
	`date` bigint(20) NOT NULL default '0',
	`period` int(11) NOT NULL default 0,
	`max` int (11) NOT NULL default 0,
	`show_graph` varchar(60),
	`order` int (11) NOT NULL default 0,
	PRIMARY KEY(`id_rc`),
	FOREIGN KEY (`id_report`) REFERENCES tnetflow_report(`id_report`)
	ON DELETE CASCADE,
	FOREIGN KEY (`id_filter`) REFERENCES tnetflow_filter(`id_sg`)
	ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE `tusuario` ADD COLUMN `disabled` int(4) NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `shortcut` tinyint(1) DEFAULT 0;
ALTER TABLE tusuario ADD COLUMN `shortcut_data` text;

-- -----------------------------------------------------
-- Table `tincidencia`
-- -----------------------------------------------------

ALTER TABLE `tincidencia` ADD COLUMN `id_agent` int(10) unsigned NULL default 0;

-- -----------------------------------------------------
-- Table `tagente`
-- -----------------------------------------------------

ALTER TABLE `tagente` ADD COLUMN `url_address` mediumtext NULL;

-- -----------------------------------------------------
-- Table `talert_special_days`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `talert_special_days` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`date` date NOT NULL DEFAULT '0000-00-00',
	`same_day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL DEFAULT 'sunday',
	`description` text,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `talert_templates`
-- -----------------------------------------------------

ALTER TABLE `talert_templates` ADD COLUMN `special_day` tinyint(1) DEFAULT '0';

-- -----------------------------------------------------
-- Table `tplanned_downtime_agents`
-- -----------------------------------------------------
DELETE FROM tplanned_downtime_agents
WHERE id_downtime NOT IN (SELECT id FROM tplanned_downtime);

ALTER TABLE tplanned_downtime_agents
ADD FOREIGN KEY(`id_downtime`) REFERENCES tplanned_downtime(`id`)
ON DELETE CASCADE;

-- -----------------------------------------------------
-- Table `tevento`
-- -----------------------------------------------------

ALTER TABLE `tevento` ADD COLUMN (`source` tinytext NOT NULL,
`id_extra` tinytext NOT NULL);

-- -----------------------------------------------------
-- Table `talert_snmp`
-- -----------------------------------------------------
ALTER TABLE `talert_snmp` ADD COLUMN (`_snmp_f1_` text, `_snmp_f2_` text, `_snmp_f3_` text,
`_snmp_f4_` text, `_snmp_f5_` text, `_snmp_f6_` text, `trap_type` int(11) NOT NULL default '-1',
`single_value` varchar(255) DEFAULT '');

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------
ALTER TABLE `tagente_modulo` ADD COLUMN `module_ff_interval` int(4) unsigned default '0';
ALTER TABLE `tagente_modulo` CHANGE COLUMN `post_process` `post_process` double(18,5) default NULL;

-- -----------------------------------------------------
-- Table `tnetwork_component`
-- -----------------------------------------------------
ALTER TABLE `tnetwork_component` CHANGE COLUMN `post_process` `post_process` double(18,5) default NULL;
ALTER TABLE `tnetwork_component` ADD COLUMN `unit` TEXT  NOT NULL AFTER `post_process`;

-- -----------------------------------------------------
-- Table `tgraph_source` Alter table to allow negative values in weight
-- -----------------------------------------------------
ALTER TABLE tgraph_source MODIFY weight FLOAT(5,3) NOT NULL DEFAULT '0.000';

-- -----------------------------------------------------
-- Table `tevent_filter`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tevent_filter` (
  `id_filter`  int(10) unsigned NOT NULL auto_increment,
  `id_group_filter` int(10) NOT NULL default 0,
  `id_name` varchar(600) NOT NULL,
  `id_group` int(10) NOT NULL default 0,
  `event_type` text NOT NULL,
  `severity` int(10) NOT NULL default -1,
  `status` int(10) NOT NULL default -1,
  `search` TEXT,
  `text_agent` TEXT, 
  `pagination` int(10) NOT NULL default 25,
  `event_view_hr` int(10) NOT NULL default 8,
  `id_user_ack` TEXT,
  `group_rep` int(10) NOT NULL default 0,
  `tag` varchar(600) NOT NULL default '',
  `filter_only_alert` int(10) NOT NULL default -1, 
PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
ALTER TABLE tconfig MODIFY value TEXT NOT NULL;
--Join the all ips of "list_ACL_IPs_for_API_%" in one row (now We have a field "value" with hudge size)
INSERT INTO tconfig (token, `value`) SELECT 'list_ACL_IPs_for_API', GROUP_CONCAT(`value` SEPARATOR ';') AS `value` FROM tconfig WHERE token LIKE "list_ACL_IPs_for_API%";
INSERT INTO `tconfig` (`token`, `value`) VALUES ('event_fields', 'evento,id_agente,estado,timestamp');
DELETE FROM tconfig WHERE token LIKE "list_ACL_IPs_for_API_%";

-- -----------------------------------------------------
-- Table `treport_content_item`
-- -----------------------------------------------------
ALTER TABLE treport_content_item ADD FOREIGN KEY (`id_report_content`) REFERENCES treport_content(`id_rc`) ON UPDATE CASCADE ON DELETE CASCADE;

-- -----------------------------------------------------
-- Table `treport`
-- -----------------------------------------------------
ALTER TABLE treport ADD COLUMN `id_template` INTEGER UNSIGNED DEFAULT 0;

-- -----------------------------------------------------
-- Table `tgraph`
-- -----------------------------------------------------
ALTER TABLE `tgraph` ADD COLUMN `id_graph_template` int(11) NOT NULL DEFAULT 0;

-- -----------------------------------------------------
-- Table `ttipo_modulo`
-- -----------------------------------------------------
UPDATE ttipo_modulo SET descripcion='Generic data' WHERE id_tipo=1;

UPDATE ttipo_modulo SET descripcion='Generic data incremental' WHERE id_tipo=4;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------
ALTER TABLE `tusuario` ADD COLUMN `section` TEXT NOT NULL;
INSERT INTO `tusuario` (`section`) VALUES ('Default');
ALTER TABLE `tusuario` ADD COLUMN `data_section` TEXT NOT NULL;

-- -----------------------------------------------------
-- Table `treport_content_item`
-- -----------------------------------------------------
ALTER TABLE `treport_content_item` ADD COLUMN `operation` TEXT;

-- -----------------------------------------------------
-- Table `tmensajes`
-- -----------------------------------------------------
ALTER TABLE `tmensajes` MODIFY COLUMN `mensaje` TEXT NOT NULL;

-- -----------------------------------------------------
-- Table `talert_compound`
-- -----------------------------------------------------

ALTER TABLE `talert_compound` ADD COLUMN `special_day` tinyint(1) DEFAULT '0';

-- -----------------------------------------------------
-- Table `ttimezone`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `ttimezone` (
  `id_tz`  int(10) unsigned NOT NULL auto_increment,
  `zone` varchar(60) NOT NULL,
  `timezone` varchar(60) NOT NULL,
PRIMARY KEY  (`id_tz`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE `tusuario` ADD COLUMN `force_change_pass` tinyint(1) DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `last_pass_change` DATETIME  NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `last_failed_login` DATETIME  NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `failed_attempt` int(4) NOT NULL DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `login_blocked` tinyint(1) DEFAULT 0;

-- -----------------------------------------------------
-- Table `talert_commands`
-- -----------------------------------------------------

INSERT INTO `talert_commands` (`name`, `command`, `description`, `internal`) VALUES ('Validate Event','Internal type','This alert validate the events matched with a module given the agent name (_field1_) and module name (_field2_)', 1);

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------

INSERT INTO `tconfig` (`token`, `value`) VALUES
('enable_pass_policy', 0),
('pass_size', 4),
('pass_needs_numbers', 0),
('pass_needs_symbols', 0),
('pass_expire', 0),
('first_login', 0),
('mins_fail_pass', 5),
('number_attempts', 5),
('enable_pass_policy_admin', 0),
('enable_pass_history', 0),
('compare_pass', 3),
('meta_style', 'meta_pandora');

-- -----------------------------------------------------
-- Table `tpassword_history`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tpassword_history` (
  `id_pass`  int(10) unsigned NOT NULL auto_increment,
  `id_user` varchar(60) NOT NULL,
  `password` varchar(45) default NULL,
  `date_begin` DATETIME  NOT NULL DEFAULT 0,
  `date_end` DATETIME  NOT NULL DEFAULT 0,
PRIMARY KEY  (`id_pass`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- -----------------------------------------------------
-- Table `tconfig`
-- -----------------------------------------------------
UPDATE tconfig SET `value`='comparation'
WHERE `token`= 'prominent_time';

-- -----------------------------------------------------
-- Table `tnetwork_component`
-- -----------------------------------------------------

ALTER TABLE tnetwork_component ADD `wizard_level` enum('basic','advanced','custom','nowizard') default 'nowizard';
ALTER TABLE tnetwork_component ADD `only_metaconsole` tinyint(1) unsigned default '0';
ALTER TABLE tnetwork_component ADD `macros` text;

-- -----------------------------------------------------
-- Table `tagente_modulo`
-- -----------------------------------------------------

ALTER TABLE tagente_modulo ADD `wizard_level` enum('basic','advanced','custom','nowizard') default 'nowizard';
ALTER TABLE tagente_modulo ADD `macros` text;

-- -----------------------------------------------------
-- Table `tusuario`
-- -----------------------------------------------------

ALTER TABLE tusuario ADD `metaconsole_access` enum('basic','advanced','custom','all','only_console') default 'only_console';
