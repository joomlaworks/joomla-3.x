<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_admin
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 *
 * The "Page Cache has new options" post-installation message, for sites updating to 3.18 which use the page cache.
 */

defined('_JEXEC') or die;

/**
 * Shown while "System - Page Cache" is enabled.
 *
 * @return  boolean
 *
 * @since   3.18.0
 */
function admin_postinstall_pagecache_condition()
{
    return JPluginHelper::isEnabled('system', 'cache');
}

/**
 * Opens the plugin's options.
 *
 * @return  void
 *
 * @since   3.18.0
 */
function admin_postinstall_pagecache_action()
{
    $db = JFactory::getDbo();
    $id = (int) $db->setQuery(
        $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('cache'))
    )->loadResult();

    JFactory::getApplication()->redirect(
        JRoute::_($id ? 'index.php?option=com_plugins&task=plugin.edit&extension_id=' . $id . '&return=' . urlencode(base64_encode('index.php?option=com_postinstall&eid=700')) : 'index.php?option=com_plugins&view=plugins', false)
    );
}
