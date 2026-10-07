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
 * "Mosaic": a category's articles in groups of seven on a four-column grid: a large article, its image as the canvas of its
 * text (three columns, two rows), two cards beside it and four below; every other group with the large article on the right.
 * Best with 14 articles a page (Blog Layout: 0 leading, 14 intro articles).
 *
 * @var ContentViewCategory $this
 */
$items = array_merge((array) $this->lead_items, (array) $this->intro_items);
$first = $this->pagination->limitstart == 0;
?>
<div class="listView categoryView mosaicView">
	<?php echo RookwoodHelper::listHeader(RookwoodHelper::categoryTitle($this->params, $this->category), RookwoodHelper::categoryDescription($this->params, $this->category)); ?>
	<div class="container">
		<div class="mosaic">
			<?php foreach ($items as $i => $item) : $feature = $i % 7 === 0; ?>
			<?php echo RookwoodHelper::card($item, array(
				'class'   => $feature ? 'cardFeature' . ((int) ($i / 7) % 2 ? ' cardFeatureRight' : '') : '',
				'heading' => $feature ? 'h2' : 'h3',
				'excerpt' => $feature ? 220 : 0,
				'eager'   => $first && $i < 3,
				'sizes'   => $feature ? '(min-width: 1100px) 75vw, 100vw' : '(min-width: 1100px) 25vw, (min-width: 700px) 50vw, 100vw',
			)); ?>
			<?php endforeach; ?>
		</div>
		<?php if ($this->pagination->pagesTotal > 1) : ?>
		<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo RookwoodHelper::pagination($this->pagination); ?></nav>
		<?php endif; ?>
	</div>
</div>
