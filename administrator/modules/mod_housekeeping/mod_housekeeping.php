<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_housekeeping
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('ModHousekeepingHelper', __DIR__ . '/helper.php');

// Clean Cache for everyone in the administrator; Clean Everything and Global Check-in for those who manage the cache and check-in
$canClean = ModHousekeepingHelper::canCleanCache();
$isAdmin  = ModHousekeepingHelper::isAdmin();

if (!$canClean && !$isAdmin)
{
	return;
}

require JModuleHelper::getLayoutPath('mod_housekeeping', $params->get('layout', 'default'));
