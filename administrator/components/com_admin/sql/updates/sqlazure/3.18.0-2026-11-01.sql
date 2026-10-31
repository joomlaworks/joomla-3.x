-- Joomla 3.x UTD 3.18.0 migration — November 1, 2026
-- SQL Server only: defaults MySQL and PostgreSQL have. Without them sending a private message failed ("Cannot insert the value
-- NULL into column 'priority'") and privacy consents were never recorded. Skipped where the component was uninstalled.

ALTER TABLE [#__messages] ADD DEFAULT (0) FOR [priority];
ALTER TABLE [#__privacy_consents] ADD DEFAULT (0) FOR [remind];
