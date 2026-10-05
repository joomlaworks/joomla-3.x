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
 * Shows a module.
 *
 * @since  3.17.0
 */
class ModuleGetCommand extends AbstractModuleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'module:get';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show a module';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows a module: its fields, options (params), the pages it shows on and its content.';

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
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The module ID');
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
		$data = $this->loadModule($io, $this->getModel('com_modules', 'Module', 'ModulesModel'), $io->getArgument('id'));

		if ($data === false)
		{
			return self::NOT_FOUND;
		}

		$module = $this->describeModule($data);
		$io->title('Module ' . $module['id']);
		$list              = $module;
		$list['menuItems'] = implode(', ', $module['menuItems']);
		$list['params']    = json_encode($module['params'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$io->definitionList($list);
		$io->setData('menuItems', $module['menuItems']);
		$io->setData('params', (object) $module['params']);

		return self::SUCCESS;
	}
}
