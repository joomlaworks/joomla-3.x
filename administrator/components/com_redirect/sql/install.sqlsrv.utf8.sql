-- com_redirect: its tables, created only where they don't exist yet (a restore may find them kept)

BEGIN TRY
CREATE TABLE "#__redirect_links" (
  "id" bigint IDENTITY(1,1) NOT NULL,
  "old_url" nvarchar(2048) NOT NULL,
  "new_url" nvarchar(2048),
  "referer" nvarchar(2048) NOT NULL,
  "comment" nvarchar(255) NOT NULL DEFAULT '',
  "hits" bigint NOT NULL DEFAULT 0,
  "published" smallint NOT NULL,
  "created_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "modified_date" datetime2(0) NOT NULL DEFAULT '1900-01-01 00:00:00',
  "header" smallint NOT NULL DEFAULT 301,
 CONSTRAINT "PK_#__redirect_links_id" PRIMARY KEY CLUSTERED
(
  "id" ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
) ON [PRIMARY]
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (2714) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_old_url" ON "#__redirect_links"
(
  "old_url" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
CREATE NONCLUSTERED INDEX "idx_link_modifed2" ON "#__redirect_links"
(
  "modified_date" ASC
)WITH (STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF)
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (1913) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
