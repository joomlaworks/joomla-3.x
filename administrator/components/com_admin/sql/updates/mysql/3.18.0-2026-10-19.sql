-- Joomla 3.x UTD 3.18.0 migration — October 19, 2026
-- Adds the "Editor - EasyMDE (Markdown)" plugin (plg_editors_easymde), enabled; sites and users choose it as their editor in
-- Global Configuration or their profile. Listed in runDataMigrations() with 3.18.0, so an uninstalled plugin stays
-- uninstalled on later updates.

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'plg_editors_easymde', 'plugin', 'easymde', 'editors', 0, 1, 1, 0, '', '{"height":"500px","side_by_side":"1","spellcheck":"1","upload":"1","folder":"uploads"}', '', '', 0, '0000-00-00 00:00:00', 5, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `type` = 'plugin' AND `element` = 'easymde' AND `folder` = 'editors'
);
