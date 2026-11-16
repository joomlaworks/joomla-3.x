<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('behavior.core');
JHtml::_('formbehavior.chosen', 'select');

$function  = JFactory::getApplication()->input->getCmd('function', 'jSelectPosition');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$clientId  = (int) $this->state->get('client_id');
$lang      = JFactory::getLanguage();
$i         = 0;
?>
<div class="container-popup">

	<form action="<?php echo JRoute::_('index.php?option=com_modules&view=positions&layout=modal&tmpl=component&function=' . $function . '&client_id=' . $clientId); ?>" method="post" name="adminForm" id="adminForm">

		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

		<div class="clearfix"></div>

		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table table-striped table-condensed">
				<thead>
					<tr>
						<th class="title" width="35%">
							<?php echo JHtml::_('searchtools.sort', 'COM_MODULES_HEADING_POSITION', 'value', $listDirn, $listOrder); ?>
						</th>
						<th class="hidden-phone">
							<?php echo JHtml::_('searchtools.sort', 'COM_MODULES_HEADING_TEMPLATES', 'templates', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="2">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $value => $templates) : ?>
					<tr class="row<?php echo $i++ % 2; ?>">
						<td>
							<a class="select-link" href="javascript:void(0)" data-function="<?php echo $this->escape($function); ?>" data-id="<?php echo $this->escape((string) $value); ?>" data-select-args="id">
								<?php echo (string) $value === '' ? JText::_('JNONE') : $this->escape((string) $value); ?></a>
						</td>
						<td class="small hidden-phone">
							<?php foreach ($templates as $template => $label) : ?>
								<div><?php echo $this->escape($lang->hasKey($label) ? JText::sprintf('COM_MODULES_MODULE_TEMPLATE_POSITION', JText::_($template), JText::_($label)) : JText::_($template)); ?></div>
							<?php endforeach; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>

	</form>
</div>
