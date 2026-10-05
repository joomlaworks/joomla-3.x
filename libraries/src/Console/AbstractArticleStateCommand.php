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
 * Base class of article:publish, article:unpublish, article:archive and article:trash.
 *
 * @since  3.17.0
 */
abstract class AbstractArticleStateCommand extends AbstractStateCommand
{
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
