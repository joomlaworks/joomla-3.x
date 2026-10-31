<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$canCreate = $user->authorise('core.create', 'com_redirect');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$root      = $this->escape(JUri::root());

if ($canCreate) {
    JHtml::_('behavior.cms');
    JHtml::_('stylesheet', 'administrator/components/com_redirect/css/redirect.css', array('version' => 'auto'));
    JHtml::_('script', 'administrator/components/com_redirect/js/redirect.js', array('version' => 'auto'), array('defer' => true));
    JText::script('COM_REDIRECT_CREATE_REDIRECT');
    JText::script('JCANCEL');
    JText::script('JAPPLY');
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_redirect&view=errors'); ?>" method="post" name="adminForm" id="adminForm">
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
			<table class="table table-striped" id="redirectErrors">
				<thead>
					<tr>
						<th width="1%" class="center nowrap">
							<?php echo JHtml::_('grid.checkall'); ?>
						</th>
						<th class="nowrap title">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_URL', 'a.url', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_CODE', 'a.code', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_ERROR_HITS', 'a.hits', $listDirn, $listOrder); ?>
						</th>
						<th width="25%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_REFERRER', 'a.referer', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_FIRST_HIT', 'a.created_date', $listDirn, $listOrder); ?>
						</th>
						<th width="1%" class="nowrap hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_REDIRECT_HEADING_LAST_HIT', 'a.last_hit', $listDirn, $listOrder); ?>
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
				<?php foreach ($this->items as $i => $item) : ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="center">
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						</td>
						<td class="break-word">
							<?php echo $this->escape('/' . $item->url); ?>
							<?php if ($canCreate && $item->url !== '') : ?>
								<div>
									<button type="button" class="btn btn-mini redirect-create" data-id="<?php echo (int) $item->id; ?>" data-url="<?php echo $this->escape($item->url); ?>">
										<span class="icon-refresh" aria-hidden="true"></span>
										<?php echo JText::_('COM_REDIRECT_CREATE_REDIRECT'); ?>
									</button>
								</div>
							<?php endif; ?>
						</td>
						<td>
							<span class="label<?php echo (int) $item->code >= 500 ? ' label-important' : ''; ?>"><?php echo (int) $item->code; ?></span>
						</td>
						<td class="hidden-phone">
							<?php echo (int) $item->hits; ?>
						</td>
						<td class="small break-word hidden-phone hidden-tablet">
							<?php echo $this->escape($item->referer); ?>
						</td>
						<td class="small nowrap hidden-phone hidden-tablet">
							<?php echo JHtml::_('date', $item->created_date, JText::_('DATE_FORMAT_LC4')); ?>
						</td>
						<td class="small nowrap hidden-phone">
							<?php echo JHtml::_('date', $item->last_hit, JText::_('DATE_FORMAT_LC5')); ?>
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
<?php if ($canCreate) : ?>
<template id="redirect-create-form">
	<form action="<?php echo JRoute::_('index.php?option=com_redirect&view=errors'); ?>" method="post" class="redirect-create-form form-horizontal">
		<div class="control-group">
			<div class="control-label"><label><?php echo JText::_('COM_REDIRECT_FIELD_OLD_URL_LABEL'); ?></label></div>
			<div class="controls"><div class="input-prepend redirect-url"><span class="add-on"><?php echo $root; ?></span><input type="text" class="input-xlarge redirect-create-source" readonly="readonly" /></div></div>
		</div>
		<div class="control-group">
			<div class="control-label"><label><?php echo JText::_('COM_REDIRECT_FIELD_TARGET_TYPE_LABEL'); ?></label></div>
			<div class="controls">
				<label class="radio inline"><input type="radio" name="target_type" value="internal" checked="checked" /> <?php echo JText::_('COM_REDIRECT_FIELD_TARGET_TYPE_INTERNAL'); ?></label>
				<label class="radio inline"><input type="radio" name="target_type" value="external" /> <?php echo JText::_('COM_REDIRECT_FIELD_TARGET_TYPE_EXTERNAL'); ?></label>
			</div>
		</div>
		<div class="control-group redirect-create-internal">
			<div class="control-label"><label for="redirect-create-new-url"><?php echo JText::_('COM_REDIRECT_FIELD_NEW_URL_LABEL'); ?></label></div>
			<div class="controls"><div class="input-prepend redirect-url"><span class="add-on"><?php echo $root; ?></span><input type="text" name="new_url" id="redirect-create-new-url" class="input-xlarge" /></div></div>
		</div>
		<div class="control-group redirect-create-external" hidden="hidden">
			<div class="control-label"><label for="redirect-create-new-url-external"><?php echo JText::_('COM_REDIRECT_FIELD_NEW_URL_EXTERNAL_LABEL'); ?></label></div>
			<div class="controls"><input type="url" name="new_url_external" id="redirect-create-new-url-external" class="input-xxlarge" placeholder="https://" /></div>
		</div>
		<div class="control-group">
			<div class="control-label"><label for="redirect-create-header"><?php echo JText::_('COM_REDIRECT_FIELD_REDIRECT_STATUS_CODE_LABEL'); ?></label></div>
			<div class="controls"><?php echo JHtml::_('select.genericlist', RedirectHelper::getCodeOptions(), 'header', 'class="input-xlarge"', 'value', 'text', '0', 'redirect-create-header'); ?></div>
		</div>
		<input type="hidden" name="error_id" value="" />
		<input type="hidden" name="task" value="errors.createRedirect" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</template>
<?php endif; ?>
