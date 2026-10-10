-- com_redirect: its tables, created only where they don't exist yet (a restore may find them kept)

CREATE TABLE IF NOT EXISTS "#__redirect_links" (
  "id" serial NOT NULL,
  "old_url" varchar(2048) NOT NULL,
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
