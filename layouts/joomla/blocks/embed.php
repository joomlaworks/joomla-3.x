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
 * An embedded video or post: data.service, data.embed (the player's address), data.width, data.height, data.caption. Only the services the editor offers, through their own players' addresses.
 *
 * Override in a template: html/layouts/joomla/blocks/embed.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$players = array(
    'youtube'   => '#^https://www\.youtube\.com/embed/[A-Za-z0-9_-]+(\?[A-Za-z0-9_=&;%-]*)?$#',
    'vimeo'     => '#^https://player\.vimeo\.com/video/[0-9]+(\?[A-Za-z0-9_=&;%-]*)?$#',
    'twitter'   => '#^https://platform\.twitter\.com/embed/Tweet\.html\?id=[0-9]+$#',
    'instagram' => '#^https://www\.instagram\.com/p/[A-Za-z0-9_-]+/embed/?$#',
    'codepen'   => '#^https://codepen\.io/[A-Za-z0-9_-]+/embed/[A-Za-z0-9_-]+(\?[A-Za-z0-9_=&;%-]*)?$#',
);
$service = isset($data['service']) ? (string) $data['service'] : '';
$embed   = isset($data['embed']) ? (string) $data['embed'] : '';

if (!isset($players[$service]) || !preg_match($players[$service], $embed)) {
    return;
}

$width   = !empty($data['width']) ? max(100, min(2000, (int) $data['width'])) : 580;
$height  = !empty($data['height']) ? max(100, min(2000, (int) $data['height'])) : 320;
$caption = $r->inline(isset($data['caption']) ? $data['caption'] : '');
$title   = $r->plain($caption) !== '' ? $r->plain($caption) : ucfirst($service);
?>
<figure class="block-embed block-embed-<?php echo $service; ?>"><iframe src="<?php echo $r->e($embed); ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" loading="lazy" allowfullscreen="allowfullscreen" title="<?php echo $r->e($title); ?>"></iframe><?php echo $caption !== '' ? '<figcaption>' . $caption . '</figcaption>' : ''; ?></figure>
