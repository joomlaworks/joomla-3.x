-- com_banners: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__banners" (
  "id" int IDENTITY(1,1) NOT NULL,
  "cid" int NOT NULL DEFAULT 0,
  "type" int NOT NULL DEFAULT 0,
  "name" nvarchar(255) NOT NULL DEFAULT '',
  "alias" nvarchar(255) NOT NULL DEFAULT '',
  "imptotal" int NOT NULL DEFAULT 0,
  "impmade" int NOT NULL DEFAULT 0,
  "clicks" int NOT NULL DEFAULT 0,
  "clickurl" nvarchar(200) NOT NULL DEFAULT '',
  "state" smallint NOT NULL DEFAULT 0,
  "catid" bigint NOT NULL DEFAULT 0,
  "description" nvarchar(max) NOT NULL,
  "custombannercode" nvarchar(2048) NOT NULL,
  "sticky" tinyint NOT NULL DEFAULT 0,
  "ordering" int NOT NULL DEFAULT 0,
  "metakey" nvarchar(max) NOT NULL,
  "params" nvarchar(max) NOT NULL,
  "own_prefix" smallint NOT NULL DEFAULT 0,
  "metakey_prefix" nvarchar(255) NOT NULL DEFAULT '',
  "purchase_type" smallint NOT NULL DEFAULT -1,
  "track_clicks" smallint NOT NULL DEFAULT -1,
  "track_impressions" smallint NOT NULL DEFAULT -1,
  "checked_out" bigint NOT NULL DEFAULT 0,
  "checked_out_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_up" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_down" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "reset" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "created" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "language" nvarchar(7) NOT NULL DEFAULT '',
  "created_by" bigint NOT NULL DEFAULT 0,
  "created_by_alias" nvarchar(255) NOT NULL DEFAULT '',
  "modified" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "modified_by" bigint NOT NULL DEFAULT 0,
  "version" bigint NOT NULL DEFAULT 1,
 CONSTRAINT "PK_#__banners_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_banner_catid" ON "#__banners"
(
  "catid" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_language" ON "#__banners"
(
  "language" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_metakey_prefix" ON "#__banners"
(
  "metakey_prefix" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_own_prefix" ON "#__banners"
(
  "own_prefix" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_state" ON "#__banners"
(
  "state" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_createdby" ON "#__banners"
(
  "created_by" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__banner_clients" (
  "id" int IDENTITY(1,1) NOT NULL,
  "name" nvarchar(255) NOT NULL DEFAULT '',
  "contact" nvarchar(255) NOT NULL DEFAULT '',
  "email" nvarchar(255) NOT NULL DEFAULT '',
  "extrainfo" nvarchar(max) NOT NULL,
  "state" smallint NOT NULL DEFAULT 0,
  "checked_out" bigint NOT NULL DEFAULT 0,
  "checked_out_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "metakey" nvarchar(max) NOT NULL DEFAULT 0,
  "own_prefix" smallint NOT NULL DEFAULT 0,
  "metakey_prefix" nvarchar(255) NOT NULL DEFAULT '',
  "purchase_type" smallint NOT NULL DEFAULT -1,
  "track_clicks" smallint NOT NULL DEFAULT -1,
  "track_impressions" smallint NOT NULL DEFAULT -1,
 CONSTRAINT "PK_#__banner_clients_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_metakey_prefix" ON "#__banner_clients"
(
  "metakey_prefix" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_own_prefix" ON "#__banner_clients"
(
  "own_prefix" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__banner_tracks" (
  "track_date" datetime2(0) NOT NULL,
  "track_type" bigint NOT NULL,
  "banner_id" bigint NOT NULL,
  "count" bigint NOT NULL DEFAULT 0,
 CONSTRAINT "PK_#__banner_tracks_track_date_type_id" PRIMARY KEY CLUSTERED
(
  "track_date" ASC,
  "track_type" ASC,
  "banner_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_banner_id" ON "#__banner_tracks"
(
  "banner_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_track_date2" ON "#__banner_tracks"
(
  "track_date" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_track_type" ON "#__banner_tracks"
(
  "track_type" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
