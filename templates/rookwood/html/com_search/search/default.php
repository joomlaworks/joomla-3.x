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
class_exists('RookwoodHelper', false) || require_once dirname(__DIR__, 3) . '/helper.php';

/**
 * Search: the list header, the form (its options folded away) and the results, one under another.
 *
 * @var SearchViewSearch $this
 */
$upperLimit = JFactory::getLanguage()->getUpperLimitSearchWord();
$hasOptions = $this->params->get('search_phrases', 1) || $this->params->get('search_areas', 1) || $this->total > 0;
$searched   = $this->searchword !== '' && $this->searchword !== null;
$title      = $searched ? JText::sprintf('TPL_ROOKWOOD_SEARCH_RESULTS_FOR', $this->origkeyword)
	: ($this->params->get('page_heading') ?: ($this->params->get('page_title') ?: JText::_('TPL_ROOKWOOD_SEARCH')));
?>
<div class="listView searchView<?php echo $this->pageclass_sfx; ?>">
	<?php echo RookwoodHelper::listHeader($title, $searched ? JText::plural('COM_SEARCH_SEARCH_KEYWORD_N_RESULTS', (int) $this->total) : ''); ?>
	<div class="container">
		<form class="searchPageForm" action="<?php echo JRoute::_('index.php?option=com_search'); ?>" method="post" role="search">
			<div class="searchForm">
				<label for="search-searchword" class="visuallyHidden"><?php echo JText::_('COM_SEARCH_SEARCH_KEYWORD'); ?></label>
				<input type="search" name="searchword" id="search-searchword" maxlength="<?php echo (int) $upperLimit; ?>" value="<?php echo $this->escape($this->origkeyword); ?>" placeholder="<?php echo JText::_('COM_SEARCH_SEARCH_KEYWORD'); ?>" />
				<button type="submit" aria-label="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><?php echo RookwoodHelper::icon('search'); ?></button>
			</div>
			<input type="hidden" name="task" value="search" />

			<?php if ($hasOptions) : ?>
			<details class="searchOptions">
				<summary><?php echo JText::_('TPL_ROOKWOOD_SEARCH_OPTIONS'); ?></summary>
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
		<ol class="searchList" start="<?php echo (int) $this->pagination->limitstart + 1; ?>">
			<?php foreach (array_values($this->results) as $i => $result) : $link = $result->href ? JRoute::_($result->href) : ''; ?>
			<li class="searchResult">
				<span class="searchResultNumber" aria-hidden="true"><?php echo sprintf('%02d', (int) $this->pagination->limitstart + $i + 1); ?></span>
				<?php if ($result->section) : ?><span class="itemCategory"><?php echo $this->escape($result->section); ?></span><?php endif; ?>
				<?php // The title and text keep the highlight markup of the searched words ?>
				<h2 class="searchResultTitle">
					<?php if ($link) : ?><a href="<?php echo $link; ?>"<?php echo $result->browsernav == 1 ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo $result->title; ?></a><?php else : ?><?php echo $result->title; ?><?php endif; ?>
				</h2>
				<p class="searchResultText"><?php echo $result->text; ?></p>
				<?php if ($this->params->get('show_date') && $result->created) : ?>
				<p class="cardMeta"><?php echo $this->escape($result->created); ?></p>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ol>
		<?php if ($this->pagination->pagesTotal > 1) : ?>
		<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo RookwoodHelper::pagination($this->pagination); ?></nav>
		<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
