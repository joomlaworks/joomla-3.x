-- com_fields: its tables, created only where they don't exist yet (a restore may find them kept)

CREATE TABLE IF NOT EXISTS "#__fields" (
  "id" serial NOT NULL,
  "asset_id" bigint DEFAULT 0 NOT NULL,
  "context" varchar(255) DEFAULT '' NOT NULL,
  "group_id" bigint DEFAULT 0 NOT NULL,
  "title" varchar(255) DEFAULT '' NOT NULL,
  "name" varchar(255) DEFAULT '' NOT NULL,
  "label" varchar(255) DEFAULT '' NOT NULL,
  "default_value" text,
  "type" varchar(255) DEFAULT 'text' NOT NULL,
  "note" varchar(255) DEFAULT '' NOT NULL,
  "description" text NOT NULL,
  "state" smallint DEFAULT 0 NOT NULL,
  "required" smallint DEFAULT 0 NOT NULL,
  "checked_out" integer DEFAULT 0 NOT NULL,
  "checked_out_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "ordering" bigint DEFAULT 0 NOT NULL,
  "params" text NOT NULL,
  "fieldparams" text NOT NULL,
  "language" varchar(7) DEFAULT '' NOT NULL,
  "created_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "created_user_id" bigint DEFAULT 0 NOT NULL,
  "modified_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "modified_by" bigint DEFAULT 0 NOT NULL,
  "access" bigint DEFAULT 0 NOT NULL,
  PRIMARY KEY ("id")
);

DROP INDEX IF EXISTS "#__fields_idx_checked_out";

CREATE INDEX "#__fields_idx_checked_out" ON "#__fields" ("checked_out");

DROP INDEX IF EXISTS "#__fields_idx_state";

CREATE INDEX "#__fields_idx_state" ON "#__fields" ("state");

DROP INDEX IF EXISTS "#__fields_idx_created_user_id";

CREATE INDEX "#__fields_idx_created_user_id" ON "#__fields" ("created_user_id");

DROP INDEX IF EXISTS "#__fields_idx_access";

CREATE INDEX "#__fields_idx_access" ON "#__fields" ("access");

DROP INDEX IF EXISTS "#__fields_idx_context";

CREATE INDEX "#__fields_idx_context" ON "#__fields" ("context");

DROP INDEX IF EXISTS "#__fields_idx_language";

CREATE INDEX "#__fields_idx_language" ON "#__fields" ("language");

CREATE TABLE IF NOT EXISTS "#__fields_categories" (
  "field_id" bigint DEFAULT 0 NOT NULL,
  "category_id" bigint DEFAULT 0 NOT NULL,
  PRIMARY KEY ("field_id", "category_id")
);

CREATE TABLE IF NOT EXISTS "#__fields_groups" (
  "id" serial NOT NULL,
  "asset_id" bigint DEFAULT 0 NOT NULL,
  "context" varchar(255) DEFAULT '' NOT NULL,
  "title" varchar(255) DEFAULT '' NOT NULL,
  "note" varchar(255) DEFAULT '' NOT NULL,
  "description" text NOT NULL,
  "state" smallint DEFAULT 0 NOT NULL,
  "checked_out" integer DEFAULT 0 NOT NULL,
  "checked_out_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "ordering" integer DEFAULT 0 NOT NULL,
  "params" text NOT NULL,
  "language" varchar(7) DEFAULT '' NOT NULL,
  "created" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "created_by" bigint DEFAULT 0 NOT NULL,
  "modified" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "modified_by" bigint DEFAULT 0 NOT NULL,
  "access" bigint DEFAULT 1 NOT NULL,
  PRIMARY KEY ("id")
);

DROP INDEX IF EXISTS "#__fields_groups_idx_checked_out";

CREATE INDEX "#__fields_groups_idx_checked_out" ON "#__fields_groups" ("checked_out");

DROP INDEX IF EXISTS "#__fields_groups_idx_state";

CREATE INDEX "#__fields_groups_idx_state" ON "#__fields_groups" ("state");

DROP INDEX IF EXISTS "#__fields_groups_idx_created_by";

CREATE INDEX "#__fields_groups_idx_created_by" ON "#__fields_groups" ("created_by");

DROP INDEX IF EXISTS "#__fields_groups_idx_access";

CREATE INDEX "#__fields_groups_idx_access" ON "#__fields_groups" ("access");

DROP INDEX IF EXISTS "#__fields_groups_idx_context";

CREATE INDEX "#__fields_groups_idx_context" ON "#__fields_groups" ("context");

DROP INDEX IF EXISTS "#__fields_groups_idx_language";

CREATE INDEX "#__fields_groups_idx_language" ON "#__fields_groups" ("language");

CREATE TABLE IF NOT EXISTS "#__fields_values" (
"field_id" bigint DEFAULT 0 NOT NULL,
"item_id" varchar(255) DEFAULT '' NOT NULL,
"value" text
);

DROP INDEX IF EXISTS "#__fields_values_idx_field_id";

CREATE INDEX "#__fields_values_idx_field_id" ON "#__fields_values" ("field_id");

DROP INDEX IF EXISTS "#__fields_values_idx_item_id";

CREATE INDEX "#__fields_values_idx_item_id" ON "#__fields_values" ("item_id");
