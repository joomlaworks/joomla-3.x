<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once dirname(__DIR__) . '/helper.php';

/**
 * Module chrome: a moduleContainer with the module's class suffix (e.g. grid-col-span-8), and its title. The title of a
 * module showing one category links to that category.
 *
 * @param   object                     $module   The module
 * @param   \Joomla\Registry\Registry  $params   Its parameters
 * @param   array                      $attribs  The jdoc:include attributes
 *
 * @return  void
 *
 * @since   3.17.0
 */
function modChrome_hammond($module, &$params, &$attribs)
{
	if ((string) $module->content === '')
	{
		return;
	}

	$suffix = trim((string) $params->get('moduleclass_sfx'));
	$title  = HammondHelper::e($module->title);
	$link   = '';
	$catids = array_filter((array) $params->get('catid', array()));

	if ($module->module === 'mod_articles_category' && count($catids) === 1)
	{
		$link = JRoute::_(ContentHelperRoute::getCategoryRoute((int) reset($catids)));
	}

	echo '<section class="moduleContainer ' . HammondHelper::e($suffix) . ' ' . str_replace('_', '-', $module->module) . '" id="module-' . (int) $module->id . '">';

	if ($module->showtitle)
	{
		echo '<header class="moduleHeader"><h2 class="moduleTitle">' . ($link ? '<a href="' . $link . '">' . $title . '</a>' : $title) . '</h2>'
			. ($link ? '<a class="moduleMore" href="' . $link . '">' . JText::_('TPL_HAMMOND_MORE') . HammondHelper::icon('arrow-right') . '</a>' : '') . '</header>';
	}

	echo '<div class="moduleContent">' . $module->content . '</div></section>';
}
