<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

JHtml::_('stylesheet', 'administrator/components/com_languages/css/languages.css', array('version' => 'auto'));

// The search (js/languages.js): the cache of known strings is refreshed first when it expired; requests send the form token
JHtml::_('behavior.core');
JHtml::_('form.csrf');
JFactory::getDocument()->addScriptOptions('com_languages.override', array('expired' => $this->state->get('cache_expired') == 1));
JHtml::_('script', 'administrator/components/com_languages/js/languages.min.js', array('version' => 'auto'));

?>

<form action="<?php echo JRoute::_('index.php?option=com_languages&id=' . $this->item->key); ?>" method="post" name="adminForm" data-submit-validate id="override-form" class="form-validate form-horizontal">
	<div class="row-fluid">
		<div class="span6">
			<fieldset>
				<legend><?php echo empty($this->item->key) ? JText::_('COM_LANGUAGES_VIEW_OVERRIDE_EDIT_NEW_OVERRIDE_LEGEND') : JText::_('COM_LANGUAGES_VIEW_OVERRIDE_EDIT_EDIT_OVERRIDE_LEGEND'); ?></legend>
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('language'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('language'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('client'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('client'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('key'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('key'); ?>
					</div>
				</div>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('override'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('override'); ?>
					</div>
				</div>

				<?php if ($this->state->get('filter.client') == 'administrator') : ?>
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('both'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('both'); ?>
					</div>
				</div>
				<?php endif; ?>

				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('file'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('file'); ?>
					</div>
				</div>
			</fieldset>

		</div>

		<div class="span6">
			<fieldset>
				<legend><?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDE_SEARCH_LEGEND'); ?></legend>

				<div class="alert alert-info"><p><?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDE_SEARCH_TIP'); ?></p></div>

				<div class="control-group">
					<?php echo $this->form->getInput('searchstring'); ?>
					<button type="submit" class="btn btn-primary" onclick="Joomla.overrider.searchStrings();return false;" formnovalidate>
						<?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDE_SEARCH_BUTTON'); ?>
					</button>
					<span id="refresh-status" class="overrider-spinner  help-block">
						<?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDE_REFRESHING'); ?>
					</span>
				</div>
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getLabel('searchtype'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('searchtype'); ?>
					</div>
				</div>

			</fieldset>

			<fieldset id="results-container" class="adminform">
				<legend><?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDE_RESULTS_LEGEND'); ?></legend>
				<span id="more-results">
					<a href="javascript:Joomla.overrider.searchStrings(Joomla.overrider.states.more);">
						<?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDE_MORE_RESULTS'); ?></a>
				</span>
			</fieldset>

			<input type="hidden" name="task" value="" />
			<input type="hidden" name="id" value="<?php echo $this->item->key; ?>" />

			<?php echo JHtml::_('form.token'); ?>
		</div>
	</div>
</form>
