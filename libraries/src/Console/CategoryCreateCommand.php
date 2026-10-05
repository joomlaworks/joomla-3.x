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
 * Creates a category.
 *
 * @since  3.17.0
 */
class CategoryCreateCommand extends AbstractCategoryCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'category:create';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Create a category';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Creates a category of articles (or of another component with --extension), saved as the Categories manager saves it. '
		. 'It\'s published unless --state says otherwise. --title is required; --parent places it under another category.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addFieldOptions();
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
		$io->title('Create Category');

		if ((string) $io->getOption('title') === '')
		{
			$io->error('--title is required.');

			return self::INVALID;
		}

		$extension = (string) $io->getOption('extension');
		$model     = $this->getCategoryModel($io);
		$data      = array(
			'id'          => 0,
			'alias'       => '',
			'extension'   => $extension,
			'parent_id'   => 1,
			'published'   => 1,
			'access'      => (int) Factory::getConfig()->get('access', 1),
			'language'    => '*',
			'description' => '',
			'params'      => array(),
			'tags'        => array(),
		);

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		$asset = (int) $data['parent_id'] > 1 ? $extension . '.category.' . (int) $data['parent_id'] : $extension;

		if (!$this->user->authorise('core.create', $asset))
		{
			$io->error(sprintf('The user "%s" may not create categories there.', $this->user->username));

			return self::REFUSED;
		}

		if ((int) $data['published'] !== 0 && !$this->user->authorise('core.edit.state', $asset))
		{
			$io->error(sprintf('The user "%s" may not publish categories there; create it with --state=unpublished.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$this->planChanges($io, sprintf('Create the %s category "%s"', $extension, $data['title']), array_diff_key($data, array('id' => 0, 'params' => 0, 'tags' => 0)));

			return self::SUCCESS;
		}

		$category = $this->describeCategory($this->loadCategory($io, $this->getCategoryModel($io), $id));

		foreach ($category as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Category "%s" created with ID %d.', $category['path'], $id));

		return self::SUCCESS;
	}
}
