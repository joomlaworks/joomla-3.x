-- Joomla 3.x UTD 3.18.0 migration — October 18, 2026
-- Adds the "System - Structured Data & Social Tags" plugin (plg_system_structureddata), enabled: structured data, Open Graph
-- and X card tags for every site from this update on. Listed in runDataMigrations() with 3.18.0, so an uninstalled plugin
-- stays uninstalled on later updates.

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_system_structureddata', 'plugin', 'structureddata', 'system', 0, 1, 1, 0, '', '{}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'structureddata' AND "folder" = 'system'
);
