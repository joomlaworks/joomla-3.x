-- com_messages: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__messages" (
  "message_id" bigint IDENTITY(1,1) NOT NULL,
  "user_id_from" bigint NOT NULL DEFAULT 0,
  "user_id_to" bigint NOT NULL DEFAULT 0,
  "folder_id" tinyint NOT NULL DEFAULT 0,
  "date_time" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "state" smallint NOT NULL DEFAULT 0,
  "priority" tinyint NOT NULL DEFAULT 0,
  "subject" nvarchar(255) NOT NULL DEFAULT '',
  "message" nvarchar(max) NOT NULL,
 CONSTRAINT "PK_#__messages_message_id" PRIMARY KEY CLUSTERED
(
  "message_id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "useridto_state" ON "#__messages"
(
  "user_id_to" ASC,
  "state" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__messages_cfg" (
  "user_id" bigint NOT NULL DEFAULT 0,
  "cfg_name" nvarchar(100) NOT NULL DEFAULT '',
  "cfg_value" nvarchar(255) NOT NULL DEFAULT '',
 CONSTRAINT "#__messages_cfg$idx_user_var_name" UNIQUE CLUSTERED
(
  "user_id" ASC,
  "cfg_name" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
