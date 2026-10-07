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
 * A category's articles as a grid of cards. The template's other layouts of a category blog: "Mosaic" (the journal's mixed
 * grid) and "Showcase" (case studies as large panels or a grid), chosen for the menu item.
 *
 * @var ContentViewCategory $this
 */
$items = array_merge((array) $this->lead_items, (array) $this->intro_items);
$first = $this->pagination->limitstart == 0;
?>
<div class="listView categoryView">
	<?php echo RookwoodHelper::listHeader(RookwoodHelper::categoryTitle($this->params, $this->category), RookwoodHelper::categoryDescription($this->params, $this->category)); ?>
	<div class="container">
		<div class="cardGrid">
			<?php foreach ($items as $i => $item) : ?>
			<?php echo RookwoodHelper::card($item, array('eager' => $first && $i < 3)); ?>
			<?php endforeach; ?>
		</div>
		<?php if ($this->pagination->pagesTotal > 1) : ?>
		<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo RookwoodHelper::pagination($this->pagination); ?></nav>
		<?php endif; ?>
	</div>
</div>
