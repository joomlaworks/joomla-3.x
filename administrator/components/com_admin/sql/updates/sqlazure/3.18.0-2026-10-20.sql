-- Joomla 3.x UTD 3.18.0 migration — October 20, 2026
-- Adds the "Custom Code" module (mod_customcode) for the site and the administrator: code of your own, written in
-- CodeMirror. Listed in runDataMigrations() with 3.18.0, so an uninstalled module stays uninstalled on later updates.

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'mod_customcode', 'module', 'mod_customcode', '', 0, 1, 1, 0, '', '', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'module' AND "element" = 'mod_customcode' AND "client_id" = 0
);

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'mod_customcode', 'module', 'mod_customcode', '', 1, 1, 1, 0, '', '', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'module' AND "element" = 'mod_customcode' AND "client_id" = 1
);
