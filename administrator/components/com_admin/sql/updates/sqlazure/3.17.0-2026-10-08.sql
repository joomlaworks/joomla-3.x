-- Joomla 3.17.0 migration — October 8, 2026
-- Adds the Finch blog template (not as the default: existing sites keep theirs).

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'finch', 'template', 'finch', '', 0, 1, 1, 0, '', '{"siteName":"","tagline":"","authorBio":"","footerText":"","social_x":"","social_instagram":"","social_facebook":"","social_linkedin":"","social_rss":""}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'template' AND "element" = 'finch' AND "client_id" = 0
);

INSERT INTO "#__template_styles" ("template", "client_id", "home", "title", "params")
SELECT 'finch', 0, '0', 'Finch - Default', '{"siteName":"","tagline":"","authorBio":"","footerText":"","social_x":"","social_instagram":"","social_facebook":"","social_linkedin":"","social_rss":""}'
WHERE NOT EXISTS (
	SELECT 1 FROM "#__template_styles" WHERE "template" = 'finch' AND "client_id" = 0
);
