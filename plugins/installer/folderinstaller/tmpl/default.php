<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.folderinstaller
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');

$app = JFactory::getApplication('administrator');

?>
<legend><?php echo JText::_('PLG_INSTALLER_FOLDERINSTALLER_TEXT'); ?></legend>
<div class="control-group">
	<label for="install_directory" class="control-label"><?php echo JText::_('PLG_INSTALLER_FOLDERINSTALLER_TEXT'); ?></label>
	<div class="controls">
		<input type="text" id="install_directory" name="install_directory" class="span5 input_box" size="70"
			value="<?php echo $app->input->get('install_directory', $app->get('tmp_path')); ?>" />
	</div>
</div>
<div class="form-actions">
	<input type="button" class="btn btn-primary" id="installbutton_directory"
		value="<?php echo JText::_('PLG_INSTALLER_FOLDERINSTALLER_BUTTON'); ?>" data-install-type="folder" data-install-field="install_directory" data-install-message="<?php echo JText::_('PLG_INSTALLER_FOLDERINSTALLER_NO_INSTALL_PATH'); ?>" />
</div>
