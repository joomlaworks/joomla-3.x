-- Joomla 3.x UTD 3.18.0 migration — October 31, 2026
-- Redirects: a hash of the normalised source URL for indexed lookups, and a table of their own for the errors the
-- plugin collects. The update script's "redirect-scheme" step fills the hashes and moves the data.

ALTER TABLE `#__redirect_links` ADD COLUMN `old_url_hash` char(32) NOT NULL DEFAULT '' AFTER `old_url`;
ALTER TABLE `#__redirect_links` ADD INDEX `idx_old_url_hash` (`old_url_hash`);

CREATE TABLE IF NOT EXISTS `#__redirect_errors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(2048) NOT NULL,
  `url_hash` char(32) NOT NULL DEFAULT '',
  `code` smallint NOT NULL DEFAULT 404,
  `referer` varchar(2048) NOT NULL DEFAULT '',
  `hits` int unsigned NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_hit` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_url_hash` (`url_hash`),
  KEY `idx_code` (`code`),
  KEY `idx_last_hit` (`last_hit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
