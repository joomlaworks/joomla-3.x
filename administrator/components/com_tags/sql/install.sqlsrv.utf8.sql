-- com_tags: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__contentitem_tag_map" (
  "type_alias" nvarchar(255) NOT NULL DEFAULT '',
  "core_content_id" bigint NOT NULL,
  "content_item_id" int NOT NULL,
  "tag_id" bigint NOT NULL,
  "tag_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "type_id" int NOT NULL,
 CONSTRAINT "#__contentitem_tag_map$uc_ItemnameTagid" UNIQUE NONCLUSTERED
(
  "type_id" ASC,
  "content_item_id" ASC,
  "tag_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_date_id2" ON "#__contentitem_tag_map"
(
  "tag_date" ASC,
  "tag_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_core_content_id" ON "#__contentitem_tag_map"
(
  "core_content_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__tags" (
  "id" bigint IDENTITY(1,1) NOT NULL,
  "parent_id" bigint NOT NULL DEFAULT 0,
  "lft" int NOT NULL DEFAULT 0,
  "rgt" int NOT NULL DEFAULT 0,
  "level" bigint NOT NULL DEFAULT 0,
  "path" nvarchar(255) NOT NULL DEFAULT '',
  "title" nvarchar(255) NOT NULL,
  "alias" nvarchar(255) NOT NULL DEFAULT '',
  "note" nvarchar(255) NOT NULL DEFAULT '',
  "description" nvarchar(max) NOT NULL,
  "published" smallint NOT NULL DEFAULT 0,
  "checked_out" bigint NOT NULL DEFAULT 0,
  "checked_out_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "access" bigint NOT NULL DEFAULT 0,
  "params" nvarchar(max) NOT NULL,
  "metadesc" nvarchar(1024) NOT NULL,
  "metakey" nvarchar(1024) NOT NULL,
  "metadata" nvarchar(2048) NOT NULL,
  "created_user_id" bigint NOT NULL DEFAULT 0,
  "created_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "created_by_alias" nvarchar(255) NOT NULL DEFAULT '',
  "modified_user_id" bigint NOT NULL DEFAULT 0,
  "modified_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "images" nvarchar(max) NOT NULL,
  "urls" nvarchar(max) NOT NULL,
  "hits" bigint NOT NULL DEFAULT 0,
  "language" nvarchar(7) NOT NULL,
  "version" bigint NOT NULL DEFAULT 1,
  "publish_up" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_down" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
 CONSTRAINT "PK_#__tags_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "tag_idx" ON "#__tags"
(
  "published" ASC,
  "access" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_access" ON "#__tags"
(
  "access" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_checkout" ON "#__tags"
(
  "checked_out" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_path" ON "#__tags"
(
  "path" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_left_right" ON "#__tags"
(
  "lft" ASC,
  "rgt" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_alias" ON "#__tags"
(
  "alias" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_language" ON "#__tags"
(
  "language" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

SET IDENTITY_INSERT "#__tags" ON;

IF NOT EXISTS (SELECT 1 FROM "#__tags")
BEGIN
INSERT INTO "#__tags" ("id", "parent_id", "lft", "rgt", "level", "path", "title", "alias", "note", "description", "published", "checked_out", "checked_out_time", "access", "params", "metadesc", "metakey", "metadata", "created_user_id", "created_time", "created_by_alias", "modified_user_id", "modified_time", "images", "urls", "hits", "language", "version") VALUES
(1, 0, 0, 1, 0, '', 'ROOT', 'root', '', '', 1, 0, '1900-01-01 00:00:00', 1, '', '', '', '', 42, '2011-01-01 00:00:01', '', 0, '1900-01-01 00:00:00', '', '', 0, '*', 1)
END;

SET IDENTITY_INSERT "#__tags" OFF;
