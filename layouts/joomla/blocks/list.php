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
 * A list: data.style (unordered, ordered, checklist), data.meta.start, data.items (content, meta.checked, items: nested)
 *
 * Override in a template: html/layouts/joomla/blocks/list.php. $displayData: data (the block's data), block (the whole block),
 * renderer (Joomla\CMS\Blocks\BlockRenderer: inline(), url(), siteUrl(), e(), plain()).
 */

/** @var \Joomla\CMS\Blocks\BlockRenderer $r */
$r    = $displayData['renderer'];
$data = $displayData['data'];

$style = isset($data['style']) && in_array($data['style'], array('ordered', 'unordered', 'checklist'), true) ? $data['style'] : 'unordered';
$start = isset($data['meta']['start']) ? (int) $data['meta']['start'] : 1;

$render = function ($list, $depth) use (&$render, $r, $style, $start) {
    if (!is_array($list) || !$list || $depth > 10) {
        return '';
    }

    $tag  = $style === 'ordered' ? 'ol' : 'ul';
    $html = '<' . $tag . ($style === 'checklist' ? ' class="block-checklist"' : '')
        . ($tag === 'ol' && $start !== 1 && $depth === 0 ? ' start="' . $start . '"' : '') . '>';

    foreach ($list as $item) {
        $item    = is_array($item) ? $item : array('content' => (string) $item);
        $checked = $style === 'checklist' && !empty($item['meta']['checked']);

        $html .= '<li' . ($style === 'checklist' ? ' class="' . ($checked ? 'is-checked' : 'is-unchecked') . '"' : '') . '>'
            . ($style === 'checklist' ? '<input type="checkbox" disabled="disabled"' . ($checked ? ' checked="checked"' : '') . ' /> ' : '')
            . $r->inline(isset($item['content']) ? $item['content'] : '')
            . $render(isset($item['items']) ? $item['items'] : array(), $depth + 1)
            . '</li>';
    }

    return $html . '</' . $tag . '>';
};

echo $render(isset($data['items']) ? $data['items'] : array(), 0);
