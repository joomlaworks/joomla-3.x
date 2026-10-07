-- Joomla 3.17.0 migration — October 11, 2026
-- Adds the Rookwood studio template (not as the default: existing sites keep theirs).

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`)
SELECT 0, 'rookwood', 'template', 'rookwood', '', 0, 1, 1, 0, '', '{"siteName":"","defaultTheme":"dark","footerHeadline":"","footerEmail":"","footerText":"","social_x":"","social_linkedin":"","social_instagram":"","social_github":"","social_dribbble":"","social_rss":""}', '', '', 0, '1970-01-01 00:00:00', 0, 0
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__extensions` WHERE `type` = 'template' AND `element` = 'rookwood' AND `client_id` = 0
);

INSERT INTO `#__template_styles` (`template`, `client_id`, `home`, `title`, `params`)
SELECT 'rookwood', 0, '0', 'Rookwood - Default', '{"siteName":"","defaultTheme":"dark","footerHeadline":"","footerEmail":"","footerText":"","social_x":"","social_linkedin":"","social_instagram":"","social_github":"","social_dribbble":"","social_rss":""}'
FROM DUAL
WHERE NOT EXISTS (
	SELECT 1 FROM `#__template_styles` WHERE `template` = 'rookwood' AND `client_id` = 0
);
