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
 * Rookwood's services block (fields in services.xml): numbered cards in the style of the Studio home page's services.
 *
 * @var \Joomla\CMS\Blocks\BlockRenderer $r
 */
$r     = $displayData['renderer'];
$data  = $displayData['data'];
$cards = array();

foreach (isset($data['services']) && is_array($data['services']) ? $data['services'] : array() as $service) {
    $title = is_array($service) && isset($service['title']) ? trim((string) $service['title']) : '';
    $text  = is_array($service) && isset($service['text']) ? trim((string) $service['text']) : '';
    $url   = is_array($service) && isset($service['link']) ? $r->url($service['link']) : '';

    if ($title === '' && $text === '') {
        continue;
    }

    $heading = $url !== '' ? '<a class="serviceLink" href="' . $r->e($url) . '">' . $r->e($title) . '</a>' : $r->e($title);
    $cards[] = '<li class="service"><span class="serviceNumber">' . sprintf('%02d', count($cards) + 1) . '</span>'
        . ($title !== '' ? '<h3>' . $heading . '</h3>' : '') . ($text !== '' ? '<p>' . nl2br($r->e($text)) . '</p>' : '') . '</li>';
}

if (!$cards) {
    return;
}

$heading = isset($data['heading']) ? trim((string) $data['heading']) : '';
$intro   = isset($data['intro']) ? trim((string) $data['intro']) : '';
?>
<section class="blockServices wide"><?php
echo $heading !== '' ? '<h2 class="blockTitle">' . $r->e($heading) . '</h2>' : '';
echo $intro !== '' ? '<p class="lead">' . nl2br($r->e($intro)) . '</p>' : ''; ?><ul class="services count-<?php echo min(4, count($cards)); ?>"><?php echo implode('', $cards); ?></ul></section>
