-- com_tags: its own tables, dropped when it's uninstalled

BEGIN TRY
DROP TABLE "#__tags"
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (3701) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;

BEGIN TRY
DROP TABLE "#__contentitem_tag_map"
END TRY
BEGIN CATCH IF ERROR_NUMBER() NOT IN (3701) BEGIN DECLARE @message nvarchar(2048) SET @message = ERROR_MESSAGE() RAISERROR(@message, 16, 1) END END CATCH;
