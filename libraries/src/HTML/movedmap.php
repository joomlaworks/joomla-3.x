<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/*
 * Files and folders of the site that moved, by their path from the site's root: old => new, folders ending in "/". Used by
 * Joomla\CMS\HTML\MovedPaths for addresses extensions give the document directly ($doc->addScript(JUri::root(true) .
 * '/media/...')) and for JHtml requests by path. A path is mapped only when nothing is at the old one and something is at
 * the new one. The media folders renamed in 3.18 (HTMLHelper::MOVED_FOLDERS) and the media files moved into their extensions
 * (HTMLHelper::MOVED_FILES) are added from there: list here what they don't cover.
 */
return array(
    // "Editor - TinyMCE (legacy)" (TinyMCE 4), whose files are in the plugin's folder since 3.18: extensions load its
    // tinymce.min.js, skins or plugins from the old folder (Convert Forms and Tassos' framework do)
    'media/editors/tinymce/' => 'plugins/editors/tinymce/',
);
