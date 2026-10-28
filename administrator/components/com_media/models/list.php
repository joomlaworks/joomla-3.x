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
		$audios  = array ();

		$fileList   = false;
		$folderList = false;

		if (file_exists($basePath))
		{
			// Get the list of files and folders from the given folder
			$fileList   = JFolder::files($basePath);
			$folderList = JFolder::folders($basePath);
		}

		// Images with an audio or video file's name (song.mp3 and song.jpg) are its cover or poster; the first type of this list wins
		$covers = array();

		foreach ($fileList ?: array() as $file)
		{
			$rank = array_search(strtolower(JFile::getExt($file)), array('webp', 'jpg', 'jpeg', 'png', 'avif', 'gif'), true);
			$key  = strtolower(JFile::stripExt($file));

			if ($rank !== false && (!isset($covers[$key]) || $rank < $covers[$key][0]))
			{
				$covers[$key] = array($rank, $file);
			}
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
							$tmp->icon    = $this->getTypeIcon(strtolower(JFile::getExt($file)));
							$tmp->icon_32 = '';
							$tmp->icon_16 = '';
							$tmp->cover   = $this->findCover($file, $tmp->path_relative, $covers);
							$videos[] = $tmp;
							break;

						// Audio
						case 'mp3':
						case 'm4a':
						case 'aac':
						case 'ogg':
						case 'oga':
						case 'opus':
						case 'wav':
						case 'flac':
							$tmp->icon    = $this->getTypeIcon(strtolower(JFile::getExt($file)));
							$tmp->icon_32 = '';
							$tmp->icon_16 = '';
							$tmp->cover   = $this->findCover($file, $tmp->path_relative, $covers);
							$audios[] = $tmp;
							break;

						// Non-image document
						default:
							$tmp->icon    = $this->getTypeIcon(strtolower(JFile::getExt($file)));
							$tmp->icon_32 = '';
							$tmp->icon_16 = '';
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

		$list = array('folders' => $folders, 'docs' => $docs, 'images' => $images, 'videos' => $videos, 'audios' => $audios);

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
	 * Get the audio files on the current folder
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	public function getAudios()
	{
		$list = $this->getList();

		return $list['audios'];
	}

	/**
	 * The cover of an audio file or the poster of a video: an image of the same name in its folder
	 *
	 * @param   string  $file          The file's name
	 * @param   string  $pathRelative  Its path from the media folder
	 * @param   array   $covers        The folder's images by lower-case name without extension: array(rank, file name)
	 *
	 * @return  string  The image's path from the media folder, '' when there's none
	 *
	 * @since   3.18.0
	 */
	protected function findCover($file, $pathRelative, array $covers)
	{
		$key = strtolower(JFile::stripExt($file));

		if (!isset($covers[$key]))
		{
			return '';
		}

		$folder = dirname($pathRelative);

		return ($folder === '.' || $folder === '/' || $folder === '\\' ? '' : rtrim($folder, '/') . '/') . $covers[$key][1];
	}

	/**
	 * The icon of a file type: a Bootstrap Icons name in the Media Manager's sprite (media/com_media/images/icons.svg), the
	 * plain file's for types without one of their own
	 *
	 * @param   string  $ext  The file's extension, in lower case
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	protected function getTypeIcon($ext)
	{
		static $icons = array(
			'pdf'  => 'file-earmark-pdf',
			'doc'  => 'file-earmark-word', 'docx' => 'file-earmark-word', 'odt' => 'file-earmark-word',
			'xls'  => 'file-earmark-excel', 'xlsx' => 'file-earmark-excel', 'ods' => 'file-earmark-excel',
			'csv'  => 'file-earmark-spreadsheet',
			'ppt'  => 'file-earmark-ppt', 'pptx' => 'file-earmark-ppt', 'odp' => 'file-earmark-ppt',
			'txt'  => 'file-earmark-text', 'rtf' => 'file-earmark-richtext',
			'zip'  => 'file-earmark-zip', 'rar' => 'file-earmark-zip', '7z' => 'file-earmark-zip', 'tar' => 'file-earmark-zip',
			'tgz'  => 'file-earmark-zip', 'gz' => 'file-earmark-zip',
			'mp4'  => 'file-earmark-play', 'webm' => 'file-earmark-play', 'ogv' => 'file-earmark-play', 'mov' => 'file-earmark-play',
			'avi'  => 'file-earmark-play', 'wmv' => 'file-earmark-play',
			'mp3'  => 'file-earmark-music', 'm4a' => 'file-earmark-music', 'aac' => 'file-earmark-music', 'ogg' => 'file-earmark-music',
			'oga'  => 'file-earmark-music', 'opus' => 'file-earmark-music', 'wav' => 'file-earmark-music', 'flac' => 'file-earmark-music',
			'wma'  => 'file-earmark-music',
			'svg'  => 'file-earmark-image', 'odg' => 'file-earmark-image', 'sxd' => 'file-earmark-image', 'xcf' => 'file-earmark-image',
			'html' => 'file-earmark-code', 'htm' => 'file-earmark-code', 'xml' => 'file-earmark-code', 'json' => 'file-earmark-code',
			'css'  => 'file-earmark-code', 'js' => 'file-earmark-code',
		);

		return isset($icons[$ext]) ? $icons[$ext] : 'file-earmark';
	}
}
