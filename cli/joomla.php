<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/**
 * The Joomla command line interface. Run it from the site's root folder, for example:
 *
 *   php cli/joomla.php list
 *   php cli/joomla.php help user:add
 *   php cli/joomla.php extension:list --type=plugin --format=json
 *
 * Command names, arguments and options follow the command line interface of Joomla 4 and later.
 */

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg')
{
	echo 'This script must be run from the command line.';
	exit(1);
}

// Initialize Joomla framework
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

// Before installation only core:install runs (and its help)
if (!file_exists(JPATH_CONFIGURATION . '/configuration.php') && !in_array('core:install', array_slice($_SERVER['argv'], 1), true))
{
	fwrite(STDERR, 'No configuration file found at ' . JPATH_CONFIGURATION . '/configuration.php. Install Joomla first, e.g. with:'
		. PHP_EOL . '  php cli/joomla.php core:install' . PHP_EOL);
	exit(1);
}

// PHP notices go to stderr, so stdout stays clean for the command output (e.g. --format=json)
ini_set('display_errors', 'stderr');

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Honour the site's error reporting setting, like the web application does
switch (JFactory::getConfig()->get('error_reporting'))
{
	case 'none':
	case '0':
		error_reporting(0);
		break;

	case 'simple':
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		break;

	case 'maximum':
	case 'development':
		error_reporting(E_ALL);
		break;
}

$application = new \Joomla\CMS\Application\ConsoleApplication;
$application->execute();

exit($application->getExitCode());
