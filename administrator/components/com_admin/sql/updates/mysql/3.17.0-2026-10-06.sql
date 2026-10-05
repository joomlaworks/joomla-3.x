-- Joomla 3.17.0 migration — October 6, 2026
-- Adds the TinyMCE 8 editor (plg_editors_tinymce_latest, "Editor - TinyMCE"), enabled, next to the TinyMCE 4 editor, which is now
-- called "Editor - TinyMCE (legacy)". Sites keep the editor they have; admins switch in Global Configuration.

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'plg_editors_tinymce_latest', 'plugin', 'tinymce_latest', 'editors', 0, 1, 1, 0, '', '{}', '', '', 0, '1970-01-01 00:00:00', 4, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `type` = 'plugin' AND `element` = 'tinymce_latest' AND `folder` = 'editors'
);
