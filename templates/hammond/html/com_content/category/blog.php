<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('HammondHelper', false) || require_once dirname(__DIR__, 3) . '/helper.php';

/** @var ContentViewCategory $this */
$items = array_merge((array) $this->lead_items, (array) $this->intro_items);
$first = $this->pagination->limitstart == 0;
?>
<div class="listView categoryView">
	<header class="listHeader">
		<p class="listKicker"><?php echo JText::_('TPL_HAMMOND_LATEST'); ?></p>
		<h1 class="listTitle"><?php echo HammondHelper::e($this->category->title); ?></h1>
		<?php if ($this->params->get('show_description', 1) && $this->category->description) : ?>
		<div class="listDescription"><?php echo HammondHelper::lazyImages(JHtml::_('content.prepare', $this->category->description, '', 'com_content.category')); ?></div>
		<?php endif; ?>
	</header>

	<div class="items layout-list">
		<?php foreach ($items as $i => $item) : ?>
		<?php echo HammondHelper::listItem($item, $first && !$i, !$i); ?>
		<?php endforeach; ?>
	</div>

	<?php if ($this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo HammondHelper::pagination($this->pagination); ?></nav>
	<?php endif; ?>
</div>
