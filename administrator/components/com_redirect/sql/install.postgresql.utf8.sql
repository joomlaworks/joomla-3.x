-- com_redirect: its tables, created only where they don't exist yet (a restore may find them kept)

CREATE TABLE IF NOT EXISTS "#__redirect_links" (
  "id" serial NOT NULL,
  "old_url" varchar(2048) NOT NULL,
  "old_url_hash" char(32) DEFAULT '' NOT NULL,
  "new_url" varchar(2048),
  "referer" varchar(2048) NOT NULL,
  "comment" varchar(255) DEFAULT '' NOT NULL,
  "hits" bigint DEFAULT 0 NOT NULL,
  "published" smallint NOT NULL,
  "created_date" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "modified_date" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "header" integer DEFAULT 301 NOT NULL,
  PRIMARY KEY ("id")
);

DROP INDEX IF EXISTS "#__redirect_links_idx_old_url";

CREATE INDEX "#__redirect_links_idx_old_url" ON "#__redirect_links" ("old_url");

DROP INDEX IF EXISTS "#__redirect_links_idx_link_modifed";

CREATE INDEX "#__redirect_links_idx_link_modifed" ON "#__redirect_links" ("modified_date");

DROP INDEX IF EXISTS "#__redirect_links_idx_old_url_hash";

CREATE INDEX "#__redirect_links_idx_old_url_hash" ON "#__redirect_links" USING btree ("old_url_hash");

CREATE TABLE IF NOT EXISTS "#__redirect_errors" (
  "id" serial NOT NULL,
  "url" varchar(2048) NOT NULL,
  "url_hash" char(32) DEFAULT '' NOT NULL,
  "code" smallint DEFAULT 404 NOT NULL,
  "referer" varchar(2048) DEFAULT '' NOT NULL,
  "hits" bigint DEFAULT 0 NOT NULL,
  "created_date" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "last_hit" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  PRIMARY KEY ("id")
);

DROP INDEX IF EXISTS "#__redirect_errors_idx_url_hash";

CREATE UNIQUE INDEX "#__redirect_errors_idx_url_hash" ON "#__redirect_errors" USING btree ("url_hash");

DROP INDEX IF EXISTS "#__redirect_errors_idx_code";

CREATE INDEX "#__redirect_errors_idx_code" ON "#__redirect_errors" USING btree ("code");

DROP INDEX IF EXISTS "#__redirect_errors_idx_last_hit";

CREATE INDEX "#__redirect_errors_idx_last_hit" ON "#__redirect_errors" USING btree ("last_hit");
