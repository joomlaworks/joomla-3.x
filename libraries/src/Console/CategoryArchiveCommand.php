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
 * Archive categories.
 *
 * @since  3.17.0
 */
class CategoryArchiveCommand extends AbstractCategoryStateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'category:archive';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Archive categories';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Archives the given categories, as the toolbar button of the Categories manager does, after checking that the acting user (--as) may.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 2;
}
