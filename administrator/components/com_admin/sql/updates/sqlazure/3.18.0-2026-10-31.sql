-- Joomla 3.x UTD 3.18.0 migration — October 31, 2026
-- Adds "Content - PDF" (PDF files in a viewer, {pdf images/x.pdf}) and "Button - PDF" (the editors' PDF button), both enabled.
-- Listed in runDataMigrations() with 3.18.0, so what an administrator uninstalled stays uninstalled.

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_content_pdf', 'plugin', 'pdf', 'content', 0, 1, 1, 0, '', '{"height":"","download":"1"}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'pdf' AND "folder" = 'content'
);

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_editors-xtd_pdf', 'plugin', 'pdf', 'editors-xtd', 0, 1, 1, 0, '', '', '', '', 0, '1900-01-01 00:00:00', 2, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'pdf' AND "folder" = 'editors-xtd'
);
