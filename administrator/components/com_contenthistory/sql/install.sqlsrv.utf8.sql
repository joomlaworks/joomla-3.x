-- com_contenthistory: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__ucm_history" (
  "version_id" bigint IDENTITY(1,1) NOT NULL,
  "ucm_item_id" bigint NOT NULL,
  "ucm_type_id" bigint NOT NULL,
  "version_note" nvarchar(255) NOT NULL DEFAULT '',
  "save_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "editor_user_id" bigint NOT NULL DEFAULT 0,
  "character_count" bigint NOT NULL DEFAULT 0,
  "sha1_hash" nvarchar(50) NOT NULL DEFAULT '',
  "version_data" nvarchar(max) NOT NULL,
  "keep_forever" smallint NOT NULL DEFAULT 0,
 CONSTRAINT "PK_#__ucm_history_version_id" PRIMARY KEY CLUSTERED
(
  "version_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_ucm_item_id" ON "#__ucm_history"
(
  "ucm_type_id" ASC,
  "ucm_item_id" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_save_date2" ON "#__ucm_history"
(
  "save_date" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
