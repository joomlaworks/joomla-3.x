-- Joomla 3.16.0 migration — August 21, 2026
-- Adds the "Little WAF" system plugin (plg_system_littlewaf), disabled by default.
-- Once enabled, its individual protections default to on, but the plugin itself
-- stays disabled here, so this insert alone changes no site behavior.

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'plg_system_littlewaf', 'plugin', 'littlewaf', 'system', 0, 0, 1, 0, '', '{"filter_sourcerer":"1","filter_modulesanywhere":"1","log_blocked":"1"}', '', '', 0, '1970-01-01 00:00:00', -10000, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `element` = 'littlewaf' AND `folder` = 'system' AND `type` = 'plugin'
);
