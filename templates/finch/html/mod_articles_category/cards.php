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

// Three posts as cards (e.g. "Read next" under a post, the module set to four), without the post being read
/** @var array $list */
$current = JFactory::getApplication()->input->get('view') === 'article' ? JFactory::getApplication()->input->getInt('id') : 0;
$items   = array_values(array_filter($list, function ($item) use ($current)
{
	return (int) $item->id !== $current;
}));
$items   = array_slice($items, 0, 3);

if (!$items)
{
	return;
}
?>
<div class="postGrid postGridThree">
	<?php foreach ($items as $item) : ?>
	<?php echo FinchHelper::card($item); ?>
	<?php endforeach; ?>
</div>
