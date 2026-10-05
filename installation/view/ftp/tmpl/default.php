<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewDefault $this */
?>
<?php echo JHtml::_('InstallationHtml.helper.stepbar'); ?>

<form action="index.php" method="post" id="adminForm" class="view" novalidate="novalidate">
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_STEP_FTP_LABEL'); ?></p>
			<h1><?php echo JText::_('INSTL_FTP'); ?></h1>
		</div>
<?php echo JHtml::_('InstallationHtml.helper.nav', 'database', 'JNEXT', 2); ?>

	</header>
	<section class="panel">
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'ftp_enable', null, '', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'ftp_user', 'INSTL_FTP_USER_DESC', '', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'ftp_pass', 'INSTL_FTP_PASSWORD_DESC', '', null, 2); ?>

		<div class="field">
			<div class="field-label"></div>
			<div class="field-control">
				<button type="button" id="verifybutton" class="btn btn-success" data-action="verify-ftp"><?php echo JHtml::_('InstallationHtml.helper.icon', 'check'); ?> <span><?php echo JText::_('INSTL_VERIFY_FTP_SETTINGS'); ?></span></button>
			</div>
		</div>
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'ftp_host', null, '', null, 2); ?>

<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'ftp_port', null, '', null, 2); ?>

		<div class="field">
			<div class="field-label"><?php echo trim($this->form->getLabel('ftp_root')); ?></div>
			<div class="field-control">
				<div class="input-group">
<?php echo JHtml::_('InstallationHtml.helper.indent', JHtml::_('InstallationHtml.helper.normalize', $this->form->getInput('ftp_root')), 5); ?>

					<button type="button" id="findbutton" class="btn btn-ghost" data-action="detect-ftp-root"><?php echo JHtml::_('InstallationHtml.helper.icon', 'folder'); ?> <span><?php echo JText::_('INSTL_AUTOFIND_FTP_PATH'); ?></span></button>
				</div>
			</div>
		</div>
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'ftp_save', null, '', null, 2); ?>

	</section>
	<footer class="view-footer">
<?php echo JHtml::_('InstallationHtml.helper.nav', 'database', 'JNEXT', 2); ?>

	</footer>
	<input type="hidden" name="task" value="ftp" />
	<?php echo JHtml::_('form.token'); ?>

</form>
