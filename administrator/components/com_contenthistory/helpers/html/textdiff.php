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
 * HTML utility class for creating text diffs using jQuery and media/com_contenthistory/js/contenthistory.js (diff-match-patch and jQuery.PrettyTextDiff).
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

        // Depends on jQuery UI
        JHtml::_('bootstrap.framework');
        JHtml::_('script', 'com_contenthistory/contenthistory.min.js', array('version' => 'auto', 'relative' => true));
        JHtml::_('stylesheet', 'com_contenthistory/contenthistory.css', array('version' => 'auto', 'relative' => true));

        // Attach diff to document
        JFactory::getDocument()->addScriptDeclaration(
            "
			(function ($){
				$(document).ready(function (){
 					$('#" . $containerId . " tr').prettyTextDiff();
 				});
			})(jQuery);
			"
        );

        // Set static array
        static::$loaded[__METHOD__] = true;

        return;
    }
}
