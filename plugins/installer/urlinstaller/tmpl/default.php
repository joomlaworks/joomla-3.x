<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.urlinstaller
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');

?>
<legend><?php echo JText::_('PLG_INSTALLER_URLINSTALLER_TEXT'); ?></legend>
<div class="control-group">
	<label for="install_url" class="control-label"><?php echo JText::_('PLG_INSTALLER_URLINSTALLER_TEXT'); ?></label>
	<div class="controls">
		<input type="text" id="install_url" name="install_url" class="span5 input_box" size="70" placeholder="https://"/>
	</div>
</div>
<div class="form-actions">
	<input type="button" class="btn btn-primary" id="installbutton_url"
		value="<?php echo JText::_('PLG_INSTALLER_URLINSTALLER_BUTTON'); ?>" data-install-type="url" data-install-field="install_url" data-install-message="<?php echo JText::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'); ?>" />
</div>
