-- Joomla 3.x UTD 3.18.0: removes the "Authentication - GMail" plugin (Google no longer accepts its password sign-in),
-- the "System - P3P Policy" plugin (no browser reads P3P headers any more) and joomla.org's update site for the
-- Joomla Update component, whose feed only has versions for Joomla 3.6 and 4.0. "User - Joomla!" becomes protected: without it nobody
-- can log in (it's the user plugin which accepts logins), so it can't be uninstalled any more.
DELETE FROM "#__extensions" WHERE "type" = 'plugin' AND "folder" = 'authentication' AND "element" = 'gmail';
DELETE FROM "#__extensions" WHERE "type" = 'plugin' AND "folder" = 'system' AND "element" = 'p3p';
DELETE FROM "#__updates" WHERE "update_site_id" IN (SELECT "update_site_id" FROM "#__update_sites" WHERE "location" = 'https://update.joomla.org/core/extensions/com_joomlaupdate.xml');
DELETE FROM "#__update_sites_extensions" WHERE "update_site_id" IN (SELECT "update_site_id" FROM "#__update_sites" WHERE "location" = 'https://update.joomla.org/core/extensions/com_joomlaupdate.xml');
DELETE FROM "#__update_sites" WHERE "location" = 'https://update.joomla.org/core/extensions/com_joomlaupdate.xml';
DELETE FROM "#__update_sites_extensions" WHERE "extension_id" NOT IN (SELECT "extension_id" FROM "#__extensions");
UPDATE "#__extensions" SET "protected" = 1 WHERE "type" = 'plugin' AND "folder" = 'user' AND "element" = 'joomla';
