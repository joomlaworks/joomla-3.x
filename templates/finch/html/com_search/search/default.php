<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once JPATH_THEMES . '/finch/helper.php';

/** @var SearchViewSearch $this */
$upperLimit = JFactory::getLanguage()->getUpperLimitSearchWord();
$hasOptions = $this->params->get('search_phrases', 1) || $this->params->get('search_areas', 1) || $this->total > 0;
?>
<div class="searchResults<?php echo $this->pageclass_sfx; ?>">
	<header class="listHeader">
		<p class="listKicker"><?php echo FinchHelper::icon('search') . JText::_('TPL_FINCH_SEARCH'); ?></p>
		<h1 class="listTitle">
			<?php if ($this->searchword !== '' && $this->searchword !== null) : ?>
				<?php echo JText::sprintf('TPL_FINCH_SEARCH_RESULTS_FOR', $this->escape($this->origkeyword)); ?>
			<?php else : ?>
				<?php echo $this->escape($this->params->get('page_heading') ?: $this->params->get('page_title')); ?>
			<?php endif; ?>
		</h1>
		<?php if (!empty($this->searchword)) : ?>
		<p class="listDescription"><?php echo JText::plural('COM_SEARCH_SEARCH_KEYWORD_N_RESULTS', (int) $this->total); ?></p>
		<?php endif; ?>
	</header>

	<form class="searchPageForm" action="<?php echo JRoute::_('index.php?option=com_search'); ?>" method="post" role="search">
		<div class="searchForm">
			<label for="search-searchword" class="visuallyHidden"><?php echo JText::_('COM_SEARCH_SEARCH_KEYWORD'); ?></label>
			<input type="search" name="searchword" id="search-searchword" maxlength="<?php echo (int) $upperLimit; ?>" value="<?php echo $this->escape($this->origkeyword); ?>" placeholder="<?php echo JText::_('COM_SEARCH_SEARCH_KEYWORD'); ?>" />
			<button type="submit" aria-label="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><?php echo FinchHelper::icon('search'); ?></button>
		</div>
		<input type="hidden" name="task" value="search" />

		<?php if ($hasOptions) : ?>
		<details class="searchOptions">
			<summary><?php echo JText::_('TPL_FINCH_SEARCH_OPTIONS'); ?></summary>
			<div class="searchOptionsBody">
				<?php if ($this->params->get('search_phrases', 1)) : ?>
				<fieldset>
					<legend><?php echo JText::_('COM_SEARCH_FOR'); ?></legend>
					<div class="searchChoices"><?php echo $this->lists['searchphrase']; ?></div>
				</fieldset>
				<div class="searchField">
					<label for="ordering"><?php echo JText::_('COM_SEARCH_ORDERING'); ?></label>
					<?php echo $this->lists['ordering']; ?>
				</div>
				<?php endif; ?>
				<?php if ($this->params->get('search_areas', 1)) : ?>
				<fieldset>
					<legend><?php echo JText::_('COM_SEARCH_SEARCH_ONLY'); ?></legend>
					<div class="searchChoices">
						<?php foreach ($this->searchareas['search'] as $value => $text) : ?>
						<label for="area-<?php echo $this->escape($value); ?>">
							<input type="checkbox" name="areas[]" value="<?php echo $this->escape($value); ?>" id="area-<?php echo $this->escape($value); ?>"<?php echo is_array($this->searchareas['active']) && in_array($value, $this->searchareas['active']) ? ' checked="checked"' : ''; ?> />
							<?php echo JText::_($text); ?>
						</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<?php endif; ?>
				<?php if ($this->total > 0) : ?>
				<div class="searchField">
					<label for="limit"><?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?></label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
				<?php endif; ?>
			</div>
		</details>
		<?php endif; ?>
	</form>

	<?php if ($this->error) : ?>
	<p class="searchError"><?php echo $this->escape($this->error); ?></p>
	<?php elseif (count($this->results)) : ?>
	<div class="searchList">
		<?php foreach ($this->results as $result) : $link = $result->href ? JRoute::_($result->href) : ''; ?>
		<article class="post">
			<div class="postBody">
				<?php if ($result->section) : ?><span class="postCategory"><?php echo $this->escape($result->section); ?></span><?php endif; ?>
				<?php // The title and text keep the highlight markup of the searched words ?>
				<h2 class="postTitle">
					<?php if ($link) : ?><a href="<?php echo $link; ?>"<?php echo $result->browsernav == 1 ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo $result->title; ?></a><?php else : ?><?php echo $result->title; ?><?php endif; ?>
				</h2>
				<p class="postExcerpt"><?php echo $result->text; ?></p>
				<?php if ($this->params->get('show_date') && $result->created) : ?>
				<div class="postMeta"><span class="postDate"><?php echo $this->escape($result->created); ?></span></div>
				<?php endif; ?>
			</div>
		</article>
		<?php endforeach; ?>
	</div>
	<?php if ($this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>">
		<?php echo $this->pagination->getPagesLinks(); ?>
	</nav>
	<?php endif; ?>
	<?php endif; ?>
</div>
