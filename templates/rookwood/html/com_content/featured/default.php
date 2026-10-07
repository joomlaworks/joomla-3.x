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
 * The featured articles (a home page without modules in "frontpage", e.g. a new site without sample data): a grid of cards.
 *
 * @var ContentViewFeatured $this
 */
$items = array_merge((array) $this->lead_items, (array) $this->intro_items);
$first = $this->pagination->limitstart == 0;
?>
<div class="listView featuredView">
	<?php echo RookwoodHelper::listHeader($this->params->get('page_heading') ?: JFactory::getApplication()->get('sitename')); ?>
	<div class="container">
		<div class="cardGrid">
			<?php foreach ($items as $i => $item) : ?>
			<?php echo RookwoodHelper::card($item, array('eager' => $first && $i < 3, 'labels' => 'category')); ?>
			<?php endforeach; ?>
		</div>
		<?php if ($this->pagination->pagesTotal > 1) : ?>
		<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo RookwoodHelper::pagination($this->pagination); ?></nav>
		<?php endif; ?>
	</div>
</div>
