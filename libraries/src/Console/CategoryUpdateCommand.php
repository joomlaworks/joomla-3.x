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
 * Changes a category.
 *
 * @since  3.17.0
 */
class CategoryUpdateCommand extends AbstractCategoryCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'category:update';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Change a category';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Changes the given fields of a category and leaves the others as they are. --parent moves it. A category someone has open '
		. 'for editing is refused unless --force is given.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The category ID');
		$this->addFieldOptions();
		$this->addOption('force', null, self::OPTION_NONE, 'Change the category even when someone has it open for editing');
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
		$io->title('Change Category');

		$model = $this->getCategoryModel($io);
		$data  = $this->loadCategory($io, $model, $io->getArgument('id'));

		if ($data === false)
		{
			return self::NOT_FOUND;
		}

		if (!$this->checkNotCheckedOut($io, (object) $data))
		{
			return self::REFUSED;
		}

		$current = $data;

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		$asset = $data['extension'] . '.category.' . (int) $data['id'];

		if (!$this->user->authorise('core.edit', $asset)
			&& !($this->user->authorise('core.edit.own', $asset) && (int) $current['created_user_id'] === (int) $this->user->id))
		{
			$io->error(sprintf('The user "%s" may not edit this category.', $this->user->username));

			return self::REFUSED;
		}

		if ((int) $data['published'] !== (int) $current['published'] && !$this->user->authorise('core.edit.state', $asset))
		{
			$io->error(sprintf('The user "%s" may not change the state of this category.', $this->user->username));

			return self::REFUSED;
		}

		if ((int) $data['parent_id'] === (int) $data['id'])
		{
			$io->error('A category can\'t be its own parent.');

			return self::INVALID;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$changes = array_diff_key($data, array('params' => 0, 'tags' => 0));
			$changes['tags'] = implode(',', (array) $data['tags']);
			$current['tags'] = implode(',', (array) $current['tags']);
			$this->planChanges($io, sprintf('Change the category %d "%s"', $current['id'], $current['title']), $changes, $current);

			return self::SUCCESS;
		}

		$category = $this->describeCategory($this->loadCategory($io, $this->getCategoryModel($io), $id));

		foreach ($category as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Category %d "%s" saved.', $id, $category['path']));

		return self::SUCCESS;
	}
}
