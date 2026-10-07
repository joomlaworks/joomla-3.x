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
use Joomla\CMS\Version;

/**
 * An MCP (Model Context Protocol) server over the standard input and output, offering the commands as tools to AI assistants
 * (e.g. Claude Code or Claude Desktop), locally or over SSH. Read-only unless --allow-write is given.
 *
 * @since  3.17.0
 */
class McpServeCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'mcp:serve';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Serve the commands to AI assistants over MCP';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Runs an MCP (Model Context Protocol) server on the standard input and output, so AI assistants (e.g. Claude Code, '
		. 'Claude Desktop) can use the commands as tools, each described with its arguments. Configure the client to start '
		. '"php /path/to/site/cli/joomla.php mcp:serve" (over SSH for a remote site). It\'s read-only by default: only commands which change '
		. 'nothing, and dry runs of the others, so an assistant can look and plan but not change. --allow-write offers every command; --allow and '
		. '--deny narrow the commands down (e.g. --allow="article:*,category:*"), and --as makes the content commands act as that account, '
		. 'whose permissions then apply. Changes are recorded in the User Actions Log as made through MCP. Options which name files or '
		. 'folders on the server (--text-file, --folder, --path of a package etc.) or reveal secrets (--show-secrets) aren\'t offered; database '
		. 'exports and imports use a folder of the site\'s protected backup folder. Writing code also needs --allow-code: installing or '
		. 'updating extensions or Joomla, configuration.php (config:set, database:convert), and a template\'s PHP, XML and dot files (or '
		. 'restoring a template backup); a template\'s CSS, '
		. 'JavaScript and images only need --allow-write.';

	/**
	 * Commands never offered: this one, and those for people (help, list) or internal use
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const EXCLUDED = array('mcp:serve', 'help', 'list');

	/**
	 * The protocol versions this server speaks, newest first
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const PROTOCOL_VERSIONS = array('2025-11-25', '2025-06-18', '2025-03-26', '2024-11-05');

	/**
	 * Tool name => command
	 *
	 * @var    AbstractCommand[]
	 * @since  3.17.0
	 */
	private $tools = array();

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $allowWrite = false;

	/**
	 * Allow writing code which then runs on the server: extension and core packages, templates' PHP, XML and dot files
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $allowCode = false;

	/**
	 * The account the content commands act as, when the server sets it
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	private $actAs = '';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('allow-write', null, self::OPTION_NONE, 'Offer the commands which change the site too (by default only reading and dry runs)');
		$this->addOption('allow-code', null, self::OPTION_NONE, 'With --allow-write: also allow writing code which runs on the server (installing '
			. 'or updating extensions or Joomla, configuration.php, templates\' PHP, XML and dot files, restoring template backups)');
		$this->addOption('allow', null, self::OPTION_REQUIRED, 'Only these commands, separated by commas; wildcards allowed (e.g. "article:*,site:*")');
		$this->addOption('deny', null, self::OPTION_REQUIRED, 'Never these commands, separated by commas; wildcards allowed (e.g. "database:*,core:update")');
		$this->addOption('as', null, self::OPTION_REQUIRED, 'Make the content commands act as this account (its permissions apply), whatever the assistant asks');
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
		$this->allowWrite = (bool) $io->getOption('allow-write');
		$this->allowCode  = $this->allowWrite && $io->getOption('allow-code');
		$this->actAs      = (string) $io->getOption('as');

		$allow = array_filter(array_map('trim', explode(',', (string) $io->getOption('allow'))), 'strlen');
		$deny  = array_filter(array_map('trim', explode(',', (string) $io->getOption('deny'))), 'strlen');

		foreach (Factory::getApplication()->getCommands() as $name => $command)
		{
			if ($command->isHidden() || in_array($name, self::EXCLUDED, true)
				|| ($allow && !$this->matchesAny($allow, $name))
				|| $this->matchesAny($deny, $name)
				|| (!$this->allowWrite && !$command->hasReadOnlyRuns() && !$command->supportsDryRun()))
			{
				continue;
			}

			$this->tools[str_replace(':', '_', $name)] = $command;
		}

		if ($this->actAs !== '' && !\JUserHelper::getUserId($this->actAs))
		{
			$io->error(sprintf('The user "%s" does not exist.', $this->actAs));

			return self::NOT_FOUND;
		}

		// The protocol owns the standard output: PHP messages go to the standard error, command output is captured
		ini_set('display_errors', 'stderr');
		Factory::getApplication()->setInterface('MCP');

		while (($line = fgets(STDIN)) !== false)
		{
			$line = trim($line);

			if ($line === '')
			{
				continue;
			}

			$message = json_decode($line, true);

			if (!is_array($message))
			{
				$this->send(array('jsonrpc' => '2.0', 'id' => null, 'error' => array('code' => -32700, 'message' => 'Parse error')));

				continue;
			}

			// A batch (protocol versions before 2025-06-18)
			if ($message && array_keys($message) === range(0, count($message) - 1))
			{
				$replies = array_values(array_filter(array_map(array($this, 'handle'), $message)));

				if ($replies)
				{
					$this->send($replies);
				}

				continue;
			}

			$reply = $this->handle($message);

			if ($reply !== null)
			{
				$this->send($reply);
			}
		}

		return self::SUCCESS;
	}

	/**
	 * @param   array  $message  A JSON-RPC message
	 *
	 * @return  array|null  The reply, none for notifications
	 *
	 * @since   3.17.0
	 */
	private function handle($message)
	{
		if (!is_array($message) || !isset($message['method']) || !is_string($message['method']))
		{
			// A response to a request of ours (we make none), or nonsense
			return isset($message['id']) ? $this->error($message['id'], -32600, 'Invalid Request') : null;
		}

		$id     = array_key_exists('id', $message) ? $message['id'] : null;
		$params = isset($message['params']) && is_array($message['params']) ? $message['params'] : array();

		// Notifications (e.g. notifications/initialized, notifications/cancelled) need no reply
		if (!array_key_exists('id', $message))
		{
			return null;
		}

		switch ($message['method'])
		{
			case 'initialize':
				$requested = isset($params['protocolVersion']) ? (string) $params['protocolVersion'] : '';
				$version   = in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : self::PROTOCOL_VERSIONS[0];

				return $this->result($id, array(
					'protocolVersion' => $version,
					'capabilities'    => array('tools' => array('listChanged' => false)),
					'serverInfo'      => array('name' => 'joomla', 'title' => 'Joomla! ' . (new Version)->getShortVersion(), 'version' => (new Version)->getShortVersion()),
					'instructions'    => $this->getInstructions(),
				));

			case 'ping':
				return $this->result($id, new \stdClass);

			case 'tools/list':
				$tools = array();

				foreach ($this->tools as $name => $command)
				{
					$tools[] = $this->describeTool($name, $command);
				}

				return $this->result($id, array('tools' => $tools));

			case 'tools/call':
				return $this->callTool($id, $params);

			case 'resources/list':
				return $this->result($id, array('resources' => array()));

			case 'prompts/list':
				return $this->result($id, array('prompts' => array()));
		}

		return $this->error($id, -32601, 'Method not found: ' . $message['method']);
	}

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function getInstructions()
	{
		$text = 'Tools for the Joomla! site "' . Factory::getConfig()->get('sitename') . '" (Joomla ' . (new Version)->getShortVersion() . '). '
			. 'Start with site_info and site_health. Tools named *_list find items and their IDs; *_get show one. Every tool returns a JSON '
			. 'document: "success", "data", "messages", and on failure "error" with a code (invalid, not_found, refused, failed). '
			. 'Tools which change the site take "dry_run": true to report what they would change ("data.plan") without changing anything; '
			. 'use it first. Trashed items are deleted only with the *_delete tools. Changes are recorded in the site\'s User Actions Log. ';

		if (!$this->allowWrite)
		{
			$text .= 'This server is read-only: tools which change the site only accept dry runs. ';
		}

		if ($this->actAs !== '')
		{
			$text .= 'Content is changed as the user "' . $this->actAs . '", whose permissions apply. ';
		}

		$text .= 'Templates: template_info shows a style\'s positions (for module_create), options and CSS design tokens. Put the site\'s own '
			. 'CSS in the file the template loads for it (css/custom.css in Hammond and Finch), which updates never touch, and run '
			. 'template_backup before changing a template\'s files. '
			. ($this->allowCode ? '' : 'Writing code (installing or updating extensions or Joomla, configuration.php, a template\'s PHP, XML or dot files) is not allowed by '
			. 'this server (it needs --allow-code). ');

		return trim($text);
	}

	/**
	 * Describe a command as an MCP tool, with a JSON Schema of its arguments and options.
	 *
	 * @param   string           $name     The tool name
	 * @param   AbstractCommand  $command  The command
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	private function describeTool($name, AbstractCommand $command)
	{
		$properties = array();
		$required   = array();

		foreach ($command->getArguments() as $argument => $definition)
		{
			$schema = $definition[0] & self::ARGUMENT_ARRAY
				? array('type' => 'array', 'items' => array('type' => 'string'), 'description' => $definition[1])
				: array('type' => 'string', 'description' => $definition[1]);

			$properties[$argument] = $schema;

			if ($definition[0] & self::ARGUMENT_REQUIRED)
			{
				$required[] = $argument;
			}
		}

		foreach ($command->getOptions() as $option => $definition)
		{
			if (!$this->offersOption($command, $option))
			{
				continue;
			}

			$schema = $definition[1] === self::OPTION_NONE
				? array('type' => 'boolean', 'description' => $definition[2])
				: array('type' => 'string', 'description' => $definition[2]);

			if ($definition[1] !== self::OPTION_NONE && $definition[3] !== null && $definition[3] !== '')
			{
				$schema['default'] = (string) $definition[3];
			}

			$properties[$option] = $schema;
		}

		$readOnly = $command->isReadOnly();

		if (!$readOnly && $command->supportsDryRun())
		{
			$properties['dry_run'] = array('type' => 'boolean', 'description' => 'Only report what would change (data.plan); change nothing'
				. ($this->allowWrite ? '' : '. Required: this server is read-only'));
		}

		$destructive = (bool) preg_match('/(delete|remove|trash|convert|import|reinstall|restore|core:update$|reset-password|block$)/', $command->getName());

		return array(
			'name'        => $name,
			'title'       => $command->getName(),
			'description' => $command->getDescription() . '. ' . $command->getHelp(),
			'inputSchema' => array(
				'type'                 => 'object',
				'properties'           => $properties ?: new \stdClass,
				'required'             => $required,
				'additionalProperties' => false,
			),
			'annotations' => array(
				'title'           => $command->getDescription(),
				'readOnlyHint'    => $readOnly,
				'destructiveHint' => !$readOnly && $destructive,
				'idempotentHint'  => $readOnly,
				'openWorldHint'   => (bool) preg_match('/^(core:update|extension:install|extension:update|extension:reinstall|update:extensions:check|core:update:check)/', $command->getName()),
			),
		);
	}

	/**
	 * Whether an option is offered to the assistant: not those naming files or folders on the server (the command's
	 * getServerPathOptions()) or revealing secrets, nor --as when the server sets the account.
	 *
	 * @param   AbstractCommand  $command  The command
	 * @param   string           $option   The option name
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	private function offersOption(AbstractCommand $command, $option)
	{
		return !in_array($option, $command->getServerPathOptions(), true) && $option !== 'show-secrets' && !($option === 'as' && $this->actAs !== '');
	}

	/**
	 * Run a command for a tools/call request.
	 *
	 * @param   mixed  $id      The request ID
	 * @param   array  $params  The request parameters
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	private function callTool($id, array $params)
	{
		$name = isset($params['name']) ? (string) $params['name'] : '';
		$args = isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : array();

		if (!isset($this->tools[$name]))
		{
			return $this->error($id, -32602, 'Unknown tool: ' . $name);
		}

		$command   = $this->tools[$name];
		$arguments = array();
		$options   = array('format' => 'json', 'no-interaction' => true);
		$known     = $command->getArguments();
		$defined   = $command->getOptions();

		foreach ($args as $key => $value)
		{
			if ($key === 'dry_run')
			{
				$options['dry-run'] = (bool) $value;

				continue;
			}

			if (isset($known[$key]))
			{
				$arguments[$key] = $known[$key][0] & self::ARGUMENT_ARRAY ? array_map('strval', (array) $value) : (is_scalar($value) ? (string) $value : json_encode($value));

				continue;
			}

			if (isset($defined[$key]) && $this->offersOption($command, $key))
			{
				$options[$key] = $defined[$key][1] === self::OPTION_NONE ? (bool) $value : (is_scalar($value) ? (is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value)
					: json_encode($value));

				continue;
			}

			return $this->toolError($id, sprintf('The %s tool has no "%s" argument.', $name, $key));
		}

		if ($this->actAs !== '' && isset($defined['as']))
		{
			$options['as'] = $this->actAs;
		}

		$fullOptions = $options;

		foreach ($defined as $option => $definition)
		{
			if (!array_key_exists($option, $fullOptions))
			{
				$fullOptions[$option] = $definition[3];
			}
		}

		if (!$this->allowWrite && empty($options['dry-run']) && !$command->isReadOnly($fullOptions, $arguments))
		{
			return $this->toolError($id, sprintf('This server is read-only, so %s only runs with "dry_run": true. The site\'s administrator can '
				. 'start the server with --allow-write to allow changes.', $command->getName()));
		}

		if (!$this->allowCode && empty($options['dry-run']) && $command->writesCode($fullOptions, $arguments))
		{
			return $this->toolError($id, sprintf('This server does not write code (extension or Joomla packages, configuration.php, a template\'s PHP, '
				. 'XML and dot files, or a template restore), so %s only runs with "dry_run": true here. The site\'s administrator can start the server with '
				. '--allow-code to allow it; a template\'s CSS, JavaScript and images can be changed with --allow-write alone.', $command->getName()));
		}

		$stdout = fopen('php://memory', 'w+');
		CommandIO::redirect($stdout);
		ob_start();

		try
		{
			Factory::getApplication()->runCommand($command->getName(), $arguments, $options);
		}
		finally
		{
			$stray = ob_get_clean();
			CommandIO::redirect(null);
		}

		// Output a command printed itself (e.g. old code echoing) doesn't belong in the protocol stream
		if ($stray !== '' && $stray !== false)
		{
			fwrite(STDERR, $stray . PHP_EOL);
		}

		rewind($stdout);
		$output = (string) stream_get_contents($stdout);
		fclose($stdout);

		$document = json_decode($output, true);

		if (!is_array($document))
		{
			return $this->toolError($id, 'The command gave no JSON result: ' . substr(trim($output), 0, 500));
		}

		return $this->result($id, array(
			'content'           => array(array('type' => 'text', 'text' => json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))),
			'structuredContent' => $document,
			'isError'           => empty($document['success']),
		));
	}

	/**
	 * @param   array   $patterns  Command name patterns
	 * @param   string  $name      A command name
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	private function matchesAny(array $patterns, $name)
	{
		foreach ($patterns as $pattern)
		{
			if (static::matchesPattern($pattern, $name))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param   mixed  $id      The request ID
	 * @param   mixed  $result  The result
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	private function result($id, $result)
	{
		return array('jsonrpc' => '2.0', 'id' => $id, 'result' => $result);
	}

	/**
	 * A protocol error.
	 *
	 * @param   mixed    $id       The request ID
	 * @param   integer  $code     The JSON-RPC error code
	 * @param   string   $message  The message
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	private function error($id, $code, $message)
	{
		return array('jsonrpc' => '2.0', 'id' => $id, 'error' => array('code' => $code, 'message' => $message));
	}

	/**
	 * A tool error the assistant should see (as opposed to a protocol error).
	 *
	 * @param   mixed   $id       The request ID
	 * @param   string  $message  The message
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	private function toolError($id, $message)
	{
		return $this->result($id, array('content' => array(array('type' => 'text', 'text' => $message)), 'isError' => true));
	}

	/**
	 * @param   array  $message  A message or a batch of replies
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function send(array $message)
	{
		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		$json  = json_encode($message, defined('JSON_INVALID_UTF8_SUBSTITUTE') ? $flags | JSON_INVALID_UTF8_SUBSTITUTE : $flags);

		fwrite(STDOUT, ($json === false ? json_encode($this->error(null, -32603, 'Internal error: ' . json_last_error_msg())) : $json) . "\n");
		fflush(STDOUT);
	}
}
