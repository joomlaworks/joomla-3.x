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
 * "Journal": the latest articles as a row of cards.
 *
 * @var array $list  @var Joomla\Registry\Registry $params
 */
$items = array_values($list);

if (!$items)
{
	return;
}
?>
<div class="cardGrid">
	<?php foreach ($items as $item) : ?>
	<?php echo RookwoodHelper::card($item, array('excerpt' => 120)); ?>
	<?php endforeach; ?>
</div>
