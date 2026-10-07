<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;


JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');
// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('RookwoodHelper', false) || require_once dirname(__DIR__, 3) . '/helper.php';

/**
 * All tags. Unlike the stock layout, no captions script (and with it jQuery) and no inline scripts: the filter is a plain form.
 *
 * @var TagsViewTags $this
 */
$params = $this->params;
$levels = $this->user->getAuthorisedViewLevels();
$items  = array_filter((array) $this->items, function ($item) use ($levels)
{
	return !empty($item->access) && in_array($item->access, $levels);
});
?>
<div class="listView tagsView<?php echo $this->pageclass_sfx; ?>">
	<?php echo RookwoodHelper::listHeader($params->get('page_heading') ?: JText::_('TPL_ROOKWOOD_TAGS'), (string) $params->get('all_tags_description')); ?>
	<div class="container">
		<?php if ($params->get('filter_field')) : ?>
		<form class="listFilters" action="<?php echo RookwoodHelper::e(JUri::getInstance()->toString()); ?>" method="post" role="search">
			<label for="filter-search" class="visuallyHidden"><?php echo JText::_('COM_TAGS_TITLE_FILTER_LABEL'); ?></label>
			<input type="search" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>" placeholder="<?php echo JText::_('COM_TAGS_TITLE_FILTER_LABEL'); ?>" />
			<button type="submit" class="btn"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<input type="hidden" name="limitstart" value="0" />
		</form>
		<?php endif; ?>
		<?php if (!$items) : ?>
		<p class="noResults"><?php echo JText::_('COM_TAGS_NO_TAGS'); ?></p>
		<?php else : ?>
		<ul class="tagCloud">
			<?php foreach ($items as $item) : ?>
			<li><a href="<?php echo JRoute::_(TagsHelperRoute::getTagRoute($item->id . ':' . $item->alias)); ?>">#<?php echo $this->escape($item->title); ?><?php if ($params->get('all_tags_show_tag_hits')) : ?> <span><?php echo (int) $item->hits; ?></span><?php endif; ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
		<?php if ($items && $params->def('show_pagination', 2) && $this->pagination->pagesTotal > 1) : ?>
		<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo RookwoodHelper::pagination($this->pagination); ?></nav>
		<?php endif; ?>
	</div>
</div>
