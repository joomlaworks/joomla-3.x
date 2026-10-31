<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_joomlaupdate
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/** @var JoomlaupdateViewDefault $this */

// Joomla.submitbuttonUpload() and Joomla.installpackageChange() (js/joomlaupdate.js)
JText::script('COM_INSTALLER_MSG_INSTALL_PLEASE_SELECT_A_PACKAGE');
JText::script('COM_INSTALLER_MSG_WARNINGS_UPLOADFILETOOBIG');
JText::script('JGLOBAL_SELECTED_UPLOAD_FILE_SIZE');

$ajaxLoaderImage = JHtml::_('image', 'system/ajax-loader.gif', '', null, true, true);
$css             = <<< CSS
	#loading {
		background: rgba(255, 255, 255, .8) url('$ajaxLoaderImage') 50% 15% no-repeat;
		position: fixed;
		opacity: 1;
		overflow: hidden;
	}
CSS;
JFactory::getDocument()->addStyleDeclaration($css);
?>

<div class="alert alert-info">
	<p>
		<span class="icon icon-info" aria-hidden="true"></span>
		<?php echo JText::sprintf('COM_JOOMLAUPDATE_VIEW_DEFAULT_UPLOAD_INTRO', 'https://downloads.joomla.org/latest'); ?>
	</p>
</div>

<?php if (count($this->warnings)) : ?>
<fieldset>
	<legend>
		<?php echo JText::_('COM_INSTALLER_SUBMENU_WARNINGS'); ?>
	</legend>

	<?php $i = 0; ?>
	<?php echo JHtml::_('bootstrap.startAccordion', 'warnings', array('active' => 'warning' . $i)); ?>
	<?php foreach ($this->warnings as $message) : ?>
		<?php echo JHtml::_('bootstrap.addSlide', 'warnings', $message['message'], 'warning' . ($i++)); ?>
		<?php echo $message['description']; ?>
		<?php echo JHtml::_('bootstrap.endSlide'); ?>
	<?php endforeach; ?>
	<?php echo JHtml::_('bootstrap.addSlide', 'warnings', JText::_('COM_INSTALLER_MSG_WARNINGFURTHERINFO'), 'furtherinfo'); ?>
	<?php echo JText::_('COM_INSTALLER_MSG_WARNINGFURTHERINFODESC'); ?>
	<?php echo JHtml::_('bootstrap.endSlide'); ?>
	<?php echo JHtml::_('bootstrap.endAccordion'); ?>
</fieldset>
<?php endif; ?>

<form enctype="multipart/form-data" action="index.php" method="post" id="uploadForm" class="form-horizontal">
	<fieldset class="uploadform">
		<legend><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_TAB_UPLOAD'); ?></legend>
		<table class="table table-striped">
			<tbody>
			<tr>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_UPLOAD_PACKAGE_FILE'); ?>
				</td>
				<td>
					<input class="input_box" id="install_package" name="install_package" type="file" size="57" accept=".zip,application/zip" onchange="Joomla.installpackageChange()" /><br>
					<?php $maxSizeBytes = JUtility::getMaxUploadSize(); ?>
					<?php $maxSize = JHtml::_('number.bytes', $maxSizeBytes); ?>
					<input id="max_upload_size" name="max_upload_size" type="hidden" value="<?php echo $maxSizeBytes; ?>" />
					<div class="small"><?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', '&#x200E;' . $maxSize); ?></div>
					<div class="small hidden" id="file_size" name="file_size"><?php echo JText::sprintf('JGLOBAL_SELECTED_UPLOAD_FILE_SIZE', '&#x200E;' . ''); ?></div>
					<div class="alert alert-warning hidden" id="max_upload_size_warn">
						<?php echo JText::_('COM_INSTALLER_MSG_WARNINGS_UPLOADFILETOOBIG'); ?>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<label for="upload_restore_core_extensions"><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_RESTORE_CORE_EXTENSIONS'); ?></label>
				</td>
				<td>
					<label class="checkbox" for="upload_restore_core_extensions" style="max-width: 300px;">
						<input type="checkbox" name="restore_core_extensions" id="upload_restore_core_extensions" value="1" />
						<span class="small"><?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_RESTORE_CORE_EXTENSIONS_DESC'); ?></span>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_METHOD'); ?>
				</td>
				<td>
					<?php echo $this->methodSelectUpload; ?>
				</td>
			</tr>
			<tr id="upload_ftp_notice" <?php echo $this->ftpFieldsDisplay; ?>>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_FTP_NOTICE'); ?>
				</td>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_FTP_NOTICE_MESSAGE'); ?>
				</td>
			</tr>
			<tr id="upload_ftp_hostname" <?php echo $this->ftpFieldsDisplay; ?>>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_FTP_HOSTNAME'); ?>
				</td>
				<td>
					<input type="text" name="ftp_host" value="<?php echo $this->ftp['host']; ?>" />
				</td>
			</tr>
			<tr id="upload_ftp_port" <?php echo $this->ftpFieldsDisplay; ?>>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_FTP_PORT'); ?>
				</td>
				<td>
					<input type="text" name="ftp_port" value="<?php echo $this->ftp['port']; ?>" />
				</td>
			</tr>
			<tr id="upload_ftp_username" <?php echo $this->ftpFieldsDisplay; ?>>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_FTP_USERNAME'); ?>
				</td>
				<td>
					<input type="text" name="ftp_user" value="<?php echo $this->ftp['username']; ?>" />
				</td>
			</tr>
			<tr id="upload_ftp_password" <?php echo $this->ftpFieldsDisplay; ?>>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_FTP_PASSWORD'); ?>
				</td>
				<td>
					<input type="password" name="ftp_pass" value="<?php echo $this->ftp['password']; ?>" />
				</td>
			</tr>
			<tr id="upload_ftp_directory" <?php echo $this->ftpFieldsDisplay; ?>>
				<td>
					<?php echo JText::_('COM_JOOMLAUPDATE_VIEW_DEFAULT_FTP_DIRECTORY'); ?>
				</td>
				<td>
					<input type="text" name="ftp_root" value="<?php echo $this->ftp['directory']; ?>" />
				</td>
			</tr>
			</tbody>
			<tfoot>
			<tr>
				<td>
					&nbsp;
				</td>
				<td>
					<button class="btn btn-primary" type="button" onclick="Joomla.submitbuttonUpload()"><?php echo JText::_('COM_INSTALLER_UPLOAD_AND_INSTALL'); ?></button>
				</td>
			</tr>
			</tfoot>
		</table>
	</fieldset>

	<input type="hidden" name="task" value="update.upload" />
	<input type="hidden" name="option" value="com_joomlaupdate" />
	<?php echo JHtml::_('form.token'); ?>

</form>
