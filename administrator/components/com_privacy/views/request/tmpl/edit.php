<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_privacy
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var PrivacyViewRequest $this */

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

?>

<form action="<?php echo JRoute::_('index.php?option=com_privacy&view=request&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" data-submit-validate id="item-form" class="form-validate">
	<div class="form-horizontal">
		<div class="row-fluid">
			<div class="span9">
				<fieldset class="adminform">
					<?php echo $this->form->renderField('email'); ?>
					<?php echo $this->form->renderField('status'); ?>
					<?php echo $this->form->renderField('request_type'); ?>
				</fieldset>
			</div>
		</div>

		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
