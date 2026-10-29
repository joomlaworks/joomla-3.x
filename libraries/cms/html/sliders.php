<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class for Sliders elements
 *
 * @since       1.6
 * @deprecated  3.7.0 These helpers are dependent on the deprecated MooTools support
 */
abstract class JHtmlSliders
{
    /**
     * Creates a panes and loads the javascript behavior for it.
     *
     * @param   string  $group   The pane identifier.
     * @param   array   $params  An array of options.
     *
     * @return  string
     *
     * @since   1.6
     * @deprecated  3.7.0 These helpers are dependent on the deprecated MooTools support
     */
    public static function start($group = 'sliders', $params = array())
    {
        static::loadBehavior($group, $params);

        return '<div id="' . $group . '" class="pane-sliders"><div style="display:none;"><div>';
    }

    /**
     * Close the current pane.
     *
     * @return  string  hTML to close the pane
     *
     * @since   1.6
     * @deprecated  3.7.0 These helpers are dependent on the deprecated MooTools support
     */
    public static function end()
    {
        return '</div></div></div>';
    }

    /**
     * Begins the display of a new panel.
     *
     * @param   string  $text  Text to display.
     * @param   string  $id    Identifier of the panel.
     *
     * @return  string  HTML to start a panel
     *
     * @since   1.6
     * @deprecated  3.7.0 These helpers are dependent on the deprecated MooTools support
     */
    public static function panel($text, $id)
    {
        return '</div></div><div class="panel"><h3 class="pane-toggler title" id="' . $id . '"><a href="javascript:void(0);"><span>' . $text
            . '</span></a></h3><div class="pane-slider content">';
    }

    /**
     * Load the JavaScript behavior.
     *
     * @param   string  $group   The pane identifier.
     * @param   array   $params  Array of options.
     *
     * @return  void
     *
     * @since   1.6
     * @deprecated  3.7.0 These helpers are dependent on the deprecated MooTools support
     */
    protected static function loadBehavior($group, $params = array())
    {
        static $loaded = array();

        if (!array_key_exists($group, $loaded)) {
            // Get the JInput object
            $input = JFactory::getApplication()->input;

            $loaded[$group] = true;

            // CMS.sliders (global.js), what MooTools' Fx.Accordion was here
            JHtml::_('behavior.cms');

            $display = (isset($params['startOffset']) && isset($params['startTransition']) && $params['startTransition'])
                ? (int) $params['startOffset'] : null;
            $show = (isset($params['startOffset']) && !(isset($params['startTransition']) && $params['startTransition']))
                ? (int) $params['startOffset'] : null;

            // The open pane is remembered in a cookie (-1: all closed), read back when useCookie is set
            $opt['cookie'] = 'jpanesliders_' . $group;
            $opt['duration'] = isset($params['duration']) ? (int) $params['duration'] : 300;
            $opt['display'] = (isset($params['useCookie']) && $params['useCookie']) ? $input->cookie->get('jpanesliders_' . $group, $display, 'integer')
                : $display;
            $opt['show'] = (isset($params['useCookie']) && $params['useCookie']) ? $input->cookie->get('jpanesliders_' . $group, $show, 'integer') : $show;
            $opt['opacity'] = isset($params['opacityTransition']) && $params['opacityTransition'];
            $opt['alwaysHide'] = !(isset($params['allowAllClose']) && !$params['allowAllClose']);

            $options = JHtml::getJSObject($opt);
            $panes   = 'div#' . $group . '.pane-sliders > .panel > ';

            $js = 'CMS.ready(function () { CMS.sliders.create(' . json_encode($panes . 'h3.pane-toggler', JSON_HEX_TAG | JSON_HEX_AMP) . ', '
                . json_encode($panes . 'div.pane-slider', JSON_HEX_TAG | JSON_HEX_AMP) . ', ' . $options . '); });';

            JFactory::getDocument()->addScriptDeclaration($js);
        }
    }
}
