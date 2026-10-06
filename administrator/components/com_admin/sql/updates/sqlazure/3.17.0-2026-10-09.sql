-- Joomla 3.17.0 migration — October 9, 2026
-- Adds the Housekeeping administrator module (Clean Cache, Clean Everything and Global Check-in in the status bar).

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'mod_housekeeping', 'module', 'mod_housekeeping', '', 1, 1, 1, 0, '', '', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'module' AND "element" = 'mod_housekeeping' AND "client_id" = 1
);

INSERT INTO "#__modules" ("asset_id", "title", "note", "content", "ordering", "position", "checked_out", "checked_out_time", "publish_up", "publish_down", "published", "module", "access", "showtitle", "params", "client_id", "language")
SELECT 0, 'Housekeeping', '', '', 3, 'status', 0, '1900-01-01 00:00:00', '1900-01-01 00:00:00', '1900-01-01 00:00:00', 1, 'mod_housekeeping', 1, 1, '', 1, '*'
WHERE NOT EXISTS (
	SELECT 1 FROM "#__modules" WHERE "module" = 'mod_housekeeping' AND "client_id" = 1
);

INSERT INTO "#__modules_menu" ("moduleid", "menuid")
SELECT "id", 0 FROM "#__modules" m
WHERE m."module" = 'mod_housekeeping' AND m."client_id" = 1
	AND NOT EXISTS (SELECT 1 FROM "#__modules_menu" mm WHERE mm."moduleid" = m."id");
