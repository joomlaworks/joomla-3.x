-- Joomla 3.12.0 migration — May 21, 2026
-- Removes the eos310 and phpversioncheck quickicon plugins and the hathor backend
-- template, which are no longer shipped with this distribution. Beez 3 is decided
-- by the update script (JoomlaInstallerScript::keepOrRemoveBeez3()): kept when in use.

-- If hathor is currently the global default backend template, switch to isis
UPDATE `#__template_styles` SET `home` = '1'
WHERE `template` = 'isis' AND `client_id` = 1
AND EXISTS (SELECT 1 FROM (SELECT `id` FROM `#__template_styles` WHERE `template` = 'hathor' AND `home` = '1' AND `client_id` = 1) AS t);

-- Remove all template style records for hathor
DELETE FROM `#__template_styles` WHERE `template` = 'hathor';

-- Remove the extensions
DELETE FROM `#__extensions` WHERE `element` = 'eos310' AND `type` = 'plugin' AND `folder` = 'quickicon';
DELETE FROM `#__extensions` WHERE `element` = 'phpversioncheck' AND `type` = 'plugin' AND `folder` = 'quickicon';
DELETE FROM `#__extensions` WHERE `element` = 'hathor' AND `type` = 'template';

-- Remove hathor post-install message
DELETE FROM `#__postinstall_messages` WHERE `title_key` = 'TPL_HATHOR_MESSAGE_POSTINSTALL_TITLE';

-- Remove any orphaned update site mappings left behind by the removed extensions
DELETE FROM `#__update_sites_extensions` WHERE `extension_id` NOT IN (SELECT `extension_id` FROM `#__extensions`);
