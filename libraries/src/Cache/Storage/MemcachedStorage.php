<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Cache\Storage;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheStorage;
use Joomla\CMS\Cache\Exception\CacheConnectingException;

/**
 * Memcached cache storage handler
 *
 * @link   https://www.php.net/manual/en/book.memcached.php
 * @since  3.0.0
 */
class MemcachedStorage extends CacheStorage
{
    /**
     * Memcached connection object
     *
     * @var    \Memcached
     * @since  3.0.0
     */
    protected static $_db = null;

    /**
     * Payload compression level
     *
     * @var    integer
     * @since  3.0.0
     */
    protected $_compress = 0;

    /**
     * The generations read: group => array(generation, the time read)
     *
     * @var    array
     * @since  3.18.0
     */
    protected static $generations = array();

    /**
     * The groups known to be on the list of groups
     *
     * @var    array
     * @since  3.18.0
     */
    protected static $knownGroups = array();

    /**
     * Constructor
     *
     * @param   array  $options  Optional parameters.
     *
     * @since   3.0.0
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_compress = \JFactory::getConfig()->get('memcached_compress', true) ? \Memcached::OPT_COMPRESSION : 0;

        if (static::$_db === null) {
            $this->getConnection();
        }
    }

    /**
     * Create the Memcached connection
     *
     * @return  void
     *
     * @since   3.0.0
     * @throws  \RuntimeException
     */
    protected function getConnection()
    {
        if (!static::isSupported()) {
            throw new \RuntimeException('Memcached Extension is not available');
        }

        $config = \JFactory::getConfig();

        $host = $config->get('memcached_server_host', 'localhost');
        $port = $config->get('memcached_server_port', 11211);


        // Create the memcached connection
        if ($config->get('memcached_persist', true)) {
            static::$_db = new \Memcached($this->_hash);
            $servers = static::$_db->getServerList();

            if ($servers && ($servers[0]['host'] != $host || $servers[0]['port'] != $port)) {
                static::$_db->resetServerList();
                $servers = array();
            }

            if (!$servers) {
                static::$_db->addServer($host, $port);
            }
        } else {
            static::$_db = new \Memcached();
            static::$_db->addServer($host, $port);
        }

        static::$_db->setOption(\Memcached::OPT_COMPRESSION, $this->_compress);

        $stats  = static::$_db->getStats();
        $result = !empty($stats["$host:$port"]) && $stats["$host:$port"]['pid'] > 0;

        if (!$result) {
            // Null out the connection to inform the constructor it will need to attempt to connect if this class is instantiated again
            static::$_db = null;

            throw new CacheConnectingException('Could not connect to memcached server');
        }
    }

    /**
     * Get a cache_id string from an id/group pair
     *
     * @param   string  $id     The cache data id
     * @param   string  $group  The cache data group
     *
     * @return  string   The cache_id string
     *
     * @since   1.7.0
     */
    protected function _getCacheId($id, $group)
    {
        $prefix   = Cache::getPlatformPrefix();
        $length   = strlen($prefix);
        $cache_id = parent::_getCacheId($id, $group . '-' . $this->getGeneration($group));

        if ($length) {
            // Memcached use suffix instead of prefix
            $cache_id = substr($cache_id, $length) . strrev($prefix);
        }

        return $cache_id;
    }

    /**
     * Check if the cache contains data stored by ID and group
     *
     * @param   string  $id     The cache data ID
     * @param   string  $group  The cache data group
     *
     * @return  boolean
     *
     * @since   3.7.0
     */
    public function contains($id, $group)
    {
        static::$_db->get($this->_getCacheId($id, $group));

        return static::$_db->getResultCode() !== \Memcached::RES_NOTFOUND;
    }

    /**
     * Get cached data by ID and group
     *
     * @param   string   $id         The cache data ID
     * @param   string   $group      The cache data group
     * @param   boolean  $checkTime  True to verify cache time expiration threshold
     *
     * @return  mixed  Boolean false on failure or a cached data object
     *
     * @since   3.0.0
     */
    public function get($id, $group, $checkTime = true)
    {
        return static::$_db->get($this->_getCacheId($id, $group));
    }

    /**
     * Get all cached data: the counters of each group, which count what was stored since it was last cleaned
     *
     * @return  mixed  Boolean false on failure or a cached data object
     *
     * @since   3.0.0
     */
    public function getAll()
    {
        $groups = static::$_db->get($this->_hash . '-groups');
        $data   = array();

        if (!is_array($groups) || !$groups) {
            return $data;
        }

        $keys = array();

        foreach ($groups as $group) {
            $keys[] = $this->_hash . '-count-' . $group;
            $keys[] = $this->_hash . '-size-' . $group;
        }

        $values = static::$_db->getMulti($keys) ?: array();

        foreach ($groups as $group) {
            $count = $this->_hash . '-count-' . $group;

            // A cleaned group counts 0; one whose counter the server dropped may still hold entries
            if (isset($values[$count]) && (int) $values[$count] <= 0) {
                continue;
            }

            $item        = new CacheStorageHelper($group);
            $item->count = isset($values[$count]) ? (int) $values[$count] : 0;
            $item->size  = (int) ($values[$this->_hash . '-size-' . $group] ?? 0);

            $data[$group] = $item;
        }

        return $data;
    }

    /**
     * Store the data to cache by ID and group
     *
     * @param   string  $id     The cache data ID
     * @param   string  $group  The cache data group
     * @param   string  $data   The data to store in cache
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    public function store($id, $group, $data)
    {
        if (!static::$_db->set($this->_getCacheId($id, $group), $data, $this->_lifetime)) {
            return false;
        }

        $this->addGroup($group);
        $this->addToCounter($this->_hash . '-count-' . $group, 1);
        $this->addToCounter($this->_hash . '-size-' . $group, strlen((string) $data));

        return true;
    }

    /**
     * Remove a cached data entry by ID and group
     *
     * @param   string  $id     The cache data ID
     * @param   string  $group  The cache data group
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    public function remove($id, $group)
    {
        $removed = static::$_db->delete($this->_getCacheId($id, $group));

        if ($removed) {
            static::$_db->decrement($this->_hash . '-count-' . $group);
        }

        return $removed;
    }

    /**
     * Clean cache for a group given a mode: a group's entries are out of reach as soon as it has a new generation, and expire
     * by themselves, however many there are.
     *
     * group mode    : cleans all cache in the group
     * notgroup mode : cleans all cache not in the group
     *
     * @param   string  $group  The cache data group
     * @param   string  $mode   The mode for cleaning cache [group|notgroup]
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    public function clean($group, $mode = null)
    {
        $groups = array((string) $group);

        if ($mode === 'notgroup') {
            $groups = array_diff((array) static::$_db->get($this->_hash . '-groups'), $groups);
        }

        $cleaned = true;

        foreach ($groups as $name) {
            $generation = bin2hex(random_bytes(6));

            if (!static::$_db->set($this->_hash . '-generation-' . $name, $generation, 0)) {
                $cleaned = false;

                continue;
            }

            static::$generations[$name] = array($generation, $this->_now);
            static::$_db->set($this->_hash . '-count-' . $name, 0, 0);
            static::$_db->set($this->_hash . '-size-' . $name, 0, 0);
        }

        return $cleaned;
    }

    /**
     * Flush the cache
     *
     * @return  boolean
     *
     * @since   3.6.3
     */
    public function flush()
    {
        if (!$this->lockindex()) {
            return false;
        }

        try {
            static::$generations = array();
            static::$knownGroups = array();

            return static::$_db->flush();
        } finally {
            $this->unlockindex();
        }
    }

    /**
     * Test to see if the storage handler is available.
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    public static function isSupported()
    {
        /*
         * GAE and HHVM have both had instances where Memcached the class was defined but no extension was loaded.
         * If the class is there, we can assume support.
         */
        return class_exists('Memcached');
    }

    /**
     * Lock cached item
     *
     * @param   string   $id        The cache data ID
     * @param   string   $group     The cache data group
     * @param   integer  $locktime  Cached item max lock time
     *
     * @return  mixed  Boolean false if locking failed or an object containing properties lock and locklooped
     *
     * @since   3.0.0
     */
    public function lock($id, $group, $locktime)
    {
        $returning = new \stdClass();
        $returning->locklooped = false;

        $looptime = $locktime * 10;

        $cache_id = $this->_getCacheId($id, $group);

        $data_lock = static::$_db->add($cache_id . '_lock', 1, $locktime);

        if ($data_lock === false) {
            $lock_counter = 0;

            // Loop until you find that the lock has been released.
            // That implies that data get from other thread has finished.
            while ($data_lock === false) {
                if ($lock_counter > $looptime) {
                    break;
                }

                // 10 ms between tries, so the wait (up to a tenth of the lock time: 1.5 s by default) can see the other request finish
                usleep(10000);
                $data_lock = static::$_db->add($cache_id . '_lock', 1, $locktime);
                $lock_counter++;
            }

            $returning->locklooped = true;
        }

        $returning->locked = $data_lock;

        return $returning;
    }

    /**
     * Unlock cached item
     *
     * @param   string  $id     The cache data ID
     * @param   string  $group  The cache data group
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    public function unlock($id, $group = null)
    {
        $cache_id = $this->_getCacheId($id, $group) . '_lock';
        return static::$_db->delete($cache_id);
    }

    /**
     * The generation of a group, part of its entries' keys. It's a random value, never a number counted up: if the server drops
     * it, a new one leaves the old entries out of reach instead of bringing old ones back.
     *
     * @param   string  $group  The cache data group
     *
     * @return  string
     *
     * @since   3.18.0
     */
    protected function getGeneration($group)
    {
        $group = (string) $group;

        // Read once a second at most: long-running processes must see another process' clean
        if (isset(static::$generations[$group]) && static::$generations[$group][1] === $this->_now) {
            return static::$generations[$group][0];
        }

        $key        = $this->_hash . '-generation-' . $group;
        $generation = static::$_db->get($key);

        if (!$generation) {
            static::$_db->add($key, bin2hex(random_bytes(6)), 0);
            $generation = static::$_db->get($key) ?: '0';
        }

        static::$generations[$group] = array($generation, $this->_now);

        return $generation;
    }

    /**
     * Keep a group on the list of groups, which the Clear Cache page reads. The list is written only for a new group, so the
     * lock is rarely taken; a group missed here is added by its next store.
     *
     * @param   string  $group  The cache data group
     *
     * @return  void
     *
     * @since   3.18.0
     */
    protected function addGroup($group)
    {
        $group = (string) $group;

        if (isset(static::$knownGroups[$group])) {
            return;
        }

        $key    = $this->_hash . '-groups';
        $groups = static::$_db->get($key);

        if (!is_array($groups) || !in_array($group, $groups, true)) {
            if (!$this->lockindex()) {
                return;
            }

            try {
                $groups = static::$_db->get($key);

                if (!is_array($groups)) {
                    $groups = array();

                    // The index of entries versions before 3.18 kept
                    static::$_db->delete($this->_hash . '-index');
                }

                if (!in_array($group, $groups, true)) {
                    $groups[] = $group;
                    static::$_db->set($key, $groups, 0);
                }
            } finally {
                $this->unlockindex();
            }
        }

        static::$knownGroups[$group] = true;
    }

    /**
     * Add to a counter, without a lock.
     *
     * @param   string   $key    The counter's key
     * @param   integer  $value  What to add
     *
     * @return  void
     *
     * @since   3.18.0
     */
    protected function addToCounter($key, $value)
    {
        if (static::$_db->increment($key, $value) === false && !static::$_db->add($key, $value, 0)) {
            static::$_db->increment($key, $value);
        }
    }

    /**
     * Lock cache index
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function lockindex()
    {
        $looptime  = 300;
        $data_lock = static::$_db->add($this->_hash . '-index_lock', 1, 30);

        if ($data_lock === false) {
            $lock_counter = 0;

            // Loop until you find that the lock has been released.  that implies that data get from other thread has finished
            while ($data_lock === false) {
                if ($lock_counter > $looptime) {
                    return false;
                }

                usleep(100);
                $data_lock = static::$_db->add($this->_hash . '-index_lock', 1, 30);
                $lock_counter++;
            }
        }

        return true;
    }

    /**
     * Unlock cache index
     *
     * @return  boolean
     *
     * @since   3.0.0
     */
    protected function unlockindex()
    {
        return static::$_db->delete($this->_hash . '-index_lock');
    }
}
