-- Joomla 3.17.0 migration — October 7, 2026
-- Adds the Hammond news template (not as the default: existing sites keep theirs).

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'hammond', 'template', 'hammond', '', 0, 1, 1, 0, '', '{"siteName":"","tagline":"","footerText":"","pagesMenu":"companymenu","social_facebook":"#","social_x":"#","social_instagram":"#","social_youtube":"#","social_linkedin":"#","social_rss":""}', '', '', 0, '1970-01-01 00:00:00', 0, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `type` = 'template' AND `element` = 'hammond' AND `client_id` = 0
);

INSERT INTO `#__template_styles` (`template`, `client_id`, `home`, `title`, `params`)
SELECT 'hammond', 0, '0', 'Hammond - Default', '{"siteName":"","tagline":"","footerText":"","pagesMenu":"companymenu","social_facebook":"#","social_x":"#","social_instagram":"#","social_youtube":"#","social_linkedin":"#","social_rss":""}'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__template_styles` WHERE `template` = 'hammond' AND `client_id` = 0
);
