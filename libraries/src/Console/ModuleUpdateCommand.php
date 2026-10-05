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
 * Changes a module.
 *
 * @since  3.17.0
 */
class ModuleUpdateCommand extends AbstractModuleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'module:update';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Change a module';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Changes the given fields of a module and leaves the others as they are; --params is merged into the module\'s options. '
		. 'A module someone has open for editing is refused unless --force is given.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The module ID');
		$this->addFieldOptions();
		$this->addOption('force', null, self::OPTION_NONE, 'Change the module even when someone has it open for editing');
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
		$io->title('Change Module');

		$model = $this->getModel('com_modules', 'Module', 'ModulesModel');
		$data  = $this->loadModule($io, $model, $io->getArgument('id'));

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

		$asset = 'com_modules.module.' . (int) $data['id'];

		if (!$this->user->authorise('core.edit', $asset))
		{
			$io->error(sprintf('The user "%s" may not edit this module.', $this->user->username));

			return self::REFUSED;
		}

		if ((int) $data['published'] !== (int) $current['published'] && !$this->user->authorise('core.edit.state', $asset))
		{
			$io->error(sprintf('The user "%s" may not change the state of this module.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$changes = $data;
			$changes['params']   = json_encode($data['params']);
			$changes['assigned'] = implode(',', $data['assigned']);
			$current['params']   = json_encode($current['params']);
			$current['assigned'] = implode(',', $current['assigned']);
			$this->planChanges($io, sprintf('Change the module %d "%s"', $current['id'], $current['title']), $changes, $current);

			return self::SUCCESS;
		}

		$module = $this->describeModule($this->loadModule($io, $this->getModel('com_modules', 'Module', 'ModulesModel'), $id));

		foreach ($module as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Module %d "%s" saved.', $id, $module['title']));

		return self::SUCCESS;
	}
}
