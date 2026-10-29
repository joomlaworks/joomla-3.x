<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Quickicon.Extensionupdate
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Joomla! update notification plugin
 *
 * @since  2.5
 */
class PlgQuickiconExtensionupdate extends JPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an icon definition for an icon which looks for extensions updates
     * via AJAX and displays a notification when such updates are found.
     *
     * @param   string  $context  The calling context
     *
     * @return  array  A list of icon definition associative arrays, consisting of the
     *                 keys link, image, text and access.
     *
     * @since   2.5
     */
    public function onGetIcons($context)
    {
        if ($context !== $this->params->get('context', 'mod_quickicon') || !JFactory::getUser()->authorise('core.manage', 'com_installer')) {
            return;
        }

        $token = JSession::getFormToken() . '=' . 1;

        // The check runs in js/extensionupdate.js, from these options
        JHtml::_('behavior.core');
        JFactory::getDocument()->addScriptOptions('plg_quickicon_extensionupdate', array(
            'url'     => JUri::base() . 'index.php?option=com_installer&view=update&task=update.find&' . $token,
            'ajaxUrl' => JUri::base() . 'index.php?option=com_installer&view=update&task=update.ajax&' . $token,
            'text'    => array(
                'UPTODATE'            => JText::_('PLG_QUICKICON_EXTENSIONUPDATE_UPTODATE'),
                'UPDATEFOUND'         => JText::_('PLG_QUICKICON_EXTENSIONUPDATE_UPDATEFOUND'),
                'UPDATEFOUND_MESSAGE' => JText::_('PLG_QUICKICON_EXTENSIONUPDATE_UPDATEFOUND_MESSAGE'),
                'UPDATEFOUND_BUTTON'  => JText::_('PLG_QUICKICON_EXTENSIONUPDATE_UPDATEFOUND_BUTTON'),
                'ERROR'               => JText::_('PLG_QUICKICON_EXTENSIONUPDATE_ERROR'),
            ),
        ));
        JHtml::_('script', 'plugins/quickicon/extensionupdate/js/extensionupdate.js', array('version' => 'auto'));

        return array(
            array(
                'link'  => 'index.php?option=com_installer&view=update&task=update.find&' . $token,
                'image' => 'asterisk',
                'icon'  => 'header/icon-48-extension.png',
                'text'  => JText::_('PLG_QUICKICON_EXTENSIONUPDATE_CHECKING'),
                'id'    => 'plg_quickicon_extensionupdate',
                'group' => 'MOD_QUICKICON_MAINTENANCE'
            )
        );
    }
}
