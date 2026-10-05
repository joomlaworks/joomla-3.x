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
 * Base class of the commands which publish, unpublish, archive or trash items (articles, categories, modules, menu items) through
 * their component's model, as the toolbar buttons of their manager do, after checking the acting user's permissions.
 *
 * @since  3.17.0
 */
abstract class AbstractStateCommand extends AbstractContentCommand
{
	/**
	 * The new state: 1 published, 0 unpublished, 2 archived, -2 trashed
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	protected $state = 1;

	/**
	 * What the items are called, e.g. "article"
	 *
	 * @var    string
	 * @since  3.17.0
	 */
	protected $item = '';

	/**
	 * The component, model name and model prefix, e.g. array('com_content', 'Article', 'ContentModel')
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $model = array();

	/**
	 * The table, and its title and state columns
	 *
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $table = array('#__content', 'title', 'state');

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addArgument('id', self::ARGUMENT_REQUIRED | self::ARGUMENT_ARRAY, 'The ' . $this->item . ' IDs');
		$this->addOption('force', null, self::OPTION_NONE, 'Change items even when someone has them open for editing');
	}

	/**
	 * The asset whose core.edit.state permission is checked.
	 *
	 * @param   object  $row  The item
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	abstract protected function getAssetName($row);

	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$verb = array(1 => 'Publish', 0 => 'Unpublish', 2 => 'Archive', -2 => 'Trash');
		$io->title($verb[$this->state] . ' ' . ucwords($this->item));

		$rows = $this->loadRows($io);

		if ($rows === false)
		{
			return self::NOT_FOUND;
		}

		foreach ($rows as $row)
		{
			if (!$this->checkNotCheckedOut($io, $row))
			{
				return self::REFUSED;
			}

			if (!$this->user->authorise('core.edit.state', $this->getAssetName($row)))
			{
				$io->error(sprintf('The user "%s" may not change the state of the %s %d.', $this->user->username, $this->item, $row->id));

				return self::REFUSED;
			}
		}

		$ids = array();

		foreach ($rows as $row)
		{
			if ((int) $row->state === $this->state)
			{
				$io->text(sprintf('The %s %d "%s" is already %s.', $this->item, $row->id, $row->title, $this->stateName($this->state)));

				continue;
			}

			$ids[] = (int) $row->id;

			if ($io->isDryRun())
			{
				$io->plan(sprintf('%s the %s %d "%s" (now %s)', $verb[$this->state], $this->item, $row->id, $row->title, $this->stateName($row->state)),
					array('action' => strtolower($verb[$this->state]), 'id' => (int) $row->id));
			}
		}

		$io->setData('ids', $ids);

		if (!$ids || $io->isDryRun())
		{
			return self::SUCCESS;
		}

		$model = $this->getItemModel($io);

		// The model takes the IDs by reference and drops those it skips
		$pks = $ids;

		if (!$model->publish($pks, $this->state))
		{
			$io->error($model->getError() ?: sprintf('The %s state was not changed.', $this->item));

			return self::FAILURE;
		}

		// The model skips items it won't change (e.g. the default home page) and reports them as messages; count what changed
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName($this->table[0]))
			->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
			->where($db->quoteName($this->table[2]) . ' = ' . (int) $this->state);
		$changed   = array_map('intval', $db->setQuery($query)->loadColumn());
		$unchanged = array_values(array_diff($ids, $changed));

		$io->setData('ids', $changed);
		$io->setData('unchanged', $unchanged);

		if ($unchanged)
		{
			$io->error(sprintf('%d of %d %s(s) %s; not changed: %s.', count($changed), count($ids), $this->item, $this->stateName($this->state), implode(', ', $unchanged)));

			return self::FAILURE;
		}

		$io->success(sprintf('%d %s(s) %s.', count($ids), $this->item, $this->stateName($this->state)));

		return self::SUCCESS;
	}

	/**
	 * The model which changes the items.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  \JModelAdmin
	 *
	 * @since   3.17.0
	 */
	protected function getItemModel(CommandIO $io)
	{
		return $this->getModel($this->model[0], $this->model[1], $this->model[2]);
	}

	/**
	 * Load the items given as arguments.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  object[]|false
	 *
	 * @since   3.17.0
	 */
	protected function loadRows(CommandIO $io)
	{
		$db   = Factory::getDbo();
		$rows = array();

		foreach ((array) $io->getArgument('id') as $id)
		{
			$query = $db->getQuery(true)
				->select('*')
				->select($db->quoteName($this->table[1], 'title') . ', ' . $db->quoteName($this->table[2], 'state'))
				->from($db->quoteName($this->table[0]))
				->where($db->quoteName('id') . ' = ' . (int) $id);
			$row = ctype_digit((string) $id) ? $db->setQuery($query)->loadObject() : null;

			if (!$row)
			{
				$io->error(sprintf('There is no %s with ID %s.', $this->item, $id));

				return false;
			}

			$rows[] = $row;
		}

		return $rows;
	}
}
