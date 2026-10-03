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
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;

/**
 * Installs an extension package from a file or a URL.
 *
 * @since  3.17.0
 */
class ExtensionInstallCommand extends AbstractExtensionCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:install';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Install an extension from a URL or from a path';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Installs or updates an extension from a package file (--path, a .zip or .tar.gz file, or an already extracted folder) or from a download URL (--url).';

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('path', null, self::OPTION_REQUIRED, 'The path to the extension');
		$this->addOption('url', null, self::OPTION_REQUIRED, 'The url to the extension');
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
		$path = (string) $io->getOption('path');
		$url  = (string) $io->getOption('url');

		if (($path === '') === ($url === ''))
		{
			$io->error('Give either --path or --url.');

			return self::INVALID;
		}

		$io->title('Install Extension');

		Factory::getLanguage()->load('com_installer', JPATH_ADMINISTRATOR);

		$tmpPath = Factory::getConfig()->get('tmp_path');
		$copy    = null;

		if ($url !== '')
		{
			$download = InstallerHelper::downloadPackage($url);

			if (!$download)
			{
				$io->error('The package could not be downloaded from ' . $url . '.');

				return self::FAILURE;
			}

			$copy = $tmpPath . '/' . $download;
		}
		elseif (!file_exists($path))
		{
			$io->error('The file path specified does not exist.');

			return self::FAILURE;
		}
		elseif (is_file($path))
		{
			// Packages are extracted next to the package file, so work on a copy in the temporary folder, like an upload
			$copy = $tmpPath . '/' . uniqid('install_') . '_' . basename($path);

			if (!@copy($path, $copy))
			{
				$io->error('The package could not be copied to the temporary folder ' . $tmpPath . '.');

				return self::FAILURE;
			}
		}

		if ($copy === null)
		{
			$package = array('dir' => $path, 'extractdir' => null, 'packagefile' => null, 'type' => InstallerHelper::detectType($path));
		}
		else
		{
			$package = InstallerHelper::unpack($copy, true);
		}

		if (!is_array($package) || empty($package['type']))
		{
			$io->error('The path is not a valid extension package.');
			$this->cleanup($copy, is_array($package) ? $package : array());

			return self::FAILURE;
		}

		$installer = Installer::getInstance();

		try
		{
			$result = $installer->install($package['dir']);
		}
		finally
		{
			$this->cleanup($copy, $package);
		}

		if (!$result)
		{
			$io->error('Unable to install extension.');

			return self::FAILURE;
		}

		$manifest = $installer->getManifest();

		if ($manifest instanceof \SimpleXMLElement)
		{
			$io->setData('name', (string) $manifest->name);
			$io->setData('type', (string) $manifest->attributes()->type);
			$io->setData('version', (string) $manifest->version);
		}

		$io->success('Extension installed successfully.');

		return self::SUCCESS;
	}

	/**
	 * Remove the package copy and the folder it was extracted to.
	 *
	 * @param   string|null  $copy      The package file in the temporary folder
	 * @param   array        $package   The package details
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function cleanup($copy, array $package)
	{
		if ($copy || !empty($package['extractdir']))
		{
			InstallerHelper::cleanupInstall($copy ?: '', isset($package['extractdir']) ? $package['extractdir'] : '');
		}
	}
}
