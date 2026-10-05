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
 * Trash articles.
 *
 * @since  3.17.0
 */
class ArticleTrashCommand extends AbstractArticleStateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'article:trash';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Trash articles';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Trashes the given articles, as the toolbar button of Content: Articles does, after checking that the acting user (--as) may.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = -2;
}
