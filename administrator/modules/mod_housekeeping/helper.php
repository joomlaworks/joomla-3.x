<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_housekeeping
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Housekeeping from the status bar: clean the cache (everyone in the administrator), clean everything (the cache, its expired entries, the cache and temporary
 * folders) and check in everything. Called through com_ajax (index.php?option=com_ajax&module=housekeeping&method=clean).
 *
 * @since  3.17.0
 */
abstract class ModHousekeepingHelper
{
	/**
	 * Files kept when a folder is emptied: they protect it (no listing, no web access)
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const KEEP = array('index.html', '.htaccess', 'web.config');

	/**
	 * Folders of the site's root which are never emptied, nor anything inside them
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	const PROTECTED_FOLDERS = array('administrator', 'backup', 'bin', 'cli', 'components', 'database', 'images', 'includes', 'installation',
		'language', 'layouts', 'libraries', 'media', 'modules', 'plugins', 'templates');

	/**
	 * Whether the user may clean the cache: everyone who can log in to the administrator (e.g. editors, after changing an
	 * article which shows cached elsewhere).
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function canCleanCache()
	{
		return JFactory::getUser()->authorise('core.login.admin');
	}

	/**
	 * Whether the user may clean everything and check in everything: those who may manage the cache and Global Check-in in
	 * the administrator (by default Administrators and Super Users).
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public static function isAdmin()
	{
		$user = JFactory::getUser();

		return $user->authorise('core.manage', 'com_cache') && $user->authorise('core.manage', 'com_checkin');
	}

	/**
	 * Run an action (the request's "action": cache, all or checkin).
	 *
	 * @return  array  message, and what was done
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	public static function cleanAjax()
	{
		$input = JFactory::getApplication()->input;

		// It changes things: POST only, with the form token in the request's body (never in the URL, which ends up in logs)
		if (strtoupper($input->server->getWord('REQUEST_METHOD')) !== 'POST')
		{
			throw new RuntimeException(JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 405);
		}

		if (!JSession::checkToken('post'))
		{
			throw new RuntimeException(JText::_('JINVALID_TOKEN_NOTICE'), 403);
		}

		// com_ajax only checks that the module is installed: act only while the module is shown to the user (published, in
		// the administrator, at an access level they have)
		if (!JModuleHelper::getModule('mod_housekeeping')->id)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$action = $input->post->getCmd('action');

		if ($action === 'cache')
		{
			if (!static::canCleanCache())
			{
				throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			$groups = static::cleanCache(false);

			return array('message' => JText::plural('MOD_HOUSEKEEPING_CACHE_CLEANED', $groups), 'groups' => $groups);
		}

		if (!in_array($action, array('all', 'checkin'), true))
		{
			throw new InvalidArgumentException('Unknown action.', 400);
		}

		if (!static::isAdmin())
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if ($action === 'checkin')
		{
			$items = static::checkin();

			return array('message' => $items ? JText::plural('MOD_HOUSEKEEPING_CHECKED_IN', $items) : JText::_('MOD_HOUSEKEEPING_NOTHING_CHECKED_IN'),
				'items' => $items);
		}

		$groups  = static::cleanCache(true);
		$deleted = 0;
		$bytes   = 0;
		$skipped = array();
		$config  = JFactory::getConfig();
		$folders = array(
			$config->get('cache_path', JPATH_SITE . '/cache') ?: JPATH_SITE . '/cache',
			JPATH_ADMINISTRATOR . '/cache',
		);
		$updating = is_file(JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/restoration.php');

		// Joomla Update extracts its package from the temporary folder over many requests: never empty it meanwhile
		if (!$updating)
		{
			$folders[] = $config->get('tmp_path', JPATH_ROOT . '/tmp') ?: JPATH_ROOT . '/tmp';
		}

		foreach (array_unique(array_filter(array_map('realpath', $folders))) as $folder)
		{
			if (!static::isSafeFolder($folder))
			{
				$skipped[] = $folder;

				continue;
			}

			static::emptyFolder($folder, $folder, $deleted, $bytes);
		}

		$message = JText::sprintf('MOD_HOUSEKEEPING_ALL_CLEANED', $groups, $deleted, static::size($bytes));

		if ($updating)
		{
			$message .= ' ' . JText::_('MOD_HOUSEKEEPING_TMP_SKIPPED_UPDATE');
		}

		foreach ($skipped as $folder)
		{
			$message .= ' ' . JText::sprintf('MOD_HOUSEKEEPING_FOLDER_SKIPPED', JPath::removeRoot($folder));
		}

		return array('message' => $message, 'groups' => $groups, 'deleted' => $deleted, 'bytes' => $bytes, 'skipped' => array_map('JPath::removeRoot', $skipped),
			'updating' => $updating);
	}

	/**
	 * Clean every group of the site's and the administrator's cache, whatever the cache handler (file, Memcached, Redis,
	 * APCu, WinCache), and optionally remove the expired entries.
	 *
	 * @param   boolean  $expired  Also remove the expired entries
	 *
	 * @return  integer  The number of groups cleaned
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	protected static function cleanCache($expired)
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cache/models', 'CacheModel');
		$model   = JModelLegacy::getInstance('Cache', 'CacheModel', array('ignore_request' => true));
		$groups  = 0;
		$cleaned = true;

		foreach (array(0, 1) as $clientId)
		{
			$cache = $model->getCache($clientId);

			// Group by group: the Redis, Memcached, APCu and WinCache handlers clean nothing for an empty group name
			foreach ($cache->getAll() ?: array() as $group)
			{
				$cleaned = $cache->clean($group->group) !== false && $cleaned;
				$groups++;
			}

			if ($expired)
			{
				$cleaned = $cache->gc() !== false && $cleaned;
			}
		}

		if (!$cleaned)
		{
			throw new RuntimeException(JText::_('MOD_HOUSEKEEPING_CACHE_NOT_CLEANED'), 500);
		}

		// As the Clear Cache manager does after a purge
		JFactory::getApplication()->triggerEvent('onAfterPurge', array());

		return $groups;
	}

	/**
	 * Check in every item of every table which can be checked out.
	 *
	 * @return  integer  The number of items checked in
	 *
	 * @since   3.17.0
	 */
	protected static function checkin()
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_checkin/models', 'CheckinModel');
		$model = JModelLegacy::getInstance('Checkin', 'CheckinModel', array('ignore_request' => true));

		return (int) $model->checkin(JFactory::getDbo()->getTableList());
	}

	/**
	 * Whether a folder may be emptied: inside the site's folder (never the site's folder itself, nor one holding Joomla's
	 * code). A cache or temporary folder set somewhere else, e.g. the server's /tmp, may hold other programs' files.
	 *
	 * @param   string  $folder  The folder (real path)
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected static function isSafeFolder($folder)
	{
		$root = realpath(JPATH_ROOT);

		if ($root === false || !is_dir($folder) || is_link($folder) || strpos($folder, $root . DIRECTORY_SEPARATOR) !== 0
			|| is_file($folder . '/index.php') || is_file($folder . '/configuration.php'))
		{
			return false;
		}

		// Never one of Joomla's own folders or inside one (a cache or temporary folder set to e.g. images by mistake), but the
		// administrator's cache
		$relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($folder, strlen($root) + 1));
		$first    = strtok($relative, '/');

		return $relative === 'administrator/cache' || !in_array($first, static::PROTECTED_FOLDERS, true);
	}

	/**
	 * Delete everything in a folder but the files which protect it, without following links out of it.
	 *
	 * @param   string   $folder   The folder
	 * @param   string   $top      The folder being emptied (whose protection files are kept)
	 * @param   integer  $deleted  Counts the files and folders deleted
	 * @param   integer  $bytes    Counts their size
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected static function emptyFolder($folder, $top, &$deleted, &$bytes)
	{
		foreach (@scandir($folder) ?: array() as $entry)
		{
			$path = $folder . '/' . $entry;

			if ($entry === '.' || $entry === '..' || ($folder === $top && in_array($entry, static::KEEP, true)))
			{
				continue;
			}

			if (is_dir($path) && !is_link($path))
			{
				static::emptyFolder($path, $top, $deleted, $bytes);

				if (@rmdir($path))
				{
					$deleted++;
				}

				continue;
			}

			$size = is_link($path) ? 0 : (int) @filesize($path);

			if (@unlink($path))
			{
				$deleted++;
				$bytes += $size;
			}
		}
	}

	/**
	 * A size for people: 512 B, 3.4 KB, 12.1 MB.
	 *
	 * @param   integer  $bytes  The size
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected static function size($bytes)
	{
		$units = array('B', 'KB', 'MB', 'GB');
		$unit  = 0;

		while ($bytes >= 1024 && $unit < count($units) - 1)
		{
			$bytes /= 1024;
			$unit++;
		}

		return ($unit ? number_format($bytes, 1) : (int) $bytes) . ' ' . $units[$unit];
	}
}
