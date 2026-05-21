-- Joomla 3.12.0 migration — May 21, 2026
-- Removes the eos310 and phpversioncheck quickicon plugins, and the beez3
-- frontend and hathor backend templates, which are no longer shipped with
-- this distribution.

-- If beez3 is currently the global default frontend template, switch to protostar
UPDATE "#__template_styles" SET "home" = '1'
WHERE "template" = 'protostar' AND "client_id" = 0
AND EXISTS (SELECT 1 FROM "#__template_styles" WHERE "template" = 'beez3' AND "home" = '1' AND "client_id" = 0);

-- If hathor is currently the global default backend template, switch to isis
UPDATE "#__template_styles" SET "home" = '1'
WHERE "template" = 'isis' AND "client_id" = 1
AND EXISTS (SELECT 1 FROM "#__template_styles" WHERE "template" = 'hathor' AND "home" = '1' AND "client_id" = 1);

-- Remove all template style records for beez3 and hathor
DELETE FROM "#__template_styles" WHERE "template" IN ('beez3', 'hathor');

-- Remove the extensions
DELETE FROM "#__extensions" WHERE "element" = 'eos310' AND "type" = 'plugin' AND "folder" = 'quickicon';
DELETE FROM "#__extensions" WHERE "element" = 'phpversioncheck' AND "type" = 'plugin' AND "folder" = 'quickicon';
DELETE FROM "#__extensions" WHERE "element" = 'beez3' AND "type" = 'template';
DELETE FROM "#__extensions" WHERE "element" = 'hathor' AND "type" = 'template';

-- Remove hathor post-install message
DELETE FROM "#__postinstall_messages" WHERE "language_key" = 'TPL_HATHOR_MESSAGE_POSTINSTALL_TITLE';

-- Remove any orphaned update site mappings left behind by the removed extensions
DELETE FROM "#__update_sites_extensions" WHERE "extension_id" NOT IN (SELECT "extension_id" FROM "#__extensions");
