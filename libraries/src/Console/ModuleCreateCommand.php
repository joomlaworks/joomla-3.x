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
 * Creates a module.
 *
 * @since  3.17.0
 */
class ModuleCreateCommand extends AbstractModuleCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'module:create';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Create a module';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Creates a module of the given type (e.g. mod_custom for HTML content, mod_menu, mod_articles_latest), saved as the Modules '
		. 'manager saves it. It\'s unpublished unless --state=published is given. --type and --title are required; set the module\'s own options '
		. 'with --params (JSON) and where it shows with --pages and --menu-items.';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		parent::configure();
		$this->addOption('type', null, self::OPTION_REQUIRED, 'The module type (e.g. mod_custom)');
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site or administrator', 'site');
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
		$io->title('Create Module');

		$type   = (string) $io->getOption('type');
		$client = (string) $io->getOption('client');

		if ($type === '' || (string) $io->getOption('title') === '')
		{
			$io->error('--type and --title are required.');

			return self::INVALID;
		}

		if (!in_array($client, array('site', 'administrator'), true))
		{
			$io->error('--client must be site or administrator.');

			return self::INVALID;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('module'))
			->where($db->quoteName('element') . ' = ' . $db->quote($type))
			->where($db->quoteName('client_id') . ' = ' . ($client === 'site' ? 0 : 1))
			->where($db->quoteName('state') . ' <> -1');

		if (!$db->setQuery($query)->loadResult())
		{
			$io->error(sprintf('There is no %s module type "%s" installed. extension:list --type=module shows them.', $client, $type));

			return self::NOT_FOUND;
		}

		if (!$this->user->authorise('core.create', 'com_modules'))
		{
			$io->error(sprintf('The user "%s" may not create modules.', $this->user->username));

			return self::REFUSED;
		}

		$model = $this->getModel('com_modules', 'Module', 'ModulesModel');
		$data  = array(
			'id'         => 0,
			'module'     => $type,
			'client_id'  => $client === 'site' ? 0 : 1,
			'published'  => 0,
			'showtitle'  => 1,
			'position'   => '',
			'access'     => (int) Factory::getConfig()->get('access', 1),
			'language'   => '*',
			'content'    => '',
			'note'       => '',
			'params'     => array(),
			'assignment' => 0,
			'assigned'   => array(),
		);

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		if ((int) $data['published'] !== 0 && !$this->user->authorise('core.edit.state', 'com_modules'))
		{
			$io->error(sprintf('The user "%s" may not publish modules.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $model, $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$this->planChanges($io, sprintf('Create the %s module "%s"', $type, $data['title']), array_diff_key($data, array('id' => 0, 'params' => 0, 'assigned' => 0)));

			return self::SUCCESS;
		}

		$module = $this->describeModule($this->loadModule($io, $this->getModel('com_modules', 'Module', 'ModulesModel'), $id));

		foreach ($module as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Module "%s" created with ID %d (%s).', $module['title'], $id, $module['state']));

		return self::SUCCESS;
	}
}
