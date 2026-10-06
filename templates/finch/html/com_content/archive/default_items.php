<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('FinchHelper', false) || require_once dirname(__DIR__, 3) . '/helper.php';

/** @var ContentViewArchive $this */
echo FinchHelper::postList((array) $this->items, false);
?>
<?php if ($this->pagination->pagesTotal > 1) : ?>
<nav class="pagination" aria-label="<?php echo JText::_('JLIB_HTML_PAGINATION'); ?>"><?php echo FinchHelper::pagination($this->pagination); ?></nav>
<?php endif; ?>
