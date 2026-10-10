-- Joomla 3.18.0: these core extensions may be uninstalled (with the extensions which only serve them, their <companions>);
-- "Restore uninstalled core extensions" in Joomla Update brings them back
UPDATE "#__extensions" SET "protected" = 0
WHERE ("type" = 'component' AND "element" IN ('com_content', 'com_messages', 'com_privacy', 'com_redirect', 'com_tags', 'com_wrapper'))
OR ("type" = 'module' AND "client_id" = 0 AND "element" IN ('mod_breadcrumbs', 'mod_syndicate'))
OR ("type" = 'plugin' AND "folder" = 'editors' AND "element" = 'codemirror')
OR ("type" = 'plugin' AND "folder" = 'system' AND "element" = 'redirect');
