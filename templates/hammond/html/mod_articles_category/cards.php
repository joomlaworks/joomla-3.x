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

// Cards: image, category, title and date; the excerpt for the first card of a "lead-first" module
$leadFirst = strpos((string) $params->get('moduleclass_sfx'), 'lead-first') !== false;
?>
<div class="items layout-cards">
	<?php foreach ($items as $i => $item) : $link = HammondHelper::link($item); ?>
	<article class="item<?php echo $leadFirst && !$i ? ' itemLead' : ''; ?>">
		<?php echo HammondHelper::figure($item, $link); ?>
		<div class="itemBody">
			<?php echo HammondHelper::category($item); ?>
			<h3 class="itemTitle"><a href="<?php echo $link; ?>"><?php echo HammondHelper::e($item->title); ?></a></h3>
			<?php if ($leadFirst && !$i) : ?><p class="itemIntroText"><?php echo HammondHelper::excerpt($item, 180); ?></p><?php endif; ?>
			<div class="itemMeta">
				<?php if ($author = HammondHelper::author($item)) : ?><span class="itemAuthor"><?php echo HammondHelper::e($author); ?></span><?php endif; ?>
				<?php echo HammondHelper::date($item); ?>
			</div>
		</div>
	</article>
	<?php endforeach; ?>
</div>
