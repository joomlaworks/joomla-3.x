<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;


// A copy of the template (Templates: Copy Template) has the same class: whichever the page loaded first is used
class_exists('RookwoodHelper', false) || require_once dirname(__DIR__, 2) . '/helper.php';

/**
 * "Work": articles (e.g. case studies) as large cards in two columns, the first across both.
 *
 * @var array $list  @var Joomla\Registry\Registry $params
 */
$items = array_values($list);

if (!$items)
{
	return;
}
?>
<div class="cardGrid cardGridLarge columns-2 workGrid">
	<?php foreach ($items as $i => $item) : ?>
	<?php echo RookwoodHelper::card($item, array('class' => 'cardLarge' . (!$i ? ' cardWide' : ''), 'excerpt' => !$i ? 180 : 0,
		'sizes' => !$i ? '(min-width: 1440px) 1400px, 100vw' : '(min-width: 1100px) 50vw, 100vw')); ?>
	<?php endforeach; ?>
</div>
