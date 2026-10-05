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
 * Input values and output for a console command.
 *
 * In text mode everything is written as it happens. In JSON mode (--format=json) nothing is written until
 * finish(), which prints a single JSON document holding the collected data and messages, so the output can
 * always be parsed. JSON mode never asks questions; the defaults are used instead.
 *
 * @since  3.17.0
 */
class CommandIO
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	private $command;

	/**
	 * @var    array
	 * @since  3.17.0
	 */
	private $arguments;

	/**
	 * @var    array
	 * @since  3.17.0
	 */
	private $options;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $json;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $quiet;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $interactive;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $ansi;

	/**
	 * @var    resource
	 * @since  3.17.0
	 */
	private $stdout;

	/**
	 * @var    resource
	 * @since  3.17.0
	 */
	private $stderr;

	/**
	 * Data for the JSON document
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	private $data = array();

	/**
	 * Messages for the JSON document
	 *
	 * @var    array
	 * @since  3.17.0
	 */
	private $messages = array();

	/**
	 * @var    string|null
	 * @since  3.17.0
	 */
	private $errorMessage;

	/**
	 * The stream written to last and whether that line was empty, for the blank line finish() adds
	 *
	 * @var    resource|null
	 * @since  3.17.0
	 */
	private $lastStream;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $lastLineBlank = false;

	/**
	 * Streams which replace STDOUT and STDERR, e.g. to capture a command's output (see redirect())
	 *
	 * @var    resource[]|null
	 * @since  3.17.0
	 */
	private static $streams;

	/**
	 * Exit code => the name of the error in the JSON document
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	private static $errorCodes = array(1 => 'failed', 2 => 'invalid', 3 => 'not_found', 4 => 'refused');

	/**
	 * Constructor.
	 *
	 * @param   string  $command    The command name
	 * @param   array   $arguments  The argument values
	 * @param   array   $options    The option values, including the global ones
	 *
	 * @since   3.17.0
	 */
	public function __construct($command, array $arguments, array $options)
	{
		$this->command     = $command;
		$this->arguments   = $arguments;
		$this->options     = $options;
		$this->json        = isset($options['format']) && $options['format'] === 'json';
		$this->quiet       = !empty($options['quiet']);
		$this->interactive = !$this->json && empty($options['no-interaction']) && static::streamIsInteractive(STDIN);
		$this->stdout      = self::$streams ? self::$streams[0] : STDOUT;
		$this->stderr      = self::$streams ? self::$streams[1] : (defined('STDERR') ? STDERR : STDOUT);
		$this->ansi        = !$this->json && empty($options['no-ansi']) && static::streamIsInteractive($this->stdout);
	}

	/**
	 * @param   string  $name  The argument name
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function getArgument($name)
	{
		return array_key_exists($name, $this->arguments) ? $this->arguments[$name] : null;
	}

	/**
	 * @param   string  $name  The option name
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function getOption($name)
	{
		return array_key_exists($name, $this->options) ? $this->options[$name] : null;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isJson()
	{
		return $this->json;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isQuiet()
	{
		return $this->quiet;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isInteractive()
	{
		return $this->interactive;
	}

	/**
	 * Whether this is a dry run (--dry-run): the command reports what it would change, through plan(), and changes nothing.
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isDryRun()
	{
		return !empty($this->options['dry-run']);
	}

	/**
	 * Report a change a dry run would make: "Would ..." in text mode, and an entry of the "plan" list in JSON mode.
	 *
	 * @param   string  $text  What would be done, e.g. "Block the user jdoe"
	 * @param   array   $item  Details for the JSON entry, e.g. array('action' => 'block', 'user' => 'jdoe')
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function plan($text, array $item = array())
	{
		$this->data['plan'][] = array_merge(array('description' => $text), $item);

		if (!$this->json && !$this->quiet)
		{
			$this->write($this->stdout, $this->style('[DRY RUN] Would: ', '36') . $text . "\n");
		}
	}

	/**
	 * Send the output of every command run from now on to other streams, or back to STDOUT/STDERR with null.
	 *
	 * @param   resource|null  $stdout  The stream for output
	 * @param   resource|null  $stderr  The stream for errors, by default the same as $stdout
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public static function redirect($stdout = null, $stderr = null)
	{
		self::$streams = $stdout ? array($stdout, $stderr ?: $stdout) : null;
	}

	/**
	 * Write a line of plain text (text mode only; ignored in JSON mode and when quiet).
	 *
	 * @param   string  $text  The text
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function writeln($text = '')
	{
		if (!$this->json && !$this->quiet)
		{
			$this->write($this->stdout, $text . "\n");
		}
	}

	/**
	 * @param   string  $text  The title
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function title($text)
	{
		$this->writeln($this->style($text, '1'));
		$this->writeln($this->style(str_repeat('=', static::width($text)), '1'));
		$this->writeln();
	}

	/**
	 * An informational message.
	 *
	 * @param   string  $text  The message
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function text($text)
	{
		$this->message('info', $text);
	}

	/**
	 * @param   string  $text  The message
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function success($text)
	{
		$this->message('success', $text);
	}

	/**
	 * @param   string  $text  The message
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function warning($text)
	{
		$this->message('warning', $text);
	}

	/**
	 * An error message. Errors are shown even in quiet mode, on stderr in text mode.
	 *
	 * @param   string  $text  The message
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function error($text)
	{
		$this->errorMessage = $this->errorMessage === null ? $text : $this->errorMessage;
		$this->message('error', $text);
	}

	/**
	 * Show rows of data: a table in text mode, a list of objects under $key in JSON mode.
	 *
	 * @param   array   $columns  Row key => column heading
	 * @param   array   $rows     The rows, each an associative array or object
	 * @param   string  $key      The key of the list in the JSON data
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function table(array $columns, array $rows, $key = 'items')
	{
		$normalised = array();

		foreach ($rows as $row)
		{
			$row  = (array) $row;
			$item = array();

			foreach ($columns as $column => $heading)
			{
				$item[$column] = array_key_exists($column, $row) ? $row[$column] : null;
			}

			$normalised[] = $item;
		}

		if ($this->json)
		{
			$this->data[$key] = $normalised;

			return;
		}

		// The commands say in words when there's nothing to list
		if ($this->quiet || !$normalised)
		{
			return;
		}

		$widths = array();

		foreach ($columns as $column => $heading)
		{
			$widths[$column] = static::width($heading);

			foreach ($normalised as $item)
			{
				$widths[$column] = max($widths[$column], static::width(static::toText($item[$column])));
			}
		}

		$separator = '+';

		foreach ($widths as $width)
		{
			$separator .= str_repeat('-', $width + 2) . '+';
		}

		$this->writeln($separator);
		$line = '|';

		foreach ($columns as $column => $heading)
		{
			$line .= ' ' . $this->style(static::pad($heading, $widths[$column]), '1') . ' |';
		}

		$this->writeln($line);
		$this->writeln($separator);

		foreach ($normalised as $item)
		{
			$line = '|';

			foreach ($columns as $column => $heading)
			{
				$line .= ' ' . static::pad(static::toText($item[$column]), $widths[$column]) . ' |';
			}

			$this->writeln($line);
		}

		$this->writeln($separator);
	}

	/**
	 * Show key/value pairs: aligned lines in text mode, an object under $key in JSON mode.
	 *
	 * @param   array   $pairs  Key => value; in text mode the key is shown as the label
	 * @param   string  $key    The key of the object in the JSON data, or null to merge into the data root
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function definitionList(array $pairs, $key = null)
	{
		if ($this->json)
		{
			if ($key === null)
			{
				$this->data = array_merge($this->data, $pairs);
			}
			else
			{
				$this->data[$key] = $pairs;
			}

			return;
		}

		$width = 0;

		foreach ($pairs as $label => $value)
		{
			$width = max($width, static::width((string) $label));
		}

		foreach ($pairs as $label => $value)
		{
			$this->writeln($this->style(static::pad((string) $label, $width), '36') . '  ' . static::toText($value));
		}
	}

	/**
	 * Add a value to the JSON data (ignored in text mode).
	 *
	 * @param   string  $key    The key
	 * @param   mixed   $value  The value
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function setData($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Ask for a value. Without interaction the default is returned.
	 *
	 * @param   string   $question  The question
	 * @param   mixed    $default   The default value
	 * @param   boolean  $hidden    Hide the typed value (for passwords), where the terminal allows it
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function ask($question, $default = null, $hidden = false)
	{
		if (!$this->interactive)
		{
			return $default;
		}

		// A question ("What is ...?") needs no colon after it
		$prompt = $this->style($question, '32') . ($default !== null && !$hidden ? ' [' . $default . ']' : '') . (substr($question, -1) === '?' && ($default === null || $hidden) ? ' ' : ': ');
		fwrite($this->stdout, $prompt);

		$stty = $hidden && DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec') ? @shell_exec('stty -g 2>/dev/null') : null;

		if ($stty)
		{
			@shell_exec('stty -echo 2>/dev/null');
		}

		$answer = fgets(STDIN);

		if ($stty)
		{
			@shell_exec('stty ' . escapeshellarg(trim($stty)) . ' 2>/dev/null');
			fwrite($this->stdout, "\n");
		}

		$answer = $answer === false ? '' : trim($answer);

		return $answer === '' ? $default : $answer;
	}

	/**
	 * Ask a yes/no question. Without interaction the default is returned.
	 *
	 * @param   string   $question  The question
	 * @param   boolean  $default   The default answer
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function confirm($question, $default = false)
	{
		$answer = $this->ask($question . ' (yes/no)', $default ? 'yes' : 'no');

		return in_array(strtolower((string) $answer), array('y', 'yes', '1', 'true'), true);
	}

	/**
	 * Finish the command. In JSON mode, print the JSON document.
	 *
	 * @param   integer  $exitCode  The exit code of the command
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function finish($exitCode)
	{
		if (!$this->json)
		{
			// Keep the shell prompt apart from the output
			if ($this->lastStream && !$this->lastLineBlank)
			{
				fwrite($this->lastStream, "\n");
			}

			return;
		}

		$document = array(
			'command'  => $this->command,
			'success'  => $exitCode === 0,
			'exitCode' => $exitCode,
		);

		if ($exitCode !== 0)
		{
			$document['error'] = array(
				'code'    => isset(self::$errorCodes[$exitCode]) ? self::$errorCodes[$exitCode] : 'failed',
				'message' => $this->errorMessage,
			);
		}

		if ($this->isDryRun())
		{
			$document['dryRun'] = true;
		}

		$document['data']     = (object) $this->data;
		$document['messages'] = $this->messages;

		$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		$json  = json_encode($document, defined('JSON_INVALID_UTF8_SUBSTITUTE') ? $flags | JSON_INVALID_UTF8_SUBSTITUTE : $flags);

		fwrite($this->stdout, ($json === false ? json_encode(array('command' => $this->command, 'success' => false, 'error' => json_last_error_msg())) : $json) . "\n");
	}

	/**
	 * Write text output.
	 *
	 * @param   resource  $stream  The stream
	 * @param   string    $text    The text, ending with a line break
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function write($stream, $text)
	{
		fwrite($stream, $text);

		$this->lastStream    = $stream;
		$this->lastLineBlank = trim($text) === '';
	}

	/**
	 * @param   string  $type  info, success, warning or error
	 * @param   string  $text  The message
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function message($type, $text)
	{
		$text = trim(strip_tags(str_replace(array('<br />', '<br/>', '<br>'), "\n", (string) $text)));

		if ($this->json)
		{
			$this->messages[] = array('type' => $type, 'text' => $text);

			return;
		}

		$styles = array('success' => '32', 'warning' => '33', 'error' => '31');
		$labels = array('success' => '[OK] ', 'warning' => '[WARNING] ', 'error' => '[ERROR] ');
		$line   = (isset($labels[$type]) ? $labels[$type] : '') . $text;
		$line   = isset($styles[$type]) ? $this->style($line, $styles[$type]) : $line;

		if ($type === 'error')
		{
			$this->write($this->stderr, $line . "\n");

			return;
		}

		$this->writeln($line);
	}

	/**
	 * @param   string  $text   The text
	 * @param   string  $codes  ANSI SGR codes
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private function style($text, $codes)
	{
		return $this->ansi ? "\033[" . $codes . 'm' . $text . "\033[0m" : $text;
	}

	/**
	 * @param   mixed  $value  A value
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private static function toText($value)
	{
		if ($value === null)
		{
			return '';
		}

		if (is_bool($value))
		{
			return $value ? 'yes' : 'no';
		}

		if (is_array($value) || is_object($value))
		{
			return implode(', ', array_map(array(__CLASS__, 'toText'), (array) $value));
		}

		return str_replace(array("\r", "\n"), ' ', (string) $value);
	}

	/**
	 * @param   string  $text  The text
	 *
	 * @return  integer  The display width
	 *
	 * @since   3.17.0
	 */
	private static function width($text)
	{
		return function_exists('mb_strwidth') ? mb_strwidth($text, 'UTF-8') : strlen($text);
	}

	/**
	 * @param   string   $text   The text
	 * @param   integer  $width  The display width to pad to
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	private static function pad($text, $width)
	{
		return $text . str_repeat(' ', max(0, $width - static::width($text)));
	}

	/**
	 * @param   resource  $stream  A stream
	 *
	 * @return  boolean  Whether a person is likely at the other end
	 *
	 * @since   3.17.0
	 */
	private static function streamIsInteractive($stream)
	{
		if (function_exists('stream_isatty'))
		{
			return @stream_isatty($stream);
		}

		return function_exists('posix_isatty') && @posix_isatty($stream);
	}
}
