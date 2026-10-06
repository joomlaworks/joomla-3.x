<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('FinchHelper', false) || require_once dirname(__DIR__) . '/helper.php';

/**
 * Module chrome: a section with the module's class suffix and its title.
 *
 * @param   object                     $module   The module
 * @param   \Joomla\Registry\Registry  $params   Its parameters
 * @param   array                      $attribs  The jdoc:include attributes
 *
 * @return  void
 *
 * @since   3.17.0
 */
function modChrome_finch($module, &$params, &$attribs)
{
	if ((string) $module->content === '')
	{
		return;
	}

	echo '<section class="moduleContainer ' . FinchHelper::e(trim((string) $params->get('moduleclass_sfx'))) . ' ' . str_replace('_', '-', $module->module)
		. '" id="module-' . (int) $module->id . '">';

	if ($module->showtitle)
	{
		echo '<h2 class="moduleTitle">' . FinchHelper::e($module->title) . '</h2>';
	}

	echo '<div class="moduleContent">' . FinchHelper::lazyImages($module->content) . '</div></section>';
}
