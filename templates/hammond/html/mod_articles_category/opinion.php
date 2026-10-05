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

// Opinion: the columnist first, then the title and a short excerpt
?>
<div class="items layout-opinion">
	<?php foreach ($items as $item) : $link = HammondHelper::link($item); $author = HammondHelper::author($item); ?>
	<article class="item">
		<div class="itemAuthorBlock">
			<span class="avatar" aria-hidden="true" style="--hue:<?php echo (int) (crc32($author) % 360); ?>"><?php echo HammondHelper::e(HammondHelper::initials($author)); ?></span>
			<span class="itemAuthor"><?php echo HammondHelper::e($author); ?></span>
		</div>
		<h3 class="itemTitle"><a href="<?php echo $link; ?>"><?php echo HammondHelper::icon('quote', 'quoteMark') . HammondHelper::e($item->title); ?></a></h3>
		<p class="itemIntroText"><?php echo HammondHelper::excerpt($item, 120); ?></p>
		<?php echo HammondHelper::date($item); ?>
	</article>
	<?php endforeach; ?>
</div>
