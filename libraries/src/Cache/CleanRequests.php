<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  Cache
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Cache;

defined('JPATH_PLATFORM') or die;

/**
 * Cleans of a cache kept in the web server's own memory (APCu, WinCache), asked for on the command line.
 *
 * PHP gives the command line a memory of its own, so it can't reach the web server's cache: it records what to clean in a
 * file of the site's cache folder, and the web server's storage handler applies what it hasn't applied yet when it starts.
 *
 * @since  3.18.0
 */
abstract class CleanRequests
{
    /**
     * The file's first line, so that the web server never shows it
     *
     * @var    string
     * @since  3.18.0
     */
    public const HEADER = "<?php exit; ?>\n";

    /**
     * Whether PHP runs on the command line (the built-in web server, cli-server, is a web server)
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public static function isCommandLine()
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * The file holding the requests: in the site's cache folder, which the site and the administrator share
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function getFile()
    {
        $folder = (string) \JFactory::getConfig()->get('cache_path', JPATH_SITE . '/cache');

        return rtrim($folder !== '' ? $folder : JPATH_SITE . '/cache', '\\/') . '/clean-requests.php';
    }

    /**
     * Record a clean, as CacheStorage::clean() takes it ("" in "notgroup" mode is the whole cache).
     *
     * @param   string  $group  The group
     * @param   string  $mode   group or notgroup
     *
     * @return  boolean  Whether it was recorded
     *
     * @since   3.18.0
     */
    public static function record($group, $mode = 'group')
    {
        $file   = static::getFile();
        $folder = dirname($file);

        if (!is_dir($folder) && !@mkdir($folder, 0755, true)) {
            return false;
        }

        $handle = @fopen($file, 'c+');

        if (!$handle) {
            return false;
        }

        $written = false;

        if (flock($handle, LOCK_EX)) {
            $requests = static::decode(stream_get_contents($handle));
            $key      = ($mode === 'notgroup' ? 'notgroup' : 'group') . ':' . $group;

            // The whole cache covers every request before it
            if ($key === 'notgroup:') {
                $requests = array();
            }

            $requests[$key] = microtime(true);

            $data    = static::HEADER . json_encode($requests);
            $written = ftruncate($handle, 0) && rewind($handle) && fwrite($handle, $data) === strlen($data) && fflush($handle);

            flock($handle, LOCK_UN);
        }

        fclose($handle);

        return $written;
    }

    /**
     * The requests made after a time, oldest first.
     *
     * @param   float  $since  The time of the last request applied (0 for all)
     *
     * @return  array  Arrays of group, mode and time
     *
     * @since   3.18.0
     */
    public static function since($since)
    {
        $data = @file_get_contents(static::getFile());

        if ($data === false) {
            return array();
        }

        $requests = array();

        foreach (static::decode($data) as $key => $time) {
            list($mode, $group) = explode(':', $key, 2);

            if ($time > $since) {
                $requests[] = array('group' => $group, 'mode' => $mode, 'time' => $time);
            }
        }

        usort($requests, function ($a, $b) {
            return $a['time'] < $b['time'] ? -1 : ($a['time'] > $b['time'] ? 1 : 0);
        });

        return $requests;
    }

    /**
     * The requests in the file's text
     *
     * @param   string|false  $data  The text
     *
     * @return  array  "mode:group" => time
     *
     * @since   3.18.0
     */
    protected static function decode($data)
    {
        $data = (string) $data;

        if (strpos($data, static::HEADER) === 0) {
            $data = substr($data, strlen(static::HEADER));
        }

        $requests = json_decode($data, true);

        if (!is_array($requests)) {
            return array();
        }

        return array_filter($requests, function ($time, $key) {
            return is_numeric($time) && preg_match('/^(group|notgroup):/', (string) $key);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
