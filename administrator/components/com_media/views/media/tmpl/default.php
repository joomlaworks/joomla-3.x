<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

$user  = JFactory::getUser();
$input = JFactory::getApplication()->input;
$lang  = JFactory::getLanguage();
$style = JFactory::getApplication()->getUserStateFromRequest('media.list.layout', 'layout', 'thumbs', 'word');

// The files folder relative to the site (e.g. "images"), shown before a new folder's name: COM_MEDIA_BASE is the server's path to it
$base = trim(str_replace('\\', '/', (string) JComponentHelper::getParams('com_media')->get('file_path', 'images')), '/');

JFactory::getDocument()->addScriptDeclaration(
    "
		var basepath = " . json_encode($base) . ";
		var viewstyle = " . json_encode((string) $style) . ";
	"
);

JHtml::_('behavior.keepalive');
JHtml::_('bootstrap.framework');
JHtml::_('script', 'com_media/media.min.js', array('version' => 'auto', 'relative' => true));
JHtml::_('stylesheet', 'com_media/media.min.css', array('version' => 'auto', 'relative' => true));
JHtml::_('behavior.styles');

// The Crop button's window (media.js, global.js' cropper)
if ($user->authorise('core.create', 'com_media') && $user->authorise('core.delete', 'com_media')) {
    JHtml::_('behavior.cms');
    JFactory::getDocument()->addScriptOptions('com_media.crop', array(
        'url'  => JUri::base(true) . '/index.php?option=com_media&task=file.crop&format=json',
        'base' => COM_MEDIA_BASEURL,
    ));

    foreach (array('COM_MEDIA_CROP_TITLE', 'COM_MEDIA_CROP_FREE', 'COM_MEDIA_CROP_RATIO', 'COM_MEDIA_CROP_SIZE', 'COM_MEDIA_CROP_NO_AREA', 'COM_MEDIA_CROP_SAVE_NEW', 'COM_MEDIA_CROP_SAVE_REPLACE', 'COM_MEDIA_CROP_CONFIRM_REPLACE', 'COM_MEDIA_ERROR_UNABLE_TO_CROP', 'JCANCEL') as $key) {
        JText::script($key);
    }
}

?>
<div class="row-fluid">
	<!-- Begin Sidebar -->
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
		<div class="j-toggle-sidebar-header">
		<h3><?php echo JText::_('COM_MEDIA_FOLDERS'); ?> </h3>
		</div>
		<div id="treeview" class="sidebar">
			<div id="media-tree_tree" class="tree-holder">
				<?php echo $this->loadTemplate('folders'); ?>
			</div>
		</div>
	</div>
	<!-- End Sidebar -->

	<!-- Begin Content -->
	<div id="j-main-container" class="span10">
		<?php echo $this->loadTemplate('navigation'); ?>
		<?php if (($user->authorise('core.create', 'com_media')) and $this->require_ftp) : ?>
			<form action="index.php?option=com_media&amp;task=ftpValidate" name="ftpForm" id="ftpForm" method="post">
				<fieldset title="<?php echo JText::_('COM_MEDIA_DESCFTPTITLE'); ?>">
					<legend><?php echo JText::_('COM_MEDIA_DESCFTPTITLE'); ?></legend>
					<?php echo JText::_('COM_MEDIA_DESCFTP'); ?>
					<label for="username"><?php echo JText::_('JGLOBAL_USERNAME'); ?></label>
					<input type="text" id="username" name="username" size="70" value="" />

					<label for="password"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
					<input type="password" id="password" name="password" size="70" value="" />
				</fieldset>
			</form>
		<?php endif; ?>

		<form action="index.php?option=com_media" name="adminForm" id="mediamanager-form" method="post" enctype="multipart/form-data" >
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="cb1" id="cb1" value="0" />
			<input class="update-folder" type="hidden" name="folder" id="folder" value="<?php echo $this->escape($this->state->folder); ?>" />
		</form>

		<?php if ($user->authorise('core.create', 'com_media')) : ?>
		<!-- File Upload Form -->
		<div id="collapseUpload" class="collapse">
			<form action="<?php echo JUri::base(); ?>index.php?option=com_media&amp;task=file.upload&amp;tmpl=component&amp;<?php echo $this->session->getName() . '=' . $this->session->getId(); ?>&amp;<?php echo JSession::getFormToken(); ?>=1&amp;format=html" id="uploadForm" class="form-inline" name="uploadForm" method="post" enctype="multipart/form-data">
				<div id="uploadform" class="uploadform">
					<fieldset id="upload-noflash" class="actions">
							<label for="upload-file" class="control-label"><?php echo JText::_('COM_MEDIA_UPLOAD_FILE'); ?></label>
								<input required type="file" id="upload-file" name="Filedata[]" multiple /> <button class="btn btn-primary" id="upload-submit"><span class="icon-upload icon-white"></span> <?php echo JText::_('COM_MEDIA_START_UPLOAD'); ?></button>
							<p class="help-block">
								<?php $cMax    = (int) $this->config->get('upload_maxsize'); ?>
								<?php $maxSize = JUtility::getMaxUploadSize($cMax . 'MB'); ?>
								<?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', JHtml::_('number.bytes', $maxSize)); ?>
							</p>
					</fieldset>
					<input class="update-folder" type="hidden" name="folder" id="folder" value="<?php echo $this->escape($this->state->folder); ?>" />
					<?php JFactory::getSession()->set('com_media.return_url', 'index.php?option=com_media'); ?>
				</div>
			</form>
		</div>
		<div id="collapseFolder" class="collapse">
			<form action="index.php?option=com_media&amp;task=folder.create&amp;tmpl=<?php echo $input->getCmd('tmpl', 'index'); ?>" name="folderForm" id="folderForm" class="form-inline" method="post">
					<div class="path">
						<input type="text" id="folderpath" readonly="readonly" class="update-folder" />
						<input required type="text" id="foldername" name="foldername" />
						<input class="update-folder" type="hidden" name="folderbase" id="folderbase" value="<?php echo $this->escape($this->state->folder); ?>" />
						<button type="submit" class="btn"><span class="icon-folder-open"></span> <?php echo JText::_('COM_MEDIA_CREATE_FOLDER'); ?></button>
					</div>
					<?php echo JHtml::_('form.token'); ?>
			</form>
		</div>
		<?php endif; ?>

		<form action="index.php?option=com_media&amp;task=folder.create&amp;tmpl=<?php echo $input->getCmd('tmpl', 'index'); ?>" name="folderForm" id="folderForm" method="post">
			<div id="folderview">
				<div class="view">
					<iframe class="thumbnail" src="index.php?option=com_media&amp;view=mediaList&amp;tmpl=component&amp;folder=<?php echo $this->escape($this->state->folder); ?>" id="folderframe" name="folderframe" width="100%" height="500px" marginwidth="0" marginheight="0" scrolling="auto"></iframe>
				</div>
				<?php echo JHtml::_('form.token'); ?>
			</div>
		</form>
	</div>
<?php // Pre render all the bootstrap modals on the parent window

echo JHtml::_(
    'bootstrap.renderModal',
    'imagePreview',
    array(
        'title'  => JText::_('COM_MEDIA_PREVIEW'),
        'footer' => '<button type="button" class="btn" data-dismiss="modal">'
            . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
    ),
    '<div id="image" style="text-align:center;"><img id="imagePreviewSrc" src="../media/system/images/alpha.png" alt="preview" style="max-width:100%; max-height:300px;"/></div>'
);

echo JHtml::_(
    'bootstrap.renderModal',
    'videoPreview',
    array(
        'title'  => JText::_('COM_MEDIA_PREVIEW'),
        'footer' => '<button type="button" class="btn" data-dismiss="modal">'
            . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
    ),
    '<div id="videoPlayer"><video id="videoPreviewPlayer" controls="controls" preload="metadata"></video></div>'
);

echo JHtml::_(
    'bootstrap.renderModal',
    'audioPreview',
    array(
        'title'  => JText::_('COM_MEDIA_PREVIEW'),
        'footer' => '<button type="button" class="btn" data-dismiss="modal">'
            . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
    ),
    '<div id="audioPlayer"><img id="audioPreviewCover" src="" alt="" hidden="hidden" /><svg class="media-audio-wave" viewBox="0 0 102 60" fill="currentColor" aria-hidden="true" focusable="false"><rect x="0" y="19" width="6" height="22" rx="3"></rect><rect x="12" y="11" width="6" height="38" rx="3"></rect><rect x="24" y="3" width="6" height="54" rx="3"></rect><rect x="36" y="14" width="6" height="32" rx="3"></rect><rect x="48" y="7" width="6" height="46" rx="3"></rect><rect x="60" y="17" width="6" height="26" rx="3"></rect><rect x="72" y="5" width="6" height="50" rx="3"></rect><rect x="84" y="15" width="6" height="30" rx="3"></rect><rect x="96" y="21" width="6" height="18" rx="3"></rect></svg><audio id="audioPreviewPlayer" controls="controls" preload="metadata"></audio></div>'
);
?>
	<!-- End Content -->
</div>
