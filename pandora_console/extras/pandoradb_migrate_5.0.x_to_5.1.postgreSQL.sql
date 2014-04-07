-- ---------------------------------------------------------------------
-- Table "talert_templates"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_templates" ADD COLUMN "field1_recovery" text NULL default '';

-- ---------------------------------------------------------------------
-- Table "talert_actions"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_actions" ADD COLUMN "field1_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field2_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field3_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field4_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field5_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field6_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field7_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field8_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field9_recovery" text NULL default '';
ALTER TABLE "talert_actions" ADD COLUMN "field10_recovery" text NULL default '';

-- ---------------------------------------------------------------------
-- Table "tconfig"
-- ---------------------------------------------------------------------
INSERT INTO "tconfig" ("token", "value") VALUES
('graph_color4', '#FF66CC'),
('graph_color5', '#CC0000'),
('graph_color6', '#0033FF'),
('graph_color7', '#99FF99'),
('graph_color8', '#330066'),
('graph_color9', '#66FFFF'),
('graph_color10', '#6666FF');

UPDATE "tconfig" SET "value"='#FFFF00' WHERE "token"='graph_color2';
UPDATE "tconfig" SET "value"='#FF6600' WHERE "token"='graph_color3';

-- ---------------------------------------------------------------------
-- Table "tconfig_os"
-- ---------------------------------------------------------------------
INSERT INTO "tconfig_os" VALUES (17, 'Router', 'Generic router', 'so_router.png');
INSERT INTO "tconfig_os" VALUES (18, 'Switch', 'Generic switch', 'so_switch.png');

/* 2014/03/18 */
-- ----------------------------------------------------------------------
-- Table `tmodule_relationship`
-- ----------------------------------------------------------------------
CREATE TABLE "tmodule_relationship" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"module_a" INTEGER NOT NULL REFERENCES tagente_modulo("id_agente_modulo")
		ON DELETE CASCADE,
	"module_b" INTEGER NOT NULL REFERENCES tagente_modulo("id_agente_modulo")
		ON DELETE CASCADE,
	"disable_update" SMALLINT NOT NULL default 0
);

-- ---------------------------------------------------------------------
-- Table "talert_snmp"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_snmp" ADD COLUMN "id_group" INTEGER NOT NULL default 0;

/* 2014/03/19 */
-- ---------------------------------------------------------------------
-- Table "talert_snmp"
-- ---------------------------------------------------------------------
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f11_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f12_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f13_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f14_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f15_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f16_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f17_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f18_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f19_" text DEFAULT '';
ALTER TABLE "talert_snmp" ADD COLUMN "_snmp_f20_" text DEFAULT '';

ALTER TABLE "tnetwork_map" ADD COLUMN "l2_network" SMALLINT NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
ALTER TABLE "tlayout_data" ADD COLUMN "id_group" INTEGER NOT NULL default 0;

-- ---------------------------------------------------------------------
-- Table talert_snmp_action
-- ---------------------------------------------------------------------
CREATE TABLE  "talert_snmp_action" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_alert_snmp" INTEGER default 0,
	"alert_type" INTEGER default 0,
	"al_field1" TEXT default '',
	"al_field2" TEXT default '',
	"al_field3" TEXT default '',
	"al_field4" TEXT default '',
	"al_field5" TEXT default '',
	"al_field6" TEXT default '',
	"al_field7" TEXT default '',
	"al_field8" TEXT default '',
	"al_field9" TEXT default '',
	"al_field10" TEXT default ''
);

-- ---------------------------------------------------------------------
-- Table treport
-- ---------------------------------------------------------------------
ALTER TABLE "treport" ADD COLUMN "non_interactive" SMALLINT DEFAULT 0;
