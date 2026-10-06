<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JLoader::register('TagsHelperRoute', JPATH_SITE . '/components/com_tags/helpers/route.php');
// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('FinchHelper', false) || require_once dirname(__DIR__, 3) . '/helper.php';

/**
 * All tags. Unlike the stock layout, no captions script (and with it jQuery) and no inline scripts: the filter is a plain form.
 *
 * @var TagsViewTags $this
 */
$params      = $this->params;
$description = $params->get('all_tags_description');
$image       = $params->get('all_tags_show_description_image') ? $params->get('all_tags_description_image') : '';
$levels      = $this->user->getAuthorisedViewLevels();
$items       = array_filter((array) $this->items, function ($item) use ($levels)
{
	return !empty($item->access) && in_array($item->access, $levels);
});
?>
<div class="listView tagsView<?php echo $this->pageclass_sfx; ?>">
	<header class="listHeader">
		<h1 class="listTitle"><?php echo $this->escape($params->get('page_heading')); ?></h1>
		<?php if ($image) : ?>
		<img class="tagsViewImage" src="<?php echo FinchHelper::e($image); ?>" alt="" fetchpriority="high" />
		<?php endif; ?>
		<?php if (!empty($description)) : ?>
		<div class="listDescription"><?php echo $description; ?></div>
		<?php endif; ?>
	</header>

	<?php if ($params->get('filter_field')) : ?>
	<form class="tagsFilter" action="<?php echo FinchHelper::e(JUri::getInstance()->toString()); ?>" method="post" role="search">
		<div class="searchForm">
			<label for="filter-search" class="visuallyHidden"><?php echo JText::_('COM_TAGS_TITLE_FILTER_LABEL'); ?></label>
			<input type="search" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>" placeholder="<?php echo JText::_('COM_TAGS_TITLE_FILTER_LABEL'); ?>" />
			<button type="submit" aria-label="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><?php echo FinchHelper::icon('search'); ?></button>
		</div>
		<input type="hidden" name="limitstart" value="0" />
	</form>
	<?php endif; ?>

	<?php if (!$items) : ?>
	<p class="noResults"><?php echo JText::_('COM_TAGS_NO_TAGS'); ?></p>
	<?php else : ?>
	<ul class="tagsIndex">
		<?php foreach ($items as $item) : ?>
		<?php $images = $params->get('all_tags_show_tag_image') ? json_decode((string) $item->images) : null; ?>
		<li>
			<a class="tagsIndexItem" href="<?php echo JRoute::_(TagsHelperRoute::getTagRoute($item->id . ':' . $item->alias)); ?>">
				<?php if (!empty($images->image_intro)) : ?>
				<img src="<?php echo FinchHelper::e($images->image_intro); ?>" alt="<?php echo FinchHelper::e(isset($images->image_intro_alt) ? $images->image_intro_alt : ''); ?>" loading="lazy" />
				<?php endif; ?>
				<strong><?php echo $this->escape($item->title); ?></strong>
				<?php if ($params->get('all_tags_show_tag_description', 1) && !empty($item->description)) : ?>
				<span class="tagsIndexText"><?php echo JHtml::_('string.truncate', strip_tags((string) $item->description), (int) $params->get('all_tags_tag_maximum_characters', 0)); ?></span>
				<?php endif; ?>
				<?php if ($params->get('all_tags_show_tag_hits')) : ?>
				<span class="tagsIndexMeta"><?php echo JText::sprintf('JGLOBAL_HITS_COUNT', $item->hits); ?></span>
				<?php endif; ?>
			</a>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>

	<?php if ($items && $params->def('show_pagination', 2) && $this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo FinchHelper::pagination($this->pagination); ?></nav>
	<?php endif; ?>
</div>
