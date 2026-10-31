-- Joomla 3.x UTD 3.18.0 migration — October 31, 2026
-- Redirects: a hash of the normalised source URL for indexed lookups, and a table of their own for the errors the
-- plugin collects. The update script's "redirect-scheme" step fills the hashes and moves the data.

ALTER TABLE "#__redirect_links" ADD COLUMN "old_url_hash" char(32) DEFAULT '' NOT NULL;
CREATE INDEX "#__redirect_links_idx_old_url_hash" ON "#__redirect_links" USING btree ("old_url_hash");

CREATE TABLE "#__redirect_errors" (
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
CREATE UNIQUE INDEX "#__redirect_errors_idx_url_hash" ON "#__redirect_errors" USING btree ("url_hash");
CREATE INDEX "#__redirect_errors_idx_code" ON "#__redirect_errors" USING btree ("code");
CREATE INDEX "#__redirect_errors_idx_last_hit" ON "#__redirect_errors" USING btree ("last_hit");
