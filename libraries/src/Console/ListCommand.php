<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

/**
 * Lists the available commands. In JSON mode it includes every command's full definition.
 *
 * @since  3.17.0
 */
class ListCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List the available commands';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists all commands, or only those of a namespace (e.g. "user" for user:*). With --format=json, each command includes its arguments and options, so the whole interface can be discovered in one call.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('namespace', self::ARGUMENT_OPTIONAL, 'Only list the commands of this namespace');
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
		return static::listCommands($io, (string) $io->getArgument('namespace'));
	}

	/**
	 * Show the commands, or those of a namespace; "help <namespace>" uses it too.
	 *
	 * @param   CommandIO  $io         The output
	 * @param   string     $namespace  The namespace, e.g. "user", or '' for all
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	public static function listCommands(CommandIO $io, $namespace)
	{
		/** @var ConsoleApplication $app */
		$app      = Factory::getApplication();
		$commands = array();

		foreach ($app->getCommands() as $name => $command)
		{
			if ($command->isHidden() || ($namespace !== '' && strpos($name, $namespace . ':') !== 0))
			{
				continue;
			}

			$commands[$name] = $command;
		}

		if ($namespace !== '' && !$commands)
		{
			$io->error(sprintf('There are no commands defined in the "%s" namespace.', $namespace));

			return self::INVALID;
		}

		if ($io->isJson())
		{
			$io->setData('application', 'Joomla! ' . (new Version)->getShortVersion());
			$io->setData('commands', array_values(array_map(array('Joomla\\CMS\\Console\\HelpCommand', 'describe'), $commands)));
			$io->setData('globalOptions', HelpCommand::describeGlobalOptions());

			return self::SUCCESS;
		}

		$io->writeln('Joomla! ' . (new Version)->getShortVersion() . ' command line interface');
		$io->writeln();
		$io->writeln('Usage: php cli/joomla.php <command> [options] [arguments]');
		$io->writeln();
		$io->writeln('Name filters, e.g. of config:get, user:list and extension:list, accept the wildcards * (any characters) and ? (one');
		$io->writeln('character), as in config:get \'memcached_*\'. Quote them, so the shell doesn\'t expand them.');
		$io->writeln();
		$io->writeln('Global options:');

		foreach (ConsoleApplication::getGlobalOptions() as $name => $global)
		{
			$io->writeln(sprintf('  %-26s %s', ($global[0] ? '-' . $global[0] . ', ' : '    ') . '--' . $name . ($global[1] ? '=VALUE' : ''), $global[2]));
		}

		$io->writeln();
		$io->writeln('Available commands:');
		$width   = max(array_map('strlen', array_keys($commands)));
		$current = null;

		foreach ($commands as $name => $command)
		{
			$group = strpos($name, ':') === false ? '' : substr($name, 0, strpos($name, ':'));

			if ($group !== $current && $group !== '')
			{
				$io->writeln(' ' . $group);
			}

			$current = $group;
			$io->writeln(sprintf('  %-' . $width . 's  %s', $name, $command->getDescription()));
		}

		$io->writeln();
		$io->writeln('Run "php cli/joomla.php help <command>" for the details of a command, or "help <group>" (e.g. "help user") for the commands of a group.');

		if ($namespace === '')
		{
			$io->writeln();
			$io->writeln('Examples:');

			foreach (array(
				'Enable progressive caching'                                               => array('config:set caching=2'),
				'Update Joomla from a local copy of Joomla 3.x UTD, restoring uninstalled core extensions'
					=> array('core:update --file=/path/to/joomla-3.x-main.zip --restore-core'),
				'Export the database to a ZIP file with a custom name'                     => array('database:export --folder=/path/to/backups --zip=mysite-backup.zip'),
				'Check for extension updates, then update a single extension by its ID'   => array('update:extensions:check', 'extension:update 10188'),
				'Find a user, then block them'                                             => array('user:list \'*smith*\'', 'user:block --username=jsmith'),
			) as $label => $lines)
			{
				$io->writeln('  ' . $label . ':');

				foreach ($lines as $line)
				{
					$io->writeln('    php cli/joomla.php ' . $line);
				}
			}
		}

		return self::SUCCESS;
	}
}
