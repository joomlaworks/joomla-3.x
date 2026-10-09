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
class_exists('FinchHelper', false) || require_once dirname(__DIR__, 2) . '/helper.php';

// Posts as a short numbered list: the title and the date (e.g. popular posts in the sidebar, in order)
/** @var array $list */
if (!$list)
{
	return;
}
?>
<ol class="compactList">
	<?php foreach (array_values($list) as $item) : ?>
	<li class="compactItem">
		<a class="compactTitle" href="<?php echo FinchHelper::link($item); ?>"><?php echo FinchHelper::e($item->title); ?></a>
		<?php echo FinchHelper::date($item); ?>
	</li>
	<?php endforeach; ?>
</ol>
