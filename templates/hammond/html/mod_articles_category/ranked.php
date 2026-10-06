<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

require_once dirname(__DIR__, 2) . '/helper.php';

/** @var array $list  @var Joomla\Registry\Registry $params */
$items = array_slice(array_values($list), HammondHelper::skip($params));

if (!$items)
{
	return;
}

// Most read and similar: numbered, with a small image
?>
<ol class="items layout-ranked">
	<?php foreach ($items as $i => $item) : $link = HammondHelper::link($item); ?>
	<li class="item">
		<span class="rank" aria-hidden="true"><?php echo $i + 1; ?></span>
		<div class="itemBody">
			<?php echo HammondHelper::category($item); ?>
			<h3 class="itemTitle"><a href="<?php echo $link; ?>"><?php echo HammondHelper::e($item->title); ?></a></h3>
		</div>
		<?php echo HammondHelper::figure($item, $link, 'itemImage itemThumb'); ?>
	</li>
	<?php endforeach; ?>
</ol>
