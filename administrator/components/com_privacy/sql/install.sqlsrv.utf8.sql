-- com_privacy: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__privacy_requests" (
  "id" int IDENTITY(1,1) NOT NULL,
  "email" nvarchar(100) NOT NULL DEFAULT '',
  "requested_at" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "status" smallint NOT NULL,
  "request_type" nvarchar(25) NOT NULL DEFAULT '',
  "confirm_token" nvarchar(100) NOT NULL DEFAULT '',
  "confirm_token_created_at" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
CONSTRAINT "PK_#__privacy_requests_id" PRIMARY KEY CLUSTERED(
  "id" ASC)
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON
) ON [PRIMARY]) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE TABLE "#__privacy_consents" (
  "id" int IDENTITY(1,1) NOT NULL,
  "user_id" bigint NOT NULL DEFAULT 0,
  "state" smallint NOT NULL DEFAULT 1,
  "created" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "subject" nvarchar(255) NOT NULL DEFAULT '',
  "body" nvarchar(max) NOT NULL,
  "remind" smallint NOT NULL DEFAULT 0,
  "token" nvarchar(100) NOT NULL DEFAULT '',
CONSTRAINT "PK_#__privacy_consents_id" PRIMARY KEY CLUSTERED(
  "id" ASC)
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON
) ON [PRIMARY]) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_user_id" ON "#__privacy_consents" (
  "user_id" ASC)
WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
