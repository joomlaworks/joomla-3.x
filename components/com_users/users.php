<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('UsersHelperRoute', JPATH_COMPONENT . '/helpers/route.php');

// Check access
$app    = JFactory::getApplication();
$user   = JFactory::getUser();
$view = $app->input->get('view');

// Do any specific processing by view.
switch ($view) {
	case 'registration':
		// If the user is already logged in, redirect to the profile page.
		if ($user->get('guest') != 1) {
			// Redirect to profile page.
			$app->redirect(JRoute::_('index.php?option=com_users&view=profile', false));
		}

		// Check if user registration is enabled
		if (JComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0) {
			// Registration is disabled - Redirect to login page.
			$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
		}
		break;

		// Handle view specific models.
	case 'profile':
		if ($user->get('guest') == 1) {
			// Redirect to login page.
			$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
		}
		break;

	case 'remind':
	case 'reset':
		if ($user->get('guest') != 1) {
			// Redirect to profile page.
			$app->redirect(JRoute::_('index.php?option=com_users&view=profile', false));
		}
}

$controller = JControllerLegacy::getInstance('Users');
$controller->execute(JFactory::getApplication()->input->get('task', 'display'));
$controller->redirect();
