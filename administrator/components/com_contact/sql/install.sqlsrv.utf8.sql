-- com_contact: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__contact_details" (
  "id" int IDENTITY(1,1) NOT NULL,
  "name" nvarchar(255) NOT NULL,
  "alias" nvarchar(255) NOT NULL,
  "con_position" nvarchar(255),
  "address" nvarchar(max),
  "suburb" nvarchar(100),
  "state" nvarchar(100),
  "country" nvarchar(100),
  "postcode" nvarchar(100),
  "telephone" nvarchar(255),
  "fax" nvarchar(255),
  "misc" nvarchar(max),
  "image" nvarchar(255),
  "email_to" nvarchar(255),
  "default_con" tinyint NOT NULL DEFAULT 0,
  "published" smallint NOT NULL DEFAULT 0,
  "checked_out" int NOT NULL DEFAULT 0,
  "checked_out_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "ordering" int NOT NULL DEFAULT 0,
  "params" nvarchar(max) NOT NULL,
  "user_id" int NOT NULL DEFAULT 0,
  "catid" int NOT NULL DEFAULT 0,
  "access" int NOT NULL DEFAULT 0,
  "mobile" nvarchar(255) NOT NULL DEFAULT '',
  "webpage" nvarchar(255) NOT NULL DEFAULT '',
  "sortname1" nvarchar(255) NOT NULL DEFAULT '',
  "sortname2" nvarchar(255) NOT NULL DEFAULT '',
  "sortname3" nvarchar(255) NOT NULL DEFAULT '',
  "language" nvarchar(7) NOT NULL,
  "created" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "created_by" bigint NOT NULL DEFAULT 0,
  "created_by_alias" nvarchar(255) NOT NULL DEFAULT '',
  "modified" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "modified_by" bigint NOT NULL DEFAULT 0,
  "metakey" nvarchar(max) NOT NULL,
  "metadesc" nvarchar(max) NOT NULL,
  "metadata" nvarchar(max) NOT NULL,
  "featured" tinyint NOT NULL DEFAULT 0,
  "xreference" nvarchar(50) NOT NULL DEFAULT '',
  "publish_up" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "publish_down" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "version" bigint NOT NULL DEFAULT 1,
  "hits" bigint NOT NULL DEFAULT 0,
 CONSTRAINT "PK_#__contact_details_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_access" ON "#__contact_details"
(
  "access" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_catid" ON "#__contact_details"
(
  "catid" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_checkout" ON "#__contact_details"
(
  "checked_out" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_createdby" ON "#__contact_details"
(
  "created_by" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_featured_catid" ON "#__contact_details"
(
  "featured" ASC,
  "catid" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_language" ON "#__contact_details"
(
  "language" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_state" ON "#__contact_details"
(
  "published" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_xreference" ON "#__contact_details"
(
  "xreference" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
