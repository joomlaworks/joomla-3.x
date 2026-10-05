<?php
/**
 * @package     Joomla.Installation
 * @subpackage  View
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewDefault $this */
?>
<?php echo JHtml::_('InstallationHtml.helper.stepbar'); ?>

<form action="index.php" method="post" id="languageForm" class="language-picker">
	<label for="jform_language"><?php echo JHtml::_('InstallationHtml.helper.icon', 'globe'); ?><span><?php echo JText::_('INSTL_SELECT_LANGUAGE_TITLE'); ?></span></label>
<?php echo JHtml::_('InstallationHtml.helper.indent', JHtml::_('InstallationHtml.helper.normalize', $this->form->getInput('language')), 1); ?>

	<input type="hidden" name="task" value="setlanguage" />
	<?php echo JHtml::_('form.token'); ?>

</form>
<form action="index.php" method="post" id="adminForm" class="view" novalidate="novalidate">
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_STEP_SITE_LABEL'); ?></p>
			<h1><?php echo JText::_('INSTL_SITE'); ?></h1>
		</div>
<?php echo JHtml::_('InstallationHtml.helper.nav', null, 'JNEXT', 2); ?>

	</header>
	<div class="grid-2">
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_STEP_SITE_LABEL'); ?></h2>
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'site_name', 'INSTL_SITE_NAME_DESC', '', null, 3); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'site_metadesc', 'INSTL_SITE_METADESC_TITLE_LABEL', '', null, 3); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'site_offline', 'INSTL_SITE_OFFLINE_TITLE_LABEL', '', null, 3); ?>

		</section>
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_SUPER_USER_TITLE'); ?></h2>
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'admin_email', 'INSTL_ADMIN_EMAIL_DESC', '', null, 3); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'admin_user', 'INSTL_ADMIN_USER_DESC', '', null, 3); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'admin_password', 'INSTL_ADMIN_PASSWORD_DESC', '', null, 3); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'admin_password2', null, '', null, 3); ?>

		</section>
	</div>
	<footer class="view-footer">
<?php echo JHtml::_('InstallationHtml.helper.nav', null, 'JNEXT', 2); ?>

	</footer>
	<input type="hidden" name="task" value="site" />
	<?php echo JHtml::_('form.token'); ?>

</form>
