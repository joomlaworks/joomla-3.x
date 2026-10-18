<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_BASE') or die;

/**
 * An image: data.file (url, alt, width, height, srcset: a list of {location, width}), data.caption, data.withBorder, data.withBackground, data.stretched
 *
 * Override in a template: html/layouts/joomla/blocks/image.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$file = isset($data['file']) && is_array($data['file']) ? $data['file'] : array();
$src  = $r->siteUrl(isset($file['url']) ? $file['url'] : '');

if ($src === '')
{
	return;
}

$caption = $r->inline(isset($data['caption']) ? $data['caption'] : '');
$classes = array('block-image');

foreach (array('withBorder' => 'has-border', 'withBackground' => 'has-background', 'stretched' => 'is-stretched') as $flag => $class)
{
	if (!empty($data[$flag]))
	{
		$classes[] = $class;
	}
}

// The copy for small screens the Media Manager made on upload, written as the other editors write it
$srcset = array();
$widest = 0;

if (isset($file['srcset']) && is_array($file['srcset']))
{
	foreach ($file['srcset'] as $source)
	{
		$url   = $r->siteUrl(isset($source['location']) ? $source['location'] : '');
		$width = isset($source['width']) ? (int) $source['width'] : 0;

		if ($url !== '' && $width > 0 && !preg_match('/[\s,]/', $url))
		{
			$srcset[] = $url . ' ' . $width . 'w';
			$widest   = max($widest, $width);
		}
	}
}

// The alt text the image had in the content it came from, else the caption's text
$alt    = isset($file['alt']) && trim((string) $file['alt']) !== '' ? trim((string) $file['alt']) : $r->plain($caption);
$width  = !empty($file['width']) ? (int) $file['width'] : 0;
$height = !empty($file['height']) ? (int) $file['height'] : 0;
$attrs  = count($srcset) > 1 ? ' srcset="' . $r->e(implode(', ', $srcset)) . '" sizes="(max-width: ' . $widest . 'px) 100vw, ' . $widest . 'px"' : '';
$attrs .= $width > 0 && $height > 0 ? ' width="' . $width . '" height="' . $height . '"' : '';
?>
<figure class="<?php echo implode(' ', $classes); ?>"><img src="<?php echo $r->e($src); ?>" alt="<?php echo $r->e($alt); ?>"<?php echo $attrs; ?> /><?php echo $caption !== '' ? '<figcaption>' . $caption . '</figcaption>' : ''; ?></figure>
