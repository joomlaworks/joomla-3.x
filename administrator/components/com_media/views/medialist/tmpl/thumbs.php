<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;
$params = JComponentHelper::getParams('com_media');
$path   = 'file_path';

JHtml::_('jquery.framework');
JHtml::_('behavior.core');
JHtml::_('stylesheet', 'com_media/media.min.css', array('version' => 'auto', 'relative' => true));

$doc = JFactory::getDocument();

// Need to override this core function because we use a different form id
$doc->addScriptDeclaration(
    "
		Joomla.isChecked = function( isitchecked, form ) {
			if ( typeof form  === 'undefined' ) {
				form = document.getElementById( 'mediamanager-form' );
			}

			form.boxchecked.value += isitchecked ? 1 : -1;

			// If we don't have a checkall-toggle, done.
			if ( !form.elements[ 'checkall-toggle' ] ) return;

			// Toggle main toggle checkbox depending on checkbox selection
			var c = true,
				i, e, n;

			for ( i = 0, n = form.elements.length; i < n; i++ ) {
				e = form.elements[ i ];

				if ( e.type == 'checkbox' && e.name != 'checkall-toggle' && !e.checked ) {
					c = false;
					break;
				}
			}

			form.elements[ 'checkall-toggle' ].checked = c;
		};
	"
);

$doc->addScriptDeclaration(
    "
		jQuery(document).ready(function($){
			window.parent.document.updateUploader();
			$('.img-preview, .preview').each(function(index, value) {
				$(this).on('click', function(e) {
					// The CMS's modal, the image as large as the window allows, with the folder's other images
					if (window.parent.MediaPreview) {
						window.parent.MediaPreview.open($(this).attr('href'), document);
					} else {
						window.parent.CMS.modal.open($(this).attr('href'), {handler: 'image'});
					}

					// Focus leaves the list's frame for the modal, so Escape closes it
					var close = window.parent.document.getElementById('sbox-btn-close');

					if (close) {
						close.focus({focusVisible: false});
					}

					return false;
				});
			});
			$('.audio-preview').each(function(index, value) {
				$(this).unbind('click');
				$(this).on('click', function(e) {
					e.preventDefault();
					window.parent.jQuery('#audioPreview').modal('show');
					window.parent.MediaManager.playAudio($(this).attr('href'), $(this).attr('data-cover') || '');

					return false;
				});
			});
			$('.video-preview').each(function(index, value) {
				$(this).unbind('click');
				$(this).on('click', function(e) {
					e.preventDefault();
					window.parent.jQuery('#videoPreview').modal('show');
					window.parent.MediaManager.playVideo($(this).attr('href'), $(this).attr('data-cover') || '');

					return false;
				});
			});
		});
	"
);
?>
<form target="_parent" action="index.php?option=com_media&amp;tmpl=index&amp;folder=<?php echo rawurlencode((string) $this->state->folder); ?>" method="post" id="mediamanager-form" name="mediamanager-form">
	<div class="muted breadcrumbs media-path">
		<?php // The folder above (in the thumbnails' place until 3.18)?>
		<?php if ($this->state->folder != '') : ?>
			<a class="btn btn-small media-back" href="index.php?option=com_media&amp;view=mediaList&amp;tmpl=component&amp;folder=<?php echo rawurlencode((string) $this->state->parent); ?>" target="folderframe">
				<?php echo JHtml::_('icons.svg', 'arrow-left', '', array('path' => 'media/com_media/images/icons', 'size' => 16)); ?>
				<?php echo JText::_('JTOOLBAR_BACK'); ?>
			</a>
		<?php endif; ?>
		<p>
			<span class="icon-folder"></span>
			<?php
                echo $params->get($path, 'images'),
($this->escape($this->state->folder) != '') ? '/' . $this->escape($this->state->folder) : '';
?>
		</p>
	</div>

	<ul class="manager thumbnails thumbnails-media">
		<?php
echo $this->loadTemplate('folders'),
$this->loadTemplate('docs'),
$this->loadTemplate('videos'),
$this->loadTemplate('audios'),
$this->loadTemplate('imgs');
?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="username" value="" />
		<input type="hidden" name="password" value="" />
		<input type="hidden" name="boxchecked" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</ul>
</form>
