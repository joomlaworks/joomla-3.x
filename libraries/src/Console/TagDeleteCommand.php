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
 * Delete trashed tags for good.
 *
 * @since  3.17.0
 */
class TagDeleteCommand extends AbstractDeleteCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'tag:delete';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Delete trashed tags for good';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Deletes the given tags for good, as Empty Trash in the Tags manager does. Only trashed tags are deleted; their child '
		. 'tags move up to the deleted tag\'s parent, and items tagged with them lose the tag.';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $model = array('com_tags', 'Tag', 'TagsModel');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $item = 'tag';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $table = array('#__tags', 'title', 'published');

	/**
	 * The tags, without the root of the tag tree (ID 1), which isn't a tag.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  object[]|false
	 *
	 * @since   3.17.0
	 */
	protected function loadRows(CommandIO $io)
	{
		foreach ((array) $io->getArgument('id') as $id)
		{
			if (ctype_digit((string) $id) && (int) $id <= 1)
			{
				$io->error(sprintf('There is no tag with ID %s.', $id));

				return false;
			}
		}

		return parent::loadRows($io);
	}

	/**
	 * Tags have no permissions of their own: the Tags component's apply.
	 *
	 * @param   object  $row  The item
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getAssetName($row)
	{
		return 'com_tags';
	}
}
