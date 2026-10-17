<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\MediaHelper;

/**
 * Optimises the images already in a folder of the Media Manager, with its Image Optimisation options: resizes them, turns
 * photos upright, drops their metadata and adds the copies for small screens. Files keep their names and formats, as content
 * links to them.
 *
 * @since  3.18.0
 */
class MediaOptimizeCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.18.0
	 */
	protected $name = 'media:optimize';

	/**
	 * @var    string
	 * @since  3.18.0
	 */
	protected $description = 'Optimise the images in a folder of the Media Manager';

	/**
	 * @var    string
	 * @since  3.18.0
	 */
	protected $help = 'Optimises the images in a folder (and its folders) with the Media Manager\'s Image Optimisation options: images wider '
		. 'than the Maximum Width are made that wide, photos stored sideways are turned upright, metadata (EXIF, which on phone photos '
		. 'includes where they were taken) is dropped, and a copy for small screens is saved next to every image wider than the Small '
		. 'Screen Width (photo.jpg gets photo-600.jpg). Files keep their names and formats, even with Save as WebP on, as articles, '
		. 'modules and settings link to them. GIFs, images already optimised and the small screen copies are left alone, so running it '
		. 'again changes nothing. The folder is relative to the site (e.g. images/uploads) and must be inside the Media Manager\'s '
		. 'images folder. Also media:optimise.';

	/**
	 * @var    boolean
	 * @since  3.18.0
	 */
	protected $dryRun = true;

	/**
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	protected function configure()
	{
		$this->addArgument('folder', self::ARGUMENT_REQUIRED, 'The folder, relative to the site (e.g. images/uploads), inside the Media Manager\'s images folder');
	}

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.18.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$io->title('Optimise Images');

		$params   = ComponentHelper::getParams('com_media');
		$helper   = new MediaHelper;
		$settings = $helper->getOptimiseSettings($params);

		$helper->raiseMemoryLimit($params);
		$images   = realpath(JPATH_ROOT . '/' . trim((string) $params->get('image_path', 'images'), '/'));
		$folder   = trim(str_replace('\\', '/', (string) $io->getArgument('folder')), '/');
		$path     = realpath(JPATH_ROOT . '/' . $folder);

		if ($folder === '' || preg_match('#(^|/)\.\.(/|$)#', $folder) || !$images || !$path || !is_dir($path)
			|| ($path !== $images && strpos($path, $images . DIRECTORY_SEPARATOR) !== 0))
		{
			$io->error(sprintf('"%s" isn\'t a folder inside the Media Manager\'s images folder (%s).', $folder, trim((string) $params->get('image_path', 'images'), '/')));

			return self::INVALID;
		}

		if (!function_exists('imagecreatetruecolor'))
		{
			$io->error('PHP\'s GD extension, which resizes images, isn\'t available.');

			return self::FAILURE;
		}

		$settings['keep_name'] = true;

		$rows  = array();
		$saved = 0;
		$done  = 0;
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

		foreach ($files as $file)
		{
			$name = $file->getPathname();

			if (!$file->isFile() || $file->isLink() || !$this->needsWork($name, $settings))
			{
				continue;
			}

			$relative = substr($name, strlen(JPATH_ROOT) + 1);
			$before   = getimagesize($name);
			$size     = filesize($name);

			if ($io->isDryRun())
			{
				$io->plan(sprintf('Optimise %s (%dx%d)', $relative, $before[0], $before[1]), array('file' => $relative, 'width' => $before[0], 'height' => $before[1], 'bytes' => $size));

				continue;
			}

			$helper->optimiseImage($name, $settings);
			clearstatcache(true, $name);

			$after = @getimagesize($name);
			$now   = (int) @filesize($name);
			$saved += $size - $now;
			$done++;

			$rows[] = array(
				'file'   => $relative,
				'before' => $before[0] . 'x' . $before[1] . ', ' . $this->bytes($size),
				'after'  => ($after ? $after[0] . 'x' . $after[1] : '?') . ', ' . $this->bytes($now),
				'mobile' => $settings['mobile'] > 0 && $after && $after[0] > $settings['mobile'] ? basename($this->mobileName($name, $settings['mobile'])) : '',
			);
		}

		if ($io->isDryRun())
		{
			return self::SUCCESS;
		}

		if ($rows)
		{
			$io->table(array('file' => 'File', 'before' => 'Before', 'after' => 'After', 'mobile' => 'Small screen copy'), $rows);
		}

		$io->setData('optimised', $done);
		$io->setData('bytesSaved', $saved);
		$io->success($done ? sprintf('%d image(s) optimised, %s saved.', $done, $this->bytes($saved)) : 'Nothing to optimise.');

		return self::SUCCESS;
	}

	/**
	 * Whether an image needs optimising: an image GD writes (not a GIF), not a small screen copy, and wider than the maximum
	 * width, stored sideways, or without its small screen copy.
	 *
	 * @param   string  $file      The file
	 * @param   array   $settings  The settings
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 */
	private function needsWork($file, array $settings)
	{
		$info = @getimagesize($file);
		$types = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_BMP);

		if (defined('IMAGETYPE_AVIF') && function_exists('imagecreatefromavif'))
		{
			$types[] = IMAGETYPE_AVIF;
		}

		if (!$info || !in_array((int) $info[2], $types, true))
		{
			return false;
		}

		// A small screen copy: photo-600.jpg next to photo.jpg
		if ($settings['mobile'] > 0 && preg_match('/^(.+)-' . (int) $settings['mobile'] . '$/', pathinfo($file, PATHINFO_FILENAME), $match)
			&& is_file(dirname($file) . '/' . $match[1] . '.' . pathinfo($file, PATHINFO_EXTENSION)))
		{
			return false;
		}

		$width = (int) $info[0];

		if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data'))
		{
			$exif = @exif_read_data($file);

			if (is_array($exif) && isset($exif['Orientation']) && (int) $exif['Orientation'] > 1)
			{
				return true;
			}
		}

		if ($settings['width'] > 0 && $width > $settings['width'])
		{
			return true;
		}

		return $settings['mobile'] > 0 && $width > $settings['mobile'] && !is_file($this->mobileName($file, $settings['mobile']));
	}

	/**
	 * The name of an image's small screen copy.
	 *
	 * @param   string   $file   The image
	 * @param   integer  $width  The small screen width
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	private function mobileName($file, $width)
	{
		return dirname($file) . '/' . pathinfo($file, PATHINFO_FILENAME) . '-' . (int) $width . '.' . pathinfo($file, PATHINFO_EXTENSION);
	}

	/**
	 * A size in bytes, for people.
	 *
	 * @param   integer  $bytes  The size
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	private function bytes($bytes)
	{
		$negative = $bytes < 0;
		$bytes    = abs($bytes);
		$units    = array('B', 'KB', 'MB', 'GB');
		$unit     = 0;

		while ($bytes >= 1024 && $unit < 3)
		{
			$bytes /= 1024;
			$unit++;
		}

		return ($negative ? '-' : '') . ($unit ? sprintf('%.1f', $bytes) : (int) $bytes) . ' ' . $units[$unit];
	}
}
