<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\HTML;

use Joomla\CMS\Uri\Uri;

defined('JPATH_PLATFORM') or die;

/**
 * Files of the site that moved (movedmap.php, HTMLHelper::MOVED_FOLDERS and MOVED_FILES), found by their old path or address.
 *
 * @since  3.18.0
 */
abstract class MovedPaths
{
    /**
     * Old path => new path, longest first
     *
     * @var    array|null
     * @since  3.18.0
     */
    protected static $map;

    /**
     * Old path => new path, longest first (folders end in "/").
     *
     * @return  array
     *
     * @since   3.18.0
     */
    public static function getMap()
    {
        if (static::$map === null) {
            $map = (array) require __DIR__ . '/movedmap.php';

            foreach (HTMLHelper::MOVED_FOLDERS as $old => $new) {
                $map['media/' . $old . '/'] = 'media/' . $new . '/';
            }

            // Relative names: com_x/file is media/com_x/js/file.js (or .min.js)
            foreach (HTMLHelper::MOVED_FILES as $type => $files) {
                foreach ($files as $name => $new) {
                    list($extension, $file) = explode('/', $name, 2);

                    foreach (array('', '.min') as $suffix) {
                        $old = 'media/' . $extension . '/' . $type . '/' . $file . $suffix . '.' . $type;

                        if (!isset($map[$old])) {
                            $map[$old] = $new;
                        }
                    }
                }
            }

            uksort($map, function ($a, $b) {
                return strlen($b) - strlen($a);
            });

            static::$map = $map;
        }

        return static::$map;
    }

    /**
     * The new path of a moved file.
     *
     * @param   string  $path  The file's path from the site's root (media/editors/tinymce/tinymce.min.js)
     *
     * @return  string|null  Its new path, or null when it didn't move (or something is still at the old path)
     *
     * @since   3.18.0
     */
    public static function find($path)
    {
        $path = ltrim((string) $path, '/');

        if ($path === '' || strpos($path, '..') !== false) {
            return null;
        }

        foreach (static::getMap() as $old => $new) {
            $folder = substr($old, -1) === '/';

            if ($folder ? strpos($path, $old) !== 0 : $path !== $old) {
                continue;
            }

            $moved = $folder ? $new . substr($path, strlen($old)) : $new;

            if (is_file(JPATH_ROOT . '/' . $path) || !is_file(JPATH_ROOT . '/' . $moved)) {
                return null;
            }

            return $moved;
        }

        return null;
    }

    /**
     * The address of a moved file of this site, from its old address.
     *
     * @param   string  $url  An address (/folder/media/x.js, media/x.js, or the site's own https://host/folder/media/x.js)
     *
     * @return  string|null  Its new address, from the site's root path (/folder/plugins/x.js), with the old one's query; null
     *                       when it isn't a moved file of this site
     *
     * @since   3.18.0
     */
    public static function url($url)
    {
        $url = (string) $url;

        // Cheap: only addresses containing an old path's first part
        if (strpos($url, 'media/') === false) {
            return null;
        }

        $query = '';

        if (($pos = strcspn($url, '?#')) < strlen($url)) {
            $query = substr($url, $pos);
            $url   = substr($url, 0, $pos);
        }

        if (preg_match('#^(https?:)?//#i', $url)) {
            // The site's own address, whatever its scheme
            $site = preg_replace('#^https?:#i', '', rtrim(Uri::root(), '/') . '/');
            $url  = preg_replace('#^https?:#i', '', $url);

            if (stripos($url, $site) !== 0) {
                return null;
            }

            $path = substr($url, strlen($site));
        } elseif (substr($url, 0, 1) === '/') {
            $base = rtrim(Uri::root(true), '/') . '/';

            if (strpos($url, $base) !== 0) {
                return null;
            }

            $path = substr($url, strlen($base));
        } else {
            $path = $url;
        }

        $moved = static::find($path);

        return $moved === null ? null : rtrim(Uri::root(true), '/') . '/' . $moved . $query;
    }
}
