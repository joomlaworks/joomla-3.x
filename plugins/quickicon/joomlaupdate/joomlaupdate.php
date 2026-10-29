<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Quickicon.Joomlaupdate
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
class PlgQuickiconJoomlaupdate extends JPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;

    /**
     * This method is called when the Quick Icons module is constructing its set
     * of icons. You can return an array which defines a single icon and it will
     * be rendered right after the stock Quick Icons.
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
        if ($context !== $this->params->get('context', 'mod_quickicon') || !JFactory::getUser()->authorise('core.manage', 'com_joomlaupdate')) {
            return;
        }

        // The check runs in js/joomlaupdate.js, from these options
        JHtml::_('behavior.core');
        JFactory::getDocument()->addScriptOptions('plg_quickicon_joomlaupdate', array(
            'url'     => JUri::base() . 'index.php?option=com_joomlaupdate',
            'ajaxUrl' => JUri::base() . 'index.php?option=com_joomlaupdate&task=update.ajax&' . JSession::getFormToken() . '=1',
            'version' => JVERSION,
            'text'    => array(
                'UPTODATE'            => JText::_('PLG_QUICKICON_JOOMLAUPDATE_UPTODATE'),
                'UPDATEFOUND'         => JText::_('PLG_QUICKICON_JOOMLAUPDATE_UPDATEFOUND'),
                'UPDATEFOUND_MESSAGE' => JText::_('PLG_QUICKICON_JOOMLAUPDATE_UPDATEFOUND_MESSAGE'),
                'UPDATEFOUND_BUTTON'  => JText::_('PLG_QUICKICON_JOOMLAUPDATE_UPDATEFOUND_BUTTON'),
                'ERROR'               => JText::_('PLG_QUICKICON_JOOMLAUPDATE_ERROR'),
            ),
        ));
        JHtml::_('script', 'plugins/quickicon/joomlaupdate/js/joomlaupdate.js', array('version' => 'auto'));

        return array(
            array(
                'link' => 'index.php?option=com_joomlaupdate',
                'image' => 'joomla',
                'icon' => 'header/icon-48-download.png',
                'text' => JText::_('PLG_QUICKICON_JOOMLAUPDATE_CHECKING'),
                'id' => 'plg_quickicon_joomlaupdate',
                'group' => 'MOD_QUICKICON_MAINTENANCE'
            )
        );
    }
}
