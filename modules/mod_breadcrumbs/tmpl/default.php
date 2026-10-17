<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_breadcrumbs
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;
?>
<div aria-label="<?php echo htmlspecialchars((string) $module->title, ENT_QUOTES, 'UTF-8'); ?>" role="navigation">
	<ul class="breadcrumb<?php echo $moduleclass_sfx; ?>">
		<?php if ($params->get('showHere', 1)) : ?>
			<li>
				<?php echo JText::_('MOD_BREADCRUMBS_HERE'); ?>&#160;
			</li>
		<?php else : ?>
			<li class="active">
				<span class="divider icon-location"></span>
			</li>
		<?php endif; ?>

		<?php
		// Get rid of duplicated entries on trail including home page when using multilanguage
		for ($i = 0; $i < $count; $i++)
		{
			if ($i === 1 && !empty($list[$i]->link) && !empty($list[$i - 1]->link) && $list[$i]->link === $list[$i - 1]->link)
			{
				unset($list[$i]);
			}
		}

		// Find last and penultimate items in breadcrumbs list
		end($list);
		$last_item_key   = key($list);
		prev($list);
		$penult_item_key = key($list);

		// Make a link if not the last item in the breadcrumbs
		$show_last = $params->get('showLast', 1);

		// Generate the trail
		foreach ($list as $key => $item) :
			if ($key !== $last_item_key) :
				// Render all but last item - along with separator ?>
				<li>
					<?php if (!empty($item->link)) : ?>
						<a href="<?php echo $item->link; ?>" class="pathway"><span><?php echo $item->name; ?></span></a>
					<?php else : ?>
						<span>
							<?php echo $item->name; ?>
						</span>
					<?php endif; ?>

					<?php if (($key !== $penult_item_key) || $show_last) : ?>
						<span class="divider">
							<?php echo $separator; ?>
						</span>
					<?php endif; ?>
				</li>
			<?php elseif ($show_last) :
				// Render last item if reqd. ?>
				<li class="active">
					<span>
						<?php echo $item->name; ?>
					</span>
				</li>
			<?php endif;
		endforeach; ?>
	</ul>
</div>
