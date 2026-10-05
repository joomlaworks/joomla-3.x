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

/** @var array $list  @var Joomla\Registry\Registry $params */
$items = array_slice(array_values($list), HammondHelper::skip($params));

if (!$items)
{
	return;
}

// A category section: the lead article, then the others as a compact list
$lead = array_shift($items);
$link = HammondHelper::link($lead);
?>
<div class="items layout-section">
	<article class="item itemLead">
		<?php echo HammondHelper::figure($lead, $link); ?>
		<div class="itemBody">
			<h3 class="itemTitle"><a href="<?php echo $link; ?>"><?php echo HammondHelper::e($lead->title); ?></a></h3>
			<p class="itemIntroText"><?php echo HammondHelper::excerpt($lead, 200); ?></p>
			<div class="itemMeta">
				<?php if ($author = HammondHelper::author($lead)) : ?><span class="itemAuthor"><?php echo HammondHelper::e($author); ?></span><?php endif; ?>
				<?php echo HammondHelper::date($lead); ?>
			</div>
		</div>
	</article>
	<?php if ($items) : ?>
	<ul class="subItems">
		<?php foreach ($items as $item) : $link = HammondHelper::link($item); ?>
		<li class="item">
			<?php echo HammondHelper::figure($item, $link, 'itemImage itemThumb'); ?>
			<div class="itemBody">
				<h4 class="itemTitle"><a href="<?php echo $link; ?>"><?php echo HammondHelper::e($item->title); ?></a></h4>
				<?php echo HammondHelper::date($item); ?>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</div>
