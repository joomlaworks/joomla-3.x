<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_installer
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');

// JoomlaInstaller, Joomla.submitbutton4() and the install buttons (js/installer.js); install plugins call them by name
JHtml::_('behavior.core');
JHtml::_('behavior.tabstate');
JText::script('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL');
JHtml::_('script', 'administrator/components/com_installer/js/installer.min.js', array('version' => 'auto'));

JFactory::getDocument()->addStyleDeclaration(
    '
	#loading {
		background: rgba(255, 255, 255, .8) url(\'' . JHtml::_('image', 'system/ajax-loader.gif', '', null, true, true) . '\') 50% 15% no-repeat;
		position: fixed;
		opacity: 0.8;
		overflow: hidden;
	}
	'
);

?>

<div id="installer-install" class="clearfix">
	<form enctype="multipart/form-data" action="<?php echo JRoute::_('index.php?option=com_installer&view=install'); ?>"
		method="post" name="adminForm" id="adminForm" class="form-horizontal">
		<?php if (!empty($this->sidebar)) : ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
			<?php else : ?>
			<div id="j-main-container">
				<?php endif; ?>
				<!-- Render messages set by extension install scripts here -->
				<?php if ($this->showMessage) : ?>
					<?php echo $this->loadTemplate('message'); ?>
				<?php elseif ($this->showJedAndWebInstaller) : ?>
					<div class="alert alert-info j-jed-message"
						style="margin-bottom: 40px; line-height: 2em; color:#333333;">
						<?php echo JHtml::_(
						    'link',
						    JRoute::_('index.php?option=com_config&view=component&component=com_installer&path=&return=' . urlencode(base64_encode(JUri::getInstance()))),
						    '<span class="element-invisible">' . str_replace('"', '&quot;', JText::_('COM_INSTALLER_SHOW_JED_INFORMATION_TOOLTIP')) . '</span>',
						    'class="alert-options hasTooltip icon-options" data-dismiss="alert" title="' . str_replace('"', '&quot;', JText::_('COM_INSTALLER_SHOW_JED_INFORMATION_TOOLTIP')) . '"'
						);
				    ?>
						<p><?php echo JText::_('COM_INSTALLER_INSTALL_FROM_WEB_INFO'); ?>
							<?php echo JText::_('COM_INSTALLER_INSTALL_FROM_WEB_TOS'); ?></p>
						<a class="btn" href="<?php echo JRoute::_('index.php?option=com_plugins&view=plugins&filter_folder=installer&filter_element=webinstaller&filter_enabled=&filter_search='); ?>">
							<?php echo JText::_('COM_INSTALLER_INSTALL_FROM_WEB_ADD_TAB'); ?></a>
					</div>
				<?php endif; ?>
				<?php echo JHtml::_('bootstrap.startTabSet', 'myTab'); ?>
				<?php // Show installation tabs at the start?>
				<?php $firstTab = JEventDispatcher::getInstance()->trigger('onInstallerViewBeforeFirstTab', array()); ?>
				<?php // Show installation tabs?>
				<?php $tabs = JEventDispatcher::getInstance()->trigger('onInstallerAddInstallationTab', array()); ?>
				<?php foreach ($tabs as $tab) : ?>
					<?php echo JHtml::_('bootstrap.addTab', 'myTab', $tab['name'], $tab['label']); ?>
					<fieldset class="uploadform">
						<?php echo $tab['content']; ?>
					</fieldset>
					<?php echo JHtml::_('bootstrap.endTab'); ?>
				<?php endforeach; ?>
				<?php // Show installation tabs at the end?>
				<?php $lastTab = JEventDispatcher::getInstance()->trigger('onInstallerViewAfterLastTab', array()); ?>
				<?php $tabs = array_merge($firstTab, $tabs, $lastTab); ?>
				<?php if (!$tabs) : ?>
					<?php JFactory::getApplication()->enqueueMessage(JText::_('COM_INSTALLER_NO_INSTALLATION_PLUGINS_FOUND'), 'warning'); ?>
				<?php endif; ?>

				<?php if ($this->ftp) : ?>
					<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'ftp', JText::_('COM_INSTALLER_MSG_DESCFTPTITLE')); ?>
					<?php echo $this->loadTemplate('ftp'); ?>
					<?php echo JHtml::_('bootstrap.endTab'); ?>
				<?php endif; ?>

				<input type="hidden" name="installtype" value=""/>
				<input type="hidden" name="task" value="install.install"/>
				<?php echo JHtml::_('form.token'); ?>

				<?php echo JHtml::_('bootstrap.endTabSet'); ?>
			</div>
	</form>
</div>
<div id="loading"></div>
