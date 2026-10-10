-- com_tags: its tables, created only where they don't exist yet (a restore may find them kept)

CREATE TABLE IF NOT EXISTS "#__contentitem_tag_map" (
  "type_alias" varchar(255) NOT NULL DEFAULT '',
  "core_content_id" integer NOT NULL,
  "content_item_id" integer NOT NULL,
  "tag_id" integer NOT NULL,
  "tag_date" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "type_id" integer NOT NULL,
 CONSTRAINT "#__uc_ItemnameTagid" UNIQUE ("type_id", "content_item_id", "tag_id")
);

DROP INDEX IF EXISTS "#__contentitem_tag_map_idx_tag_type";

CREATE INDEX "#__contentitem_tag_map_idx_tag_type" ON "#__contentitem_tag_map" ("tag_id", "type_id");

DROP INDEX IF EXISTS "#__contentitem_tag_map_idx_date_id";

CREATE INDEX "#__contentitem_tag_map_idx_date_id" ON "#__contentitem_tag_map" ("tag_date", "tag_id");

DROP INDEX IF EXISTS "#__contentitem_tag_map_idx_core_content_id";

CREATE INDEX "#__contentitem_tag_map_idx_core_content_id" ON "#__contentitem_tag_map" ("core_content_id");

COMMENT ON COLUMN "#__contentitem_tag_map"."core_content_id" IS 'PK from the core content table';

COMMENT ON COLUMN "#__contentitem_tag_map"."content_item_id" IS 'PK from the content type table';

COMMENT ON COLUMN "#__contentitem_tag_map"."tag_id" IS 'PK from the tag table';

COMMENT ON COLUMN "#__contentitem_tag_map"."tag_date" IS 'Date of most recent save for this tag-item';

COMMENT ON COLUMN "#__contentitem_tag_map"."type_id" IS 'PK from the content_type table';

CREATE TABLE IF NOT EXISTS "#__tags" (
  "id" serial NOT NULL,
  "parent_id" bigint DEFAULT 0 NOT NULL,
  "lft" bigint DEFAULT 0 NOT NULL,
  "rgt" bigint DEFAULT 0 NOT NULL,
  "level" integer DEFAULT 0 NOT NULL,
  "path" varchar(255) DEFAULT '' NOT NULL,
  "title" varchar(255) NOT NULL,
  "alias" varchar(255) DEFAULT '' NOT NULL,
  "note" varchar(255) DEFAULT '' NOT NULL,
  "description" text NOT NULL,
  "published" smallint DEFAULT 0 NOT NULL,
  "checked_out" bigint DEFAULT 0 NOT NULL,
  "checked_out_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "access" bigint DEFAULT 0 NOT NULL,
  "params" text NOT NULL,
  "metadesc" varchar(1024) NOT NULL,
  "metakey" varchar(1024) NOT NULL,
  "metadata" varchar(2048) NOT NULL,
  "created_user_id" integer DEFAULT 0 NOT NULL,
  "created_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "created_by_alias" varchar(255) DEFAULT '' NOT NULL,
  "modified_user_id" integer DEFAULT 0 NOT NULL,
  "modified_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "images" text NOT NULL,
  "urls" text NOT NULL,
  "hits" integer DEFAULT 0 NOT NULL,
  "language" varchar(7) DEFAULT '' NOT NULL,
  "version" bigint DEFAULT 1 NOT NULL,
  "publish_up" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "publish_down" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  PRIMARY KEY ("id")
);

DROP INDEX IF EXISTS "#__tags_cat_idx";

CREATE INDEX "#__tags_cat_idx" ON "#__tags" ("published", "access");

DROP INDEX IF EXISTS "#__tags_idx_access";

CREATE INDEX "#__tags_idx_access" ON "#__tags" ("access");

DROP INDEX IF EXISTS "#__tags_idx_checkout";

CREATE INDEX "#__tags_idx_checkout" ON "#__tags" ("checked_out");

DROP INDEX IF EXISTS "#__tags_idx_path";

CREATE INDEX "#__tags_idx_path" ON "#__tags" ("path");

DROP INDEX IF EXISTS "#__tags_idx_left_right";

CREATE INDEX "#__tags_idx_left_right" ON "#__tags" ("lft", "rgt");

DROP INDEX IF EXISTS "#__tags_idx_alias";

CREATE INDEX "#__tags_idx_alias" ON "#__tags" ("alias");

DROP INDEX IF EXISTS "#__tags_idx_language";

CREATE INDEX "#__tags_idx_language" ON "#__tags" ("language");

INSERT INTO "#__tags" ("id", "parent_id", "lft", "rgt", "level", "path", "title", "alias", "note", "description", "published", "checked_out", "checked_out_time", "access", "params", "metadesc", "metakey", "metadata", "created_user_id", "created_time", "created_by_alias", "modified_user_id", "modified_time", "images", "urls", "hits", "language", "version")
SELECT 1, 0, 0, 1, 0, '', 'ROOT', 'root', '', '', 1, 0, '1970-01-01 00:00:00', 1, '', '', '', '', 42, '2011-01-01 00:00:01', '', 0, '1970-01-01 00:00:00', '', '',  0, '*', 1
WHERE NOT EXISTS (SELECT 1 FROM "#__tags" WHERE "id" = 1);

SELECT setval('#__tags_id_seq', GREATEST(COALESCE(MAX("id"), 0) + 1, 2), false) FROM "#__tags";
