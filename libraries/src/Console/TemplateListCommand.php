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
 * Lists the template styles.
 *
 * @since  3.17.0
 */
class TemplateListCommand extends AbstractTemplateCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'template:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List the template styles';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the template styles of the site (or the administrator, or both): each style is a template with its settings. '
		. 'The default style is used on every page whose menu item doesn\'t choose another one. template:info shows a style in detail.';

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
		$this->addOption('client', null, self::OPTION_REQUIRED, 'site, administrator or all', 'site');
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
		$client = (string) $io->getOption('client');

		if (!in_array($client, array('site', 'administrator', 'all'), true))
		{
			$io->error('--client must be site, administrator or all.');

			return self::INVALID;
		}

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('s.id', 's.template', 's.client_id', 's.home', 's.title', 'e.enabled')))
			->from($db->quoteName('#__template_styles', 's'))
			->join('LEFT', $db->quoteName('#__extensions', 'e') . ' ON ' . $db->quoteName('e.element') . ' = ' . $db->quoteName('s.template')
				. ' AND ' . $db->quoteName('e.client_id') . ' = ' . $db->quoteName('s.client_id') . ' AND ' . $db->quoteName('e.type') . ' = '
				. $db->quote('template'))
			->order($db->quoteName('s.client_id') . ', ' . $db->quoteName('s.template') . ', ' . $db->quoteName('s.id'));

		if ($client !== 'all')
		{
			$query->where($db->quoteName('s.client_id') . ' = ' . ($client === 'site' ? 0 : 1));
		}

		$rows = array();

		foreach ($db->setQuery($query)->loadObjectList() as $row)
		{
			$rows[] = array(
				'id'       => (int) $row->id,
				'title'    => $row->title,
				'template' => $row->template,
				'client'   => (int) $row->client_id === 0 ? 'site' : 'administrator',
				// "1" is the default of all languages; a language code makes it the default of that language
				'default'  => $row->home === '1' ? 'yes' : ($row->home === '0' || $row->home === '' ? 'no' : $row->home),
				'enabled'  => $row->enabled === null ? null : (bool) $row->enabled,
			);
		}

		$io->title('Template styles');

		if (!$rows)
		{
			$io->setData('items', array());
			$io->text('No template styles.');

			return self::SUCCESS;
		}

		$io->table(array('id' => 'ID', 'title' => 'Title', 'template' => 'Template', 'client' => 'Client', 'default' => 'Default',
			'enabled' => 'Template enabled'), $rows);

		return self::SUCCESS;
	}
}
