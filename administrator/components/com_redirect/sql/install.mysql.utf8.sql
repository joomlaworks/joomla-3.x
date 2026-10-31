-- com_redirect: its tables, created only where they don't exist yet (a restore may find them kept)

CREATE TABLE IF NOT EXISTS `#__redirect_links` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `old_url` varchar(2048) NOT NULL,
  `old_url_hash` char(32) NOT NULL DEFAULT '',
  `new_url` varchar(2048),
  `referer` varchar(2048) NOT NULL,
  `comment` varchar(255) NOT NULL DEFAULT '',
  `hits` int unsigned NOT NULL DEFAULT 0,
  `published` tinyint NOT NULL,
  `created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `header` smallint NOT NULL DEFAULT 301,
  PRIMARY KEY (`id`),
  KEY `idx_old_url` (`old_url`(100)),
  KEY `idx_old_url_hash` (`old_url_hash`),
  KEY `idx_link_modifed` (`modified_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

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
