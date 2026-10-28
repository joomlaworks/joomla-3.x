<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));
JHtml::_('bootstrap.popover', '.hasPopover', array('placement' => 'bottom'));
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.multiselect');

// Special case for the search field tooltip.
$searchFilterDesc = $this->filterForm->getFieldAttribute('search', 'description', null, 'filter');
JHtml::_('bootstrap.tooltip', '#filter_search', array('title' => JText::_($searchFilterDesc), 'placement' => 'bottom'));

$input           = JFactory::getApplication()->input;
$field           = $input->getCmd('field');
$listOrder       = $this->escape($this->state->get('list.ordering'));
$listDirn        = $this->escape($this->state->get('list.direction'));
$enabledStates   = array(0 => 'icon-publish', 1 => 'icon-unpublish');
$activatedStates = array(0 => 'icon-publish', 1 => 'icon-unpublish');
$userRequired    = (int) $input->get('required', 0, 'int');

// Choosing calls the field's page (jSelectUser(), global.js), which fills the field and closes the modal; the old
// ismoo=0 (the Bootstrap modal of fielduser.js, removed in 3.18) is passed on but no longer changes anything
$isMoo   = $input->getInt('ismoo', 1);
$onClick = 'window.parent.jSelectUser(this);';

?>
<div class="container-popup">
	<form action="<?php echo JRoute::_('index.php?option=com_users&view=users&layout=modal&tmpl=component&groups=' . $input->get('groups', '', 'BASE64') . '&excluded=' . $input->get('excluded', '', 'BASE64')); ?>" method="post" name="adminForm" id="adminForm">
		<?php if (!$userRequired) : ?>
		<div class="pull-left">
			<button type="button" class="btn button-select" data-user-value="0" data-user-name="<?php echo $this->escape(JText::_('JLIB_FORM_SELECT_USER')); ?>"
				data-user-field="<?php echo $this->escape($field); ?>" value="" onclick="<?php echo $onClick; ?>"><?php echo JText::_('JOPTION_NO_USER'); ?></button>&nbsp;
		</div>
		<?php endif; ?>
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
		<table class="table table-striped table-condensed">
			<thead>
				<tr>
					<th class="nowrap">
						<?php echo JHtml::_('searchtools.sort', 'COM_USERS_HEADING_NAME', 'a.name', $listDirn, $listOrder); ?>
					</th>
					<th width="25%" class="nowrap">
						<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_USERNAME', 'a.username', $listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap center">
						<?php echo JHtml::_('searchtools.sort', 'COM_USERS_HEADING_ENABLED', 'a.block', $listDirn, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap center">
						<?php echo JHtml::_('searchtools.sort', 'COM_USERS_HEADING_ACTIVATED', 'a.activation', $listDirn, $listOrder); ?>
					</th>
					<th width="25%" class="nowrap">
						<?php echo JText::_('COM_USERS_HEADING_GROUPS'); ?>
					</th>
					<th width="1%" class="nowrap">
						<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="6">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php $i = 0; ?>
				<?php foreach ($this->items as $item) : ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td>
							<a class="pointer button-select" href="#" data-user-value="<?php echo $item->id; ?>" data-user-name="<?php echo $this->escape($item->name); ?>"
								data-user-field="<?php echo $this->escape($field); ?>" onclick="<?php echo $onClick; ?> return false;">
								<?php echo $this->escape($item->name); ?>
							</a>
						</td>
						<td>
							<?php echo $this->escape($item->username); ?>
						</td>
						<td class="center">
							<span class="<?php echo $enabledStates[(int) $this->escape($item->block)]; ?>"></span>
						</td>
						<td class="center">
							<span class="<?php echo $activatedStates[(empty($item->activation) ? 0 : 1)]; ?>"></span>
						</td>
						<td>
							<?php echo nl2br((string) $item->group_names); ?>
						</td>
						<td>
							<?php echo (int) $item->id; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="field" value="<?php echo $this->escape($field); ?>" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="required" value="<?php echo $userRequired; ?>" />
		<input type="hidden" name="ismoo" value="<?php echo $isMoo; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
