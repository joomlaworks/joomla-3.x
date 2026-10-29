<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Helper;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\String\PunycodeHelper;

/**
 * Media helper class
 *
 * @since  3.2
 */
class MediaHelper
{
    /**
     * Checks if the file is an image
     *
     * @param   string  $fileName  The filename
     *
     * @return  boolean
     *
     * @since   3.2
     */
    public function isImage($fileName)
    {
        static $imageTypes = 'xcf|odg|gif|jpe?g|png|bmp|webp|avif|ico';

        return preg_match("/\.(?:$imageTypes)$/i", $fileName);
    }

    /**
     * Gets the file extension for purposed of using an icon
     *
     * @param   string  $fileName  The filename
     *
     * @return  string  File extension to determine icon
     *
     * @since   3.2
     */
    public static function getTypeIcon($fileName)
    {
        return strtolower(substr($fileName, strrpos($fileName, '.') + 1));
    }

    /**
     * Get the Mime type
     *
     * @param   string   $file     The link to the file to be checked
     * @param   boolean  $isImage  True if the passed file is an image else false
     *
     * @return  mixed    the mime type detected false on error
     *
     * @since   3.7.2
     */
    private function getMimeType($file, $isImage = false)
    {
        // If we can't detect anything mime is false
        $mime = false;

        try {
            if ($isImage && function_exists('exif_imagetype')) {
                $mime = image_type_to_mime_type(exif_imagetype($file));
            } elseif ($isImage && function_exists('getimagesize')) {
                $imagesize = getimagesize($file);
                $mime      = isset($imagesize['mime']) ? $imagesize['mime'] : false;
            } elseif (function_exists('mime_content_type')) {
                // We have mime magic.
                $mime = mime_content_type($file);
            } elseif (function_exists('finfo_open')) {
                // We have fileinfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $file);
                // A no-op since PHP 8.1 and deprecated since PHP 8.5
                if (PHP_VERSION_ID < 80100) {
                    finfo_close($finfo);
                }
            }
        } catch (\Exception $e) {
            // If we have any kind of error here => false;
            return false;
        }

        // If we can't detect the mime try it again
        if ($mime === 'application/octet-stream' && $isImage === true) {
            $mime = $this->getMimeType($file, false);
        }

        // We have a mime here
        return $mime;
    }

    /**
     * A comma separated list option (file extensions, MIME types) as an array: trimmed, in lower case, without empty entries,
     * so the lists can be written in any case and over several lines.
     *
     * @param   \Joomla\Registry\Registry  $params  The component's options
     * @param   string                     $name    The option
     *
     * @return  string[]
     *
     * @since   3.18.0
     */
    private function getListOption($params, $name)
    {
        $values = array();

        foreach (explode(',', (string) $params->get($name)) as $value) {
            $value = strtolower(trim($value));

            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Checks the Mime type
     *
     * @param   string  $mime       The mime to be checked
     * @param   string  $component  The optional name for the component storing the parameters
     *
     * @return  boolean  true if mime type checking is disabled or it passes the checks else false
     *
     * @since   3.7
     */
    private function checkMimeType($mime, $component = 'com_media')
    {
        $params = ComponentHelper::getParams($component);
        $mime   = strtolower((string) $mime);
        $half   = (int) (strlen($mime) / 2);

        // Older libmagic versions (PHP 7.4's) report some types twice in a row, e.g. xlsx's
        if ($half && strlen($mime) % 2 === 0 && substr($mime, 0, $half) === substr($mime, $half)) {
            $mime = substr($mime, 0, $half);
        }

        // The illegal MIME types option (a blacklist) was never checked
        if ($mime !== '' && in_array($mime, $this->getListOption($params, 'upload_mime_illegal'), true)) {
            return false;
        }

        if ($params->get('check_mime', 1)) {
            // Mime should be available and in the whitelist
            return $mime !== '' && in_array($mime, $this->getListOption($params, 'upload_mime'), true);
        }

        // We don't check mime at all or it passes the checks
        return true;
    }

    /**
     * Raises PHP's memory limit for this request to the Media Manager's "Memory Limit" option (uploads and resizing large
     * images need memory), where the server allows it; never lowers it. PHP's own upload limits (upload_max_filesize,
     * post_max_size) can't be raised while a request runs.
     *
     * @param   \Joomla\Registry\Registry  $params  The Media Manager's options
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function raiseMemoryLimit($params)
    {
        $wanted  = (int) $params->get('memory_limit', 0) * 1048576;
        $current = (string) ini_get('memory_limit');

        if ($wanted <= 0 || $current === '' || (int) $current === -1 || (int) $this->toBytes($current) >= $wanted) {
            return;
        }

        @ini_set('memory_limit', (int) ($wanted / 1048576) . 'M');
    }

    /**
     * The folder an editor's upload goes to, relative to the images folder: the editor's own folder ("uploads" when it names
     * none) and, as the Media Manager's "Organise Editor Uploads" option says, a folder for the year, month or day
     * (uploads/2026/10/18). Missing levels are created, each with an index.html.
     *
     * @param   string                     $folder  The folder the editor asked for
     * @param   \Joomla\Registry\Registry  $params  The Media Manager's options
     * @param   string                     $base    The images folder (absolute)
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function getEditorUploadFolder($folder, $params, $base)
    {
        $folder = trim(str_replace('\\', '/', (string) $folder), '/');
        $folder = $folder === '' ? 'uploads' : $folder;

        if (preg_match('#(^|/)\.\.?(/|$)#', $folder)) {
            return '';
        }

        $formats  = array('day' => 'Y/m/d', 'month' => 'Y/m', 'year' => 'Y', 'none' => '');
        $organise = (string) $params->get('upload_organise', 'day');
        $format   = isset($formats[$organise]) ? $formats[$organise] : $formats['day'];

        if ($format === '') {
            $target = $folder;
        } else {
            // The date where the site is
            $date = \JFactory::getDate('now');
            $date->setTimezone(new \DateTimeZone(\JFactory::getConfig()->get('offset', 'UTC') ?: 'UTC'));
            $target = $folder . '/' . $date->format($format, true);
        }

        $path = rtrim(str_replace('\\', '/', (string) $base), '/');

        foreach (explode('/', $target) as $part) {
            $path .= '/' . $part;

            if (!is_dir($path)) {
                if (!@mkdir($path, 0755)) {
                    return is_dir(rtrim(str_replace('\\', '/', (string) $base), '/') . '/' . $folder) ? $folder : '';
                }

                @file_put_contents($path . '/index.html', '<!DOCTYPE html><title></title>');
            }
        }

        return $target;
    }

    /**
     * Whether uploaded images are optimised where they're uploaded from: "editors" (the editors' uploads) or "mediamanager"
     * (the Media Manager's upload form).
     *
     * @param   \Joomla\Registry\Registry  $params  The Media Manager's options
     * @param   string                     $where   editors or mediamanager
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function isOptimiseActive($params, $where)
    {
        return $where === 'editors' ? (bool) $params->get('optimise_editors', 1) : (bool) $params->get('optimise_mediamanager', 0);
    }

    /**
     * The image optimisation settings of the Media Manager's options.
     *
     * @param   \Joomla\Registry\Registry  $params  The Media Manager's options
     *
     * @return  array  width (0: no resizing), mobile (0: no mobile copy), webp, quality (1-100)
     *
     * @since   3.18.0
     */
    public function getOptimiseSettings($params)
    {
        return array(
            'width'   => $params->get('optimise_resize', 0) ? max(0, (int) $params->get('optimise_width', 900)) : 0,
            'mobile'  => $params->get('optimise_mobile', 0) ? max(0, (int) $params->get('optimise_mobile_width', 600)) : 0,
            'webp'    => (bool) $params->get('optimise_webp', 1),
            'quality' => min(100, max(1, (int) $params->get('optimise_quality', 90))),
        );
    }

    /**
     * Optimises an uploaded image: turns it upright (phone photos are often stored sideways with an orientation flag), makes
     * it at most "width" pixels wide, saves it as WebP when asked (with transparency), drops its metadata (EXIF, which on
     * phone photos includes where they were taken), and adds a copy "mobile" pixels wide next to it (photo-600.webp), which
     * templates offer to small screens. GIFs (often animated), SVGs and files PHP can't read are left as they are, and so are
     * images too large for PHP's memory limit. Images aren't made larger.
     *
     * @param   string  $path      The image
     * @param   array   $settings  getOptimiseSettings(), plus keep_name (true: keep the file's name and format, for files
     *                             content already links to)
     *
     * @return  string  The image's path (another one when converted to WebP)
     *
     * @since   3.18.0
     */
    public function optimiseImage($path, array $settings)
    {
        try {
            return $this->doOptimiseImage($path, $settings);
        } catch (\Throwable $e) {
            return $path;
        }
    }

    /**
     * See optimiseImage().
     *
     * @param   string  $path      The image
     * @param   array   $settings  The settings
     *
     * @return  string
     *
     * @since   3.18.0
     */
    private function doOptimiseImage($path, array $settings)
    {
        $info = function_exists('imagecreatetruecolor') ? @getimagesize($path) : false;

        if (!$info || empty($info[0]) || empty($info[1])) {
            return $path;
        }

        $readers = array(IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp');
        $writers = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');

        if (function_exists('imagecreatefrombmp')) {
            $readers[IMAGETYPE_BMP] = 'imagecreatefrombmp';
            $writers[IMAGETYPE_BMP] = 'bmp';
        }

        if (defined('IMAGETYPE_AVIF') && function_exists('imagecreatefromavif')) {
            $readers[IMAGETYPE_AVIF] = 'imagecreatefromavif';
            $writers[IMAGETYPE_AVIF] = 'avif';
        }

        $type = (int) $info[2];

        if (!isset($readers[$type]) || !function_exists($readers[$type])) {
            return $path;
        }

        list($width, $height) = $info;

        // GD needs about 5 bytes a pixel, twice when resizing; an image too large for PHP's memory stays as it is
        $limit = $this->toBytes(ini_get('memory_limit'));

        if ($limit > 0 && memory_get_usage() + $width * $height * 10 > $limit * 0.9) {
            return $path;
        }

        $image = @call_user_func($readers[$type], $path);

        if (!$image) {
            return $path;
        }

        // GD writes WebP only from true colour images (palette PNGs); transparency is kept
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Upright: the EXIF orientation of JPEG photos
        $rotated = $type === IMAGETYPE_JPEG && $this->turnUpright($image, $path);

        if ($rotated) {
            $width  = imagesx($image);
            $height = imagesy($image);
        }

        $keepName = !empty($settings['keep_name']);
        $format   = !$keepName && !empty($settings['webp']) && function_exists('imagewebp') ? 'webp' : $writers[$type];
        $resize   = $settings['width'] > 0 && $width > $settings['width'];
        $quality  = (int) $settings['quality'];

        // JPEG is always written again, which drops its metadata; other formats only when something changes
        $rewrite = $resize || $rotated || $format !== $writers[$type] || $type === IMAGETYPE_JPEG;
        $target  = $path;

        if ($format !== $writers[$type]) {
            $target = dirname($path) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.' . $format;
            $target = $target === $path ? $path : $this->getUniqueFilePath($target);
        }

        if ($rewrite) {
            $output = $resize ? $this->scaleImage($image, $settings['width']) : $image;

            if (!$this->writeImage($output, $target, $format, $quality)) {
                return $path;
            }

            if ($target !== $path) {
                @unlink($path);
            }

            if ($output !== $image) {
                $this->freeImage($image);
                $image = $output;
            }
        }

        // The copy for small screens, next to it: photo-600.webp
        $width = imagesx($image);

        if ($settings['mobile'] > 0 && $width > $settings['mobile']) {
            $mobile = dirname($target) . '/' . pathinfo($target, PATHINFO_FILENAME) . '-' . (int) $settings['mobile'] . '.' . pathinfo($target, PATHINFO_EXTENSION);

            if (!is_file($mobile)) {
                $small = $this->scaleImage($image, $settings['mobile']);
                $this->writeImage($small, $mobile, $format, $quality);
                $this->freeImage($small);
            }
        }

        $this->freeImage($image);

        return $target;
    }

    /**
     * Turns a JPEG photo upright by its EXIF orientation (phones store photos sideways with a flag, which browsers follow).
     *
     * @param   resource|\GdImage  &$image  The image, replaced when turned
     * @param   string             $path    Its file, for the EXIF data
     *
     * @return  boolean  Whether it was turned
     *
     * @since   3.18.0
     */
    private function turnUpright(&$image, $path)
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }

        $exif        = @exif_read_data($path);
        $orientation = is_array($exif) && isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
        $turns       = array(2 => array(0, true), 3 => array(180, false), 4 => array(180, true), 5 => array(270, true), 6 => array(270, false), 7 => array(90, true), 8 => array(90, false));

        if (!isset($turns[$orientation])) {
            return false;
        }

        list($angle, $flip) = $turns[$orientation];

        if ($angle) {
            $image = imagerotate($image, $angle, 0);
        }

        if ($flip) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return true;
    }

    /**
     * The image types cropImage() can read and write again (GD's reader and writer, the format written)
     *
     * @var    array
     * @since  3.18.0
     */
    private const CROP_TYPES = array(
        IMAGETYPE_JPEG => array('imagecreatefromjpeg', 'imagejpeg', 'jpg'),
        IMAGETYPE_PNG  => array('imagecreatefrompng', 'imagepng', 'png'),
        IMAGETYPE_WEBP => array('imagecreatefromwebp', 'imagewebp', 'webp'),
        IMAGETYPE_GIF  => array('imagecreatefromgif', 'imagegif', 'gif'),
    );

    /**
     * Whether cropImage() can crop a file: JPEG, PNG, WebP or GIF (its first frame), which GD can read and write here.
     *
     * @param   string  $path  The image
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function canCrop($path)
    {
        $info = is_file($path) && function_exists('imagecrop') ? @getimagesize($path) : false;
        $gd   = $info && isset(self::CROP_TYPES[(int) $info[2]]) ? self::CROP_TYPES[(int) $info[2]] : null;

        return $gd && function_exists($gd[0]) && function_exists($gd[1]);
    }

    /**
     * Crops an image into a file of the same format: an area of the upright image (as browsers show JPEG photos), written
     * once at the optimiser's quality, without metadata; transparency is kept (a GIF keeps its palette, and loses its
     * animation). The target may be the image itself.
     *
     * @param   string   $path     The image
     * @param   integer  $x        The area's left edge, in the image's pixels
     * @param   integer  $y        Its top edge
     * @param   integer  $width    Its width
     * @param   integer  $height   Its height
     * @param   string   $target   The file to write
     * @param   integer  $quality  1-100 (JPEG, WebP)
     *
     * @return  boolean  False when the image can't be read, is too large for PHP's memory, the area isn't inside it, or the
     *                   file can't be written
     *
     * @since   3.18.0
     */
    public function cropImage($path, $x, $y, $width, $height, $target, $quality = 90)
    {
        if (!$this->canCrop($path)) {
            return false;
        }

        $info = @getimagesize($path);
        $type = (int) $info[2];
        $x    = (int) $x;
        $y    = (int) $y;

        $width  = (int) $width;
        $height = (int) $height;
        $limit  = $this->toBytes(ini_get('memory_limit'));

        // GD needs about 5 bytes a pixel, twice while cropping
        if ($limit > 0 && memory_get_usage() + $info[0] * $info[1] * 10 > $limit * 0.9) {
            return false;
        }

        try {
            $image = @call_user_func(self::CROP_TYPES[$type][0], $path);

            if (!$image) {
                return false;
            }

            if (imageistruecolor($image)) {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            if ($type === IMAGETYPE_JPEG) {
                $this->turnUpright($image, $path);
            }

            if ($width < 1 || $height < 1 || $x < 0 || $y < 0 || $x + $width > imagesx($image) || $y + $height > imagesy($image)) {
                $this->freeImage($image);

                return false;
            }

            if (imageistruecolor($image)) {
                $cropped = imagecrop($image, array('x' => $x, 'y' => $y, 'width' => $width, 'height' => $height));
            } else {
                // A palette image (GIF) by hand: imagecrop() loses its palette, giving one colour
                $cropped     = imagecreate($width, $height);
                $transparent = imagecolortransparent($image);

                imagepalettecopy($cropped, $image);

                if ($transparent >= 0 && $transparent < imagecolorstotal($image)) {
                    imagefill($cropped, 0, 0, $transparent);
                    imagecolortransparent($cropped, $transparent);
                }

                imagecopy($cropped, $image, 0, 0, $x, $y, $width, $height);
            }

            $this->freeImage($image);

            if (!$cropped) {
                return false;
            }

            if (imageistruecolor($cropped)) {
                imagealphablending($cropped, false);
                imagesavealpha($cropped, true);
            }

            $written = $this->writeImage($cropped, $target, self::CROP_TYPES[$type][2], min(100, max(1, (int) $quality)));
            $this->freeImage($cropped);

            return $written;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * The sources of an optimised image for a srcset: its copy for small screens and the image itself, with their widths.
     * Empty when there's no copy.
     *
     * @param   string  $path      The image
     * @param   array   $settings  getOptimiseSettings()
     *
     * @return  array  Arrays of path and width, narrowest first
     *
     * @since   3.18.0
     */
    public function getImageSources($path, array $settings)
    {
        if ($settings['mobile'] <= 0) {
            return array();
        }

        $mobile = dirname($path) . '/' . pathinfo($path, PATHINFO_FILENAME) . '-' . (int) $settings['mobile'] . '.' . pathinfo($path, PATHINFO_EXTENSION);
        $small  = is_file($mobile) ? @getimagesize($mobile) : false;
        $large  = $small ? @getimagesize($path) : false;

        if (!$small || !$large || $small[0] >= $large[0]) {
            return array();
        }

        return array(
            array('path' => $mobile, 'width' => (int) $small[0]),
            array('path' => $path, 'width' => (int) $large[0]),
        );
    }

    /**
     * An image scaled to a width, transparency kept.
     *
     * @param   resource|\GdImage  $image  The image
     * @param   integer            $width  The width
     *
     * @return  resource|\GdImage
     *
     * @since   3.18.0
     */
    private function scaleImage($image, $width)
    {
        $height = max(1, (int) round(imagesy($image) * $width / imagesx($image)));
        $scaled = imagecreatetruecolor($width, $height);

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $scaled;
    }

    /**
     * Writes an image in a format, through a temporary file renamed into place.
     *
     * @param   resource|\GdImage  $image    The image
     * @param   string             $path     Where
     * @param   string             $format   jpg, png, webp, avif, bmp or gif
     * @param   integer            $quality  1-100 (lossy formats)
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    private function writeImage($image, $path, $format, $quality)
    {
        $temporary = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';

        switch ($format) {
            case 'jpg':
                $written = imagejpeg($image, $temporary, $quality);
                break;

            case 'png':
                imagesavealpha($image, true);
                $written = imagepng($image, $temporary, 6);
                break;

            case 'webp':
                imagesavealpha($image, true);
                $written = imagewebp($image, $temporary, $quality);
                break;

            case 'avif':
                $written = function_exists('imageavif') && imageavif($image, $temporary, $quality);
                break;

            case 'bmp':
                $written = function_exists('imagebmp') && imagebmp($image, $temporary);
                break;

            case 'gif':
                $written = imagegif($image, $temporary);
                break;

            default:
                $written = false;
        }

        if (!$written || !is_file($temporary) || !@rename($temporary, $path)) {
            @unlink($temporary);

            return false;
        }

        return true;
    }

    /**
     * Frees a GD image (a no-op since PHP 8.0, deprecated since 8.5).
     *
     * @param   resource|\GdImage  $image  The image
     *
     * @return  void
     *
     * @since   3.18.0
     */
    private function freeImage($image)
    {
        if (PHP_VERSION_ID < 80000 && is_resource($image)) {
            imagedestroy($image);
        }
    }

    /**
     * A path for an uploaded file which doesn't replace another: the path itself when it's free, else with the time added to
     * the name (photo.jpg becomes photo-20261018-143502.jpg), and a number after that if even that is taken.
     *
     * @param   string  $path  The intended path
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public function getUniqueFilePath($path)
    {
        if (!is_file($path)) {
            return $path;
        }

        $folder    = dirname($path);
        $name      = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $suffix    = $extension !== '' ? '.' . $extension : '';
        $base      = $name . '-' . date('Ymd-His');
        $candidate = $folder . DIRECTORY_SEPARATOR . $base . $suffix;

        for ($i = 2; is_file($candidate) && $i < 1000; $i++) {
            $candidate = $folder . DIRECTORY_SEPARATOR . $base . '-' . $i . $suffix;
        }

        return $candidate;
    }

    /**
     * A file or folder name (without its extension) made safe for addresses (renaming in the Media Manager): words with
     * international letters in punycode (Café becomes xn--caf-dma), each space an underscore, nothing but letters, digits,
     * hyphens and underscores. Each word is converted on its own, as the punycode converter refuses a text with spaces.
     *
     * @param   string  $name  The name
     *
     * @return  string  Possibly empty
     *
     * @since   3.18.0
     */
    public function safeFileName($name)
    {
        $parts = preg_split('/([\s_])/u', (string) $name, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: array();

        foreach ($parts as &$part) {
            if (preg_match('/^[\s_]$/u', $part)) {
                $part = '_';
            } elseif (preg_match('/[^\x00-\x7F]/', $part)) {
                $encoded = PunycodeHelper::toPunycode($part);
                $part    = is_string($encoded) && $encoded !== '' ? $encoded : $part;
            }
        }

        return preg_replace('/[^a-zA-Z0-9_\-]/', '', implode('', $parts));
    }

    /**
     * Checks if the file can be uploaded
     *
     * @param   array   $file                File information
     * @param   string  $component           The option name for the component storing the parameters
     * @param   string  $allowedExecutables  Array of executable file types that shall be whitelisted
     *
     * @return  boolean
     *
     * @since   3.2
     */
    public function canUpload($file, $component = 'com_media', $allowedExecutables = array())
    {
        $app    = \JFactory::getApplication();
        $params = ComponentHelper::getParams($component);

        if (empty($file['name'])) {
            $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_UPLOAD_INPUT'), 'error');

            return false;
        }

        jimport('joomla.filesystem.file');

        if (str_replace(' ', '', $file['name']) !== $file['name'] || $file['name'] !== \JFile::makeSafe($file['name'])) {
            $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILENAME'), 'error');

            return false;
        }

        $filetypes = explode('.', $file['name']);

        if (count($filetypes) < 2) {
            // There seems to be no extension
            $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETYPE'), 'error');

            return false;
        }

        array_shift($filetypes);

        // File types are compared in lower case: x.PHP.jpg has an executable extension too, and the lists may use any case
        $filetypes = array_map('strtolower', $filetypes);

        // Media file names should never have executable extensions buried in them.
        $executable = array(
            'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'php9', 'phps', 'pht', 'phtml', 'phar',
            'js', 'exe', 'java', 'perl', 'py', 'asp', 'dll', 'go', 'ade', 'adp', 'bat', 'chm', 'cmd', 'com', 'cpl', 'hta', 'ins', 'isp',
            'jse', 'lib', 'mde', 'msc', 'msp', 'mst', 'pif', 'scr', 'sct', 'shb', 'sys', 'vb', 'vbe', 'vbs', 'vxd', 'wsc', 'wsf', 'wsh',
            'shtml', 'html', 'htm',
        );

        // Remove allowed executables from array
        if (count($allowedExecutables)) {
            $executable = array_diff($executable, $allowedExecutables);
        }

        $check = array_intersect($filetypes, $executable);

        if (!empty($check)) {
            $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETYPE'), 'error');

            return false;
        }

        $filetype  = array_pop($filetypes);
        $allowable = $this->getListOption($params, 'upload_extensions');
        $ignored   = $this->getListOption($params, 'ignore_extensions');

        if ($filetype == '' || $filetype == false || (!in_array($filetype, $allowable) && !in_array($filetype, $ignored))) {
            $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETYPE'), 'error');

            return false;
        }

        $maxSize = (int) ($params->get('upload_maxsize', 0) * 1024 * 1024);

        if ($maxSize > 0 && (int) $file['size'] > $maxSize) {
            $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETOOLARGE'), 'error');

            return false;
        }

        if ($params->get('restrict_uploads', 1)) {
            $images = $this->getListOption($params, 'image_extensions');

            if (in_array($filetype, $images)) {
                // If tmp_name is empty, then the file was bigger than the PHP limit
                if (!empty($file['tmp_name'])) {
                    // Get the mime type this is an image file
                    $mime = $this->getMimeType($file['tmp_name'], true);

                    // Did we get anything useful?
                    if ($mime != false) {
                        $result = $this->checkMimeType($mime, $component);

                        // If the mime type is not allowed we don't upload it and show the mime code error to the user
                        if ($result === false) {
                            $app->enqueueMessage(\JText::sprintf('JLIB_MEDIA_ERROR_WARNINVALID_MIMETYPE', $mime), 'error');

                            return false;
                        }
                    }
                    // We can't detect the mime type so it looks like an invalid image
                    else {
                        $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNINVALID_IMG'), 'error');

                        return false;
                    }
                } else {
                    $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETOOLARGE'), 'error');

                    return false;
                }
            } elseif (!in_array($filetype, $ignored)) {
                // Get the mime type this is not an image file
                $mime = $this->getMimeType($file['tmp_name'], false);

                // Did we get anything useful?
                if ($mime != false) {
                    $result = $this->checkMimeType($mime, $component);

                    // If the mime type is not allowed we don't upload it and show the mime code error to the user
                    if ($result === false) {
                        $app->enqueueMessage(\JText::sprintf('JLIB_MEDIA_ERROR_WARNINVALID_MIMETYPE', $mime), 'error');

                        return false;
                    }
                }
                // We can't detect the mime type so it looks like an invalid file
                else {
                    $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNINVALID_MIME'), 'error');

                    return false;
                }

                if (!\JFactory::getUser()->authorise('core.manage', $component)) {
                    $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNNOTADMIN'), 'error');

                    return false;
                }
            }
        }

        $xss_check = file_get_contents($file['tmp_name'], false, null, 0, 256);

        $html_tags = array(
            'abbr', 'acronym', 'address', 'applet', 'area', 'audioscope', 'base', 'basefont', 'bdo', 'bgsound', 'big', 'blackface', 'blink',
            'blockquote', 'body', 'bq', 'br', 'button', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'comment', 'custom', 'dd', 'del',
            'dfn', 'dir', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'fn', 'font', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'head', 'hr', 'html', 'iframe', 'ilayer', 'img', 'input', 'ins', 'isindex', 'keygen', 'kbd', 'label', 'layer', 'legend', 'li', 'limittext',
            'link', 'listing', 'map', 'marquee', 'menu', 'meta', 'multicol', 'nobr', 'noembed', 'noframes', 'noscript', 'nosmartquotes', 'object',
            'ol', 'optgroup', 'option', 'param', 'plaintext', 'pre', 'rt', 'ruby', 's', 'samp', 'script', 'select', 'server', 'shadow', 'sidebar',
            'small', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title',
            'tr', 'tt', 'ul', 'var', 'wbr', 'xml', 'xmp', '!DOCTYPE', '!--',
        );

        foreach ($html_tags as $tag) {
            // A tag is '<tagname ', so we need to add < and a space or '<tagname>'
            if (stripos($xss_check, '<' . $tag . ' ') !== false || stripos($xss_check, '<' . $tag . '>') !== false) {
                $app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNIEXSS'), 'error');

                return false;
            }
        }

        return true;
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
     */
    public static function imageResize($width, $height, $target)
    {
        /*
         * Takes the larger size of the width and height and applies the
         * formula accordingly. This is so this script will work
         * dynamically with any size image
         */
        if ($width > $height) {
            $percentage = ($target / $width);
        } else {
            $percentage = ($target / $height);
        }

        // Gets the new value and applies the percentage, then rounds the value
        $width  = round($width * $percentage);
        $height = round($height * $percentage);

        return array($width, $height);
    }

    /**
     * Counts the files and directories in a directory that are not php or html files.
     *
     * @param   string  $dir  Directory name
     *
     * @return  array  The number of media files and directories in the given directory
     *
     * @since   3.2
     */
    public function countFiles($dir)
    {
        $total_file = 0;
        $total_dir  = 0;

        if (is_dir($dir)) {
            $d = dir($dir);

            while (($entry = $d->read()) !== false) {
                if ($entry[0] !== '.' && strpos($entry, '.html') === false && strpos($entry, '.php') === false && is_file($dir . DIRECTORY_SEPARATOR . $entry)) {
                    $total_file++;
                }

                if ($entry[0] !== '.' && is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
                    $total_dir++;
                }
            }

            $d->close();
        }

        return array($total_file, $total_dir);
    }

    /**
     * Small helper function that properly converts any
     * configuration options to their byte representation.
     *
     * @param   string|integer  $val  The value to be converted to bytes.
     *
     * @return integer The calculated bytes value from the input.
     *
     * @since 3.3
     */
    public function toBytes($val)
    {
        switch ($val[strlen((string) $val) - 1]) {
            case 'M':
            case 'm':
                return (int) $val * 1048576;
            case 'K':
            case 'k':
                return (int) $val * 1024;
            case 'G':
            case 'g':
                return (int) $val * 1073741824;
            default:
                return $val;
        }
    }
}
