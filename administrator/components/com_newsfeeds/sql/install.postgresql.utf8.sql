--
-- Table structure for table `#__newsfeeds`
--

CREATE TABLE "#__newsfeeds" (
  "catid" bigint DEFAULT 0 NOT NULL,
  "id" serial NOT NULL,
  "name" varchar(100) DEFAULT '' NOT NULL,
  "alias" varchar(100) DEFAULT '' NOT NULL,
  "link" varchar(2048) DEFAULT '' NOT NULL,
  "published" smallint DEFAULT 0 NOT NULL,
  "numarticles" bigint DEFAULT 1 NOT NULL,
  "cache_time" bigint DEFAULT 3600 NOT NULL,
  "checked_out" integer DEFAULT 0 NOT NULL,
  "checked_out_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "ordering" bigint DEFAULT 0 NOT NULL,
  "rtl" smallint DEFAULT 0 NOT NULL,
  "access" bigint DEFAULT 0 NOT NULL,
  "language" varchar(7) DEFAULT '' NOT NULL,
  "params" text NOT NULL,
  "created" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "created_by" integer DEFAULT 0 NOT NULL,
  "created_by_alias" varchar(255) DEFAULT '' NOT NULL,
  "modified" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "modified_by" integer DEFAULT 0 NOT NULL,
  "metakey" text NOT NULL,
  "metadesc" text NOT NULL,
  "metadata" text NOT NULL,
  "xreference" varchar(50) NOT NULL DEFAULT '',
  "publish_up" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "publish_down" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "description" text NOT NULL,
  "version" bigint DEFAULT 1 NOT NULL,
  "hits" bigint DEFAULT 0 NOT NULL,
  "images" text NOT NULL,
  PRIMARY KEY ("id")
);
CREATE INDEX "#__newsfeeds_idx_access" ON "#__newsfeeds" ("access");
CREATE INDEX "#__newsfeeds_idx_checkout" ON "#__newsfeeds" ("checked_out");
CREATE INDEX "#__newsfeeds_idx_state" ON "#__newsfeeds" ("published");
CREATE INDEX "#__newsfeeds_idx_catid" ON "#__newsfeeds" ("catid");
CREATE INDEX "#__newsfeeds_idx_createdby" ON "#__newsfeeds" ("created_by");
CREATE INDEX "#__newsfeeds_idx_language" ON "#__newsfeeds" ("language");
CREATE INDEX "#__newsfeeds_idx_xreference" ON "#__newsfeeds" ("xreference");

COMMENT ON COLUMN "#__newsfeeds"."xreference" IS 'A reference to enable linkages to external data sets.';
