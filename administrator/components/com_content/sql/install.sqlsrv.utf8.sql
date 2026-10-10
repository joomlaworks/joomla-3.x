-- com_content: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__content" (
  "id" bigint IDENTITY(1,1) NOT NULL,
  "asset_id" bigint NOT NULL DEFAULT 0,
  "title" nvarchar(255) NOT NULL DEFAULT '',
  "alias" nvarchar(255) NOT NULL,
  "introtext" nvarchar(max) NOT NULL,
  "fulltext" nvarchar(max) NOT NULL,
  "state" smallint NOT NULL DEFAULT 0,
  "catid" bigint NOT NULL DEFAULT 0,
  "created" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "created_by" bigint NOT NULL DEFAULT 0,
  "created_by_alias" nvarchar(255) NOT NULL DEFAULT '',
  "modified" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "modified_by" bigint NOT NULL DEFAULT 0,
  "checked_out" bigint NOT NULL DEFAULT 0,
  "checked_out_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_up" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_down" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "images" nvarchar(max) NOT NULL,
  "urls" nvarchar(max) NOT NULL,
  "attribs" nvarchar(max) NOT NULL,
  "version" bigint NOT NULL DEFAULT 1,
  "ordering" int NOT NULL DEFAULT 0,
  "metakey" nvarchar(max) NOT NULL,
  "metadesc" nvarchar(max) NOT NULL,
  "access" bigint NOT NULL DEFAULT 0,
  "hits" bigint NOT NULL DEFAULT 0,
  "metadata" nvarchar(max) NOT NULL,
  "featured" tinyint NOT NULL DEFAULT 0,
  "language" nvarchar(7) NOT NULL,
  "xreference" nvarchar(50) NOT NULL DEFAULT '',
  "note" nvarchar(255) NOT NULL DEFAULT '',
 CONSTRAINT "PK_#__content_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_access" ON "#__content"
(
  "access" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_catid" ON "#__content"
(
  "catid" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_checkout" ON "#__content"
(
  "checked_out" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_createdby" ON "#__content"
(
  "created_by" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_featured_catid" ON "#__content"
(
  "featured" ASC,
  "catid" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_language" ON "#__content"
(
  "language" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_state" ON "#__content"
(
  "state" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_xreference" ON "#__content"
(
  "xreference" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_alias" ON "#__content"
(
  "alias" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__content_frontpage" (
  "content_id" int NOT NULL DEFAULT 0,
  "ordering" int NOT NULL DEFAULT 0,
 CONSTRAINT "PK_#__content_frontpage_content_id" PRIMARY KEY CLUSTERED
(
  "content_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__content_rating" (
  "content_id" int NOT NULL DEFAULT 0,
  "rating_sum" bigint NOT NULL DEFAULT 0,
  "rating_count" bigint NOT NULL DEFAULT 0,
  "lastip" nvarchar(50) NOT NULL DEFAULT '',
 CONSTRAINT "PK_#__content_rating_content_id" PRIMARY KEY CLUSTERED
(
  "content_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
