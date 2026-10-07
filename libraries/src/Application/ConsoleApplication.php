<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Application;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Console\AbstractCommand;
use Joomla\CMS\Console\CommandIO;
use Joomla\CMS\Console\ConsoleSessionHandler;
use Joomla\CMS\Console\ConsoleUser;
use Joomla\CMS\Factory;
use Joomla\CMS\Input\Cli;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

/**
 * The Joomla command line interface (cli/joomla.php), modelled on the console of Joomla 4 and later:
 * same command names, arguments and options where the feature exists in 3.x, plus "--format=json" output.
 *
 * Commands come from getDefaultCommands() and from plugins in the "console" group, which can return
 * AbstractCommand instances from an "onGetConsoleCommands" event handler.
 *
 * The application also stands in for the CMS application while a command runs (Factory::getApplication()
 * returns it), with the methods core models and libraries commonly call on it.
 *
 * @since  3.17.0
 */
class ConsoleApplication extends CliApplication
{
	/**
	 * Global options, available to every command: name => array(shortcut, needs a value, description)
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	protected static $globalOptions = array(
		'help'           => array('h', false, 'Show the help of the command'),
		'quiet'          => array('q', false, 'Only show errors'),
		'no-interaction' => array('n', false, 'Never ask questions; use the defaults'),
		'format'         => array(null, true, 'The output format: text (default) or json'),
		'no-ansi'        => array(null, false, 'Disable coloured output'),
		'verbose'        => array('v', false, 'Show the trace of unexpected errors'),
		'live-site'      => array(null, true, 'The site URL, for commands which build URLs (e.g. https://www.example.com)'),
		'dry-run'        => array(null, false, 'Show what the command would change, without changing anything (refused by commands which can\'t)'),
	);

	/**
	 * @var    AbstractCommand[]|null
	 * @since  3.17.0
	 */
	protected $commands;

	/**
	 * @var    Registry
	 * @since  3.17.0
	 */
	protected $userState;

	/**
	 * @var    array
	 * @since  3.17.0
	 */
	protected $messageQueue = array();

	/**
	 * The IO of the running command, if any
	 *
	 * @var    CommandIO|null
	 * @since  3.17.0
	 */
	protected $io;

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $exitCode = 0;

	/**
	 * How commands are run, for the User Actions Log: "Command line", or e.g. "MCP" for the MCP server (see setInterface())
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	protected $interface = 'Command line';

	/**
	 * How many commands are running (commands run others, e.g. database:convert runs maintenance:database)
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $depth = 0;

	/**
	 * The depth of the commands recorded in the User Actions Log: the one which was asked for, not those it runs itself
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $loggedDepth = 1;

	/**
	 * Constructor.
	 *
	 * @param   Cli|null                $input       An optional argument to provide dependency injection for the application's input object
	 * @param   Registry|null           $config      An optional argument to provide dependency injection for the application's config object
	 * @param   \JEventDispatcher|null  $dispatcher  An optional argument to provide dependency injection for the application's event dispatcher
	 *
	 * @since   3.17.0
	 */
	public function __construct(?Cli $input = null, ?Registry $config = null, ?\JEventDispatcher $dispatcher = null)
	{
		parent::__construct($input, $config, $dispatcher);

		// Defined by the web entry points; core code such as Access uses it
		if (!defined('JDEBUG'))
		{
			define('JDEBUG', (bool) Factory::getConfig()->get('debug'));
		}

		// The command line needs no stored session, and must work while the session storage can't (e.g. during database:import)
		if (!Factory::$session)
		{
			$lifetime         = (int) Factory::getConfig()->get('lifetime');
			Factory::$session = new Session('none', array('expire' => $lifetime ? $lifetime * 60 : 900), new ConsoleSessionHandler);
		}

		$this->userState = new Registry;

		// Core code, e.g. the installer, reports problems through the "jerror" log; libraries/cms.php only shows them for web requests
		if (!array_key_exists('REQUEST_METHOD', $_SERVER))
		{
			Log::addLogger(array('logger' => 'messagequeue'), Log::ALL, array('jerror'));
		}

		// Core code reaches the application through the factory
		Factory::$application = $this;

		// Old code (e.g. the content plugin refusing to delete a category with items) reports through JError, as on the web
		\JError::setErrorHandling(E_NOTICE | E_WARNING | E_ERROR, 'message');

		if (method_exists($this, 'setLogger'))
		{
			$this->setLogger(Log::createDelegatedLogger());
		}

		$language = Factory::getLanguage();
		$language->load('lib_joomla', JPATH_ADMINISTRATOR) || $language->load('lib_joomla', JPATH_SITE);
	}

	/**
	 * Run the command given on the command line.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function doExecute()
	{
		$argv = isset($_SERVER['argv']) ? (array) $_SERVER['argv'] : array();
		array_shift($argv);

		$this->exitCode = $this->runFromArguments($argv);
	}

	/**
	 * @return  integer  The exit code of the last command
	 *
	 * @since   3.17.0
	 */
	public function getExitCode()
	{
		return $this->exitCode;
	}

	/**
	 * Run a command with already parsed values, e.g. from the legacy scripts in cli/.
	 *
	 * @param   string  $name       The command name
	 * @param   array   $arguments  Argument name => value
	 * @param   array   $options    Option name => value, including global options such as "quiet"
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	public function runCommand($name, array $arguments = array(), array $options = array())
	{
		$command = $this->getCommand($name);

		if (!$command)
		{
			$io = new CommandIO($name, array(), $options);
			$io->error(sprintf('Command "%s" is not defined.', $name));
			$io->finish(AbstractCommand::INVALID);

			return $this->exitCode = AbstractCommand::INVALID;
		}

		foreach ($command->getArguments() as $argument => $definition)
		{
			if (!array_key_exists($argument, $arguments))
			{
				$arguments[$argument] = $definition[2];
			}
		}

		foreach ($command->getOptions() as $option => $definition)
		{
			if (!array_key_exists($option, $options))
			{
				$options[$option] = $definition[3];
			}
		}

		return $this->exitCode = $this->dispatch($command, $arguments, $options);
	}

	/**
	 * Parse the command line arguments and run the command.
	 *
	 * @param   array  $tokens  The command line arguments, without the script name
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	public function runFromArguments(array $tokens)
	{
		// The command is the first token which is neither an option nor the value of a global option
		$name = null;
		$rest = array();

		for ($i = 0, $n = count($tokens); $i < $n; $i++)
		{
			$token = (string) $tokens[$i];

			if ($name === null && $token !== '' && $token[0] !== '-')
			{
				$name = $token;

				continue;
			}

			$rest[] = $token;

			if ($name === null && $this->globalOptionNeedsValue($token) && $i + 1 < $n)
			{
				$rest[] = (string) $tokens[++$i];
			}
		}

		$global = $this->parseGlobalOptions($rest);

		if ($name === null)
		{
			$name = 'list';
		}

		$command = $this->getCommand($name);

		// A group of commands, e.g. "user" for user:*
		if (!$command && $this->hasNamespace($name))
		{
			return $this->runCommand('list', array('namespace' => $name), $global);
		}

		if (!$command)
		{
			$options = $global;
			$io      = new CommandIO($name, array(), $options);
			$io->error(sprintf('Command "%s" is not defined. Run "php cli/joomla.php list" to see the available commands.', $name));
			$io->finish(AbstractCommand::INVALID);

			return AbstractCommand::INVALID;
		}

		if (!empty($global['help']) && $name !== 'help')
		{
			return $this->runCommand('help', array('command_name' => $name), $global);
		}

		try
		{
			list($arguments, $options) = $this->parse($command, $rest);
		}
		catch (\InvalidArgumentException $e)
		{
			$io = new CommandIO($name, array(), $global);
			$io->error($e->getMessage());
			$io->writeln();
			$io->writeln('Usage: ' . $this->getUsage($command));
			$io->finish(AbstractCommand::INVALID);

			return AbstractCommand::INVALID;
		}

		return $this->dispatch($command, $arguments, $options);
	}

	/**
	 * @param   AbstractCommand  $command    The command
	 * @param   array            $arguments  The argument values
	 * @param   array            $options    The option values
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	protected function dispatch(AbstractCommand $command, array $arguments, array $options)
	{
		if (!empty($options['live-site']) && is_string($options['live-site']))
		{
			$this->set('live_site', $options['live-site']);
			Factory::getConfig()->set('live_site', $options['live-site']);

			// Code building URLs from the request (e.g. \JUri::getInstance()) needs the host too
			$host = parse_url($options['live-site'], PHP_URL_HOST);

			if ($host && !isset($_SERVER['HTTP_HOST']))
			{
				$port                 = parse_url($options['live-site'], PHP_URL_PORT);
				$_SERVER['HTTP_HOST'] = $host . ($port ? ':' . $port : '');

				if (parse_url($options['live-site'], PHP_URL_SCHEME) === 'https')
				{
					$_SERVER['HTTPS'] = 'on';
				}
			}
		}

		$io       = new CommandIO($command->getName(), $arguments, $options);
		$previous = $this->io;
		$this->io = $io;
		$this->depth++;

		// A dry run must never change anything, so commands which can't do one refuse it rather than run for real
		if ($io->isDryRun() && !$command->supportsDryRun())
		{
			$io->error(sprintf('The %s command doesn\'t support --dry-run, so it wasn\'t run. Nothing was changed.', $command->getName()));
			$io->finish(AbstractCommand::REFUSED);
			$this->io = $previous;
			$this->depth--;

			return AbstractCommand::REFUSED;
		}

		/*
		 * Whoever runs the command line can already read and change configuration.php, so commands which change things act
		 * as a Super User, e.g. for User::save()'s Super User checks. The user is set for the command only, so it never
		 * outlives it in the session storage.
		 */
		$session = null;

		if ($command->runsAsSuperUser())
		{
			$session      = Factory::getSession();
			$previousUser = $session->get('user');
			$session->set('user', new ConsoleUser);
		}

		try
		{
			$exitCode = $command->run($io);
		}
		catch (\Throwable $e)
		{
			$io->error($e->getMessage());

			if (!empty($options['verbose']))
			{
				$io->error(get_class($e) . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
			}

			$exitCode = AbstractCommand::FAILURE;
		}
		finally
		{
			if ($session)
			{
				$session->set('user', $previousUser);
			}
		}

		if ($exitCode === AbstractCommand::SUCCESS && $io->isDryRun() && !$command->isReadOnly($options, $arguments))
		{
			$io->text('Dry run: nothing was changed.');
		}

		if ($exitCode === AbstractCommand::SUCCESS && !$io->isDryRun() && !$command->isReadOnly($options, $arguments) && $command->isLogged()
			&& $this->depth === $this->loggedDepth)
		{
			$this->logCommand($command, $arguments, $options);
		}

		$io->finish($exitCode);
		$this->io = $previous;
		$this->depth--;

		return $exitCode;
	}

	/**
	 * How commands are run: "Command line", or e.g. "MCP".
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getInterface()
	{
		return (string) $this->interface;
	}

	/**
	 * Set how commands are run, as shown in the User Actions Log (e.g. "MCP").
	 *
	 * @param   string  $interface  The name
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function setInterface($interface)
	{
		$this->interface = (string) $interface;

		// The commands a host such as the MCP server runs are the ones asked for
		$this->loggedDepth = $this->depth + 1;
	}

	/**
	 * Record a command which may have changed the site in the User Actions Log, with secret values left out, so changes made
	 * from the command line (e.g. by an AI assistant through the MCP server) can be traced.
	 *
	 * @param   AbstractCommand  $command    The command
	 * @param   array            $arguments  Its arguments
	 * @param   array            $options    Its options
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function logCommand(AbstractCommand $command, array $arguments, array $options)
	{
		$model = JPATH_ADMINISTRATOR . '/components/com_actionlogs/models/actionlog.php';

		// The database may not be there yet (e.g. a failed database:import) or the component uninstalled; the log is a record, not a condition
		try
		{
			if (!is_file($model) || !\JComponentHelper::isEnabled('com_actionlogs'))
			{
				return;
			}

			$definitions = $command->getOptions();
			$parts       = array($command->getName());

			foreach ($arguments as $name => $values)
			{
				foreach ((array) $values as $value)
				{
					if ($value !== null && $value !== '')
					{
						$parts[] = $this->describeValue($command, $name, $value);
					}
				}
			}

			foreach ($options as $name => $value)
			{
				if (!isset($definitions[$name]) || $value === $definitions[$name][3] || $value === null || $value === false)
				{
					continue;
				}

				$parts[] = '--' . $name . ($value === true ? '' : '=' . $this->describeValue($command, $name, $value));
			}

			\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_actionlogs/models', 'ActionlogsModel');

			// Commands run as the command line's own Super User (ConsoleUser, ID 0), which is no longer the session's user here
			$log = \JModelLegacy::getInstance('Actionlog', 'ActionlogsModel');

			$log->addLog(
				array(array('interface' => $this->interface, 'command' => implode(' ', $parts), 'userid' => 0, 'username' => 'cli')),
				'COM_ACTIONLOGS_CLI_COMMAND',
				'com_actionlogs.cli'
			);
		}
		catch (\Throwable $e)
		{
			// Not worth failing a command which has already done its work
		}
	}

	/**
	 * A value for the User Actions Log: secrets (e.g. --password, or config:set password=...) hidden, long values shortened.
	 *
	 * @param   AbstractCommand  $command  The command
	 * @param   string           $name     The option or argument name
	 * @param   mixed            $value    The value
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function describeValue(AbstractCommand $command, $name, $value)
	{
		if ($command->isSecret($name))
		{
			return '***';
		}

		// JSON values (e.g. --params) with secret keys inside
		$decoded = is_scalar($value) ? json_decode((string) $value, true) : $value;

		if (is_array($decoded))
		{
			$value = json_encode($this->maskSecrets($command, $decoded));
		}

		$value = is_scalar($value) ? (string) $value : json_encode($value);

		if (preg_match('/^([A-Za-z0-9_.\-]+)=/', $value, $match) && $command->isSecret($match[1]))
		{
			return $match[1] . '=***';
		}

		// Download keys and tokens in URLs (e.g. extension:reinstall --url=...?dlid=...)
		$value = preg_replace('/([?&](?:dlid|key|download_id|downloadid|token|password|pass|secret|api_?key)=)[^&#\s]+/i', '$1***', $value);

		// Passwords in URLs (https://user:password@host; up to the last @ before the path, as a password may hold one)
		$value = preg_replace('#(://[^/@\s:]*:)[^/\s]*@#', '$1***@', $value);

		if (function_exists('mb_strlen') ? mb_strlen($value) > 80 : strlen($value) > 80)
		{
			$value = (function_exists('mb_substr') ? mb_substr($value, 0, 77) : substr($value, 0, 77)) . '...';
		}

		return preg_match('/[\s"\'\\\\]/', $value) ? '"' . addcslashes($value, '"\\') . '"' : $value;
	}

	/**
	 * Mask the values of secret keys in data, e.g. the JSON of --params.
	 *
	 * @param   AbstractCommand  $command  The command, which tells which names are secret
	 * @param   array            $data     The data
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	protected function maskSecrets(AbstractCommand $command, array $data)
	{
		foreach ($data as $key => $item)
		{
			if (is_string($key) && $command->isSecret($key))
			{
				$data[$key] = '***';
			}
			elseif (is_array($item))
			{
				$data[$key] = $this->maskSecrets($command, $item);
			}
		}

		return $data;
	}

	/**
	 * @return  AbstractCommand[]  name => command, sorted by name
	 *
	 * @since   3.17.0
	 */
	public function getCommands()
	{
		if ($this->commands === null)
		{
			$this->commands = array();

			foreach ($this->getDefaultCommands() as $command)
			{
				$this->commands[$command->getName()] = $command;
			}

			// Plugins need the database, which may be empty or broken, e.g. before database:import restores it; a broken plugin mustn't take the core commands down with it
			try
			{
				// Not installed yet (core:install): there's no database
				if (!is_file(JPATH_CONFIGURATION . '/configuration.php'))
				{
					throw new \RuntimeException('Not installed');
				}

				PluginHelper::importPlugin('console');
				$results = (array) \JEventDispatcher::getInstance()->trigger('onGetConsoleCommands', array($this));
			}
			catch (\Throwable $e)
			{
				$results = array();
			}

			foreach ($results as $result)
			{
				foreach ((array) $result as $command)
				{
					if ($command instanceof AbstractCommand && $command->getName() !== '')
					{
						$this->commands[$command->getName()] = $command;
					}
				}
			}

			ksort($this->commands);
		}

		return $this->commands;
	}

	/**
	 * Whether there are commands in a namespace, e.g. "user" for user:*.
	 *
	 * @param   string  $namespace  The namespace
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function hasNamespace($namespace)
	{
		foreach ($this->getCommands() as $name => $command)
		{
			if (!$command->isHidden() && strpos($name, $namespace . ':') === 0)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param   string  $name  The command name
	 *
	 * @return  AbstractCommand|null
	 *
	 * @since   3.17.0
	 */
	public function getCommand($name)
	{
		$commands = $this->getCommands();

		return isset($commands[$name]) ? $commands[$name] : null;
	}

	/**
	 * @return  array  name => array(shortcut, needs a value, description)
	 *
	 * @since   3.17.0
	 */
	public static function getGlobalOptions()
	{
		return static::$globalOptions;
	}

	/**
	 * @param   AbstractCommand  $command  The command
	 *
	 * @return  string  A one line usage synopsis
	 *
	 * @since   3.17.0
	 */
	public function getUsage(AbstractCommand $command)
	{
		$usage = 'php cli/joomla.php ' . $command->getName();

		foreach ($command->getOptions() as $name => $definition)
		{
			$value  = $definition[1] === AbstractCommand::OPTION_REQUIRED ? '=' . strtoupper($name) : ($definition[1] === AbstractCommand::OPTION_OPTIONAL ? '[=' . strtoupper($name) . ']' : '');
			$usage .= ' [--' . $name . $value . ']';
		}

		foreach ($command->getArguments() as $name => $definition)
		{
			$text   = '<' . $name . '>' . ($definition[0] & AbstractCommand::ARGUMENT_ARRAY ? '...' : '');
			$usage .= ' ' . ($definition[0] & AbstractCommand::ARGUMENT_REQUIRED ? $text : '[' . $text . ']');
		}

		return $usage;
	}

	/**
	 * The core commands.
	 *
	 * @return  AbstractCommand[]
	 *
	 * @since   3.17.0
	 */
	protected function getDefaultCommands()
	{
		$commands = array();

		foreach (glob(JPATH_LIBRARIES . '/src/Console/*Command.php') as $file)
		{
			$class = '\\Joomla\\CMS\\Console\\' . basename($file, '.php');

			// One broken command file (e.g. from an interrupted update) mustn't take the other commands down
			try
			{
				if ($class !== '\\Joomla\\CMS\\Console\\AbstractCommand' && class_exists($class))
				{
					$reflection = new \ReflectionClass($class);

					if ($reflection->isInstantiable() && $reflection->isSubclassOf('\\Joomla\\CMS\\Console\\AbstractCommand'))
					{
						$commands[] = new $class;
					}
				}
			}
			catch (\Throwable $e)
			{
				fwrite(defined('STDERR') ? STDERR : STDOUT, sprintf('Skipping %s: %s', basename($file), $e->getMessage()) . PHP_EOL);
			}
		}

		return $commands;
	}

	/**
	 * Parse the tokens after the command name.
	 *
	 * @param   AbstractCommand  $command  The command
	 * @param   array            $tokens   The tokens
	 *
	 * @return  array  array(arguments, options)
	 *
	 * @since   3.17.0
	 * @throws  \InvalidArgumentException
	 */
	protected function parse(AbstractCommand $command, array $tokens)
	{
		// name => array(shortcut, mode, description, default)
		$definitions = $command->getOptions();

		foreach (static::$globalOptions as $name => $global)
		{
			if (!isset($definitions[$name]))
			{
				$definitions[$name] = array($global[0], $global[1] ? AbstractCommand::OPTION_REQUIRED : AbstractCommand::OPTION_NONE, $global[2], $global[1] ? null : false);
			}
		}

		$shortcuts = array();

		foreach ($definitions as $name => $definition)
		{
			if ($definition[0] !== null)
			{
				$shortcuts[$definition[0]] = $name;
			}
		}

		$options = array();

		foreach ($definitions as $name => $definition)
		{
			$options[$name] = $definition[3];
		}

		$positional  = array();
		$onlyOptions = false;

		for ($i = 0, $n = count($tokens); $i < $n; $i++)
		{
			$token = (string) $tokens[$i];

			if ($onlyOptions || $token === '' || $token[0] !== '-' || $token === '-')
			{
				$positional[] = $token;

				continue;
			}

			if ($token === '--')
			{
				$onlyOptions = true;

				continue;
			}

			if (substr($token, 0, 2) === '--')
			{
				$parts = explode('=', substr($token, 2), 2);
				$names = array($parts[0]);
				$value = isset($parts[1]) ? $parts[1] : null;
			}
			else
			{
				$letters = str_split(substr($token, 1));
				$names   = array();

				foreach ($letters as $letter)
				{
					if (!isset($shortcuts[$letter]))
					{
						throw new \InvalidArgumentException(sprintf('The "-%s" option does not exist.', $letter));
					}

					$names[] = $shortcuts[$letter];
				}

				$value = null;
			}

			foreach ($names as $name)
			{
				if (!isset($definitions[$name]))
				{
					throw new \InvalidArgumentException(sprintf('The "--%s" option does not exist.', $name));
				}

				$mode = $definitions[$name][1];

				if ($mode === AbstractCommand::OPTION_NONE)
				{
					if ($value !== null)
					{
						throw new \InvalidArgumentException(sprintf('The "--%s" option does not accept a value.', $name));
					}

					$options[$name] = true;

					continue;
				}

				if ($value === null && $mode === AbstractCommand::OPTION_REQUIRED)
				{
					if ($i + 1 >= $n || (strlen((string) $tokens[$i + 1]) > 1 && $tokens[$i + 1][0] === '-'))
					{
						throw new \InvalidArgumentException(sprintf('The "--%s" option requires a value.', $name));
					}

					$value = (string) $tokens[++$i];
				}

				$options[$name] = $value === null ? true : $value;
			}
		}

		$arguments = array();

		foreach ($command->getArguments() as $name => $definition)
		{
			if ($definition[0] & AbstractCommand::ARGUMENT_ARRAY)
			{
				if (!$positional && $definition[0] & AbstractCommand::ARGUMENT_REQUIRED)
				{
					throw new \InvalidArgumentException(sprintf('Not enough arguments (missing: "%s").', $name));
				}

				$arguments[$name] = $positional ? $positional : $definition[2];
				$positional       = array();

				continue;
			}

			if ($positional)
			{
				$arguments[$name] = array_shift($positional);

				continue;
			}

			if ($definition[0] & AbstractCommand::ARGUMENT_REQUIRED)
			{
				throw new \InvalidArgumentException(sprintf('Not enough arguments (missing: "%s").', $name));
			}

			$arguments[$name] = $definition[2];
		}

		if ($positional)
		{
			throw new \InvalidArgumentException(sprintf('Too many arguments ("%s").', implode('", "', $positional)));
		}

		if (isset($options['format']) && !in_array($options['format'], array('text', 'json'), true))
		{
			throw new \InvalidArgumentException('The "--format" option accepts "text" or "json".');
		}

		return array($arguments, $options);
	}

	/**
	 * Parse the global options given before or without a command.
	 *
	 * @param   array  $tokens  The tokens
	 *
	 * @return  array  Global option values
	 *
	 * @since   3.17.0
	 */
	protected function parseGlobalOptions(array $tokens)
	{
		$options = array();

		for ($i = 0, $n = count($tokens); $i < $n; $i++)
		{
			$token = $tokens[$i];

			foreach (static::$globalOptions as $name => $global)
			{
				if ($token === '--' . $name || ($global[0] !== null && $token === '-' . $global[0]))
				{
					$options[$name] = $global[1] ? (isset($tokens[$i + 1]) ? $tokens[++$i] : null) : true;
				}
				elseif ($global[1] && strpos($token, '--' . $name . '=') === 0)
				{
					$options[$name] = substr($token, strlen($name) + 3);
				}
			}
		}

		return $options;
	}

	/**
	 * @param   string  $token  A token
	 *
	 * @return  boolean  Whether it's a global option which takes its value from the next token
	 *
	 * @since   3.17.0
	 */
	protected function globalOptionNeedsValue($token)
	{
		foreach (static::$globalOptions as $name => $global)
		{
			if ($global[1] && $token === '--' . $name)
			{
				return true;
			}
		}

		return false;
	}

	/*
	 * Stand-ins for the CMS application, for core code running inside a command.
	 */

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getName()
	{
		return 'cli';
	}

	/**
	 * @param   string  $identifier  The client name
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isClient($identifier)
	{
		return $identifier === 'cli';
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isSite()
	{
		return false;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isAdmin()
	{
		return false;
	}

	/**
	 * Messages are shown on the running command's output straight away.
	 *
	 * @param   string  $msg   The message
	 * @param   string  $type  The message type
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		$this->messageQueue[] = array('message' => $msg, 'type' => strtolower($type));

		if ($this->io)
		{
			switch (strtolower($type))
			{
				case 'error':
					$this->io->error($msg);
					break;

				case 'warning':
				case 'notice':
					$this->io->warning($msg);
					break;

				default:
					$this->io->text($msg);
			}
		}
	}

	/**
	 * @param   boolean  $clear  Clear the queue
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	public function getMessageQueue($clear = false)
	{
		$queue = $this->messageQueue;

		if ($clear)
		{
			$this->messageQueue = array();
		}

		return $queue;
	}

	/**
	 * @param   string  $key      The key
	 * @param   mixed   $default  The default value
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function getUserState($key, $default = null)
	{
		return $this->userState->get($key, $default);
	}

	/**
	 * @param   string  $key    The key
	 * @param   mixed   $value  The value
	 *
	 * @return  mixed  The previous value
	 *
	 * @since   3.17.0
	 */
	public function setUserState($key, $value)
	{
		return $this->userState->set($key, $value);
	}

	/**
	 * @param   string  $key      The key of the user state variable
	 * @param   string  $request  The name of the request variable
	 * @param   string  $default  The default value
	 * @param   string  $type     The filter type
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none')
	{
		return $this->getUserState($key, $default);
	}

	/**
	 * @param   string  $varname  The name of the configuration value
	 * @param   mixed   $default  The default value
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function getCfg($varname, $default = null)
	{
		return $this->get($varname, $default);
	}

	/**
	 * @param   boolean  $params  Return an object (unused)
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getTemplate($params = false)
	{
		return 'system';
	}

	/**
	 * @return  \JLanguage
	 *
	 * @since   3.17.0
	 */
	public function getLanguage()
	{
		return Factory::getLanguage();
	}

	/**
	 * @return  \JSession
	 *
	 * @since   3.17.0
	 */
	public function getSession()
	{
		return Factory::getSession();
	}

	/**
	 * Make browsers fetch fresh copies of the media files, e.g. after an extension is installed.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function flushAssets()
	{
		(new \JVersion)->refreshMediaVersion();
	}

	/**
	 * @return  \JUser
	 *
	 * @since   3.17.0
	 */
	public function getIdentity()
	{
		return Factory::getUser();
	}
}
