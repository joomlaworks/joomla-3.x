<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once dirname(__DIR__, 3) . '/helper.php';

/** @var TagsViewTag $this */
$titles = array();

foreach ((array) $this->item as $tag)
{
	$titles[] = $tag->title;
}
?>
<div class="listView tagView">
	<header class="listHeader">
		<p class="listKicker"><?php echo HammondHelper::icon('hash') . JText::_('TPL_HAMMOND_TAGGED'); ?></p>
		<h1 class="listTitle"><?php echo HammondHelper::e(implode(', ', $titles)); ?></h1>
	</header>

	<div class="items layout-list">
		<?php foreach (array_values((array) $this->items) as $i => $item) : ?>
		<?php echo HammondHelper::listItem(HammondHelper::fromTagItem($item), false, !$i); ?>
		<?php endforeach; ?>
	</div>

	<?php if ($this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo HammondHelper::pagination($this->pagination); ?></nav>
	<?php endif; ?>
</div>
