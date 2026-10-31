<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.packageinstaller
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('form.csrf');

JText::script('PLG_INSTALLER_PACKAGEINSTALLER_NO_PACKAGE');
JText::script('PLG_INSTALLER_PACKAGEINSTALLER_UPLOAD_ERROR_UNKNOWN');
JText::script('PLG_INSTALLER_PACKAGEINSTALLER_UPLOAD_ERROR_EMPTY');
JText::script('COM_INSTALLER_MSG_WARNINGS_UPLOADFILETOOBIG');

// The drop area and Joomla.submitbuttonpackage() (js/packageinstaller.js)
$return = JFactory::getApplication()->input->getBase64('return');
JHtml::_('script', 'plugins/installer/packageinstaller/js/packageinstaller.min.js', array('version' => 'auto', 'position' => 'footer'), array('defer' => true));

JFactory::getDocument()->addStyleDeclaration(
    <<<CSS
	#dragarea {
		background-color: #fafbfc;
		border: 1px dashed #999;
		box-sizing: border-box;
		padding: 5% 0;
		transition: all 0.2s ease 0s;
		width: 100%;
	}

	#dragarea p.lead {
		color: #999;
	}

	#upload-icon {
		font-size: 48px;
		width: auto;
		height: auto;
		margin: 0;
		line-height: 175%;
		color: #999;
		transition: all .2s;
	}

	#dragarea.hover {
		border-color: #666;
		background-color: #eee;
	}

	#dragarea.hover #upload-icon,
	#dragarea p.lead {
		color: #666;
	}

	 .upload-progress, .install-progress {
		width: 50%;
		margin: 5px auto;
	 }

	/* Default transition (.3s) is too slow, progress will not run to 100% */
	.upload-progress .progress .bar {
		transition: width .1s;
	}

	#dragarea[data-state=pending] .upload-progress {
		display: none;
	}

	#dragarea[data-state=pending] .install-progress {
		display: none;
	}

	#dragarea[data-state=uploading] .install-progress {
		display: none;
	}

	#dragarea[data-state=uploading] .upload-actions {
		display: none;
	}

	#dragarea[data-state=installing] .upload-progress {
		display: none;
	}

	#dragarea[data-state=installing] .upload-actions {
		display: none;
	}
CSS
);

$maxSizeBytes = JFilesystemHelper::fileUploadMaxSize(false);
$maxSize = JHtml::_('number.bytes', $maxSizeBytes);
?>
<legend><?php echo JText::_('PLG_INSTALLER_PACKAGEINSTALLER_UPLOAD_INSTALL_JOOMLA_EXTENSION'); ?></legend>

<div id="uploader-wrapper">
	<div id="dragarea" data-state="pending">
		<div id="dragarea-content" class="text-center">
			<p>
				<span id="upload-icon" class="icon-upload" aria-hidden="true"></span>
			</p>
			<div class="upload-progress">
				<div class="progress progress-striped active">
					<div class="bar bar-success"
						style="width: 0;"
						role="progressbar"
						aria-valuenow="0"
						aria-valuemin="0"
						aria-valuemax="100"
					></div>
				</div>
				<p class="lead">
					<span class="uploading-text">
						<?php echo JText::_('PLG_INSTALLER_PACKAGEINSTALLER_UPLOADING'); ?>
					</span>
					<span class="uploading-number">0</span><span class="uploading-symbol">%</span>
				</p>
			</div>
			<div class="install-progress">
				<div class="progress progress-striped active">
					<div class="bar" style="width: 100%;"></div>
				</div>
				<p class="lead">
					<span class="installing-text">
						<?php echo JText::_('PLG_INSTALLER_PACKAGEINSTALLER_INSTALLING'); ?>
					</span>
				</p>
			</div>
			<div class="upload-actions">
				<p class="lead">
					<?php echo JText::_('PLG_INSTALLER_PACKAGEINSTALLER_DRAG_FILE_HERE'); ?>
				</p>
				<p>
					<button id="select-file-button" type="button" class="btn btn-success">
						<span class="icon-copy" aria-hidden="true"></span>
						<?php echo JText::_('PLG_INSTALLER_PACKAGEINSTALLER_SELECT_FILE'); ?>
					</button>
				</p>
				<p>
					<?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $maxSize); ?>
				</p>
			</div>
		</div>
	</div>
</div>

<div id="legacy-uploader" style="display: none;">
	<div class="control-group">
		<label for="install_package" class="control-label"><?php echo JText::_('PLG_INSTALLER_PACKAGEINSTALLER_EXTENSION_PACKAGE_FILE'); ?></label>
		<div class="controls">
			<input class="input_box" id="install_package" name="install_package" type="file" size="57" />
			<input id="max_upload_size" name="max_upload_size" type="hidden" value="<?php echo $maxSizeBytes; ?>" /><br>
			<?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $maxSize); ?>
		</div>
	</div>
	<div class="form-actions">
		<button class="btn btn-primary" type="button" id="installbutton_package" onclick="Joomla.submitbuttonpackage()">
			<?php echo JText::_('PLG_INSTALLER_PACKAGEINSTALLER_UPLOAD_AND_INSTALL'); ?>
		</button>
	</div>

	<input id="installer-return" name="return" type="hidden" value="<?php echo $return; ?>" />
</div>
