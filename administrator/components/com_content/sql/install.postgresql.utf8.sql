-- com_content: its tables, created only where they don't exist yet (a restore may find them kept)

CREATE TABLE IF NOT EXISTS "#__content" (
  "id" serial NOT NULL,
  "asset_id" bigint DEFAULT 0 NOT NULL,
  "title" varchar(255) DEFAULT '' NOT NULL,
  "alias" varchar(255) DEFAULT '' NOT NULL,
  "introtext" text NOT NULL,
  "fulltext" text NOT NULL,
  "state" smallint DEFAULT 0 NOT NULL,
  "catid" bigint DEFAULT 0 NOT NULL,
  "created" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "created_by" bigint DEFAULT 0 NOT NULL,
  "created_by_alias" varchar(255) DEFAULT '' NOT NULL,
  "modified" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "modified_by" bigint DEFAULT 0 NOT NULL,
  "checked_out" bigint DEFAULT 0 NOT NULL,
  "checked_out_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "publish_up" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "publish_down" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "images" text NOT NULL,
  "urls" text NOT NULL,
  "attribs" varchar(5120) NOT NULL,
  "version" bigint DEFAULT 1 NOT NULL,
  "ordering" bigint DEFAULT 0 NOT NULL,
  "metakey" text NOT NULL,
  "metadesc" text NOT NULL,
  "access" bigint DEFAULT 0 NOT NULL,
  "hits" bigint DEFAULT 0 NOT NULL,
  "metadata" text NOT NULL,
  "featured" smallint DEFAULT 0 NOT NULL,
  "language" varchar(7) DEFAULT '' NOT NULL,
  "xreference" varchar(50) DEFAULT '' NOT NULL,
  "note" varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY ("id")
);

DROP INDEX IF EXISTS "#__content_idx_access";

CREATE INDEX "#__content_idx_access" ON "#__content" ("access");

DROP INDEX IF EXISTS "#__content_idx_checkout";

CREATE INDEX "#__content_idx_checkout" ON "#__content" ("checked_out");

DROP INDEX IF EXISTS "#__content_idx_state";

CREATE INDEX "#__content_idx_state" ON "#__content" ("state");

DROP INDEX IF EXISTS "#__content_idx_catid";

CREATE INDEX "#__content_idx_catid" ON "#__content" ("catid");

DROP INDEX IF EXISTS "#__content_idx_createdby";

CREATE INDEX "#__content_idx_createdby" ON "#__content" ("created_by");

DROP INDEX IF EXISTS "#__content_idx_featured_catid";

CREATE INDEX "#__content_idx_featured_catid" ON "#__content" ("featured", "catid");

DROP INDEX IF EXISTS "#__content_idx_language";

CREATE INDEX "#__content_idx_language" ON "#__content" ("language");

DROP INDEX IF EXISTS "#__content_idx_xreference";

CREATE INDEX "#__content_idx_xreference" ON "#__content" ("xreference");

DROP INDEX IF EXISTS "#__content_idx_alias";

CREATE INDEX "#__content_idx_alias" ON "#__content" ("alias");

COMMENT ON COLUMN "#__content"."asset_id" IS 'FK to the #__assets table.';

COMMENT ON COLUMN "#__content"."featured" IS 'Set if article is featured.';

COMMENT ON COLUMN "#__content"."language" IS 'The language code for the article.';

COMMENT ON COLUMN "#__content"."xreference" IS 'A reference to enable linkages to external data sets.';

CREATE TABLE IF NOT EXISTS "#__content_frontpage" (
  "content_id" bigint DEFAULT 0 NOT NULL,
  "ordering" bigint DEFAULT 0 NOT NULL,
  PRIMARY KEY ("content_id")
);

CREATE TABLE IF NOT EXISTS "#__content_rating" (
  "content_id" bigint DEFAULT 0 NOT NULL,
  "rating_sum" bigint DEFAULT 0 NOT NULL,
  "rating_count" bigint DEFAULT 0 NOT NULL,
  "lastip" varchar(50) DEFAULT '' NOT NULL,
  PRIMARY KEY ("content_id")
);
