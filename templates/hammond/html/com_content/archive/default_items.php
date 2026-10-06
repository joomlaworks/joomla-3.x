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

/** @var ContentViewArchive $this */
?>
<div class="items layout-list">
	<?php foreach (array_values($this->items) as $i => $item) : ?>
	<?php echo HammondHelper::listItem($item, false, !$i); ?>
	<?php endforeach; ?>
</div>
<?php if ($this->pagination->pagesTotal > 1) : ?>
<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo HammondHelper::pagination($this->pagination); ?></nav>
<?php endif; ?>
