<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/**
 * Smart Search CLI.
 *
 * This is a command-line script to help with management of Smart Search.
 *
 * Called with no arguments: php finder_indexer.php
 *                           Performs an incremental update of the index using dynamic pausing.
 *
 * IMPORTANT NOTE:  since Joomla version 3.9.12 the default behavior of this script has changed.
 *                  If called with no arguments, the `--pause` argument is silently applied, in order to avoid the possibility of
 *                  stressing the server too much and making a site (or multiple sites, if on a shared environment) unresponsive.
 *                  If a pause is unwanted, just apply `--pause=0` to the command
 *
 * Called with --purge       php finder_indexer.php --purge
 *                           Purges and rebuilds the index (search filters are preserved).
 *
 * Called with --pause           `php finder_indexer.php --pause`
 *          or --pause=x         or `php finder_indexer.php --pause=x` where x = seconds.
 *          or --pause=division  or `php finder_indexer.php --pause=division` The default divisor is 5.
 *                               If another divisor is required, it can be set with --divisor=y, where
 *                               y is the integer divisor
 *
 *                               This will pause for x seconds between batches,
 *                               in order to give the server some time to catch up
 *                               if --pause is called without an assignment, it defaults to dynamic pausing
 *                               using the division method with a divisor of 5
 *                               (eg. 1 second pause for every 5 seconds of batch processing time)
 *
 * Called with --minproctime=x   Will set the minimum processing time of batches for a pause to occur. Defaults to 1
 *
 */

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_finder');

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';

// System configuration.
$config = new JConfig;
define('JDEBUG', $config->debug);

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Library language
$lang = JFactory::getLanguage();

// Try the finder_cli file in the current language (without allowing the loading of the file in the default language)
$lang->load('finder_cli', JPATH_SITE, null, false, false)
// Fallback to the finder_cli file in the default language
|| $lang->load('finder_cli', JPATH_SITE, null, true);

/**
 * A command line cron job to run the Smart Search indexer.
 *
 * @since  2.5
 */
class FinderCli extends JApplicationCli
{
	/**
	 * Entry point for Smart Search CLI script
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function doExecute()
	{
		// Same as: php cli/joomla.php finder:index [purge] [--pause=...] [--divisor=...] [--minproctime=...]
		$pause = $this->input->get('pause', 'division', 'raw');

		// A bare --pause means the default dynamic pausing
		if ($pause === true || $pause === '')
		{
			$pause = 'division';
		}

		$this->close(
			(new \Joomla\CMS\Application\ConsoleApplication)->runCommand(
				'finder:index',
				array('purge' => $this->input->getString('purge', false) ? 'purge' : null),
				array(
					'minproctime' => $this->input->getInt('minproctime', 1),
					'pause'       => $pause,
					'divisor'     => $this->input->getInt('divisor', 5),
				)
			)
		);
	}
}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('FinderCli')->execute();
