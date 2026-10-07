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
 * Base class of the template commands: finding a template and its styles, paths inside a template's folder (never outside
 * it), the backup folder, and a few helpers for files (code or not, PHP syntax checks, diffs).
 *
 * @since  3.17.0
 */
abstract class AbstractTemplateCommand extends AbstractCommand
{
	/**
	 * The largest file the file commands read or write
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const MAX_FILE_SIZE = 2097152;

	/**
	 * Find an installed template by its folder name.
	 *
	 * @param   CommandIO  $io      The input values and the output
	 * @param   string     $name    The template's folder name, e.g. "hammond"
	 * @param   string     $client  site or administrator
	 *
	 * @return  array|integer  name, client, clientId, path (real), id (the extension), enabled; or an exit code
	 *
	 * @since   3.17.0
	 */
	protected function findTemplate(CommandIO $io, $name, $client)
	{
		if (!in_array($client, array('site', 'administrator'), true))
		{
			$io->error('--client must be site or administrator.');

			return self::INVALID;
		}

		$name = trim((string) $name);

		if (!preg_match('/^[A-Za-z0-9_-]+$/', $name))
		{
			$io->error(sprintf('"%s" is not a template name: give the template\'s folder name, e.g. hammond. template:list shows them.', $name));

			return self::INVALID;
		}

		$db       = Factory::getDbo();
		$clientId = $client === 'site' ? 0 : 1;
		$row      = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName(array('extension_id', 'element', 'enabled')))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('template'))
				->where($db->quoteName('client_id') . ' = ' . $clientId)
				->where('LOWER(' . $db->quoteName('element') . ') = ' . $db->quote(strtolower($name)))
		)->loadObject();
		$path = realpath(($clientId ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/templates/' . ($row ? $row->element : $name));

		if (!$row || $path === false || !is_dir($path))
		{
			$io->error(sprintf('There is no %s template "%s". template:list shows them.', $client, $name));

			return self::NOT_FOUND;
		}

		return array(
			'name'     => $row->element,
			'client'   => $client,
			'clientId' => $clientId,
			'path'     => $path,
			'id'       => (int) $row->extension_id,
			'enabled'  => (bool) $row->enabled,
		);
	}

	/**
	 * Find a template style by ID, or the default style of a template given by name (the site's default style without either).
	 *
	 * @param   CommandIO    $io      The input values and the output
	 * @param   string|null  $value   A style ID, a template name, or null
	 * @param   string       $client  site or administrator
	 *
	 * @return  object|integer  The style's row (id, template, client_id, home, title, params); or an exit code
	 *
	 * @since   3.17.0
	 */
	protected function findStyle(CommandIO $io, $value, $client)
	{
		if (!in_array($client, array('site', 'administrator'), true))
		{
			$io->error('--client must be site or administrator.');

			return self::INVALID;
		}

		$db    = Factory::getDbo();
		$value = trim((string) $value);
		$query = $db->getQuery(true)
			->select($db->quoteName(array('id', 'template', 'client_id', 'home', 'title', 'params')))
			->from($db->quoteName('#__template_styles'));

		if (ctype_digit($value))
		{
			$query->where($db->quoteName('id') . ' = ' . (int) $value);
		}
		else
		{
			$query->where($db->quoteName('client_id') . ' = ' . ($client === 'site' ? 0 : 1))
				// The default style first (home is "1", or a language code for a language's default), then the oldest
				->order('CASE WHEN ' . $db->quoteName('home') . ' = ' . $db->quote('1') . ' THEN 0 WHEN ' . $db->quoteName('home') . ' = '
					. $db->quote('0') . ' THEN 2 ELSE 1 END, ' . $db->quoteName('id'));

			if ($value !== '')
			{
				$query->where('LOWER(' . $db->quoteName('template') . ') = ' . $db->quote(strtolower($value)));
			}
		}

		$style = $db->setQuery($query, 0, 1)->loadObject();

		if (!$style)
		{
			$io->error($value === '' ? 'There is no template style.' : sprintf('There is no template style "%s". template:list shows them.', $value));

			return self::NOT_FOUND;
		}

		return $style;
	}

	/**
	 * Resolve a path inside a template's folder. Absolute paths, "..", and links (which could lead outside) are refused.
	 *
	 * @param   CommandIO  $io         The input values and the output
	 * @param   array      $template   The template (findTemplate())
	 * @param   string     $path       The path, relative to the template's folder, e.g. "css/custom.css"
	 * @param   boolean    $mustExist  The file must exist
	 *
	 * @return  array|integer  relative, full, exists; or an exit code
	 *
	 * @since   3.17.0
	 */
	protected function resolvePath(CommandIO $io, array $template, $path, $mustExist = true)
	{
		$path = static::normalisePath($path);

		if ($path === '' || $path[0] === '/' || preg_match('#^[A-Za-z]:#', $path) || strpos($path, "\0") !== false)
		{
			$io->error(sprintf('"%s" is not a path inside the template: give it relative to the template\'s folder, e.g. css/custom.css.', $path));

			return self::INVALID;
		}

		$parts = array();

		foreach (explode('/', $path) as $part)
		{
			if ($part === '..')
			{
				$io->error(sprintf('"%s" leads outside the template\'s folder.', $path));

				return self::REFUSED;
			}

			// A name ending in a dot or a space, or holding a control character, could hide its real type (e.g. "shell.php.")
			if ($part !== '' && $part !== '.' && preg_match('/[\x00-\x1F\x7F]|[ .]$/', $part))
			{
				$io->error(sprintf('"%s" is not a valid file or folder name: names may not end in a dot or a space.', $part));

				return self::INVALID;
			}

			if ($part !== '' && $part !== '.')
			{
				$parts[] = $part;
			}
		}

		$relative = implode('/', $parts);
		$full     = $template['path'];

		foreach ($parts as $part)
		{
			$full .= '/' . $part;

			if (is_link($full))
			{
				$io->error(sprintf('"%s" is (or is in) a link, which could lead outside the template\'s folder.', $relative));

				return self::REFUSED;
			}
		}

		$exists = file_exists($full);

		if ($exists && strpos((string) realpath($full), $template['path'] . DIRECTORY_SEPARATOR) !== 0)
		{
			$io->error(sprintf('"%s" leads outside the template\'s folder.', $relative));

			return self::REFUSED;
		}

		if ($exists && is_dir($full))
		{
			$io->error(sprintf('"%s" is a folder. template:file:list shows its files.', $relative));

			return self::INVALID;
		}

		if (!$exists && $mustExist)
		{
			$io->error(sprintf('The %s template has no file "%s". template:file:list shows its files.', $template['name'], $relative));

			return self::NOT_FOUND;
		}

		return array('relative' => $relative, 'full' => $full, 'exists' => $exists);
	}

	/**
	 * Whether a file holds code the server runs or reads as configuration (PHP and other scripts a server may run, XML,
	 * .htaccess and other dot files), rather than assets (CSS, JavaScript, images, fonts). The MCP server needs --allow-code to write these.
	 *
	 * @param   string  $path  The path
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function isCode($path)
	{
		// The name as it would be written (normalisePath(), as resolvePath() uses), without trailing dots or spaces, which
		// some servers ignore; any of its extensions counts (Apache may run "shell.php.jpg" as PHP)
		$name = basename(static::normalisePath($path));
		$bare = rtrim($name, " \t\n\r\0\x0B.");

		return $bare === '' || $name[0] === '.' || (bool) preg_match('/\.(php[0-9]*|pht|phtml|phar|phps|inc|xml|shtml?|cgi|pl|py|sh|asp|aspx|jsp)(?=\.|$)/i', $bare);
	}

	/**
	 * A path as the template commands use it: slashes, no surrounding whitespace. Everything that decides about a path
	 * (isCode(), resolvePath()) starts from this, so that no decision is taken on a different string than the one written.
	 *
	 * @param   string  $path  The path
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function normalisePath($path)
	{
		return str_replace('\\', '/', trim((string) $path));
	}

	/**
	 * The files of a folder and its subfolders, without following links.
	 *
	 * @param   string  $folder  The folder
	 * @param   string  $prefix  The path of $folder in the listing
	 *
	 * @return  string[]  Relative paths, sorted
	 *
	 * @since   3.17.0
	 */
	protected static function listFiles($folder, $prefix = '')
	{
		$files   = array();
		$entries = @scandir($folder);

		foreach ($entries ?: array() as $entry)
		{
			if ($entry === '.' || $entry === '..' || is_link($folder . '/' . $entry))
			{
				continue;
			}

			if (is_dir($folder . '/' . $entry))
			{
				$files = array_merge($files, static::listFiles($folder . '/' . $entry, $prefix . $entry . '/'));
			}
			else
			{
				$files[] = $prefix . $entry;
			}
		}

		return $files;
	}

	/**
	 * Whether file contents are text (UTF-8 without NUL bytes) rather than binary.
	 *
	 * @param   string  $content  The contents
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected static function isText($content)
	{
		return strpos($content, "\0") === false && preg_match('//u', $content) === 1;
	}

	/**
	 * Check the syntax of PHP code, without running it.
	 *
	 * @param   string  $code  The code
	 *
	 * @return  string|null  The error, or null when the syntax is fine
	 *
	 * @since   3.17.0
	 */
	protected static function checkPhpSyntax($code)
	{
		try
		{
			token_get_all($code, TOKEN_PARSE);
		}
		catch (\ParseError $e)
		{
			return sprintf('%s on line %d', $e->getMessage(), $e->getLine());
		}

		return null;
	}

	/**
	 * Write a file in one step: into a new file next to it, then renamed over it, so the site never reads half a file.
	 *
	 * @param   string  $file     The file
	 * @param   string  $content  The contents
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected static function writeFile($file, $content)
	{
		$folder = dirname($file);

		if (!is_dir($folder) && !@mkdir($folder, 0755, true))
		{
			return false;
		}

		$temporary = $folder . '/.' . basename($file) . '.' . bin2hex(random_bytes(4)) . '.tmp';

		if (@file_put_contents($temporary, $content) !== strlen($content))
		{
			@unlink($temporary);

			return false;
		}

		if (is_file($file))
		{
			@chmod($temporary, fileperms($file) & 0777);
		}

		if (!@rename($temporary, $file))
		{
			@unlink($temporary);

			return false;
		}

		if (function_exists('opcache_invalidate') && static::isCode($file))
		{
			@opcache_invalidate($file, true);
		}

		clearstatcache(true, $file);

		return true;
	}

	/**
	 * The folder for backups: backup/<hard to guess name>/ in the site's root (never touched by updates), with rules which
	 * block web access for Apache and IIS. Servers such as nginx ignore those, which is why the inner folder's name can't
	 * be guessed.
	 *
	 * @param   string   $subfolder  A folder inside it, e.g. "templates"
	 * @param   boolean  $create     Create the folders when missing
	 *
	 * @return  string|false  The folder (false when it can't be created, or doesn't exist and $create is false)
	 *
	 * @since   3.17.0
	 */
	public static function getBackupFolder($subfolder = '', $create = true)
	{
		$root   = JPATH_ROOT . '/backup';
		$folder = false;

		foreach (is_dir($root) ? (array) glob($root . '/*', GLOB_ONLYDIR) : array() as $candidate)
		{
			if (preg_match('/^[0-9a-f]{24}$/', basename($candidate)) && !is_link($candidate))
			{
				$folder = $candidate;

				break;
			}
		}

		if ($folder === false)
		{
			if (!$create || (!is_dir($root) && !@mkdir($root, 0755)))
			{
				return false;
			}

			$folder = $root . '/' . bin2hex(random_bytes(12));

			if (!@mkdir($folder, 0755))
			{
				return false;
			}
		}

		if ($create)
		{
			static::protectFolder($root);
			static::protectFolder($folder);
		}

		// Every level gets its own protection files: a server which lists folders (and ignores .htaccess) would list the others
		foreach ($subfolder !== '' ? explode('/', trim($subfolder, '/')) : array() as $part)
		{
			$folder .= '/' . $part;

			if (!is_dir($folder) && (!$create || !@mkdir($folder, 0755)))
			{
				return false;
			}

			if ($create)
			{
				static::protectFolder($folder);
			}
		}

		return $folder;
	}

	/**
	 * Block web access to a folder for Apache (.htaccess) and IIS (web.config), and hide its listing (index.html).
	 *
	 * @param   string  $folder  The folder
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected static function protectFolder($folder)
	{
		$files = array(
			'.htaccess'  => "# Blocks web access to the backups in this folder\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<configuration>\n\t<system.webServer>\n\t\t<security>\n"
				. "\t\t\t<requestFiltering>\n\t\t\t\t<fileExtensions allowUnlisted=\"false\" />\n\t\t\t</requestFiltering>\n"
				. "\t\t</security>\n\t</system.webServer>\n</configuration>\n",
			'index.html' => '<!DOCTYPE html><title></title>',
		);

		foreach ($files as $name => $content)
		{
			if (!is_file($folder . '/' . $name))
			{
				@file_put_contents($folder . '/' . $name, $content);
			}
		}
	}

	/**
	 * Back up a template's folder: a ZIP file of all its files in the backup folder's "templates" folder, named
	 * <client>-<template>-<date>-<time>.zip.
	 *
	 * @param   array  $template  The template (findTemplate())
	 *
	 * @return  array|string  file (the ZIP file), name, files (their number); or what went wrong
	 *
	 * @since   3.17.0
	 */
	protected static function createBackup(array $template)
	{
		$folder = static::getBackupFolder('templates');

		if ($folder === false)
		{
			return 'The site\'s backup folder (backup/) could not be created: the site\'s root folder must be writable.';
		}

		$base = $template['client'] . '-' . $template['name'] . '-' . date('Ymd-His');
		$name = $base;

		for ($i = 2; is_file($folder . '/' . $name . '.zip'); $i++)
		{
			$name = $base . '-' . $i;
		}

		$file  = $folder . '/' . $name . '.zip';
		$files = static::listFiles($template['path']);

		if (class_exists('ZipArchive'))
		{
			$zip = new \ZipArchive;

			if ($zip->open($file, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true)
			{
				return sprintf('%s could not be created.', $file);
			}

			foreach ($files as $relative)
			{
				$zip->addFile($template['path'] . '/' . $relative, $relative);
			}

			if (!$zip->close())
			{
				@unlink($file);

				return sprintf('%s could not be written.', $file);
			}
		}
		else
		{
			$entries = array();

			foreach ($files as $relative)
			{
				$entries[] = array('name' => $relative, 'data' => (string) file_get_contents($template['path'] . '/' . $relative),
					'time' => filemtime($template['path'] . '/' . $relative));
			}

			if (!(new \JArchiveZip)->create($file, $entries))
			{
				@unlink($file);

				return sprintf('%s could not be written.', $file);
			}
		}

		return array('file' => $file, 'name' => basename($file), 'files' => count($files));
	}

	/**
	 * The template backups, newest first.
	 *
	 * @param   string  $template  Only this template's ('' for all)
	 * @param   string  $client    site, administrator or all
	 *
	 * @return  array[]  name, file, template, client, created, size
	 *
	 * @since   3.17.0
	 */
	protected static function listBackups($template = '', $client = 'all')
	{
		$folder  = static::getBackupFolder('templates', false);
		$backups = array();

		foreach ($folder === false ? array() : (array) glob($folder . '/*.zip') as $file)
		{
			if (!preg_match('/^(site|administrator)-(.+)-(\d{8})-(\d{6})(?:-\d+)?\.zip$/', basename($file), $match)
				|| ($template !== '' && strcasecmp($match[2], $template) !== 0) || ($client !== 'all' && $client !== $match[1]))
			{
				continue;
			}

			$backups[] = array(
				'name'     => basename($file),
				'file'     => $file,
				'template' => $match[2],
				'client'   => $match[1],
				'created'  => substr($match[3], 0, 4) . '-' . substr($match[3], 4, 2) . '-' . substr($match[3], 6, 2) . ' '
					. substr($match[4], 0, 2) . ':' . substr($match[4], 2, 2) . ':' . substr($match[4], 4, 2),
				'size'     => (int) filesize($file),
			);
		}

		usort($backups, function ($a, $b)
		{
			return strcmp($b['created'] . $b['name'], $a['created'] . $a['name']);
		});

		return $backups;
	}

	/**
	 * The files in a template backup. Entries which would land outside the template's folder are refused.
	 *
	 * @param   string  $file  The ZIP file
	 *
	 * @return  array|string  relative path => contents; or what went wrong
	 *
	 * @since   3.17.0
	 */
	protected static function readBackup($file)
	{
		$entries = array();

		if (class_exists('ZipArchive'))
		{
			$zip = new \ZipArchive;

			if ($zip->open($file) !== true)
			{
				return sprintf('%s is not a ZIP file which can be read.', basename($file));
			}

			for ($i = 0; $i < $zip->numFiles; $i++)
			{
				$name = (string) $zip->getNameIndex($i);

				if (substr($name, -1) !== '/')
				{
					$entries[$name] = (string) $zip->getFromIndex($i);
				}
			}

			$zip->close();
		}
		else
		{
			$temporary = dirname($file) . '/.restore-' . bin2hex(random_bytes(6));

			try
			{
				if (!(new \JArchiveZip)->extract($file, $temporary))
				{
					return sprintf('%s is not a ZIP file which can be read.', basename($file));
				}

				foreach (static::listFiles($temporary) as $name)
				{
					$entries[$name] = (string) file_get_contents($temporary . '/' . $name);
				}
			}
			catch (\Exception $e)
			{
				return sprintf('%s could not be read: %s', basename($file), $e->getMessage());
			}
			finally
			{
				if (is_dir($temporary))
				{
					\JFolder::delete($temporary);
				}
			}
		}

		foreach (array_keys($entries) as $name)
		{
			$parts = explode('/', str_replace('\\', '/', $name));

			if ($name === '' || $name[0] === '/' || preg_match('#^[A-Za-z]:#', $name) || in_array('..', $parts, true) || strpos($name, "\0") !== false)
			{
				return sprintf('%s holds a file outside the template\'s folder (%s): it is not a backup made by template:backup.', basename($file), $name);
			}
		}

		return $entries;
	}

	/**
	 * Where the previous version of a template file is kept (one per file, replaced by each change).
	 *
	 * @param   array   $template  The template (findTemplate())
	 * @param   string  $relative  The file, relative to the template's folder
	 * @param   boolean $create    Create the backup folder when missing
	 *
	 * @return  string|false
	 *
	 * @since   3.17.0
	 */
	protected static function getPreviousVersionFile(array $template, $relative, $create = true)
	{
		$folder = static::getBackupFolder('template-files/' . $template['client'] . '/' . $template['name'], $create);

		return $folder === false ? false : $folder . '/' . $relative;
	}

	/**
	 * Keep the current version of a file before it changes or goes. A file which doesn't exist yet is recorded as such
	 * (a ".new" marker), so that restoring the previous version removes it again.
	 *
	 * @param   array   $template  The template (findTemplate())
	 * @param   array   $file      The file (resolvePath())
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected static function keepPreviousVersion(array $template, array $file)
	{
		$previous = static::getPreviousVersionFile($template, $file['relative']);
		$folder   = dirname($file['relative']);

		// The file's folders inside the template, created (and protected) as the backup folder's own
		if ($previous === false || ($folder !== '.' && $folder !== ''
			&& static::getBackupFolder('template-files/' . $template['client'] . '/' . $template['name'] . '/' . $folder) === false))
		{
			return false;
		}

		@unlink($previous . '.new');

		if (!$file['exists'])
		{
			@unlink($previous);

			return @file_put_contents($previous . '.new', '') !== false;
		}

		return @copy($file['full'], $previous);
	}

	/**
	 * A unified diff of two texts (as "diff -u" shows it), for dry runs and the record of a change.
	 *
	 * @param   string   $old      The current text
	 * @param   string   $new      The new text
	 * @param   string   $name     The file's name
	 * @param   integer  $context  Unchanged lines shown around each change
	 *
	 * @return  string  Empty when the texts are the same
	 *
	 * @since   3.17.0
	 */
	protected static function diff($old, $new, $name, $context = 3)
	{
		if ($old === $new)
		{
			return '';
		}

		$a = static::diffLines($old);
		$b = static::diffLines($new);

		// The lines both share at the start and the end aren't compared
		$start = 0;

		while ($start < count($a) && $start < count($b) && $a[$start] === $b[$start])
		{
			$start++;
		}

		$endA = count($a);
		$endB = count($b);

		while ($endA > $start && $endB > $start && $a[$endA - 1] === $b[$endB - 1])
		{
			$endA--;
			$endB--;
		}

		$midA = array_slice($a, $start, $endA - $start);
		$midB = array_slice($b, $start, $endB - $start);
		$ops  = array();

		for ($i = 0; $i < $start; $i++)
		{
			$ops[] = array(' ', $a[$i]);
		}

		// The shortest edit (longest common subsequence) when small enough; otherwise the changed block replaced as a whole
		if (count($midA) * count($midB) <= 250000)
		{
			$n     = count($midA);
			$m     = count($midB);
			$table = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

			for ($i = $n - 1; $i >= 0; $i--)
			{
				for ($j = $m - 1; $j >= 0; $j--)
				{
					$table[$i][$j] = $midA[$i] === $midB[$j] ? $table[$i + 1][$j + 1] + 1 : max($table[$i + 1][$j], $table[$i][$j + 1]);
				}
			}

			for ($i = 0, $j = 0; $i < $n || $j < $m;)
			{
				if ($i < $n && $j < $m && $midA[$i] === $midB[$j])
				{
					$ops[] = array(' ', $midA[$i++]);
					$j++;
				}
				elseif ($i < $n && ($j === $m || $table[$i + 1][$j] >= $table[$i][$j + 1]))
				{
					$ops[] = array('-', $midA[$i++]);
				}
				else
				{
					$ops[] = array('+', $midB[$j++]);
				}
			}
		}
		else
		{
			foreach ($midA as $line)
			{
				$ops[] = array('-', $line);
			}

			foreach ($midB as $line)
			{
				$ops[] = array('+', $line);
			}
		}

		for ($i = $endA; $i < count($a); $i++)
		{
			$ops[] = array(' ', $a[$i]);
		}

		// Group the changes into hunks with their context
		$output = '--- a/' . $name . "\n+++ b/" . $name . "\n";
		$count  = count($ops);
		$i      = 0;
		$lineA  = 1;
		$lineB  = 1;

		while ($i < $count)
		{
			if ($ops[$i][0] === ' ')
			{
				$i++;
				$lineA++;
				$lineB++;

				continue;
			}

			$from  = max(0, $i - $context);
			$to    = $i;
			$quiet = 0;

			// The hunk ends after more than twice the context of unchanged lines
			for ($k = $i; $k < $count; $k++)
			{
				if ($ops[$k][0] === ' ')
				{
					if (++$quiet > 2 * $context)
					{
						break;
					}
				}
				else
				{
					$quiet = 0;
					$to    = $k;
				}
			}

			$to       = min($count - 1, $to + $context);
			$startA   = $lineA - ($i - $from);
			$startB   = $lineB - ($i - $from);
			$lines    = '';
			$lengthA  = 0;
			$lengthB  = 0;

			for ($k = $from; $k <= $to; $k++)
			{
				$lines .= $ops[$k][0] . str_replace("\0", "\n\\ No newline at end of file", $ops[$k][1]) . "\n";
				$lengthA += $ops[$k][0] !== '+' ? 1 : 0;
				$lengthB += $ops[$k][0] !== '-' ? 1 : 0;
			}

			$output .= sprintf("@@ -%d,%d +%d,%d @@\n", $lengthA ? $startA : $startA - 1, $lengthA, $lengthB ? $startB : $startB - 1, $lengthB) . $lines;

			for ($k = $i; $k <= $to; $k++)
			{
				$lineA += $ops[$k][0] !== '+' ? 1 : 0;
				$lineB += $ops[$k][0] !== '-' ? 1 : 0;
			}

			$i = $to + 1;
		}

		return $output;
	}

	/**
	 * The lines of a text for diff(): a last line without a line break is marked (with a NUL, which text files don't have),
	 * so that it differs from the same line with one and is shown as "\ No newline at end of file".
	 *
	 * @param   string  $text  The text
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	private static function diffLines($text)
	{
		if ($text === '')
		{
			return array();
		}

		$lines = explode("\n", $text);

		if (end($lines) === '')
		{
			array_pop($lines);
		}
		else
		{
			$lines[count($lines) - 1] .= "\0";
		}

		return $lines;
	}
}
