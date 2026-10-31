<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_stats_admin
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// A link opens its page on the tab named after # (js/stats_admin.js)
JHtml::_('script', 'administrator/modules/mod_stats_admin/js/stats_admin.min.js', array('version' => 'auto'), array('defer' => true));
?>
<?php // The Control Panel shows the icon position's modules without their titles: Quick Icons has headings of its own?>
<?php if ($module->position === 'icon' && $module->showtitle) : ?>
	<h2 class="nav-header stats-module-title"><?php echo htmlspecialchars($module->title, ENT_COMPAT, 'UTF-8'); ?></h2>
<?php endif; ?>
<div class="row-striped">
	<?php foreach ($list as $item) : ?>
		<div class="row-fluid">
			<div class="span4">
				<span class="icon-<?php echo $item->icon; ?>" aria-hidden="true"></span> <?php echo $item->title; ?>
			</div>
			<div class="span8">
				<?php if (isset($item->link)) : ?>
					<a class="btn btn-info btn-small js-revert" href="<?php echo $item->link; ?>"><?php echo $item->data; ?></a>
				<?php else : ?>
					<?php echo $item->data; ?>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
