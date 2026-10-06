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
	 * Exit codes: success, the action failed, invalid input, what was asked for doesn't exist, and refused (e.g. the last
	 * Super User, or --dry-run on a command which can't do one). In JSON, error.code names them (see CommandIO::finish()).
	 */
	const SUCCESS   = 0;
	const FAILURE   = 1;
	const INVALID   = 2;
	const NOT_FOUND = 3;
	const REFUSED   = 4;

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
	 * Leave the command out of "list", e.g. an internal step of another command
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $hidden = false;

	/**
	 * Run as a Super User (see ConsoleApplication::dispatch())
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = false;

	/**
	 * The command only reads: it changes neither the site nor its files (the MCP server offers only these by default)
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = false;

	/**
	 * The command supports --dry-run: it then reports what it would change (CommandIO::plan()) and changes nothing
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = false;

	/**
	 * Successful runs which may change something are recorded in the User Actions Log; routine housekeeping run by cron jobs
	 * (cleaning the cache or sessions, indexing) isn't, so it doesn't fill the log
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $logged = true;

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
	public function isHidden()
	{
		return $this->hidden;
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
	 * Whether the command only reads, given a run's values when known (e.g. maintenance:database only changes with --fix).
	 *
	 * @param   array|null  $options    The options of a run, or null for the command in general
	 * @param   array|null  $arguments  The arguments of a run
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isReadOnly(?array $options = null, ?array $arguments = null)
	{
		return $this->readOnly;
	}

	/**
	 * Whether some runs of the command only read (offered by a read-only MCP server, which checks each call's options).
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function hasReadOnlyRuns()
	{
		return $this->readOnly;
	}

	/**
	 * Whether a run writes code the server runs or reads as configuration (e.g. a template's PHP files), given its values:
	 * the MCP server only allows such runs when started with --allow-template-code.
	 *
	 * @param   array|null  $options    The options of a run
	 * @param   array|null  $arguments  The arguments of a run
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function writesCode(?array $options = null, ?array $arguments = null)
	{
		return false;
	}

	/**
	 * Whether --dry-run is safe: the command supports it, or never changes anything.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function supportsDryRun()
	{
		return $this->readOnly || $this->dryRun;
	}

	/**
	 * Whether successful runs are recorded in the User Actions Log.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isLogged()
	{
		return $this->logged;
	}

	/**
	 * Whether the value of an option or argument is a secret, kept out of the User Actions Log.
	 *
	 * @param   string  $name  The option or argument name
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isSecret($name)
	{
		return (bool) preg_match('/pass(word)?|secret|token|api_?key/i', (string) $name);
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
	 * Clean a cache group of the site and the administrator, e.g. "_system", which holds the component and plugin settings.
	 *
	 * @param   string  $group  The cache group
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function cleanCacheGroup($group)
	{
		$config = \JFactory::getConfig();

		foreach (array($config->get('cache_path', JPATH_SITE . '/cache'), JPATH_ADMINISTRATOR . '/cache') as $cachebase)
		{
			\JCache::getInstance('callback', array('defaultgroup' => $group, 'cachebase' => $cachebase))->clean();
		}
	}

	/**
	 * Whether a name filter holds the wildcards * (any characters) or ? (one character).
	 *
	 * @param   string  $pattern  The name filter
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function hasWildcards($pattern)
	{
		return strpbrk((string) $pattern, '*?') !== false;
	}

	/**
	 * Whether any of the values matches a name filter: with wildcards (* and ?) as a pattern, otherwise exactly; both ignore case.
	 * Commands which list things use it for their name filters, so they all behave alike.
	 *
	 * @param   string        $pattern  The name filter
	 * @param   string|array  $values   The value or values to check, e.g. a user's username, name and email
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function matchesPattern($pattern, $values)
	{
		$pattern = (string) $pattern;
		$regex   = static::hasWildcards($pattern)
			? '/^' . str_replace(array('\\*', '\\?'), array('.*', '.'), preg_quote($pattern, '/')) . '$/isu'
			: null;

		foreach ((array) $values as $value)
		{
			$value = (string) $value;

			if ($regex ? preg_match($regex, $value) === 1 : strcasecmp($pattern, $value) === 0)
			{
				return true;
			}
		}

		return false;
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
