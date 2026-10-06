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

// Latest news: time, title and category
?>
<ol class="items layout-latest">
	<?php foreach ($items as $item) : $link = HammondHelper::link($item); ?>
	<li class="item">
		<?php echo HammondHelper::date($item); ?>
		<div class="itemBody">
			<h3 class="itemTitle"><a href="<?php echo $link; ?>"><?php echo HammondHelper::e($item->title); ?></a></h3>
			<?php echo HammondHelper::category($item); ?>
		</div>
	</li>
	<?php endforeach; ?>
</ol>
