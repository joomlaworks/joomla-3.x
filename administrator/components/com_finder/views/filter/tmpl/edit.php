<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('behavior.tabstate');

// The form's script (js/finder.js): Show All / Hide All, Select All, and the count of chosen branches
JText::script('COM_FINDER_FILTER_SHOW_ALL');
JText::script('COM_FINDER_FILTER_HIDE_ALL');
JHtml::_('script', 'administrator/components/com_finder/js/finder.js', array('version' => 'auto', 'position' => 'footer'), array('defer' => true));

JFactory::getDocument()->addStyleDeclaration('
	.accordion-inner .control-group .controls {
		margin-left: 10px;
	}
	.accordion-inner > .control-group {
		margin-bottom: 0;
	}
	');
?>

<form action="<?php echo JRoute::_('index.php?option=com_finder&view=filter&layout=edit&filter_id=' . (int) $this->item->filter_id); ?>" method="post" name="adminForm" data-submit-validate id="adminForm" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_FINDER_EDIT_FILTER')); ?>
		<div class="row-fluid">
			<div class="span9">
				<?php if ($this->total > 0) : ?>
					<div class="well">
						<?php echo $this->form->renderField('map_count'); ?>
					</div>
					<button class="btn jform-rightbtn" type="button" data-finder-select-all>
						<span class="icon-checkbox-partial" aria-hidden="true"></span> <?php echo JText::_('JGLOBAL_SELECTION_INVERT'); ?></button>

					<button class="btn pull-right" type="button" id="rightbtn" ><?php echo JText::_('COM_FINDER_FILTER_SHOW_ALL'); ?></button>
					<hr>
				<?php endif; ?>

				<?php echo JHtml::_('filter.slider', array('selected_nodes' => $this->filter->data)); ?>
			</div>
			<div class="span3">
				<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row-fluid form-horizontal-desktop">
			<?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="return" value="<?php echo JFactory::getApplication()->input->get('return', '', 'BASE64'); ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
