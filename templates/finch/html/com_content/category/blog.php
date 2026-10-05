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

/** @var ContentViewCategory $this */
$items = array_merge((array) $this->lead_items, (array) $this->intro_items);
?>
<div class="listView categoryView">
	<header class="listHeader">
		<p class="listKicker"><?php echo JText::_('TPL_FINCH_TOPIC'); ?></p>
		<h1 class="listTitle"><?php echo FinchHelper::e($this->category->title); ?></h1>
		<?php if ($this->params->get('show_description', 1) && $this->category->description) : ?>
		<div class="listDescription"><?php echo JHtml::_('content.prepare', $this->category->description, '', 'com_content.category'); ?></div>
		<?php endif; ?>
	</header>

	<?php echo FinchHelper::postList($items, $this->pagination->limitstart == 0); ?>

	<?php if ($this->pagination->pagesTotal > 1) : ?>
	<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav>
	<?php endif; ?>
</div>
