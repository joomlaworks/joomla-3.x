<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once JPATH_THEMES . '/hammond/helper.php';

// Featured articles as a list: the frontpage when the module grid has no modules (e.g. a new site without sample data)
/** @var ContentViewFeatured $this */
$items = array_merge((array) $this->lead_items, (array) $this->intro_items);
$first = $this->pagination->limitstart == 0;
?>
<div class="listView featuredView">
	<?php if ($this->params->get('show_page_heading')) : ?>
	<header class="listHeader">
		<h1 class="listTitle"><?php echo HammondHelper::e($this->params->get('page_heading')); ?></h1>
	</header>
	<?php endif; ?>

	<?php if ($items) : ?>
	<div class="items layout-list">
		<?php foreach ($items as $i => $item) : ?>
		<?php echo HammondHelper::listItem($item, $first && !$i); ?>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if ($this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav>
	<?php endif; ?>
</div>
