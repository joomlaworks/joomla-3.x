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
 * Base class of the commands which delete trashed items for good, as the managers' "Empty Trash" button does: only trashed items,
 * so nothing is deleted by mistake, after checking the acting user's permissions.
 *
 * @since  3.17.0
 */
abstract class AbstractDeleteCommand extends AbstractStateCommand
{
	/**
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function doExecute(CommandIO $io)
	{
		$io->title('Delete ' . ucwords($this->item));

		$rows = $this->loadRows($io);

		if ($rows === false)
		{
			return self::NOT_FOUND;
		}

		foreach ($rows as $row)
		{
			if ((int) $row->state !== -2)
			{
				$io->error(sprintf('The %s %d "%s" isn\'t trashed. Only trashed items are deleted: trash it first.', $this->item, $row->id, $row->title));

				return self::REFUSED;
			}

			if (!$this->checkNotCheckedOut($io, $row))
			{
				return self::REFUSED;
			}

			if (!$this->user->authorise('core.delete', $this->getAssetName($row)))
			{
				$io->error(sprintf('The user "%s" may not delete the %s %d.', $this->user->username, $this->item, $row->id));

				return self::REFUSED;
			}
		}

		$ids = array_map(function ($row)
		{
			return (int) $row->id;
		}, $rows);

		$io->setData('ids', $ids);

		if ($io->isDryRun())
		{
			foreach ($rows as $row)
			{
				$io->plan(sprintf('Delete the %s %d "%s" for good', $this->item, $row->id, $row->title), array('action' => 'delete', 'id' => (int) $row->id));
			}

			return self::SUCCESS;
		}

		if ($io->isInteractive() && !$io->confirm(sprintf('Delete %d %s(s) for good?', count($ids), $this->item), false))
		{
			$io->text('Nothing deleted.');

			return self::SUCCESS;
		}

		$model = $this->getItemModel($io);

		$pks = $ids;

		if (!$model->delete($pks))
		{
			$io->error($model->getError() ?: sprintf('The %s(s) were not deleted.', $this->item));

			return self::FAILURE;
		}

		$io->success(sprintf('%d %s(s) deleted.', count($ids), $this->item));

		return self::SUCCESS;
	}
}
