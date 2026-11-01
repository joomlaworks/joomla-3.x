<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  Cache
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Cache\Storage;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Cache\CacheStorage;
use Joomla\CMS\Cache\CleanRequests;

/**
 * Stands in, on the command line, for a cache kept in the web server's own memory (APCu, WinCache): the command line has a
 * memory of its own, so it reads nothing, keeps nothing, and records cleans for the web server (CleanRequests).
 *
 * @since  3.18.0
 */
class CommandlineStorage extends CacheStorage
{
    /**
     * The handler it stands in for (apcu, wincache)
     *
     * @var    string
     * @since  3.18.0
     */
    public $handler;

    /**
     * Constructor
     *
     * @param   array  $options  Optional parameters; "handler" is the handler it stands in for
     *
     * @since   3.18.0
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->handler = isset($options['handler']) ? (string) $options['handler'] : '';
    }

    /**
     * Never offered as a handler: CacheStorage::getInstance() makes it for APCu and WinCache on the command line
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public static function isSupported()
    {
        return false;
    }

    /**
     * The groups of the web server's cache aren't known here
     *
     * @return  array
     *
     * @since   3.18.0
     */
    public function getAll()
    {
        return array();
    }

    /**
     * Removing an entry cleans its group in the web server's cache
     *
     * @param   string  $id     The cache data ID
     * @param   string  $group  The cache data group
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function remove($id, $group)
    {
        return CleanRequests::record((string) $group);
    }

    /**
     * Clean the web server's cache when it next starts.
     *
     * @param   string  $group  The cache data group
     * @param   string  $mode   The mode for cleaning cache [group|notgroup]
     *
     * @return  boolean  Whether the request was recorded
     *
     * @since   3.18.0
     */
    public function clean($group, $mode = null)
    {
        return CleanRequests::record((string) $group, $mode === 'notgroup' ? 'notgroup' : 'group');
    }

    /**
     * Clean the whole of the web server's cache when it next starts.
     *
     * @return  boolean  Whether the request was recorded
     *
     * @since   3.18.0
     */
    public function cleanAll()
    {
        return CleanRequests::record('', 'notgroup');
    }

    /**
     * Nothing is kept here, so nothing waits
     *
     * @param   string   $id        The cache data ID
     * @param   string   $group     The cache data group
     * @param   integer  $locktime  Cached item max lock time
     *
     * @return  object
     *
     * @since   3.18.0
     */
    public function lock($id, $group, $locktime)
    {
        $returning             = new \stdClass();
        $returning->locked     = true;
        $returning->locklooped = false;

        return $returning;
    }

    /**
     * Nothing is kept here (Cache::unlock() would remove the lock entry otherwise, which would clean its group)
     *
     * @param   string  $id     The cache data ID
     * @param   string  $group  The cache data group
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function unlock($id, $group = null)
    {
        return true;
    }
}
