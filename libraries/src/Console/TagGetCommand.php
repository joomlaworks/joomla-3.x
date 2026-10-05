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
 * Shows a tag.
 *
 * @since  3.17.0
 */
class TagGetCommand extends AbstractTagCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'tag:get';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Show a tag';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Shows a tag: its fields, its description and the number of items tagged with it.';

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
		$this->addArgument('id', self::ARGUMENT_REQUIRED, 'The tag ID');
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
		$data = $this->loadTag($io, $this->getTagModel(), $io->getArgument('id'));

		if ($data === false)
		{
			return self::NOT_FOUND;
		}

		$tag = $this->describeTag($data);
		$io->title('Tag ' . $tag['id']);
		$io->definitionList($tag);

		return self::SUCCESS;
	}
}
