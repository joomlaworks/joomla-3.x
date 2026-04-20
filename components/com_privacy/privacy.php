<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_privacy
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$view = JFactory::getApplication()->input->get('view');

// Submitting information requests and confirmation through the frontend is restricted to authenticated users at this time
if (\in_array($view, array('confirm', 'request')) && JFactory::getUser()->guest) {
	JFactory::getApplication()->redirect(
		JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode('index.php?option=com_privacy&view=' . $view), false)
	);
}

$controller = JControllerLegacy::getInstance('Privacy');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
