<?php

/**
 * @package     FrameworkOnFramework
 * @subpackage  less
 * @copyright   Copyright (C) 2010-2016 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 * @note        This file has been modified by the Joomla! Project and no longer reflects the original work of its author.
 */

// Protect from unauthorized access
defined('FOF_INCLUDED') or die;

/**
 * This class is taken near verbatim (changes marked with **FOF** comment markers) from:
 *
 * lessphp v0.5.0
 * http://leafo.net/lessphp
 *
 * LESS CSS compiler, adapted from http://lesscss.org
 *
 * Copyright 2013, Leaf Corcoran <leafot@gmail.com>
 * Licensed under MIT or GPLv3, see LICENSE
 *
 * Joomla 3.x UTD: updated from lessphp v0.3.9 to the copy of v0.5.0 in libraries/vendor/leafo/lessphp, with its patches
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class FOFLessFormatterClassic
{
    public $indentChar = "  ";

    public $break = "\n";
    public $open = " {";
    public $close = "}";
    public $selectorSeparator = ", ";
    public $assignSeparator = ":";

    public $openSingle = " { ";
    public $closeSingle = " }";

    public $disableSingle = false;
    public $breakSelectors = false;

    public $compressColors = false;

    // Joomla 3.x UTD patch: declared (PHP 8.2+ deprecates creating it)
    public $indentLevel;

    public function __construct()
    {
        $this->indentLevel = 0;
    }

    public function indentStr($n = 0)
    {
        return str_repeat((string) $this->indentChar, max($this->indentLevel + $n, 0));
    }

    public function property($name, $value)
    {
        return $name . $this->assignSeparator . $value . ";";
    }

    protected function isEmpty($block)
    {
        if (empty($block->lines)) {
            foreach ($block->children as $child) {
                if (!$this->isEmpty($child)) {
                    return false;
                }
            }

            return true;
        }
        return false;
    }

    public function block($block)
    {
        if ($this->isEmpty($block)) {
            return;
        }

        $inner = $pre = $this->indentStr();

        $isSingle = !$this->disableSingle &&
            is_null($block->type) && count($block->lines) == 1;

        if (!empty($block->selectors)) {
            $this->indentLevel++;

            if ($this->breakSelectors) {
                $selectorSeparator = $this->selectorSeparator . $this->break . $pre;
            } else {
                $selectorSeparator = $this->selectorSeparator;
            }

            echo $pre .
                implode($selectorSeparator, $block->selectors);
            if ($isSingle) {
                echo $this->openSingle;
                $inner = "";
            } else {
                echo $this->open . $this->break;
                $inner = $this->indentStr();
            }

        }

        if (!empty($block->lines)) {
            $glue = $this->break.$inner;
            echo $inner . implode($glue, $block->lines);
            if (!$isSingle && !empty($block->children)) {
                echo $this->break;
            }
        }

        foreach ($block->children as $child) {
            $this->block($child);
        }

        if (!empty($block->selectors)) {
            if (!$isSingle && empty($block->children)) {
                echo $this->break;
            }

            if ($isSingle) {
                echo $this->closeSingle . $this->break;
            } else {
                echo $pre . $this->close . $this->break;
            }

            $this->indentLevel--;
        }
    }
}
