<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_sampledata
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Installing a set replaces the one installed before and changes the site's template: Super Users only
if (!JFactory::getUser()->authorise('core.admin'))
{
	return;
}

// Include dependencies.
JLoader::register('ModSampledataHelper', __DIR__ . '/helper.php');

$items = ModSampledataHelper::getList();

// Filter out empty entries
$items = array_filter($items);

require JModuleHelper::getLayoutPath('mod_sampledata', $params->get('layout', 'default'));
