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
 * The items of a tag (or tags), as a grid of cards.
 *
 * @var TagsViewTag $this
 */
$titles = array();

foreach ((array) $this->item as $tag)
{
	$titles[] = $tag->title;
}
?>
<div class="listView tagView">
	<?php echo RookwoodHelper::listHeader('#' . implode(', #', $titles)); ?>
	<div class="container">
		<div class="cardGrid">
			<?php foreach (array_values((array) $this->items) as $i => $item) : ?>
			<?php echo RookwoodHelper::card(RookwoodHelper::fromTagItem($item), array('eager' => $i < 3, 'labels' => 'category')); ?>
			<?php endforeach; ?>
		</div>
		<?php if ($this->pagination->pagesTotal > 1) : ?>
		<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo RookwoodHelper::pagination($this->pagination); ?></nav>
		<?php endif; ?>
	</div>
</div>
