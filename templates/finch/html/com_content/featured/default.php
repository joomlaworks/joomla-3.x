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

/** @var ContentViewFeatured $this */
$items = array_merge((array) $this->lead_items, (array) $this->intro_items);
?>
<div class="listView featuredView">
	<?php if ($this->params->get('show_page_heading')) : ?>
	<header class="listHeader">
		<h1 class="listTitle"><?php echo FinchHelper::e($this->params->get('page_heading')); ?></h1>
	</header>
	<?php endif; ?>

	<?php echo FinchHelper::postList($items, $this->pagination->limitstart == 0); ?>

	<?php if ($this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo FinchHelper::pagination($this->pagination); ?></nav>
	<?php endif; ?>
</div>
