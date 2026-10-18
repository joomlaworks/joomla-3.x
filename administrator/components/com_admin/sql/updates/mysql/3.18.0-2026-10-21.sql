-- Joomla 3.x UTD 3.18.0 migration — October 21, 2026
-- Adds "Editor - Blocks (Editor.js)" (plg_editors_editorjs), enabled, and the "Custom Blocks" module (mod_customblocks) for the
-- site and the administrator. Listed in runDataMigrations() with 3.18.0, so what an administrator uninstalled stays uninstalled.

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'plg_editors_editorjs', 'plugin', 'editorjs', 'editors', 0, 1, 1, 0, '', '{"height":"500px","upload":"1","folder":"uploads"}', '', '', 0, '0000-00-00 00:00:00', 6, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `type` = 'plugin' AND `element` = 'editorjs' AND `folder` = 'editors'
);

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'mod_customblocks', 'module', 'mod_customblocks', '', 0, 1, 1, 0, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `type` = 'module' AND `element` = 'mod_customblocks' AND `client_id` = 0
);

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'mod_customblocks', 'module', 'mod_customblocks', '', 1, 1, 1, 0, '', '', '', '', 0, '0000-00-00 00:00:00', 0, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `type` = 'module' AND `element` = 'mod_customblocks' AND `client_id` = 1
);
