<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;

/**
 * Shows the value of one, a group or all of the global configuration options.
 *
 * @since  3.17.0
 */
class ConfigGetCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'config:get';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Display the current value of a configuration option';

	/**
	 * Option groups, as in Joomla 4 and later
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	const GROUPS = array(
		'db'      => array(
			'dbtype', 'host', 'user', 'password', 'dbprefix', 'db', 'dbencryption', 'dbsslverifyservercert', 'dbsslkey', 'dbsslcert',
			'dbsslca', 'dbsslcipher',
		),
		'mail'    => array(
			'mailonline', 'mailer', 'mailfrom', 'fromname', 'sendmail', 'smtpauth', 'smtpuser', 'smtppass', 'smtphost', 'smtpsecure',
			'smtpport',
		),
		'session' => array('session_handler', 'shared_session', 'session_metadata'),
	);

	/**
	 * Options holding passwords and keys, masked unless --show-secrets is given
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	const SECRETS = array(
		'password', 'secret', 'smtppass', 'ftp_pass', 'proxy_pass', 'redis_server_auth', 'session_redis_server_auth', 'dbsslkey',
	);

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->help = 'Shows the value of a configuration option, of a group of options (--group), or of all options. '
			. 'The option name can hold the wildcards * and ?, e.g. "memcached_*" or "*pass*"; quote it, so the shell doesn\'t expand it. '
			. 'Available groups: ' . implode(', ', array_keys(self::GROUPS)) . '. '
			. 'Passwords and keys are masked unless --show-secrets is given.';

		$this->addArgument('option', self::ARGUMENT_OPTIONAL, 'Name of the option, or a pattern with * and ?');
		$this->addOption('group', 'g', self::OPTION_REQUIRED, 'Name of the option group');
		$this->addOption('show-secrets', null, self::OPTION_NONE, 'Show passwords and keys instead of masking them');
	}

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$configs = Factory::getConfig()->toArray();
		$option  = (string) $io->getArgument('option');
		$group   = (string) $io->getOption('group');
		$secrets = (bool) $io->getOption('show-secrets');

		if ($option !== '' && $group !== '')
		{
			$io->error('Give either an option name or --group, not both.');

			return self::INVALID;
		}

		if ($group !== '')
		{
			if (!isset(self::GROUPS[$group]))
			{
				$io->error(sprintf('Group "%s" not found. Available groups: %s', $group, implode(', ', array_keys(self::GROUPS))));

				return self::FAILURE;
			}

			$names = self::GROUPS[$group];
		}
		elseif (static::hasWildcards($option))
		{
			$names = array();

			foreach (array_keys($configs) as $name)
			{
				if (static::matchesPattern($option, $name))
				{
					$names[] = $name;
				}
			}

			if (!$names)
			{
				$io->error(sprintf('No configuration options match "%s".', $option));

				return self::FAILURE;
			}
		}
		elseif ($option !== '')
		{
			if (!array_key_exists($option, $configs))
			{
				$io->error(sprintf('Can\'t find option "%s" in the configuration.', $option));

				return self::FAILURE;
			}

			$names = array($option);
		}
		else
		{
			$names = array_keys($configs);
		}

		$rows = array();

		foreach ($names as $name)
		{
			$value = array_key_exists($name, $configs) ? $configs[$name] : null;

			if (!$secrets && in_array($name, self::SECRETS, true) && $value !== null && $value !== '')
			{
				$value = '********';
			}

			$rows[] = array('option' => $name, 'value' => $value);
		}

		$io->table(array('option' => 'Option', 'value' => 'Value'), $rows, 'options');

		return self::SUCCESS;
	}
}
