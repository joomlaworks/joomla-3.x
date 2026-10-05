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
 * Creates a tag.
 *
 * @since  3.17.0
 */
class TagCreateCommand extends AbstractTagCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'tag:create';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Create a tag';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Creates a tag, saved as the Tags manager saves it. It\'s published unless --state says otherwise. --title is required; '
		. '--parent places it under another tag.';

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
		$io->title('Create Tag');

		if ((string) $io->getOption('title') === '')
		{
			$io->error('--title is required.');

			return self::INVALID;
		}

		$data = array(
			'id'          => 0,
			'alias'       => '',
			'parent_id'   => 1,
			'published'   => 1,
			'access'      => (int) Factory::getConfig()->get('access', 1),
			'language'    => '*',
			'description' => '',
			'params'      => array(),
		);

		if (!$this->applyFieldOptions($io, $data))
		{
			return self::INVALID;
		}

		if (!$this->user->authorise('core.create', 'com_tags'))
		{
			$io->error(sprintf('The user "%s" may not create tags.', $this->user->username));

			return self::REFUSED;
		}

		if ((int) $data['published'] !== 0 && !$this->user->authorise('core.edit.state', 'com_tags'))
		{
			$io->error(sprintf('The user "%s" may not publish tags; create it with --state=unpublished.', $this->user->username));

			return self::REFUSED;
		}

		$id = $this->validateAndSave($io, $this->getTagModel(), $data);

		if ($id === false)
		{
			return self::FAILURE;
		}

		if ($io->isDryRun())
		{
			$this->planChanges($io, sprintf('Create the tag "%s"', $data['title']), array_diff_key($data, array('id' => 0, 'params' => 0)));

			return self::SUCCESS;
		}

		$tag = $this->describeTag($this->loadTag($io, $this->getTagModel(), $id));

		foreach ($tag as $key => $value)
		{
			$io->setData($key, $value);
		}

		$io->success(sprintf('Tag "%s" created with ID %d.', $tag['path'], $id));

		return self::SUCCESS;
	}
}
