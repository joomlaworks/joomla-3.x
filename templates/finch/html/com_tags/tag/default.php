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

/** @var TagsViewTag $this */
$titles = array();

foreach ((array) $this->item as $tag)
{
	$titles[] = $tag->title;
}

$items = array_map(array('FinchHelper', 'fromTagItem'), (array) $this->items);
?>
<div class="listView tagView">
	<header class="listHeader">
		<p class="listKicker"><?php echo JText::_('TPL_FINCH_TAGGED'); ?></p>
		<h1 class="listTitle"><?php echo FinchHelper::e(implode(', ', $titles)); ?></h1>
	</header>

	<?php echo FinchHelper::postList($items, false); ?>

	<?php if ($this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo FinchHelper::pagination($this->pagination); ?></nav>
	<?php endif; ?>
</div>
