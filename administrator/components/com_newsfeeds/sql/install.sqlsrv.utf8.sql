-- com_newsfeeds: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__newsfeeds" (
  "catid" int NOT NULL DEFAULT 0,
  "id" bigint IDENTITY(1,1) NOT NULL,
  "name" nvarchar(100) NOT NULL DEFAULT '',
  "alias" nvarchar(255) NOT NULL,
  "link" nvarchar(2048) NOT NULL DEFAULT '',
  "published" smallint NOT NULL DEFAULT 0,
  "numarticles" bigint NOT NULL DEFAULT 1,
  "cache_time" bigint NOT NULL DEFAULT 3600,
  "checked_out" bigint NOT NULL DEFAULT 0,
  "checked_out_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "ordering" int NOT NULL DEFAULT 0,
  "rtl" smallint NOT NULL DEFAULT 0,
  "access" bigint NOT NULL DEFAULT 0,
  "language" nvarchar(7) NOT NULL,
  "params" nvarchar(max) NOT NULL,
  "created" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "created_by" bigint NOT NULL DEFAULT 0,
  "created_by_alias" nvarchar(255) NOT NULL DEFAULT '',
  "modified" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "modified_by" bigint NOT NULL DEFAULT 0,
  "metakey" nvarchar(max) NOT NULL,
  "metadesc" nvarchar(max) NOT NULL,
  "metadata" nvarchar(max) NOT NULL,
  "xreference" nvarchar(50) NOT NULL DEFAULT '',
  "publish_up" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_down" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "description" nvarchar(max) NOT NULL,
  "version" bigint NOT NULL DEFAULT 1,
  "hits" bigint NOT NULL DEFAULT 0,
  "images" nvarchar(max) NOT NULL,
 CONSTRAINT "PK_#__newsfeeds_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_access" ON "#__newsfeeds"
(
  "access" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_catid" ON "#__newsfeeds"
(
  "catid" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_checkout" ON "#__newsfeeds"
(
  "checked_out" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_createdby" ON "#__newsfeeds"
(
  "created_by" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_language" ON "#__newsfeeds"
(
  "language" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_state" ON "#__newsfeeds"
(
  "published" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_xreference" ON "#__newsfeeds"
(
  "xreference" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
