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

/**
 * Shows the help of a command.
 *
 * @since  3.17.0
 */
class HelpCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'help';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show the help of a command';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('command_name', self::ARGUMENT_OPTIONAL, 'The command name', 'help');
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
		/** @var ConsoleApplication $app */
		$app     = Factory::getApplication();
		$name    = (string) $io->getArgument('command_name');
		$command = $app->getCommand($name);

		// A group of commands, e.g. "user" for user:*
		if (!$command && $app->hasNamespace($name))
		{
			return ListCommand::listCommands($io, $name);
		}

		if (!$command)
		{
			$io->error(sprintf('Command "%s" is not defined.', $name));

			return self::INVALID;
		}

		if ($io->isJson())
		{
			$io->setData('command', static::describe($command));
			$io->setData('globalOptions', static::describeGlobalOptions());

			return self::SUCCESS;
		}

		$io->writeln('Description:');
		$io->writeln('  ' . $command->getDescription());
		$io->writeln();
		$io->writeln('Usage:');
		$io->writeln('  ' . $app->getUsage($command));

		if ($command->getArguments())
		{
			$io->writeln();
			$io->writeln('Arguments:');

			foreach ($command->getArguments() as $argument => $definition)
			{
				$default = $definition[2] !== null && $definition[2] !== array() ? ' [default: ' . json_encode($definition[2]) . ']' : '';
				$io->writeln(sprintf('  %-24s %s%s', $argument, $definition[1], $default));
			}
		}

		if ($command->getOptions())
		{
			$io->writeln();
			$io->writeln('Options:');

			foreach ($command->getOptions() as $option => $definition)
			{
				$label   = ($definition[0] ? '-' . $definition[0] . ', ' : '    ') . '--' . $option;
				$label  .= $definition[1] === self::OPTION_REQUIRED ? '=VALUE' : ($definition[1] === self::OPTION_OPTIONAL ? '[=VALUE]' : '');
				$default = $definition[1] !== self::OPTION_NONE && $definition[3] !== null ? ' [default: ' . json_encode($definition[3]) . ']' : '';
				$io->writeln(sprintf('  %-26s %s%s', $label, $definition[2], $default));
			}
		}

		$io->writeln();
		$io->writeln('Global options: --help (-h), --quiet (-q), --no-interaction (-n), --format=json, --no-ansi, --verbose (-v), --live-site=URL');

		if ($command->getHelp() !== '')
		{
			$io->writeln();
			$io->writeln('Help:');
			$io->writeln('  ' . wordwrap($command->getHelp(), 100, "\n  "));
		}

		return self::SUCCESS;
	}

	/**
	 * A machine-readable description of a command.
	 *
	 * @param   AbstractCommand  $command  The command
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	public static function describe(AbstractCommand $command)
	{
		$arguments = array();

		foreach ($command->getArguments() as $name => $definition)
		{
			$arguments[] = array(
				'name'        => $name,
				'required'    => (bool) ($definition[0] & self::ARGUMENT_REQUIRED),
				'isArray'     => (bool) ($definition[0] & self::ARGUMENT_ARRAY),
				'description' => $definition[1],
				'default'     => $definition[2],
			);
		}

		$options = array();
		$modes   = array(self::OPTION_NONE => 'flag', self::OPTION_REQUIRED => 'value', self::OPTION_OPTIONAL => 'optional value');

		foreach ($command->getOptions() as $name => $definition)
		{
			$options[] = array(
				'name'        => $name,
				'shortcut'    => $definition[0],
				'accepts'     => $modes[$definition[1]],
				'description' => $definition[2],
				'default'     => $definition[3],
			);
		}

		return array(
			'name'        => $command->getName(),
			'description' => $command->getDescription(),
			'help'        => $command->getHelp(),
			'arguments'   => $arguments,
			'options'     => $options,
		);
	}

	/**
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	public static function describeGlobalOptions()
	{
		$options = array();

		foreach (ConsoleApplication::getGlobalOptions() as $name => $global)
		{
			$options[] = array('name' => $name, 'shortcut' => $global[0], 'accepts' => $global[1] ? 'value' : 'flag', 'description' => $global[2]);
		}

		return $options;
	}
}
