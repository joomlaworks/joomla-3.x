<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Editors-xtd.pdf
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * The editors' PDF button: the Media Manager's picker showing PDFs, which inserts {pdf images/folder/file.pdf}
 *
 * @since  3.18.0
 */
class PlgButtonPdf extends JPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.18.0
     */
    protected $autoloadLanguage = true;

    /**
     * The button, for users who may create or edit the content (as the Image button)
     *
     * @param   string   $name    The name of the editor field
     * @param   string   $asset   The asset being edited
     * @param   integer  $author  The id of the item's author
     *
     * @return  JObject|false
     *
     * @since   3.18.0
     */
    public function onDisplay($name, $asset, $author)
    {
        $app       = JFactory::getApplication();
        $user      = JFactory::getUser();
        $extension = $app->input->get('option');

        if ($extension === 'com_categories') {
            $parts     = explode('.', (string) $app->input->get('extension', 'com_content'));
            $extension = $parts[0];
        }

        $asset = $asset !== '' ? $asset : $extension;

        if (!$user->authorise('core.edit', $asset)
            && !$user->authorise('core.create', $asset)
            && !(count($user->getAuthorisedCategories($asset, 'core.create')) > 0)
            && !($user->authorise('core.edit.own', $asset) && $author === $user->id)
            && !(count($user->getAuthorisedCategories($extension, 'core.edit')) > 0)
            && !(count($user->getAuthorisedCategories($extension, 'core.edit.own')) > 0 && $author === $user->id)) {
            return false;
        }

        $button = new JObject();
        $button->modal   = true;
        $button->class   = 'btn';
        $button->link    = 'index.php?option=com_media&amp;view=images&amp;tmpl=component&amp;type=pdf&amp;e_name=' . $name . '&amp;asset=' . $asset . '&amp;author=' . $author;
        $button->text    = JText::_('PLG_EDITORS-XTD_PDF_BUTTON');
        $button->name    = 'pdf';
        $button->svg     = array('name' => 'file-earmark-pdf-fill', 'path' => 'plugins/editors-xtd/pdf/images');
        $button->options = "{handler: 'iframe', size: {x: Math.min(1400, window.innerWidth - 80), y: Math.min(900, window.innerHeight - 140)}}";

        return $button;
    }
}
