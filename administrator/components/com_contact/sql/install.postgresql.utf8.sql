--
-- Table structure for table `#__contact_details`
--

CREATE TABLE "#__contact_details" (
  "id" serial NOT NULL,
  "name" varchar(255) NOT NULL,
  "alias" varchar(255) NOT NULL,
  "con_position" varchar(255),
  "address" text,
  "suburb" varchar(100),
  "state" varchar(100),
  "country" varchar(100),
  "postcode" varchar(100),
  "telephone" varchar(255),
  "fax" varchar(255),
  "misc" text,
  "image" varchar(255),
  "email_to" varchar(255),
  "default_con" smallint NOT NULL DEFAULT 0,
  "published" smallint NOT NULL DEFAULT 0,
  "checked_out" bigint NOT NULL DEFAULT 0,
  "checked_out_time" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  "ordering" bigint NOT NULL DEFAULT 0,
  "params" text NOT NULL,
  "user_id" bigint NOT NULL DEFAULT 0,
  "catid" bigint NOT NULL DEFAULT 0,
  "access" bigint NOT NULL DEFAULT 0,
  "mobile" varchar(255) NOT NULL DEFAULT '',
  "webpage" varchar(255) NOT NULL DEFAULT '',
  "sortname1" varchar(255) NOT NULL DEFAULT '',
  "sortname2" varchar(255) NOT NULL DEFAULT '',
  "sortname3" varchar(255) NOT NULL DEFAULT '',
  "language" varchar(7) NOT NULL,
  "created" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  "created_by" integer NOT NULL DEFAULT 0,
  "created_by_alias" varchar(255) NOT NULL DEFAULT '',
  "modified" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  "modified_by" integer NOT NULL DEFAULT 0,
  "metakey" text NOT NULL,
  "metadesc" text NOT NULL,
  "metadata" text NOT NULL,
  "featured" smallint NOT NULL DEFAULT 0,
  "xreference" varchar(50) NOT NULL DEFAULT '',
  "publish_up" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  "publish_down" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  "version" bigint NOT NULL DEFAULT 1,
  "hits" bigint NOT NULL DEFAULT 0,
  PRIMARY KEY ("id")
);
CREATE INDEX "#__contact_details_idx_access" ON "#__contact_details" ("access");
CREATE INDEX "#__contact_details_idx_checkout" ON "#__contact_details" ("checked_out");
CREATE INDEX "#__contact_details_idx_state" ON "#__contact_details" ("published");
CREATE INDEX "#__contact_details_idx_catid" ON "#__contact_details" ("catid");
CREATE INDEX "#__contact_details_idx_createdby" ON "#__contact_details" ("created_by");
CREATE INDEX "#__contact_details_idx_featured_catid" ON "#__contact_details" ("featured", "catid");
CREATE INDEX "#__contact_details_idx_language" ON "#__contact_details" ("language");
CREATE INDEX "#__contact_details_idx_xreference" ON "#__contact_details" ("xreference");

COMMENT ON COLUMN "#__contact_details"."featured" IS 'Set if contact is featured.';
COMMENT ON COLUMN "#__contact_details"."xreference" IS 'A reference to enable linkages to external data sets.';
