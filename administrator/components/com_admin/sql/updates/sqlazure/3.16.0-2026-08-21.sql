-- Joomla 3.16.0 migration — August 21, 2026
-- Adds the "Little WAF" system plugin (plg_system_littlewaf), disabled by default.
-- Once enabled, its individual protections default to on, but the plugin itself
-- stays disabled here, so this insert alone changes no site behavior.

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_system_littlewaf', 'plugin', 'littlewaf', 'system', 0, 0, 1, 0, '', '{"filter_sourcerer":"1","filter_modulesanywhere":"1","log_blocked":"1","block_probes":"1","block_executable_uploads":"1","block_serialized":"1","block_scanners":"1","normalize_duplicates":"1","normalize_question_marks":"1","normalize_path_ampersand":"1","normalize_search_feeds":"1","header_nosniff":"1","header_xss_protection":"0","header_referrer_policy":"strict-origin-when-cross-origin","header_frame_options":"","header_hsts":"","header_permissions_policy":""}', '', '', 0, '1900-01-01 00:00:00', -100, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "element" = 'littlewaf' AND "folder" = 'system' AND "type" = 'plugin'
);
