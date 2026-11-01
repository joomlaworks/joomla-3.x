<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_templates
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * JHtml helper class.
 *
 * @since  1.6
 */
class JHtmlTemplates
{
    /**
     * Image types a template's thumbnail and preview may have, in the order they're looked for
     *
     * @var    array
     * @since  3.18.0
     */
    public const IMAGE_TYPES = array('webp', 'avif', 'png', 'jpg', 'jpeg', 'gif');

    /**
     * Display the thumb for the template.
     *
     * @param   string   $template  The name of the template.
     * @param   integer  $clientId  The application client ID the template applies to
     *
     * @return  string  The html string
     *
     * @since   1.6
     */
    public static function thumb($template, $clientId = 0)
    {
        $client   = JApplicationHelper::getClientInfo($clientId);
        $basePath = $client->path . '/templates/' . $template;
        $baseUrl  = JUri::root(true) . ($clientId == 0 ? '' : '/administrator') . '/templates/' . $template;

        $thumbnail = static::findImage($basePath, 'template_thumbnail');

        if ($thumbnail === null) {
            return '';
        }

        $clientPath = ($clientId == 0) ? '' : 'administrator/';

        // A width only: thumbnails may be landscape (206x150, as stock Joomla's) or square, and drawn at twice their size
        $html = JHtml::_('image', $clientPath . 'templates/' . $template . '/' . $thumbnail, JText::_('COM_TEMPLATES_PREVIEW'), array('width' => 200));

        $preview = static::findImage($basePath, 'template_preview');

        if ($preview === null) {
            return $html;
        }

        // The preview opens in the CMS's modal, fitted to the window's width and scrolling when taller (js/templates.js)
        JHtml::_('behavior.cms');
        JHtml::_('script', 'administrator/components/com_templates/js/templates.min.js', array('version' => 'auto', 'position' => 'footer'), array('defer' => true));

        return '<button type="button" class="thumbnail pull-left hasTooltip" data-template-preview="'
            . htmlspecialchars($baseUrl . '/' . $preview, ENT_QUOTES, 'UTF-8') . '" data-template-name="'
            . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '" title="' . JHtml::_('tooltipText', 'COM_TEMPLATES_CLICK_TO_ENLARGE') . '">'
            . $html . '</button>';
    }

    /**
     * The file name of a template's image, whatever its type (IMAGE_TYPES; the extension in any case).
     *
     * @param   string  $basePath  The template's folder
     * @param   string  $name      The image's name without its extension (template_thumbnail, template_preview)
     *
     * @return  string|null  The file name, or null when the template has none
     *
     * @since   3.18.0
     */
    protected static function findImage($basePath, $name)
    {
        $files = is_dir($basePath) ? scandir($basePath) : false;

        if ($files === false) {
            return null;
        }

        $found = array();

        foreach ($files as $file) {
            $dot = strrpos($file, '.');

            if ($dot !== false && substr($file, 0, $dot) === $name && is_file($basePath . '/' . $file)) {
                $found[strtolower(substr($file, $dot + 1))] = $file;
            }
        }

        foreach (static::IMAGE_TYPES as $type) {
            if (isset($found[$type])) {
                return $found[$type];
            }
        }

        return null;
    }

    /**
     * Renders the html for the modal linked to thumb.
     *
     * @param   string   $template  The name of the template.
     * @param   integer  $clientId  The application client ID the template applies to
     *
     * @return  string  The html string: nothing since 3.18, as the preview opens in the CMS's modal (thumb())
     *
     * @since   3.4
     */
    public static function thumbModal($template, $clientId = 0)
    {
        return '';
    }
}
