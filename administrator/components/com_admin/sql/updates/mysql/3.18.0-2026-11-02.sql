-- Joomla 3.x UTD 3.18.0 migration: the "What's New in Joomla 3.x UTD 3.18" post-installation message.

INSERT INTO `#__postinstall_messages` (`extension_id`, `title_key`, `description_key`, `action_key`, `language_extension`, `language_client_id`, `type`, `action_file`, `action`, `condition_file`, `condition_method`, `version_introduced`, `enabled`)
SELECT 700, 'COM_CPANEL_MSG_UTD_3_18_TITLE', 'COM_CPANEL_MSG_UTD_3_18_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.18.0', 1
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__postinstall_messages` WHERE `extension_id` = 700 AND `title_key` = 'COM_CPANEL_MSG_UTD_3_18_TITLE'
);
