<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewPreinstallHtml $this */
?>
<form action="index.php" method="post" id="languageForm" class="language-picker">
	<label for="jform_language"><?php echo JHtml::_('InstallationHtml.helper.icon', 'globe'); ?><span><?php echo JText::_('INSTL_SELECT_LANGUAGE_TITLE'); ?></span></label>
<?php echo JHtml::_('InstallationHtml.helper.indent', JHtml::_('InstallationHtml.helper.normalize', $this->form->getInput('language')), 1); ?>

	<input type="hidden" name="view" value="preinstall" />
	<input type="hidden" name="task" value="setlanguage" />
	<?php echo JHtml::_('form.token'); ?>

</form>
<form action="index.php" method="post" id="adminForm" class="view" novalidate="novalidate">
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_PRECHECK_TITLE'); ?></p>
			<h1><?php echo JText::_('INSTL_PAGE_TITLE'); ?></h1>
		</div>
		<div class="actions">
			<button type="button" class="btn btn-primary" data-action="next"><?php echo JHtml::_('InstallationHtml.helper.icon', 'refresh'); ?> <span><?php echo JText::_('JCHECK_AGAIN'); ?></span></button>
		</div>
	</header>
	<div class="grid-2">
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_PRECHECK_TITLE'); ?></h2>
			<p class="lead"><?php echo JText::_('INSTL_PRECHECK_DESC'); ?></p>
<?php echo JHtml::_('InstallationHtml.helper.phpOptions', $this->options, 3); ?>

		</section>
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_PRECHECK_RECOMMENDED_SETTINGS_TITLE'); ?></h2>
			<p class="lead"><?php echo JText::_('INSTL_PRECHECK_RECOMMENDED_SETTINGS_DESC'); ?></p>
<?php echo JHtml::_('InstallationHtml.helper.phpSettings', $this->settings, 3); ?>

		</section>
	</div>
	<footer class="view-footer">
		<div class="actions">
			<button type="button" class="btn btn-primary" data-action="next"><?php echo JHtml::_('InstallationHtml.helper.icon', 'refresh'); ?> <span><?php echo JText::_('JCHECK_AGAIN'); ?></span></button>
		</div>
	</footer>
	<input type="hidden" name="task" value="preinstall" />
	<?php echo JHtml::_('form.token'); ?>

</form>
