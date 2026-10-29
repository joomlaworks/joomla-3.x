<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Document;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;

/**
 * The pages a template may leave out (error.php, offline.php, component.php, index.php) and the module chromes every
 * template can use, which were the "system" templates' until 3.18: core's own in layouts/joomla/system/ (the
 * administrator's in its admin/ folder), which a template may override in html/layouts/joomla/system/
 *
 * @since  3.18.0
 */
abstract class SystemLayout
{
    /**
     * The folder of the system pages for the application: joomla/system or joomla/system/admin
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function folder()
    {
        try {
            $admin = Factory::getApplication()->isClient('administrator');
        } catch (\Exception $e) {
            $admin = false;
        }

        return 'joomla/system' . ($admin ? '/admin' : '');
    }

    /**
     * The folder holding a system page a template doesn't have: the template's override of the layout, else core's
     *
     * @param   string  $templatePath  The template's folder
     * @param   string  $file          error.php, offline.php, component.php or index.php
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function path($templatePath, $file)
    {
        $override = $templatePath . '/html/layouts/' . static::folder();

        if (is_file($override . '/' . $file)) {
            return $override;
        }

        return JPATH_ROOT . '/layouts/' . static::folder();
    }

    /**
     * The file of the module chromes every template can use (modChrome_none, _html5, _xhtml...)
     *
     * @param   integer  $clientId  0 site, 1 administrator; the application's when null
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function chromes($clientId = null)
    {
        $folder = $clientId === null ? static::folder() : 'joomla/system' . ((int) $clientId === 1 ? '/admin' : '');

        return JPATH_ROOT . '/layouts/' . $folder . '/html/modules.php';
    }
}
