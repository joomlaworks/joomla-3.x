<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Cache\Controller;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheController;

/**
 * Joomla! Cache page type object
 *
 * @since  1.7.0
 */
class PageController extends CacheController
{
    /**
     * ID property for the cache page object.
     *
     * @var    integer
     * @since  1.7.0
     */
    protected $_id;

    /**
     * Cache group
     *
     * @var    string
     * @since  1.7.0
     */
    protected $_group;

    /**
     * Cache lock test
     *
     * @var    \stdClass
     * @since  1.7.0
     */
    protected $_locktest = null;

    /**
     * The response headers set before the cache looked for the page (null until it missed)
     *
     * @var    array|null
     * @since  3.18.0
     */
    protected $_headersBefore = null;

    /**
     * How long, in seconds, the page being made stays fresh (stored with it); null: as long as it's stored (the cache
     * lifetime), as before
     *
     * @var    integer|null
     * @since  3.18.0
     */
    protected $_fresh = null;

    /**
     * How long, in seconds, a page may still be served after it's no longer fresh: to other visitors while one request makes
     * a fresh copy, and instead of an error page when making it fails. 0: never
     *
     * @var    integer
     * @since  3.18.0
     */
    protected $_stale = 0;

    /**
     * The stale copy of the page this request is making again (see getStaleCopy())
     *
     * @var    array|null
     * @since  3.18.0
     */
    protected $_staleCopy = null;

    /**
     * When the page served from the cache was stored (a timestamp; null when unknown, e.g. stored before 3.18)
     *
     * @var    integer|null
     * @since  3.18.0
     */
    protected $_storedAt = null;

    /**
     * A refresh marker older than this many seconds belongs to a request which never finished
     *
     * @var    integer
     * @since  3.18.0
     */
    public const REFRESH_TIMEOUT = 30;

    /**
     * Sets how long the page being made stays fresh.
     *
     * @param   integer|null  $seconds  Seconds; null for the cache lifetime
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function setFreshTime($seconds)
    {
        $this->_fresh = $seconds === null ? null : max(1, (int) $seconds);
    }

    /**
     * Sets how long stale pages may still be served.
     *
     * @param   integer  $seconds  Seconds; 0 for never
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function setStaleTime($seconds)
    {
        $this->_stale = max(0, (int) $seconds);
    }

    /**
     * The page this request is making again, as it was stored (the data getWorkarounds() takes), while it may still be served:
     * what to send instead of an error page if making it fails. Null when there's none.
     *
     * @return  array|null
     *
     * @since   3.18.0
     */
    public function getStaleCopy()
    {
        return $this->_staleCopy;
    }

    /**
     * When the page get() returned from the cache was stored: what its Age header is counted from.
     *
     * @return  integer|null  A timestamp; null when unknown
     *
     * @since   3.18.0
     */
    public function getStoredTime()
    {
        return $this->_storedAt;
    }

    /**
     * Get the cached page data
     *
     * @param   boolean  $id     The cache data ID
     * @param   string   $group  The cache data group
     *
     * @return  mixed  Boolean false on no result, cached object otherwise
     *
     * @since   1.7.0
     */
    public function get($id = false, $group = 'page')
    {
        // If an id is not given, generate it from the request
        if (!$id) {
            $id = $this->_makeId();
        }

        // If the etag matches the page id ... set a no change header and exit : utilize browser cache. Only for the requests
        // the page cache serves (a guest's GET or HEAD, with caching on): the ETag names the address, not what a logged-in user
        // or a POST would get, so they were answered 304 with the guest copy, a POST without being handled
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

        if (!headers_sent() && isset($_SERVER['HTTP_IF_NONE_MATCH']) && in_array($method, array('GET', 'HEAD'), true) && $this->cache->getCaching()) {
            // The ETag is sent quoted (see _setEtag()), and browsers send it back so: "id", W/"id", or several, comma-separated
            $etags = array_map(
                function ($etag) {
                    return trim(preg_replace('#^W/#', '', trim($etag)), '"');
                },
                explode(',', stripslashes((string) $_SERVER['HTTP_IF_NONE_MATCH']))
            );

            if (in_array($id, $etags, true)) {
                $browserCache = isset($this->options['browsercache']) ? $this->options['browsercache'] : false;

                if ($browserCache) {
                    $this->_noChange();
                }
            }
        }

        // We got a cache hit... set the etag header and echo the page data
        $data = $this->getStored($id, $group);

        $this->_locktest = (object) array('locked' => null, 'locklooped' => null);
        $refreshing      = false;

        /*
         * A page past its fresh time: within the stale time one request makes it again (marked with a refresh entry, and
         * holding the page's lock) and the others get the stale copy meanwhile, at once, never waiting for the lock; past it,
         * it's a miss
         */
        if ($data !== false && is_array($data[0]) && isset($data[0]['expires']) && $data[0]['expires'] < time()) {
            if ($this->_stale > 0 && $data[0]['expires'] + $this->_stale >= time()) {
                if (!$this->isRefreshing($id, $group)) {
                    $this->cache->store((string) time(), $id . '_refresh', $group);

                    // A brief try (about 0.1 s): a request arriving at the same moment may hold it already
                    $lock = $this->cache->lock($id, $group, 1);

                    if ($lock->locked === true) {
                        $this->_locktest  = $lock;
                        $this->_staleCopy = $data[0];
                        $refreshing       = true;
                        $data             = false;
                    }
                }
            } else {
                $data = false;
            }
        }

        if ($data === false && !$refreshing) {
            $this->_locktest = $this->cache->lock($id, $group);

            // If locklooped is true try to get the cached data again; it could exist now.
            if ($this->_locktest->locked === true && $this->_locktest->locklooped === true) {
                $data = $this->getStored($id, $group);
            }
        }

        if ($data !== false) {
            if ($this->_locktest->locked === true) {
                $this->cache->unlock($id, $group);
            }

            $data            = $data[0];
            $this->_storedAt = is_array($data) && isset($data['stored']) ? (int) $data['stored'] : null;
            $data            = Cache::getWorkarounds($data);

            $this->_setEtag($id);

            return $data;
        }

        // Set ID and group placeholders
        $this->_id    = $id;
        $this->_group = $group;

        $this->_headersBefore = \JFactory::getApplication()->getHeaders();

        return false;
    }

    /**
     * Stop the cache buffer and store the cached data
     *
     * @param   mixed    $data        The data to store
     * @param   string   $id          The cache data ID
     * @param   string   $group       The cache data group
     * @param   boolean  $wrkarounds  True to use workarounds
     *
     * @return  boolean
     *
     * @since   1.7.0
     */
    public function store($data, $id, $group = null, $wrkarounds = true)
    {
        if ($this->_locktest->locked === false && $this->_locktest->locklooped === true) {
            // We can not store data because another process is in the middle of saving
            return false;
        }

        // Get page data from the application object
        if (!$data) {
            $data = \JFactory::getApplication()->getBody();

            // Only attempt to store if page data exists.
            if (!$data) {
                return false;
            }
        }

        // Get id and group and reset the placeholders
        if (!$id) {
            $id = $this->_id;
        }

        if (!$group) {
            $group = $this->_group;
        }

        if ($wrkarounds) {
            $data = Cache::setWorkarounds(
                $data,
                array(
                    'nopathway' => 1,
                    'nohead'    => 1,
                    'nomodules' => 1,
                    'headers'   => true,
                )
            );

            /*
             * Only the headers set after the cache looked for the page: what ran before it (e.g. a security plugin's headers)
             * runs on every request, cached or not, so a stored copy would repeat those which may appear more than once, and
             * keep sending those it stopped sending
             */
            if (isset($data['headers']) && is_array($this->_headersBefore)) {
                $headers = array();

                foreach ($data['headers'] as $header) {
                    if (!in_array($header, $this->_headersBefore, true)) {
                        $headers[] = $header;
                    }
                }

                $data['headers'] = $headers;
            }

            $data['stored'] = time();

            if ($this->_fresh !== null) {
                $data['expires'] = $data['stored'] + $this->_fresh;
            }
        }

        $result = $this->cache->store(serialize($data), $id, $group);

        if ($this->_locktest->locked === true) {
            $this->cache->unlock($id, $group);
        }

        if ($this->_staleCopy !== null) {
            $this->cache->remove($id . '_refresh', $group);
        }

        return $result;
    }

    /**
     * Whether another request is making the page again (a refresh entry less than REFRESH_TIMEOUT seconds old).
     *
     * @param   string  $id     The cache data ID
     * @param   string  $group  The cache data group
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    protected function isRefreshing($id, $group)
    {
        $started = $this->cache->get($id . '_refresh', $group);

        return $started !== false && (int) $started > time() - static::REFRESH_TIMEOUT;
    }

    /**
     * Generate a page cache id
     *
     * @return  string  MD5 Hash
     *
     * @since   1.7.0
     * @todo    Discuss whether this should be coupled to a data hash or a request hash ... perhaps hashed with a serialized request
     */
    protected function _makeId()
    {
        return Cache::makeId();
    }

    /**
     * There is no change in page data so send an unmodified header and die gracefully
     *
     * @return  void
     *
     * @since   1.7.0
     */
    protected function _noChange()
    {
        $app = \JFactory::getApplication();

        // Send not modified header and exit gracefully
        $app->setHeader('Status', 304, true);
        $app->sendHeaders();
        $app->close();
    }

    /**
     * Set the ETag header in the response
     *
     * @param   string  $etag  The entity tag (etag) to set
     *
     * @return  void
     *
     * @since   1.7.0
     */
    protected function _setEtag($etag)
    {
        \JFactory::getApplication()->setHeader('ETag', '"' . $etag . '"', true);
    }
}
