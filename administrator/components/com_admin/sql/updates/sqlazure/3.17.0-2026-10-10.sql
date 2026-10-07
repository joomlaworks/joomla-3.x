-- Joomla 3.17.0 migration — October 10, 2026
-- Replaces stock Joomla's post-installation messages (3.2 to 3.10, long outdated) with the "What's New" messages of every
-- Joomla 3.x UTD release (3.11 to 3.17), added oldest first: the list shows the newest on top.

DELETE FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" NOT LIKE 'COM_CPANEL_MSG_UTD%';

INSERT INTO "#__postinstall_messages" ("extension_id", "title_key", "description_key", "action_key", "language_extension", "language_client_id", "type", "action_file", "action", "condition_file", "condition_method", "version_introduced", "enabled")
SELECT 700, 'COM_CPANEL_MSG_UTD_3_11_TITLE', 'COM_CPANEL_MSG_UTD_3_11_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.11.0', 1
WHERE NOT EXISTS (
	SELECT 1 FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" = 'COM_CPANEL_MSG_UTD_3_11_TITLE'
);

INSERT INTO "#__postinstall_messages" ("extension_id", "title_key", "description_key", "action_key", "language_extension", "language_client_id", "type", "action_file", "action", "condition_file", "condition_method", "version_introduced", "enabled")
SELECT 700, 'COM_CPANEL_MSG_UTD_3_12_TITLE', 'COM_CPANEL_MSG_UTD_3_12_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.12.0', 1
WHERE NOT EXISTS (
	SELECT 1 FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" = 'COM_CPANEL_MSG_UTD_3_12_TITLE'
);

INSERT INTO "#__postinstall_messages" ("extension_id", "title_key", "description_key", "action_key", "language_extension", "language_client_id", "type", "action_file", "action", "condition_file", "condition_method", "version_introduced", "enabled")
SELECT 700, 'COM_CPANEL_MSG_UTD_3_13_TITLE', 'COM_CPANEL_MSG_UTD_3_13_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.13.0', 1
WHERE NOT EXISTS (
	SELECT 1 FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" = 'COM_CPANEL_MSG_UTD_3_13_TITLE'
);

INSERT INTO "#__postinstall_messages" ("extension_id", "title_key", "description_key", "action_key", "language_extension", "language_client_id", "type", "action_file", "action", "condition_file", "condition_method", "version_introduced", "enabled")
SELECT 700, 'COM_CPANEL_MSG_UTD_3_14_TITLE', 'COM_CPANEL_MSG_UTD_3_14_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.14.0', 1
WHERE NOT EXISTS (
	SELECT 1 FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" = 'COM_CPANEL_MSG_UTD_3_14_TITLE'
);

INSERT INTO "#__postinstall_messages" ("extension_id", "title_key", "description_key", "action_key", "language_extension", "language_client_id", "type", "action_file", "action", "condition_file", "condition_method", "version_introduced", "enabled")
SELECT 700, 'COM_CPANEL_MSG_UTD_3_15_TITLE', 'COM_CPANEL_MSG_UTD_3_15_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.15.0', 1
WHERE NOT EXISTS (
	SELECT 1 FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" = 'COM_CPANEL_MSG_UTD_3_15_TITLE'
);

INSERT INTO "#__postinstall_messages" ("extension_id", "title_key", "description_key", "action_key", "language_extension", "language_client_id", "type", "action_file", "action", "condition_file", "condition_method", "version_introduced", "enabled")
SELECT 700, 'COM_CPANEL_MSG_UTD_3_16_TITLE', 'COM_CPANEL_MSG_UTD_3_16_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.16.0', 1
WHERE NOT EXISTS (
	SELECT 1 FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" = 'COM_CPANEL_MSG_UTD_3_16_TITLE'
);

INSERT INTO "#__postinstall_messages" ("extension_id", "title_key", "description_key", "action_key", "language_extension", "language_client_id", "type", "action_file", "action", "condition_file", "condition_method", "version_introduced", "enabled")
SELECT 700, 'COM_CPANEL_MSG_UTD_3_17_TITLE', 'COM_CPANEL_MSG_UTD_3_17_BODY', '', 'com_cpanel', 1, 'message', '', '', '', '', '3.17.0', 1
WHERE NOT EXISTS (
	SELECT 1 FROM "#__postinstall_messages" WHERE "extension_id" = 700 AND "title_key" = 'COM_CPANEL_MSG_UTD_3_17_TITLE'
);
