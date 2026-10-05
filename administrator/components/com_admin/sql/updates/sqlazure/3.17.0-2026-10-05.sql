-- Joomla 3.17.0 migration — October 5, 2026
-- Bundles the "Install from Web" installer plugin (plg_installer_webinstaller), enabled and ordered first among the installer
-- plugins, so its tab comes first. Sites which installed it from the JED keep their row (and settings); its JED update site is
-- removed, since the bundled copy is updated with Joomla and the JED feed lists no version for Joomla 3.11 and later.

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'plg_installer_webinstaller', 'plugin', 'webinstaller', 'installer', 0, 1, 1, 0, '', '{}', '', '', 0, '1900-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'plugin' AND "element" = 'webinstaller' AND "folder" = 'installer'
);

DELETE FROM "#__updates" WHERE "update_site_id" IN (SELECT "update_site_id" FROM "#__update_sites" WHERE "location" LIKE '%appscdn.joomla.org/webapps/jedapps/webinstaller.xml%');

DELETE FROM "#__update_sites_extensions" WHERE "update_site_id" IN (SELECT "update_site_id" FROM "#__update_sites" WHERE "location" LIKE '%appscdn.joomla.org/webapps/jedapps/webinstaller.xml%');

DELETE FROM "#__update_sites" WHERE "location" LIKE '%appscdn.joomla.org/webapps/jedapps/webinstaller.xml%';
