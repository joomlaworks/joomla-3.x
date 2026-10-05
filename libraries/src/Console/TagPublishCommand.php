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
 * Publish tags.
 *
 * @since  3.17.0
 */
class TagPublishCommand extends AbstractTagStateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'tag:publish';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Publish tags';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Publishes the given tags, as the toolbar button of the Tags manager does, after checking that the acting user (--as) may.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 1;
}
