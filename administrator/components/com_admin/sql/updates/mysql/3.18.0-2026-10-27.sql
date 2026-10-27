-- Joomla 3.x UTD 3.18.0 migration: the "System - Page Cache has new options" post-installation message, for sites updating from an
-- earlier version (new sites have it disabled); shown while the plugin is enabled.

INSERT INTO `#__postinstall_messages` (`extension_id`, `title_key`, `description_key`, `action_key`, `language_extension`, `language_client_id`, `type`, `action_file`, `action`, `condition_file`, `condition_method`, `version_introduced`, `enabled`)
SELECT 700, 'COM_CPANEL_MSG_UTD_PAGECACHE_TITLE', 'COM_CPANEL_MSG_UTD_PAGECACHE_BODY', 'COM_CPANEL_MSG_UTD_PAGECACHE_BUTTON', 'com_cpanel', 1, 'action', 'admin://components/com_admin/postinstall/pagecache.php', 'admin_postinstall_pagecache_action', 'admin://components/com_admin/postinstall/pagecache.php', 'admin_postinstall_pagecache_condition', '3.18.0', 1
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__postinstall_messages` WHERE `extension_id` = 700 AND `title_key` = 'COM_CPANEL_MSG_UTD_PAGECACHE_TITLE'
);
