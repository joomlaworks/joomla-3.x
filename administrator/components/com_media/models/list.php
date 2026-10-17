<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Media Component List Model
 *
 * @since  1.5
 */
class MediaModelList extends JModelLegacy
{
	/**
	 * Method to get model state variables
	 *
	 * @param   string  $property  Optional parameter name
	 * @param   mixed   $default   Optional default value
	 *
	 * @return  object  The property where specified, the state object where omitted
	 *
	 * @since   1.5
	 */
	public function getState($property = null, $default = null)
	{
		static $set;

		if (!$set)
		{
			$input  = JFactory::getApplication()->input;
			$folder = $input->get('folder', '', 'path');
			$this->setState('folder', $folder);

			$parent = str_replace("\\", '/', dirname((string) $folder));
			$parent = ($parent == '.') ? null : $parent;
			$this->setState('parent', $parent);
			$set = true;
		}

		return parent::getState($property, $default);
	}

	/**
	 * Get the images on the current folder
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	public function getImages()
	{
		$list = $this->getList();

		return $list['images'];
	}

	/**
	 * Get the folders on the current folder
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	public function getFolders()
	{
		$list = $this->getList();

		return $list['folders'];
	}

	/**
	 * Get the documents on the current folder
	 *
	 * @return  array
	 *
	 * @since   1.5
	 */
	public function getDocuments()
	{
		$list = $this->getList();

		return $list['docs'];
	}

	/**
	 * Build imagelist
	 *
	 * @return  array
	 *
	 * @since 1.5
	 */
	public function getList()
	{
		static $list;

		// Only process the list once per request
		if (is_array($list))
		{
			return $list;
		}

		// Get current path from request
		$current = (string) $this->getState('folder');

		$basePath  = COM_MEDIA_BASE . ((strlen($current) > 0) ? '/' . $current : '');
		$mediaBase = str_replace(DIRECTORY_SEPARATOR, '/', COM_MEDIA_BASE . '/');

		// Reset base path
		if (strpos(realpath($basePath), JPath::clean(realpath(COM_MEDIA_BASE))) !== 0)
		{
			$basePath = COM_MEDIA_BASE;
		}

		$images  = array ();
		$folders = array ();
		$docs    = array ();
		$videos  = array ();

		$fileList   = false;
		$folderList = false;

		if (file_exists($basePath))
		{
			// Get the list of files and folders from the given folder
			$fileList   = JFolder::files($basePath);
			$folderList = JFolder::folders($basePath);
		}

		// Iterate over the files if they exist
		if ($fileList !== false)
		{
			$tmpBaseObject = new JObject;

			foreach ($fileList as $file)
			{
				if (is_file($basePath . '/' . $file) && substr($file, 0, 1) != '.' && strtolower($file) !== 'index.html')
				{
					$tmp = clone $tmpBaseObject;
					$tmp->name = $file;
					$tmp->title = $file;
					$tmp->path = str_replace(DIRECTORY_SEPARATOR, '/', JPath::clean($basePath . '/' . $file));
					$tmp->path_relative = str_replace($mediaBase, '', $tmp->path);
					$tmp->size = filesize($tmp->path);

					$ext   = strtolower(JFile::getExt($file));
					$image = in_array($ext, array('jpg', 'png', 'gif', 'xcf', 'odg', 'bmp', 'jpeg', 'ico', 'webp', 'avif'), true);
					$info  = $image ? @getimagesize($tmp->path) : false;

					// Images PHP can't read are listed as documents: GIMP and Draw files, AVIF before PHP 8.1, broken files (their
					// sizes were read from false, a warning on PHP 8)
					if ($image && $info === false)
					{
						$ext = 'unreadable-image';
					}

					switch ($ext)
					{
						// Image
						case 'jpg':
						case 'png':
						case 'gif':
						case 'xcf':
						case 'odg':
						case 'bmp':
						case 'jpeg':
						case 'ico':
						case 'webp':
						case 'avif':
							$tmp->width  = @$info[0];
							$tmp->height = @$info[1];
							$tmp->type   = @$info[2];
							$tmp->mime   = @$info['mime'];

							if (($info[0] > 60) || ($info[1] > 60))
							{
								$dimensions = MediaHelper::imageResize($info[0], $info[1], 60);
								$tmp->width_60 = $dimensions[0];
								$tmp->height_60 = $dimensions[1];
							}
							else
							{
								$tmp->width_60 = $tmp->width;
								$tmp->height_60 = $tmp->height;
							}

							if (($info[0] > 16) || ($info[1] > 16))
							{
								$dimensions = MediaHelper::imageResize($info[0], $info[1], 16);
								$tmp->width_16 = $dimensions[0];
								$tmp->height_16 = $dimensions[1];
							}
							else
							{
								$tmp->width_16 = $tmp->width;
								$tmp->height_16 = $tmp->height;
							}

							$images[] = $tmp;
							break;

						// Video
						case 'mp4':
						case 'webm':
						case 'mov':
						case 'ogv':
							$icon = $this->getTypeIcon(strtolower(JFile::getExt($file)));
							$tmp->icon_32 = 'media/mime-icon-32/' . $icon . '.png';
							$tmp->icon_16 = 'media/mime-icon-16/' . $icon . '.png';
							$videos[] = $tmp;
							break;

						// Non-image document
						default:
							$icon = $this->getTypeIcon(strtolower(JFile::getExt($file)));
							$tmp->icon_32 = 'media/mime-icon-32/' . $icon . '.png';
							$tmp->icon_16 = 'media/mime-icon-16/' . $icon . '.png';
							$docs[] = $tmp;
							break;
					}
				}
			}
		}

		// Iterate over the folders if they exist
		if ($folderList !== false)
		{
			$tmpBaseObject = new JObject;

			foreach ($folderList as $folder)
			{
				$tmp = clone $tmpBaseObject;
				$tmp->name = basename($folder);
				$tmp->path = str_replace(DIRECTORY_SEPARATOR, '/', JPath::clean($basePath . '/' . $folder));
				$tmp->path_relative = str_replace($mediaBase, '', $tmp->path);
				$count = MediaHelper::countFiles($tmp->path);
				$tmp->files = $count[0];
				$tmp->folders = $count[1];

				$folders[] = $tmp;
			}
		}

		$list = array('folders' => $folders, 'docs' => $docs, 'images' => $images, 'videos' => $videos);

		return $list;
	}

	/**
	 * Get the videos on the current folder
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	public function getVideos()
	{
		$list = $this->getList();

		return $list['videos'];
	}

	/**
	 * The icon of a file type: its own when the Media Manager has one, else a related type's (docx: doc), else the document
	 * icon (types with no icon showed a broken image).
	 *
	 * @param   string  $ext  The file's extension, in lower case
	 *
	 * @return  string  The icon's name, without .png
	 *
	 * @since   3.18.0
	 */
	protected function getTypeIcon($ext)
	{
		static $related = array(
			'docx' => 'doc', 'rtf' => 'rtf', 'txt' => 'doc', 'csv' => 'xls', 'xlsx' => 'xls', 'ods' => 'xls', 'pptx' => 'ppt', 'odp' => 'ppt',
			'odg' => 'sxd', 'webm' => 'mp4', 'ogv' => 'mp4', 'm4a' => 'mp3', 'wav' => 'mp3', 'oga' => 'ogg', 'opus' => 'ogg', '7z' => 'zip',
		);

		if (preg_match('/^[a-z0-9]+$/', $ext) && is_file(JPATH_ROOT . '/media/media/images/mime-icon-32/' . $ext . '.png'))
		{
			return $ext;
		}

		return isset($related[$ext]) ? $related[$ext] : 'doc';
	}
}
