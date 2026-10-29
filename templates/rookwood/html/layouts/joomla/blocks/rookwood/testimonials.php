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
 * Rookwood's testimonials block (fields in testimonials.xml): quotes in a two-column grid, each card in its own colour.
 *
 * @var \Joomla\CMS\Blocks\BlockRenderer $r
 */
$r      = $displayData['renderer'];
$data   = $displayData['data'];
$quotes = array();

foreach (isset($data['quotes']) && is_array($data['quotes']) ? $data['quotes'] : array() as $quote) {
    $text = is_array($quote) && isset($quote['quote']) ? trim((string) $quote['quote']) : '';

    if ($text === '') {
        continue;
    }

    $name  = isset($quote['name']) ? trim((string) $quote['name']) : '';
    $role  = isset($quote['role']) ? trim((string) $quote['role']) : '';
    $photo = isset($quote['photo']) ? $r->image($quote['photo'], '64px', array('class' => 'testimonialPhoto', 'loading' => 'lazy')) : '';

    $quotes[] = '<figure class="testimonial"><blockquote><p>' . nl2br($r->e($text)) . '</p></blockquote>'
        . ($name . $role . $photo !== '' ? '<figcaption>' . $photo . '<span>' . ($name !== '' ? '<strong>' . $r->e($name) . '</strong>' : '')
        . ($role !== '' ? '<span class="testimonialRole">' . $r->e($role) . '</span>' : '') . '</span></figcaption>' : '')
        . '</figure>';
}

if (!$quotes) {
    return;
}

$heading = isset($data['heading']) ? trim((string) $data['heading']) : '';
$intro   = isset($data['intro']) ? trim((string) $data['intro']) : '';
?>
<section class="blockTestimonials wide"><?php
    echo $heading !== '' ? '<h2 class="blockTitle">' . $r->e($heading) . '</h2>' : '';
echo $intro !== '' ? '<p class="lead">' . nl2br($r->e($intro)) . '</p>' : ''; ?><div class="testimonials"><?php echo implode('', $quotes); ?></div></section>
