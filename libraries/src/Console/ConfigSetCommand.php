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
 * Changes global configuration options, through the same model as System > Global Configuration.
 *
 * @since  3.17.0
 */
class ConfigSetCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'config:set';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Set a value for a configuration option';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Sets one or more options of configuration.php, given as option=value (e.g. "config:set caching=1 cachetime=30"). '
		. '"true" and "false" are saved as booleans. The changes go through the checks of System > Global Configuration: the database '
		. 'settings must connect, and Force HTTPS is only turned on if the site answers over HTTPS (give --live-site so the right host is checked).';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('options', self::ARGUMENT_REQUIRED | self::ARGUMENT_ARRAY, 'The options to set, as option=value');
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
		$current = get_object_vars(new \JConfig);
		$changes = array();

		foreach ((array) $io->getArgument('options') as $pair)
		{
			if (strpos($pair, '=') === false)
			{
				$io->error('Options and values should be separated by "=", e.g. sitename="My site".');

				return self::INVALID;
			}

			list($option, $value) = explode('=', $pair, 2);

			if (!array_key_exists($option, $current))
			{
				$io->error(sprintf('Can\'t find option "%s" in the configuration.', $option));

				return self::INVALID;
			}

			if ($value === 'true' || $value === 'false')
			{
				$value = $value === 'true';
			}

			$changes[$option] = $value;
		}

		$io->title('Set Configuration');

		if (!$this->saveConfiguration($io, $changes))
		{
			return self::FAILURE;
		}

		$io->success('Configuration set.');

		return self::SUCCESS;
	}

	/**
	 * Save configuration options with the Global Configuration model.
	 *
	 * @param   CommandIO  $io       The output
	 * @param   array      $changes  Option => value
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected function saveConfiguration(CommandIO $io, array $changes)
	{
		Factory::getLanguage()->load('com_config', JPATH_ADMINISTRATOR);
		\JLoader::registerPrefix('Config', JPATH_ADMINISTRATOR . '/components/com_config');
		\JLoader::registerPrefix('Config', JPATH_ROOT . '/components/com_config');

		// The model expects every option, as the Global Configuration form sends them
		$model = new \ConfigModelApplication;

		if (!$model->save(array_merge(get_object_vars(new \JConfig), $changes)))
		{
			$io->error('The configuration was not saved.');

			return false;
		}

		foreach ($changes as $option => $value)
		{
			Factory::getConfig()->set($option, $value);
		}

		return true;
	}
}
