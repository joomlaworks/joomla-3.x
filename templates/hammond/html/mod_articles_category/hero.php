<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.hammond
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('HammondHelper', false) || require_once dirname(__DIR__, 2) . '/helper.php';

/** @var array $list  @var Joomla\Registry\Registry $params */
$items = array_slice(array_values($list), HammondHelper::skip($params));

if (!$items)
{
	return;
}

// The main story: the image fills the block, title and details over it
$item = $items[0];
$link = HammondHelper::link($item);
?>
<div class="items layout-hero">
	<article class="item itemHero">
		<?php echo HammondHelper::figure($item, $link, 'itemImage', false); ?>
		<div class="itemBody">
			<?php echo HammondHelper::category($item); ?>
			<h2 class="itemTitle"><a href="<?php echo $link; ?>"><?php echo HammondHelper::e($item->title); ?></a></h2>
			<p class="itemIntroText"><?php echo HammondHelper::excerpt($item, 220); ?></p>
			<div class="itemMeta">
				<?php if ($author = HammondHelper::author($item)) : ?><span class="itemAuthor"><?php echo HammondHelper::e($author); ?></span><?php endif; ?>
				<?php echo HammondHelper::date($item); ?>
			</div>
		</div>
	</article>
</div>
