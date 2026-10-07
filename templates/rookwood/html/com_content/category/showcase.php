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
 * "Showcase": a category's articles (e.g. case studies, products or services) with large images: as panels in one column,
 * each image the canvas of its text, when the menu item's Blog Layout has one column; else as a grid of large cards in that
 * many columns.
 *
 * @var ContentViewCategory $this
 */
$items   = array_merge((array) $this->lead_items, (array) $this->intro_items);
$first   = $this->pagination->limitstart == 0;
$columns = max(1, min(3, (int) $this->params->get('num_columns', 1)));
?>
<div class="listView categoryView showcaseView">
	<?php echo RookwoodHelper::listHeader(RookwoodHelper::categoryTitle($this->params, $this->category), RookwoodHelper::categoryDescription($this->params, $this->category)); ?>
	<div class="container">
		<?php if ($columns === 1) : ?>
		<div class="panels">
			<?php foreach ($items as $i => $item) : ?>
			<?php echo RookwoodHelper::panel($item, $first && !$i); ?>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="cardGrid cardGridLarge columns-<?php echo $columns; ?>">
			<?php foreach ($items as $i => $item) : ?>
			<?php echo RookwoodHelper::card($item, array('class' => 'cardLarge', 'excerpt' => 160, 'eager' => $first && $i < $columns,
				'sizes' => '(min-width: 1100px) ' . round(100 / $columns) . 'vw, (min-width: 700px) 50vw, 100vw')); ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?php if ($this->pagination->pagesTotal > 1) : ?>
		<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo RookwoodHelper::pagination($this->pagination); ?></nav>
		<?php endif; ?>
	</div>
</div>
