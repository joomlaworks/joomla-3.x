-- Joomla 3.18.0 migration — October 12, 2026
-- Adds the Prothero administrator template (not as the default: existing sites keep theirs).

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'prothero', 'template', 'prothero', '', 1, 1, 1, 0, '', '{"colorScheme":"auto","logoFile":""}', '', '', 0, '1970-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'template' AND "element" = 'prothero' AND "client_id" = 1
);

INSERT INTO "#__template_styles" ("template", "client_id", "home", "title", "params")
SELECT 'prothero', 1, '0', 'Prothero - Default', '{"colorScheme":"auto","logoFile":""}'
WHERE NOT EXISTS (
	SELECT 1 FROM "#__template_styles" WHERE "template" = 'prothero' AND "client_id" = 1
);
