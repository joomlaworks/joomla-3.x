<?php
/**
 * @package     Joomla.Installation
 * @subpackage  View
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/* @var InstallationViewSummaryHtml $this */

// Determine if the configuration file path is writable.
$path   = JPATH_CONFIGURATION . '/configuration.php';
$useftp = file_exists($path) ? !is_writable($path) : !is_writable(JPATH_CONFIGURATION . '/');
$prev   = $useftp ? 'ftp' : 'database';
$o      = $this->options;
$e      = function ($key) use ($o) {
	return JHtml::_('InstallationHtml.helper.escape', isset($o[$key]) ? $o[$key] : '');
};
$yesNo  = function ($value, $good = true) {
	return JHtml::_('InstallationHtml.helper.badge', JText::_($value ? 'JYES' : 'JNO'), (bool) $value === $good ? 'success' : 'warning');
};
$sqlite = isset($o['db_type']) && $o['db_type'] === 'mysqlonsqlite';
$remove = isset($o['db_old']) && $o['db_old'] === 'remove';
?>
<?php echo JHtml::_('InstallationHtml.helper.stepbar'); ?>

<form action="index.php" method="post" id="adminForm" class="view" novalidate="novalidate">
	<header class="view-header">
		<div class="view-title">
			<p class="eyebrow"><?php echo JText::_('INSTL_STEP_SUMMARY_LABEL'); ?></p>
			<h1><?php echo JText::_('INSTL_FINALISATION'); ?></h1>
		</div>
<?php echo JHtml::_('InstallationHtml.helper.nav', $prev, 'INSTL_SUMMARY_INSTALL', 2); ?>

	</header>
	<section class="panel">
		<h2 class="section-title"><?php echo JText::_('INSTL_SITE_INSTALL_SAMPLE_LABEL'); ?></h2>
		<p class="lead"><?php echo JText::_('INSTL_SITE_INSTALL_SAMPLE_DESC'); ?></p>
		<div class="sample-choices">
<?php echo JHtml::_('InstallationHtml.helper.indent', JHtml::_('InstallationHtml.helper.normalize', $this->form->getInput('sample_file')), 3); ?>

		</div>
	</section>
	<section class="panel">
		<h2 class="section-title"><?php echo JText::_('INSTL_STEP_SUMMARY_LABEL'); ?></h2>
		<div class="field" id="summary_email">
			<div class="field-label"><?php echo trim($this->form->getLabel('summary_email')); ?></div>
			<div class="field-control">
<?php echo JHtml::_('InstallationHtml.helper.indent', JHtml::_('InstallationHtml.helper.normalize', $this->form->getInput('summary_email')), 4); ?>

				<p class="help"><?php echo JText::sprintf('INSTL_SUMMARY_EMAIL_DESC', '<code>' . $e('admin_email') . '</code>'); ?></p>
			</div>
		</div>
<?php echo JHtml::_('InstallationHtml.helper.field', $this->form, 'summary_email_passwords', 'INSTL_SUMMARY_EMAIL_PASSWORDS_DESC', 'id="email_passwords" data-show-when="summary_email=1"', null, 2); ?>

	</section>
	<div class="grid-2">
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_SITE'); ?></h2>
			<div class="table-wrap">
				<table class="table">
					<tbody>
						<tr><td class="item"><?php echo JText::_('INSTL_SITE_NAME_LABEL'); ?></td><td class="value"><?php echo $e('site_name'); ?></td></tr>
<?php if (!empty($o['site_metadesc'])) : ?>
						<tr><td class="item"><?php echo JText::_('INSTL_SITE_METADESC_LABEL'); ?></td><td class="value"><?php echo $e('site_metadesc'); ?></td></tr>
<?php endif; ?>
						<tr><td class="item"><?php echo JText::_('INSTL_SITE_OFFLINE_LABEL'); ?></td><td class=""><?php echo $yesNo(!empty($o['site_offline']), false); ?></td></tr>
						<tr><td class="item"><?php echo JText::_('INSTL_ADMIN_EMAIL_LABEL'); ?></td><td class="value"><code><?php echo $e('admin_email'); ?></code></td></tr>
						<tr><td class="item"><?php echo JText::_('INSTL_ADMIN_USER_LABEL'); ?></td><td class="value"><code><?php echo $e('admin_user'); ?></code></td></tr>
					</tbody>
				</table>
			</div>
		</section>
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_DATABASE'); ?></h2>
			<div class="table-wrap">
				<table class="table">
					<tbody>
						<tr><td class="item"><?php echo JText::_('INSTL_DATABASE_TYPE_LABEL'); ?></td><td class="value"><?php echo JHtml::_('InstallationHtml.helper.escape', JText::_(isset($o['db_type']) ? $o['db_type'] : '')); ?></td></tr>
<?php if (!$sqlite) : ?>
						<tr><td class="item"><?php echo JText::_('INSTL_DATABASE_HOST_LABEL'); ?></td><td class="value"><?php echo $e('db_host'); ?></td></tr>
						<tr><td class="item"><?php echo JText::_('INSTL_DATABASE_USER_LABEL'); ?></td><td class="value"><?php echo $e('db_user'); ?></td></tr>
<?php endif; ?>
						<tr><td class="item"><?php echo JText::_($sqlite ? 'INSTL_DATABASE_SQLITE_FILE_LABEL' : 'INSTL_DATABASE_NAME_LABEL'); ?></td><td class="value"><code><?php echo $e('db_name'); ?></code></td></tr>
						<tr><td class="item"><?php echo JText::_('INSTL_DATABASE_PREFIX_LABEL'); ?></td><td class="value"><code><?php echo $e('db_prefix'); ?></code></td></tr>
						<tr><td class="item"><?php echo JText::_('INSTL_DATABASE_OLD_PROCESS_LABEL'); ?></td><td class=""><?php echo JHtml::_('InstallationHtml.helper.badge', JText::_($remove ? 'INSTL_DATABASE_FIELD_VALUE_REMOVE' : 'INSTL_DATABASE_FIELD_VALUE_BACKUP'), $remove ? 'warning' : 'success'); ?></td></tr>
					</tbody>
				</table>
			</div>
		</section>
	</div>
<?php if ($useftp) : ?>
	<section class="panel">
		<h2 class="section-title"><?php echo JText::_('INSTL_FTP'); ?></h2>
		<div class="table-wrap">
			<table class="table">
				<tbody>
					<tr><td class="item"><?php echo JText::_('INSTL_FTP_ENABLE_LABEL'); ?></td><td class=""><?php echo $yesNo(!empty($o['ftp_enable'])); ?></td></tr>
<?php if (!empty($o['ftp_enable'])) : ?>
					<tr><td class="item"><?php echo JText::_('INSTL_FTP_USER_LABEL'); ?></td><td class="value"><?php echo $e('ftp_user'); ?></td></tr>
					<tr><td class="item"><?php echo JText::_('INSTL_FTP_HOST_LABEL'); ?></td><td class="value"><?php echo $e('ftp_host'); ?></td></tr>
					<tr><td class="item"><?php echo JText::_('INSTL_FTP_PORT_LABEL'); ?></td><td class="value"><?php echo $e('ftp_port'); ?></td></tr>
					<tr><td class="item"><?php echo JText::_('INSTL_FTP_ROOT_LABEL'); ?></td><td class="value"><?php echo $e('ftp_root'); ?></td></tr>
					<tr><td class="item"><?php echo JText::_('INSTL_FTP_SAVE_LABEL'); ?></td><td class=""><?php echo $yesNo(!empty($o['ftp_save']), false); ?></td></tr>
<?php endif; ?>
				</tbody>
			</table>
		</div>
	</section>
<?php endif; ?>
	<div class="grid-2">
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_PRECHECK_TITLE'); ?></h2>
			<p class="lead"><?php echo JText::_('INSTL_PRECHECK_DESC'); ?></p>
<?php echo JHtml::_('InstallationHtml.helper.phpOptions', $this->phpoptions, 3); ?>

		</section>
		<section class="panel">
			<h2 class="section-title"><?php echo JText::_('INSTL_PRECHECK_RECOMMENDED_SETTINGS_TITLE'); ?></h2>
			<p class="lead"><?php echo JText::_('INSTL_PRECHECK_RECOMMENDED_SETTINGS_DESC'); ?></p>
<?php echo JHtml::_('InstallationHtml.helper.phpSettings', $this->phpsettings, 3); ?>

		</section>
	</div>
	<footer class="view-footer">
<?php echo JHtml::_('InstallationHtml.helper.nav', $prev, 'INSTL_SUMMARY_INSTALL', 2); ?>

	</footer>
	<input type="hidden" name="task" value="summary" />
	<?php echo JHtml::_('form.token'); ?>

</form>
