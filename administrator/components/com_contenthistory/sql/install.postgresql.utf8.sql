-- com_contenthistory: its tables, created only where they don't exist yet (a restore may find them kept)

CREATE TABLE IF NOT EXISTS "#__ucm_history" (
  "version_id" serial NOT NULL,
  "ucm_item_id" integer NOT NULL,
  "ucm_type_id" integer NOT NULL,
  "version_note" varchar(255) NOT NULL DEFAULT '',
  "save_date" timestamp with time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  "editor_user_id" integer  NOT NULL DEFAULT 0,
  "character_count" integer  NOT NULL DEFAULT 0,
  "sha1_hash" varchar(50) NOT NULL DEFAULT '',
  "version_data" text NOT NULL,
  "keep_forever" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("version_id")
);

DROP INDEX IF EXISTS "#__ucm_history_idx_ucm_item_id";

CREATE INDEX "#__ucm_history_idx_ucm_item_id" ON "#__ucm_history" ("ucm_type_id", "ucm_item_id");

DROP INDEX IF EXISTS "#__ucm_history_idx_save_date";

CREATE INDEX "#__ucm_history_idx_save_date" ON "#__ucm_history" ("save_date");

COMMENT ON COLUMN "#__ucm_history"."version_note" IS 'Optional version name';

COMMENT ON COLUMN "#__ucm_history"."character_count" IS 'Number of characters in this version.';

COMMENT ON COLUMN "#__ucm_history"."sha1_hash" IS 'SHA1 hash of the version_data column.';

COMMENT ON COLUMN "#__ucm_history"."version_data" IS 'json-encoded string of version data';

COMMENT ON COLUMN "#__ucm_history"."keep_forever" IS '0=auto delete; 1=keep';
