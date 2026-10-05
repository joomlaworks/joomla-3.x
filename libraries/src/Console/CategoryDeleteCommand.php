<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;

/**
 * Deletes trashed categories.
 *
 * @since  3.17.0
 */
class CategoryDeleteCommand extends AbstractDeleteCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'category:delete';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Delete trashed categories for good';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Deletes the given categories for good, as Empty Trash in the Categories manager does. Only trashed categories without items or subcategories are deleted.';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $model = array('com_categories', 'Category', 'CategoriesModel');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $item = 'category';

	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $table = array('#__categories', 'title', 'published');

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('extension', null, self::OPTION_REQUIRED, 'The component the categories belong to (e.g. com_contact)', 'com_content');
	}

	/**
	 * Run with the component in the request, where the category model reads it.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	public function run(CommandIO $io)
	{
		$extension = (string) $io->getOption('extension');

		if (!preg_match('/^com_[a-z0-9_]+$/', $extension))
		{
			$io->error('--extension must be a component, e.g. com_content.');

			return self::INVALID;
		}

		Factory::getApplication()->input->set('extension', $extension);

		return parent::run($io);
	}

	/**
	 * The category model, set to the component of the categories.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  \CategoriesModelCategory
	 *
	 * @since   3.17.0
	 */
	protected function getItemModel(CommandIO $io)
	{
		return $this->primeCategoryModel(parent::getItemModel($io), (string) $io->getOption('extension'));
	}

	/**
	 * @param   object  $row  The item
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function getAssetName($row)
	{
		return $row->extension . '.category.' . (int) $row->id;
	}
}
