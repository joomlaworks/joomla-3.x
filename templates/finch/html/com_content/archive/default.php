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
class_exists('FinchHelper', false) || require_once dirname(__DIR__, 3) . '/helper.php';

// Unlike the stock layout, no Chosen (jQuery) for the selects: plain fields, styled by the template
?>
<div class="archive<?php echo $this->pageclass_sfx; ?>">
	<header class="listHeader">
		<p class="listKicker"><?php echo JText::_('TPL_FINCH_ARCHIVE'); ?></p>
		<h1 class="listTitle"><?php echo $this->escape($this->params->get('page_heading') ?: JText::_('TPL_FINCH_ARCHIVE')); ?></h1>
	</header>

	<form id="adminForm" action="<?php echo JRoute::_('index.php'); ?>" method="post">
		<div class="archiveFilters">
			<?php if ($this->params->get('filter_field') !== 'hide') : ?>
			<label class="visuallyHidden" for="filter-search"><?php echo JText::_('COM_CONTENT_TITLE_FILTER_LABEL'); ?></label>
			<input type="search" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->filter); ?>" placeholder="<?php echo JText::_('COM_CONTENT_TITLE_FILTER_LABEL'); ?>" />
			<?php endif; ?>
			<?php echo $this->form->monthField; ?>
			<?php echo $this->form->yearField; ?>
			<?php echo $this->form->limitField; ?>
			<button type="submit" class="btn"><?php echo JText::_('JGLOBAL_FILTER_BUTTON'); ?></button>
			<input type="hidden" name="view" value="archive" />
			<input type="hidden" name="option" value="com_content" />
			<input type="hidden" name="limitstart" value="0" />
		</div>

		<?php echo $this->loadTemplate('items'); ?>
	</form>
</div>
