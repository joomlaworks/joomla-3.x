<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_redirect&view=links'); ?>" method="post" name="adminForm" id="adminForm">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
			<table class="table table-striped">
				<thead>
					<tr>
						<th width="1%" class="center nowrap">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th width="1%" class="center nowrap">
							<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
						</th>
						<th class="nowrap title">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_OLD_URL', 'a.old_url', $listDirn, $listOrder); ?>
						</th>
						<th width="35%" class="nowrap">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_NEW_URL', 'a.new_url', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_STATUS_CODE', 'a.header', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_HITS', 'a.hits', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_CREATED_DATE', 'a.created_date', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="8">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) :
				    $canEdit   = $user->authorise('core.edit', 'com_redirect');
				    $canChange = $user->authorise('core.edit.state', 'com_redirect');
				    $source    = '/' . $item->old_url;
				    $target    = RedirectHelper::isExternal($item->new_url) ? $item->new_url : '/' . $item->new_url;
				    ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="center">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="center">
							<div class="btn-group">
								<?php echo JHtml::_('redirect.published', $item->published, $i, $canChange); ?>
								<?php // Create dropdown items and render the dropdown list.
				                if ($canChange) {
				                    JHtml::_('actionsdropdown.' . ((int) $item->published === 2 ? 'un' : '') . 'archive', 'cb' . $i, 'links');
				                    JHtml::_('actionsdropdown.' . ((int) $item->published === -2 ? 'un' : '') . 'trash', 'cb' . $i, 'links');
				                    echo JHtml::_('actionsdropdown.render', $this->escape($item->old_url));
				                }
				    ?>
							</div>
						</td>
						<td class="break-word">
							<?php if ($canEdit) : ?>
								<a href="<?php echo JRoute::_('index.php?option=com_redirect&task=link.edit&id=' . $item->id); ?>"><?php echo $this->escape($source); ?></a>
							<?php else : ?>
								<?php echo $this->escape($source); ?>
							<?php endif; ?>
							<?php if ($item->comment !== '') : ?>
								<div class="small"><?php echo $this->escape($item->comment); ?></div>
							<?php endif; ?>
						</td>
						<td class="small break-word">
							<?php echo $this->escape($target); ?>
						</td>
						<td class="hidden-phone">
							<?php if ((int) $item->header) : ?>
								<?php echo (int) $item->header; ?>
							<?php else : ?>
								<span class="muted" title="<?php echo JText::_('COM_REDIRECT_DEFAULT_CODE'); ?>"><?php echo RedirectHelper::getCode(0); ?>*</span>
							<?php endif; ?>
						</td>
						<td class="hidden-phone">
							<?php echo (int) $item->hits; ?>
						</td>
						<td class="small nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('date', $item->created_date, JText::_('DATE_FORMAT_LC4')); ?>
						</td>
						<td class="hidden-phone">
							<?php echo (int) $item->id; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
