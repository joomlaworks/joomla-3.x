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
 * Shows a category.
 *
 * @since  3.17.0
 */
class CategoryGetCommand extends AbstractCategoryCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'category:get';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show a category';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows a category: its fields and its description.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $readOnly = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The category ID');
	}

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$data = $this->loadCategory($io, $this->getCategoryModel($io), $io->getArgument('id'));

		if ($data === false)
		{
			return self::NOT_FOUND;
		}

		$category = $this->describeCategory($data);
		$io->title('Category ' . $category['id']);
		$io->definitionList($category);

		return self::SUCCESS;
	}
}
