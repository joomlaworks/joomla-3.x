<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_menus
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Load the required admin language files
$lang   = JFactory::getLanguage();
$app    = JFactory::getApplication();

// The site only serves the administrator's list of menu items for picking one (front-end editing), for any menu but the
// administrator's own (main), whose items the list shows without a check per menu
$task     = (string) $app->input->getCmd('task', '');
$menuType = $app->input->get('menutype', '', 'string');

if (
    $app->input->get('view') !== 'items'
    || $app->input->get('layout') !== 'modal'
    || !in_array($task, array('', 'display'), true)
    || (is_string($menuType) && strcasecmp(trim($menuType), 'main') === 0)
    || !JFactory::getUser()->authorise('core.create', 'com_menus')
) {
    throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

$lang->load('com_menus', JPATH_ADMINISTRATOR);

// Trigger the controller
$controller = JControllerLegacy::getInstance('Menus');
$controller->execute($app->input->get('task'));
$controller->redirect();
