-- Joomla 3.17.0 migration — October 4, 2026
-- Self-heals a missing "English (en-GB) Language Pack" (pkg_en-GB) package record. Stock Joomla adds it in
-- 3.6.0-2016-04-08.sql, a data-only statement that some upgrade paths never ran. Without it, Extensions:
-- Install Languages always shows "The update table is not up to date". Every statement is idempotent and
-- does nothing on sites that are already correct.

INSERT INTO "#__extensions" ("package_id", "name", "type", "element", "folder", "client_id", "enabled", "access", "protected", "manifest_cache", "params", "custom_data", "system_data", "checked_out", "checked_out_time", "ordering", "state")
SELECT 0, 'English (en-GB) Language Pack', 'package', 'pkg_en-GB', '', 0, 1, 1, 1, '', '', '', '', 0, '1970-01-01 00:00:00', 0, 0
WHERE NOT EXISTS (
	SELECT 1 FROM "#__extensions" WHERE "type" = 'package' AND "element" = 'pkg_en-GB'
);

INSERT INTO "#__update_sites" ("name", "type", "location", "enabled", "last_check_timestamp", "extra_query")
SELECT 'Accredited Joomla! Translations', 'collection', 'https://update.joomla.org/language/translationlist_3.xml', 1, 0, ''
WHERE NOT EXISTS (
	SELECT 1 FROM "#__update_sites" WHERE "location" LIKE '%update.joomla.org/language/translationlist_3.xml%'
);

INSERT INTO "#__update_sites_extensions" ("update_site_id", "extension_id")
SELECT "us"."update_site_id", "e"."extension_id"
FROM "#__update_sites" AS "us", "#__extensions" AS "e"
WHERE "us"."location" LIKE '%update.joomla.org/language/translationlist_3.xml%'
AND "e"."type" = 'package' AND "e"."element" = 'pkg_en-GB'
AND NOT EXISTS (
	SELECT 1 FROM "#__update_sites_extensions" AS "x" WHERE "x"."update_site_id" = "us"."update_site_id" AND "x"."extension_id" = "e"."extension_id"
);

UPDATE "#__extensions"
SET "package_id" = sub.extension_id
FROM (SELECT "extension_id" FROM "#__extensions" WHERE "type" = 'package' AND "element" = 'pkg_en-GB') AS sub
WHERE "type" = 'language' AND "element" = 'en-GB' AND "package_id" = 0;

-- The installer creates the Super User with a random ID, which PostgreSQL doesn't move the users' ID sequence past:
-- creating the user whose turn reaches that ID then failed with a duplicate key. Only ever moves the sequence forward.
SELECT setval('#__users_id_seq', MAX("id")) FROM "#__users" HAVING MAX("id") >= (SELECT "last_value" FROM "#__users_id_seq");

-- "System - Joomla! Statistics" stays installed but off: joomla.org's statistics don't cover this distribution. Runs on every
-- update and reinstall (runDataMigrations()), as asked by the maintainer.
UPDATE "#__extensions" SET "enabled" = 0 WHERE "type" = 'plugin' AND "folder" = 'system' AND "element" = 'stats' AND "enabled" = 1;
