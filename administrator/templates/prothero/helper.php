<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.prothero
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

if (!function_exists('protheroScheme')) {
    /**
     * The Colour Scheme option, for the data-scheme attribute of <html>: light, dark or auto (follows the device).
     *
     * @param   mixed  $params  The template style's options (a Registry), or null
     *
     * @return  string
     */
    function protheroScheme($params)
    {
        // The error page calls it too, when something may have failed already: never let the option break that page
        try {
            $scheme = is_object($params) ? (string) $params->get('colorScheme', 'auto') : 'auto';
        } catch (Exception $e) {
            $scheme = 'auto';
        }

        return in_array($scheme, array('light', 'dark'), true) ? $scheme : 'auto';
    }
}

if (!function_exists('protheroNavbarColor')) {
    /**
     * The Menu Bar Colour option, for the data-navbar attribute of <html> (css/template.css has a colour for each; black has none).
     *
     * @param   mixed  $params  The template style's options (a Registry), or null
     *
     * @return  string
     */
    function protheroNavbarColor($params)
    {
        try {
            $color = is_object($params) ? (string) $params->get('navbarColor', 'black') : 'black';
        } catch (Exception $e) {
            $color = 'black';
        }

        return in_array($color, array('graphite', 'navy', 'blue', 'indigo', 'purple', 'wine', 'teal', 'forest'), true) ? $color : 'black';
    }
}

if (!function_exists('protheroLogo')) {
    /**
     * The Joomla! logo inline, its wordmark in the text colour, so it follows the colour scheme; the mark keeps Joomla's colours.
     *
     * @return  string  The SVG markup
     */
    function protheroLogo()
    {
        $logo = (string) @file_get_contents(__DIR__ . '/images/joomla-logo.svg');

        return trim(str_replace(array('fill="#3b3a40"', '<svg '), array('fill="currentColor"', '<svg role="img" aria-label="Joomla!" focusable="false" '), $logo));
    }
}

if (!function_exists('protheroDropConditionalAssets')) {
    /**
     * No legacy markup: leave out the scripts and stylesheets for old Internet Explorer versions (conditional comments, e.g. the
     * HTML5 fallback and polyfills of forms, or the keepalive's event polyfill). Done when the head is built, so assets added after
     * the page file runs are covered too.
     *
     * @param   JDocument  $document  The page
     *
     * @return  void
     */
    function protheroDropConditionalAssets($document)
    {
        JEventDispatcher::getInstance()->register('onBeforeCompileHead', function () use ($document) {
            if (!$document instanceof JDocumentHtml) {
                return;
            }

            foreach (array('_scripts', '_styleSheets') as $list) {
                foreach ($document->$list as $url => $asset) {
                    if (!empty($asset['options']['conditional']) || !empty($asset['conditional'])) {
                        unset($document->{$list}[$url]);
                    }
                }
            }
        });
    }
}
