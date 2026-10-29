<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_contenthistory
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/**
 * HTML utility class for creating text diffs with media/com_contenthistory/js/contenthistory.js (diff-match-patch).
 *
 * @since       3.2
 *
 * @deprecated  4.0 No replacement
 */
abstract class JHtmlTextdiff
{
    /**
     * @var    array  Array containing information for loaded files
     * @since  3.2
     */
    protected static $loaded = array();

    /**
     * Method to load Javascript text diff
     *
     * @param   string  $containerId  DOM id of the element where the diff will be rendered
     *
     * @return  void
     *
     * @since   3.2
     */
    public static function textdiff($containerId)
    {
        // Only load once
        if (isset(static::$loaded[__METHOD__])) {
            return;
        }

        // The rows of the table are compared by media/com_contenthistory/js/contenthistory.js (diff-match-patch)
        JHtml::_('behavior.core');
        JHtml::_('script', 'com_contenthistory/contenthistory.min.js', array('version' => 'auto', 'relative' => true));
        JHtml::_('stylesheet', 'com_contenthistory/contenthistory.css', array('version' => 'auto', 'relative' => true));
        JFactory::getDocument()->addScriptOptions('com_contenthistory.diff', array('container' => $containerId));

        // Set static array
        static::$loaded[__METHOD__] = true;

        return;
    }
}
