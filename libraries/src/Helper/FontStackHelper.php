<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Helper;

defined('JPATH_PLATFORM') or die;

/**
 * Font stacks of fonts already on visitors' devices (no web fonts to load), from Modern Font Stacks
 * (https://modernfontstacks.com, CC0): each stack's name and its CSS font-family value. The Font field (type fontstack)
 * lists them; templates write the chosen one with get().
 *
 * @since  3.18.0
 */
abstract class FontStackHelper
{
    /**
     * The stacks: key => array(name, CSS font-family value)
     *
     * @var    array
     * @since  3.18.0
     */
    public const STACKS = array(
        'system-ui' => array('System UI', "system-ui, sans-serif"),
        'transitional' => array('Transitional', "Charter, 'Bitstream Charter', 'Sitka Text', Cambria, serif"),
        'old-style' => array('Old Style', "'Iowan Old Style', 'Palatino Linotype', 'URW Palladio L', P052, serif"),
        'humanist' => array('Humanist', "Seravek, 'Gill Sans Nova', Ubuntu, Calibri, 'DejaVu Sans', source-sans-pro, sans-serif"),
        'geometric-humanist' => array('Geometric Humanist', "Avenir, Montserrat, Corbel, 'URW Gothic', source-sans-pro, sans-serif"),
        'classical-humanist' => array('Classical Humanist', "Optima, Candara, 'Noto Sans', source-sans-pro, sans-serif"),
        'neo-grotesque' => array('Neo-Grotesque', "Inter, Roboto, 'Helvetica Neue', 'Arial Nova', 'Nimbus Sans', Arial, sans-serif"),
        'monospace-slab-serif' => array('Monospace Slab Serif', "'Nimbus Mono PS', 'Courier New', monospace"),
        'monospace-code' => array('Monospace Code', "ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Consolas, 'DejaVu Sans Mono', monospace"),
        'industrial' => array('Industrial', "Bahnschrift, 'DIN Alternate', 'Franklin Gothic Medium', 'Nimbus Sans Narrow', sans-serif-condensed, sans-serif"),
        'rounded-sans' => array('Rounded Sans', "ui-rounded, 'Hiragino Maru Gothic ProN', Quicksand, Comfortaa, Manjari, 'Arial Rounded MT', 'Arial Rounded MT Bold', Calibri, source-sans-pro, sans-serif"),
        'slab-serif' => array('Slab Serif', "Rockwell, 'Rockwell Nova', 'Roboto Slab', 'DejaVu Serif', 'Sitka Small', serif"),
        'antique' => array('Antique', "Superclarendon, 'Bookman Old Style', 'URW Bookman', 'URW Bookman L', 'Georgia Pro', Georgia, serif"),
        'didone' => array('Didone', "Didot, 'Bodoni MT', 'Noto Serif Display', 'URW Palladio L', P052, Sylfaen, serif"),
        'handwritten' => array('Handwritten', "'Segoe Print', 'Bradley Hand', Chilanka, TSCu_Comic, casual, cursive"),
    );

    /**
     * The stack used for a key that isn't one
     *
     * @var    string
     * @since  3.18.0
     */
    public const DEFAULT_STACK = 'system-ui';

    /**
     * A stack's CSS font-family value
     *
     * @param   string  $key  The stack's key (STACKS)
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function get($key)
    {
        $key = (string) $key;

        return isset(static::STACKS[$key]) ? static::STACKS[$key][1] : static::STACKS[static::DEFAULT_STACK][1];
    }
}
