<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * Base class for the commands of the Joomla command line interface (cli/joomla.php).
 *
 * A command declares its name, description, arguments and options in configure() and does its work in
 * doExecute(), talking to the user only through the given CommandIO, so it works in text and JSON mode alike.
 *
 * @since  3.17.0
 */
abstract class AbstractCommand
{
	/**
	 * Argument modes
	 */
	const ARGUMENT_REQUIRED = 1;
	const ARGUMENT_OPTIONAL = 2;
	const ARGUMENT_ARRAY    = 4;

	/**
	 * Option modes: a flag, an option which needs a value, or an option whose value may be omitted
	 */
	const OPTION_NONE     = 1;
	const OPTION_REQUIRED = 2;
	const OPTION_OPTIONAL = 4;

	/**
	 * Exit codes
	 */
	const SUCCESS = 0;
	const FAILURE = 1;
	const INVALID = 2;

	/**
	 * The command name, e.g. "user:list"
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = '';

	/**
	 * One line description, shown by "list"
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = '';

	/**
	 * Longer help text, shown by "help <command>"
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = '';

	/**
	 * Run as a Super User (see ConsoleApplication::dispatch())
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = false;

	/**
	 * @var    array
	 * @since  3.17.0
	 */
	private $arguments = array();

	/**
	 * @var    array
	 * @since  3.17.0
	 */
	private $options = array();

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $configured = false;

	/**
	 * Declare the arguments and options of the command.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
	}

	/**
	 * Do the work of the command.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	abstract protected function doExecute(CommandIO $io);

	/**
	 * Run the command.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	public function run(CommandIO $io)
	{
		return (int) $this->doExecute($io);
	}

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getHelp()
	{
		return $this->help;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function runsAsSuperUser()
	{
		return $this->superUser;
	}

	/**
	 * @return  array  name => array(mode, description, default)
	 *
	 * @since   3.17.0
	 */
	public function getArguments()
	{
		$this->ensureConfigured();

		return $this->arguments;
	}

	/**
	 * @return  array  name => array(shortcut, mode, description, default)
	 *
	 * @since   3.17.0
	 */
	public function getOptions()
	{
		$this->ensureConfigured();

		return $this->options;
	}

	/**
	 * @param   string  $name         The argument name
	 * @param   int     $mode         ARGUMENT_REQUIRED or ARGUMENT_OPTIONAL, optionally combined with ARGUMENT_ARRAY
	 * @param   string  $description  The description
	 * @param   mixed   $default      The default value for an optional argument
	 *
	 * @return  $this
	 *
	 * @since   3.17.0
	 */
	protected function addArgument($name, $mode, $description, $default = null)
	{
		$this->arguments[$name] = array($mode, $description, $mode & self::ARGUMENT_ARRAY ? (array) $default : $default);

		return $this;
	}

	/**
	 * @param   string       $name         The long option name, without the leading "--"
	 * @param   string|null  $shortcut     A one letter shortcut, without the leading "-"
	 * @param   int          $mode         OPTION_NONE, OPTION_REQUIRED or OPTION_OPTIONAL
	 * @param   string       $description  The description
	 * @param   mixed        $default      The value when the option isn't given
	 *
	 * @return  $this
	 *
	 * @since   3.17.0
	 */
	protected function addOption($name, $shortcut, $mode, $description, $default = null)
	{
		$this->options[$name] = array($shortcut, $mode, $description, $mode === self::OPTION_NONE ? false : $default);

		return $this;
	}

	/**
	 * Get a model of an administrator component, with the component's language loaded.
	 *
	 * @param   string  $component  The component, e.g. "com_installer"
	 * @param   string  $name       The model name
	 * @param   string  $prefix     The model class prefix
	 *
	 * @return  \JModelLegacy
	 *
	 * @since   3.17.0
	 */
	protected function getAdministratorModel($component, $name, $prefix)
	{
		$base = JPATH_ADMINISTRATOR . '/components/' . $component;

		\JFactory::getLanguage()->load($component, JPATH_ADMINISTRATOR);
		\JModelLegacy::addIncludePath($base . '/models', $prefix);
		\JTable::addIncludePath($base . '/tables');

		$model = \JModelLegacy::getInstance($name, $prefix, array('ignore_request' => true));

		if (!$model)
		{
			throw new \RuntimeException(sprintf('Cannot load the %s model of %s.', $name, $component));
		}

		return $model;
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function ensureConfigured()
	{
		if (!$this->configured)
		{
			$this->configured = true;
			$this->configure();
		}
	}
}
