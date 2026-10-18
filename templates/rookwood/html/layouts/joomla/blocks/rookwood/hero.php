<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.rookwood
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_BASE') or die;

/**
 * Rookwood's hero block (fields in hero.xml). The title's marked words (the editor's Marker) take Rookwood's gradient.
 *
 * @var \Joomla\CMS\Blocks\BlockRenderer $r
 */
$r     = $displayData['renderer'];
$data  = $displayData['data'];
$value = function ($name) use ($data)
{
	return isset($data[$name]) && is_string($data[$name]) ? trim($data[$name]) : '';
};

$title = str_replace(array('<mark class="cdx-marker">', '</mark>'), array('<span class="highlight">', '</span>'), $r->inline($value('title')));
$image = isset($data['image']) ? $r->image($data['image'], '100vw', array('class' => 'heroImage', 'loading' => 'lazy')) : '';
$size  = $value('size') === 'compact' ? 'is-compact' : 'is-large';

$buttons = '';

foreach (array(array('button', 'link', 'btn btnPrimary'), array('button2', 'link2', 'btn')) as $button)
{
	$url = $r->url($value($button[1]));

	if ($value($button[0]) !== '' && $url !== '')
	{
		$buttons .= '<a class="' . $button[2] . '" href="' . $r->e($url) . '">' . $r->e($value($button[0])) . '</a> ';
	}
}

if (trim(strip_tags($title)) === '' && $image === '')
{
	return;
}
?>
<section class="hero blockHero wide <?php echo $size; ?>"><?php echo $image; ?><div class="heroBody"><?php
	echo $value('eyebrow') !== '' ? '<p class="eyebrow">' . $r->e($value('eyebrow')) . '</p>' : '';
	echo trim(strip_tags($title)) !== '' ? '<h2 class="displayTitle">' . $title . '</h2>' : '';
	echo $value('lead') !== '' ? '<p class="lead">' . nl2br($r->e($value('lead'))) . '</p>' : '';
	echo $buttons !== '' ? '<div class="actions">' . trim($buttons) . '</div>' : ''; ?></div></section>
