<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Object\CMSObject;

/**
 * Media helper class.
 *
 * @since       1.6
 * @deprecated  4.0  Use JHelperMedia instead
 */
abstract class MediaHelper
{
	/**
	 * Checks if the file is an image
	 *
	 * @param   string  $fileName  The filename
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 * @deprecated  4.0  Use JHelperMedia::isImage instead
	 */
	public static function isImage($fileName)
	{
		try
		{
			JLog::add(
				sprintf('%s() is deprecated. Use JHelperMedia::isImage() instead.', __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		}
		catch (RuntimeException $exception)
		{
			// Informational log only
		}

		$mediaHelper = new JHelperMedia;

		return $mediaHelper->isImage($fileName);
	}

	/**
	 * Gets the file extension for the purpose of using an icon.
	 *
	 * @param   string  $fileName  The filename
	 *
	 * @return  string  File extension
	 *
	 * @since   1.5
	 * @deprecated  4.0  Use JHelperMedia::getTypeIcon instead
	 */
	public static function getTypeIcon($fileName)
	{
		try
		{
			JLog::add(
				sprintf('%s() is deprecated. Use JHelperMedia::getTypeIcon() instead.', __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		}
		catch (RuntimeException $exception)
		{
			// Informational log only
		}

		$mediaHelper = new JHelperMedia;

		return $mediaHelper->getTypeIcon($fileName);
	}

	/**
	 * Checks if the file can be uploaded
	 *
	 * @param   array   $file   File information
	 * @param   string  $error  An error message to be returned
	 *
	 * @return  boolean
	 *
	 * @since   1.5
	 * @deprecated  4.0  Use JHelperMedia::canUpload instead
	 */
	public static function canUpload($file, $error = '')
	{
		try
		{
			JLog::add(
				sprintf('%s() is deprecated. Use JHelperMedia::canUpload() instead.', __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		}
		catch (RuntimeException $exception)
		{
			// Informational log only
		}

		$mediaHelper = new JHelperMedia;

		return $mediaHelper->canUpload($file, 'com_media');
	}

	/**
	 * Method to parse a file size
	 *
	 * @param   integer  $size  The file size in bytes
	 *
	 * @return  string  The converted file size
	 *
	 * @since   1.6
	 * @deprecated  4.0  Use JHtml::_('number.bytes') instead
	 */
	public static function parseSize($size)
	{
		try
		{
			JLog::add(
				sprintf("%s() is deprecated. Use JHtml::_('number.bytes') instead.", __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		}
		catch (RuntimeException $exception)
		{
			// Informational log only
		}

		return JHtml::_('number.bytes', $size);
	}

	/**
	 * Calculate the size of a resized image
	 *
	 * @param   integer  $width   Image width
	 * @param   integer  $height  Image height
	 * @param   integer  $target  Target size
	 *
	 * @return  array  The new width and height
	 *
	 * @since   3.2
	 * @deprecated  4.0  Use JHelperMedia::imageResize instead
	 */
	public static function imageResize($width, $height, $target)
	{
		try
		{
			JLog::add(
				sprintf('%s() is deprecated. Use JHelperMedia::imageResize() instead.', __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		}
		catch (RuntimeException $exception)
		{
			// Informational log only
		}

		$mediaHelper = new JHelperMedia;

		return $mediaHelper->imageResize($width, $height, $target);
	}

	/**
	 * Counts the files and directories in a directory that are not php or html files.
	 *
	 * @param   string  $dir  Directory name
	 *
	 * @return  array  The number of files and directories in the given directory
	 *
	 * @since   1.5
	 * @deprecated  4.0  Use JHelperMedia::countFiles instead
	 */
	public static function countFiles($dir)
	{
		try
		{
			JLog::add(
				sprintf('%s() is deprecated. Use JHelperMedia::countFiles() instead.', __METHOD__),
				JLog::WARNING,
				'deprecated'
			);
		}
		catch (RuntimeException $exception)
		{
			// Informational log only
		}

		$mediaHelper = new JHelperMedia;

		return $mediaHelper->countFiles($dir);
	}

	/**
	 * Generates the URL to the object in the action logs component
	 *
	 * @param   string     $contentType  The content type
	 * @param   integer    $id           The integer id
	 * @param   CMSObject  $mediaObject  The media object being uploaded
	 *
	 * @return  string  The link for the action log
	 *
	 * @since   3.9.27
	 */
	public static function getContentTypeLink($contentType, $id, CMSObject $mediaObject)
	{
		if ($contentType === 'com_media.file')
		{
			return '';
		}

		$link         = 'index.php?option=com_media&view=media';
		$uploadedPath = substr((string) $mediaObject->get('filepath'), strlen(COM_MEDIA_BASE) + 1);

		// Now remove the filename
		$uploadedBasePath = substr_replace(
			$uploadedPath,
			'',
			(strlen(DIRECTORY_SEPARATOR . $mediaObject->get('name')) * -1)
		);

		if (!empty($uploadedBasePath))
		{
			$link = $link . '&folder=' . $uploadedBasePath;
		}

		return $link;
	}

	/**
	 * Lets files dropped on a list of the Media Manager (or the image picker) upload into the folder it shows, for users who
	 * may upload: com_media's media.js (its drop upload part), through the JSON upload with the Media Manager's options (as its upload form).
	 *
	 * @param   string   $folder  The folder shown, relative to the media folder
	 * @param   boolean  $picker  The image picker (a single dropped image is selected after the list reloads)
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public static function dropUpload($folder, $picker = false)
	{
		if (!JFactory::getUser()->authorise('core.create', 'com_media'))
		{
			return;
		}

		$params  = JComponentHelper::getParams('com_media');
		$base    = trim(str_replace(array(JPATH_ROOT, '\\'), array('', '/'), COM_MEDIA_BASE), '/');
		$keys    = array('HINT', 'UPLOADING', 'DONE', 'FAILED', 'TOO_LARGE', 'SUMMARY', 'CLOSE');
		$strings = array();

		foreach ($keys as $key)
		{
			$strings[strtolower($key)] = JText::_('COM_MEDIA_DROP_' . $key);
		}

		JHtml::_('behavior.core');
		JFactory::getDocument()->addScriptOptions(
			'com_media.dropUpload',
			array(
				'url'      => JUri::base(true) . '/index.php?option=com_media&task=file.upload&format=json&context=mediamanager',
				'token'    => JSession::getFormToken(),
				'folder'   => (string) $folder,
				'shown'    => $base . ($folder !== '' ? '/' . $folder : ''),
				'picker'   => (bool) $picker,
				'maxBytes' => (int) ((float) $params->get('upload_maxsize', 0) * 1024 * 1024),
				'text'     => $strings,
			)
		);
		JHtml::_('script', 'com_media/media.min.js', array('version' => 'auto', 'relative' => true), array('defer' => true));
		JHtml::_('stylesheet', 'com_media/media.min.css', array('version' => 'auto', 'relative' => true));
	}

	/**
	 * A file type's icon (media/com_media/images/icons), drawn in the text colour
	 *
	 * @param   string   $icon   The icon's name (the list model's $file->icon)
	 * @param   integer  $size   Its size in pixels
	 * @param   string   $label  Its text for screen readers (the file's name)
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	public static function typeIcon($icon, $size, $label)
	{
		return JHtml::_('icons.svg', $icon ?: 'file-earmark', $label, array('path' => 'media/com_media/images/icons', 'size' => $size, 'class' => 'media-type-icon'));
	}
}
