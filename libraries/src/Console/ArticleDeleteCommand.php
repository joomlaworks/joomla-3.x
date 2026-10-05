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
 * Deletes trashed articles.
 *
 * @since  3.17.0
 */
class ArticleDeleteCommand extends AbstractDeleteCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'article:delete';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Delete trashed articles for good';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Deletes the given articles for good, as Empty Trash in Content: Articles does. Only trashed articles are deleted (article:trash first).';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $model = array('com_content', 'Article', 'ContentModel');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $item = 'article';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $table = array('#__content', 'title', 'state');

	/**
	 * @param   object  $row  The item
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getAssetName($row)
	{
		return 'com_content.article.' . (int) $row->id;
	}
}
