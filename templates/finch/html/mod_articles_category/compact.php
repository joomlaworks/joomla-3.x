<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.finch
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once dirname(__DIR__, 2) . '/helper.php';

// Posts as a short list: a small image, the title and the date (e.g. popular posts in the sidebar)
/** @var array $list */
if (!$list)
{
	return;
}
?>
<ol class="compactList">
	<?php foreach (array_values($list) as $item) : $link = FinchHelper::link($item); ?>
	<li class="compactItem">
		<?php echo FinchHelper::figure($item, $link, 'compactImage'); ?>
		<div>
			<a class="compactTitle" href="<?php echo $link; ?>"><?php echo FinchHelper::e($item->title); ?></a>
			<?php echo FinchHelper::date($item); ?>
		</div>
	</li>
	<?php endforeach; ?>
</ol>
