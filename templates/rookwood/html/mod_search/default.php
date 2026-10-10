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
class_exists('RookwoodHelper', false) || require_once dirname(__DIR__, 2) . '/helper.php';

/** @var Joomla\Registry\Registry $params */
?>
<form class="searchForm" action="<?php echo JRoute::_('index.php'); ?>" method="post" role="search">
	<label for="mod-search-<?php echo (int) $module->id; ?>" class="visuallyHidden"><?php echo RookwoodHelper::e($label); ?></label>
	<input type="search" name="searchword" id="mod-search-<?php echo (int) $module->id; ?>" maxlength="<?php echo (int) $maxlength; ?>" placeholder="<?php echo RookwoodHelper::e($text); ?>" />
	<button type="submit" aria-label="<?php echo RookwoodHelper::e($button_text ?: JText::_('TPL_ROOKWOOD_SEARCH')); ?>"><?php echo RookwoodHelper::icon('search'); ?></button>
	<input type="hidden" name="task" value="search" />
	<input type="hidden" name="option" value="com_search" />
	<input type="hidden" name="Itemid" value="<?php echo (int) $mitemid; ?>" />
</form>
