<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

/**
 * media:optimise, the British spelling of media:optimize (left out of "list" and of the MCP tools, so it isn't shown twice).
 *
 * @since  3.18.0
 */
class MediaOptimiseCommand extends MediaOptimizeCommand
{
    /**
     * @var    string
     * @since  3.18.0
     */
    protected $name = 'media:optimise';

    /**
     * @var    boolean
     * @since  3.18.0
     */
    protected $hidden = true;
}
