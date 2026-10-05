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
 * Archive articles.
 *
 * @since  3.17.0
 */
class ArticleArchiveCommand extends AbstractArticleStateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'article:archive';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Archive articles';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Archives the given articles, as the toolbar button of Content: Articles does, after checking that the acting user (--as) may.';

	/**
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 2;
}
