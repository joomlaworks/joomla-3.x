<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('RookwoodHelper', false) || require_once dirname(__DIR__) . '/helper.php';

/**
 * Module chrome of the page's sections: each module is a full-width section, its content centred (at most 1400px) unless the
 * class suffix holds "section-full" (heroes and bands draw their own width). The other classes of the suffix style it, e.g.
 * "tone-alt", "tone-accent", "tone-lime" (backgrounds) and "divider-wave" (a curved top edge). The title of a module showing
 * one category links to it.
 *
 * @param   object                     $module   The module
 * @param   \Joomla\Registry\Registry  $params   Its parameters
 * @param   array                      $attribs  The jdoc:include attributes
 *
 * @return  void
 *
 * @since   3.17.0
 */
function modChrome_rookwood($module, &$params, &$attribs)
{
	if ((string) $module->content === '')
	{
		return;
	}

	$suffix = trim((string) $params->get('moduleclass_sfx'));
	$full   = preg_match('/(^|\s)section-full(\s|$)/', $suffix);
	$link   = '';
	$catids = array_filter((array) $params->get('catid', array()));

	if ($module->module === 'mod_articles_category' && count($catids) === 1)
	{
		$link = JRoute::_(ContentHelperRoute::getCategoryRoute((int) reset($catids)));
	}

	echo '<section class="section ' . RookwoodHelper::e($suffix) . ' ' . str_replace('_', '-', $module->module) . '" id="module-' . (int) $module->id . '">';

	if (strpos($suffix, 'divider-wave') !== false)
	{
		echo '<svg class="sectionWave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0 80V30C240 70 480 70 720 40S1200 0 1440 30v50Z" fill="currentColor"/></svg>';
	}

	echo $full ? '' : '<div class="container">';

	if ($module->showtitle)
	{
		echo '<header class="sectionHeader"><h2 class="sectionTitle">' . RookwoodHelper::e($module->title) . '</h2>'
			. ($link ? '<a class="sectionMore" href="' . $link . '">' . JText::_('TPL_ROOKWOOD_VIEW_ALL') . RookwoodHelper::icon('arrow-right') . '</a>' : '') . '</header>';
	}

	echo '<div class="sectionContent">' . RookwoodHelper::lazyImages($module->content) . '</div>' . ($full ? '' : '</div>') . '</section>';
}

/**
 * Module chrome of the footer: a column with its title.
 *
 * @param   object                     $module   The module
 * @param   \Joomla\Registry\Registry  $params   Its parameters
 * @param   array                      $attribs  The jdoc:include attributes
 *
 * @return  void
 *
 * @since   3.17.0
 */
function modChrome_rookwoodfooter($module, &$params, &$attribs)
{
	if ((string) $module->content === '')
	{
		return;
	}

	echo '<div class="footerColumn ' . RookwoodHelper::e(trim((string) $params->get('moduleclass_sfx'))) . '">'
		. ($module->showtitle ? '<h2 class="footerTitle">' . RookwoodHelper::e($module->title) . '</h2>' : '')
		. RookwoodHelper::lazyImages($module->content) . '</div>';
}
