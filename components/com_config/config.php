<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_config
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Load classes
JLoader::registerPrefix('Config', JPATH_COMPONENT);

// Application
$app = JFactory::getApplication();

// Tell the browser not to cache this page.
$app->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT', true);

$controllerHelper = new ConfigControllerHelper;
$controller = $controllerHelper->parseController($app);

// A task without a controller (e.g. mistyped) is a missing page, not a fatal error
if (!$controller)
{
	throw new Exception(JText::sprintf('JLIB_APPLICATION_ERROR_TASK_NOT_FOUND', $app->input->get('task', $app->input->get('controller'))), 404);
}

$controller->prefix = 'Config';

// Perform the Request task
$controller->execute();
