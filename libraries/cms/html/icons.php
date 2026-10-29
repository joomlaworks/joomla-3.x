<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class for icons.
 *
 * @since  2.5
 */
abstract class JHtmlIcons
{
    /**
     * Method to generate html code for a list of buttons
     *
     * @param   array  $buttons  Array of buttons
     *
     * @return  string
     *
     * @since   2.5
     */
    public static function buttons($buttons)
    {
        $html = array();

        foreach ($buttons as $button) {
            $html[] = JHtml::_('icons.button', $button);
        }

        return implode($html);
    }

    /**
     * Method to generate html code for a list of buttons
     *
     * @param   array  $button  Button properties
     *
     * @return  string
     *
     * @since   2.5
     */
    public static function button($button)
    {
        if (isset($button['access'])) {
            if (is_bool($button['access'])) {
                if ($button['access'] == false) {
                    return '';
                }
            } else {
                // Get the user object to verify permissions
                $user = JFactory::getUser();

                // Take each pair of permission, context values.
                for ($i = 0, $n = count($button['access']); $i < $n; $i += 2) {
                    if (!$user->authorise($button['access'][$i], $button['access'][$i + 1])) {
                        return '';
                    }
                }
            }
        }

        // Instantiate a new JLayoutFile instance and render the layout
        $layout = new JLayoutFile('joomla.quickicons.icon');

        return $layout->render($button);
    }

    /**
     * An icon (one SVG file of Bootstrap Icons, as docs/icons/build.py copies them) written into the page, so it's drawn in
     * the text colour
     *
     * @param   string  $name     The icon's name, as in Bootstrap Icons
     * @param   string  $label    Its text for screen readers; '' for an icon next to text saying the same
     * @param   array   $options  path (its folder from the site's root, default media/system/images/icons), size (16), class,
     *                            style (a colour that means something: 'color:#f26522'), legacy: the old image it replaces
     *                            (system/rating_star.png), drawn instead when the template has its own copy of it
     *
     * @return  string  Nothing when the icon doesn't exist
     *
     * @since   3.18.0
     */
    public static function svg($name, $label = '', array $options = array())
    {
        static $files = array();

        if (!empty($options['legacy'])) {
            $own = JHtml::_('image', $options['legacy'], '', null, true, true);

            if ($own && strpos($own, '/templates/') !== false) {
                return JHtml::_('image', $options['legacy'], htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'), null, true);
            }
        }

        $path = isset($options['path']) ? trim((string) $options['path'], '/') : 'media/system/images/icons';
        $file = JPATH_ROOT . '/' . $path . '/' . preg_replace('/[^a-z0-9-]/', '', strtolower((string) $name)) . '.svg';

        if (!isset($files[$file])) {
            $files[$file] = is_file($file) ? (string) file_get_contents($file) : '';
        }

        if (!preg_match('#<svg\b([^>]*)>(.*)</svg>#s', $files[$file], $svg)) {
            return '';
        }

        $viewBox = preg_match('#viewBox="([^"]*)"#', $svg[1], $match) ? $match[1] : '0 0 16 16';
        $size    = isset($options['size']) ? (int) $options['size'] : 16;
        $class   = 'cms-icon cms-icon-' . $name . (empty($options['class']) ? '' : ' ' . $options['class']);
        $label   = (string) $label === ''
            ? ' aria-hidden="true" focusable="false"'
            : ' role="img" aria-label="' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '"';
        $style   = empty($options['style']) ? '' : ' style="' . htmlspecialchars((string) $options['style'], ENT_QUOTES, 'UTF-8') . '"';

        return '<svg xmlns="http://www.w3.org/2000/svg" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" width="' . $size
            . '" height="' . $size . '" viewBox="' . htmlspecialchars($viewBox, ENT_QUOTES, 'UTF-8') . '" fill="currentColor"' . $label . $style . '>'
            . preg_replace('#>\s+<#', '><', trim($svg[2])) . '</svg>';
    }
}
