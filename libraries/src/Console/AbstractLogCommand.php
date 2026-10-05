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
 * Base class of the log commands: the log files they may read, and Joomla's log file format.
 *
 * @since  3.17.0
 */
abstract class AbstractLogCommand extends AbstractCommand
{
	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * At most this much of the end of a log file is read
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const MAX_READ = 16777216;

	/**
	 * The log files which can be read: those in the logs folder of Global Configuration and PHP's own error log. Only these, by
	 * name, so the commands can't be used to read other files.
	 *
	 * @return  array  Name => absolute path
	 *
	 * @since   3.17.0
	 */
	protected function getLogFiles()
	{
		$files  = array();
		$folder = (string) Factory::getConfig()->get('log_path', JPATH_ADMINISTRATOR . '/logs');

		if (is_dir($folder))
		{
			foreach (new \DirectoryIterator($folder) as $entry)
			{
				if ($entry->isFile() && preg_match('/\.(php|log|txt)$/i', $entry->getFilename()))
				{
					$files[$entry->getFilename()] = $entry->getPathname();
				}
			}
		}

		ksort($files);

		// PHP's error log as this command line's PHP is configured; the web server's may differ
		$phpLog = (string) ini_get('error_log');

		if ($phpLog !== '' && $phpLog !== 'syslog' && is_file($phpLog) && is_readable($phpLog))
		{
			$files['php-error-log'] = $phpLog;
		}

		return $files;
	}

	/**
	 * Read the entries of a log file: Joomla's log files are parsed by their "#Fields:" header, other files give one entry per line.
	 *
	 * @param   string  $path  The file
	 *
	 * @return  array[]  The entries, oldest first
	 *
	 * @since   3.17.0
	 */
	protected function readEntries($path)
	{
		$size   = (int) filesize($path);
		$handle = fopen($path, 'rb');

		if (!$handle)
		{
			throw new \RuntimeException(sprintf('Cannot read %s.', $path));
		}

		// The header is at the start; very large files are only read from their end
		$head = (string) fread($handle, 4096);

		if ($size > self::MAX_READ)
		{
			fseek($handle, -self::MAX_READ, SEEK_END);
			$text = (string) stream_get_contents($handle);
			$text = substr($text, (int) strpos($text, "\n") + 1);
		}
		else
		{
			rewind($handle);
			$text = (string) stream_get_contents($handle);
		}

		fclose($handle);

		$pattern = null;
		$fields  = array();

		if (preg_match('/^#Fields: (.+)$/m', $head, $match))
		{
			// The separators between the fields are kept as they are in the format, e.g. "datetime\tpriority clientip\tcategory\tmessage"
			$parts   = preg_split('/([ \t]+)/', trim($match[1], "\r\n"), -1, PREG_SPLIT_DELIM_CAPTURE);
			$pattern = '';

			foreach ($parts as $i => $part)
			{
				if ($i % 2)
				{
					$pattern .= preg_quote($part, '/');

					continue;
				}

				$fields[] = $part;
				$pattern .= $i === count($parts) - 1 ? '(.*)' : '(.*?)';
			}

			$pattern = '/^' . $pattern . '$/u';
		}

		$entries = array();

		foreach (preg_split('/\r\n|\n|\r/', $text) as $line)
		{
			if ($line === '' || $line[0] === '#')
			{
				continue;
			}

			if ($pattern && preg_match($pattern, $line, $values))
			{
				array_shift($values);
				$entries[] = array_combine($fields, $values);

				continue;
			}

			// A message spanning several lines continues the previous entry
			if ($pattern && $entries)
			{
				$entries[count($entries) - 1][end($fields)] .= "\n" . $line;

				continue;
			}

			$entries[] = array('message' => $line);
		}

		return $entries;
	}
}
