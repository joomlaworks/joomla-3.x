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
 * Lists the extensions found by extension:discover which aren't installed yet.
 *
 * @since  3.17.0
 */
class ExtensionDiscoverListCommand extends ExtensionListCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:discover:list';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'List discovered extensions';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Lists the extensions found on disk by extension:discover which are waiting to be installed with extension:discover:install, '
		. 'or only those whose name or element matches the pattern (with the wildcards * and ?, quoted; without wildcards the match is exact).';

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
		$this->addArgument('pattern', self::ARGUMENT_OPTIONAL, 'Only list extensions whose name or element matches, e.g. "*k2*"');
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
		$io->title('Discovered Extensions');
		$pattern    = (string) $io->getArgument('pattern');
		$extensions = $this->getExtensions(true);

		if (!$extensions)
		{
			$io->text('There are no pending discovered extensions to install. Perhaps you need to run extension:discover first?');
		}
		elseif (!($extensions = $this->filterExtensions($extensions, $pattern)))
		{
			$io->text(sprintf('No discovered extensions match "%s".', $pattern));
		}

		$this->showExtensions($io, $extensions);

		return self::SUCCESS;
	}
}
