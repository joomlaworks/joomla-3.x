<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/*
 * PHPMailer 6+ is namespaced. Its classes keep the global names they had in PHPMailer 5.2 (Joomla 3's until 3.18), which
 * Joomla's Mail class and extensions use: Composer's class map points every one of these names here, so whichever is
 * needed first defines them all. A catch (phpmailerException ...) then works once any of them, or JMail, is loaded.
 */
foreach (array(
	'PHPMailer'          => 'PHPMailer\\PHPMailer\\PHPMailer',
	'phpmailerException' => 'PHPMailer\\PHPMailer\\Exception',
	'SMTP'               => 'PHPMailer\\PHPMailer\\SMTP',
	'POP3'               => 'PHPMailer\\PHPMailer\\POP3',
) as $alias => $class)
{
	if (!class_exists($alias, false))
	{
		class_alias($class, $alias);
	}
}
