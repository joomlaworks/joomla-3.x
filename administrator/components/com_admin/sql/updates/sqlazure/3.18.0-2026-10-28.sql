-- Joomla 3.x UTD 3.18.0 migration — October 28, 2026
-- Adds the CAPTCHA plugins "Akismet", "Cloudflare Turnstile", "hCaptcha" and "IconCaptcha" (self-hosted), disabled. Listed in
-- runDataMigrations() with 3.18.0, so what an administrator uninstalled stays uninstalled.

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_captcha_akismet', 'plugin', 'akismet', 'captcha', 0, 0, 1, 0, '', '{"api_key":"","block":"all","unanswered":"allow","test":"0"}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'akismet' AND "folder" = 'captcha'
);

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_captcha_turnstile', 'plugin', 'turnstile', 'captcha', 0, 0, 1, 0, '', '{"public_key":"","private_key":"","theme":"auto","size":"normal","appearance":"always"}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'turnstile' AND "folder" = 'captcha'
);

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_captcha_hcaptcha', 'plugin', 'hcaptcha', 'captcha', 0, 0, 1, 0, '', '{"public_key":"","private_key":"","theme":"light","size":"normal"}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'hcaptcha' AND "folder" = 'captcha'
);

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_captcha_iconcaptcha', 'plugin', 'iconcaptcha', 'captcha', 0, 0, 1, 0, '', '{"theme":"auto","attempts":"5","credits":"1"}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'iconcaptcha' AND "folder" = 'captcha'
);
