<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.webinstaller
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var PlgInstallerWebinstaller $this */

?>

<div class="webinstaller-directory" id="webinstaller-directory">
	<div class="alert alert-error" id="web-loader-error" hidden="hidden">
		<button type="button" class="close" data-webinstaller-action="close-error" aria-label="<?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?>">&times;</button>
		<span id="web-loader-error-message"><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_LOADING_ERROR'); ?></span>
	</div>
	<div id="jed-container" class="tab-pane">
		<div class="well" id="web-loader">
			<h2><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_LOADING'); ?></h2>
		</div>
	</div>
	<div class="webinstaller-loading" id="webinstaller-loading" hidden="hidden" role="status" aria-label="<?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_LOADING'); ?>">
		<span class="webinstaller-spinner"></span>
	</div>
</div>

<fieldset class="uploadform" id="uploadform-web" hidden="hidden" dir="ltr">
	<div class="control-group">
		<strong><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM'); ?></strong><br />
		<span id="uploadform-web-name-label"><?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM_NAME'); ?>:</span> <span id="uploadform-web-name"></span><br />
		<?php echo Text::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM_URL'); ?>: <span id="uploadform-web-url"></span>
	</div>
	<div class="form-actions">
		<button type="button" class="btn btn-primary" data-webinstaller-action="install"><?php echo Text::_('COM_INSTALLER_INSTALL_BUTTON'); ?></button>
		<button type="button" class="btn btn-secondary" data-webinstaller-action="cancel"><?php echo Text::_('JCANCEL'); ?></button>
	</div>
</fieldset>
