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
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Version;

/**
 * Updates Joomla to the latest version of the update channel, like Joomla Update.
 *
 * @since  3.17.0
 */
class CoreUpdateCommand extends AbstractCommand
{
	/**
	 * @var    string[]
	 * @since  3.17.0
	 */
	protected $serverPathOptions = array('file');

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'core:update';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Update Joomla';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Downloads the latest version from the update channel of Joomla Update, verifies its checksum when the update feed '
		. 'gives one, copies its files over the site and finalises the update (database changes, removal of obsolete files) in a new PHP '
		. 'process, so the new code does it, like the separate requests of Joomla Update. With --reinstall, the files of the current '
		. 'version are installed again when there is no newer one. With --restore-core, core extensions which were uninstalled are '
		. 'installed again, like the "Restore uninstalled core extensions" option of Joomla Update. The progress is logged to '
		. 'joomla_update.php in the log folder. With --file, a local Joomla package (.zip, .tar, .tar.gz/.tgz or .tar.bz2, also '
		. 'one with all files in a single top folder, like a GitHub download) is installed instead of the update channel\'s, e.g. to test '
		. 'a newer or customised build; its version must not be older than the installed one. Zip files need the least memory. If PHP-FPM runs with opcache.validate_timestamps=0, reload it afterwards, '
		. 'as the command line can\'t clear the web server\'s OPcache.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $superUser = true;

	/**
	 * It installs code it didn't make (a package's PHP files and install script).
	 *
	 * @param   array|null  $options    The options of a run
	 * @param   array|null  $arguments  The arguments of a run
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function writesCode(?array $options = null, ?array $arguments = null)
	{
		return true;
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('reinstall', null, self::OPTION_NONE, 'Reinstall the current version\'s files when there is no update');
		$this->addOption('restore-core', null, self::OPTION_NONE, 'Install again the core extensions which were uninstalled');
		$this->addOption('file', null, self::OPTION_REQUIRED, 'Update from this local package file instead of the update channel');
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
		$file = (string) $io->getOption('file');

		if ($file !== '' && $io->getOption('reinstall'))
		{
			$io->error('Give either --file or --reinstall, not both.');

			return self::INVALID;
		}

		if ($file !== '' && (!is_file($file) || !is_readable($file)))
		{
			$io->error(sprintf('The file %s does not exist or is not readable.', $file));

			return self::INVALID;
		}

		if ($file !== '' && !preg_match('/\.(zip|tar|tar\.gz|tgz|tar\.bz2|tbz2)$/i', $file))
		{
			$io->error('The package must be a .zip, .tar, .tar.gz, .tgz or .tar.bz2 file.');

			return self::INVALID;
		}

		$io->title('Updating Joomla');

		if ($io->isDryRun())
		{
			return $this->planUpdate($io, $file);
		}

		static::addLogger();

		$oldVersion = (new Version)->getShortVersion();

		Log::add(Text::sprintf('COM_JOOMLAUPDATE_UPDATE_LOG_START', 0, 'Command line', $oldVersion), Log::INFO, 'Update');

		$io->setData('installed', $oldVersion);

		if ($file !== '')
		{
			$file = realpath($file);
			$io->setData('file', $file);
			Log::add(Text::sprintf('COM_JOOMLAUPDATE_UPDATE_LOG_FILE', $file), Log::INFO, 'Update');

			$this->warnAboutDatabase($io);

			return $this->install($io, $file, false, $oldVersion);
		}

		/** @var \JoomlaupdateModelDefault $model */
		$model = $this->getAdministratorModel('com_joomlaupdate', 'Default', 'JoomlaupdateModel');

		$io->text('Checking for updates ...');
		$model->applyUpdateSite();
		$model->purge();
		$model->refreshUpdates(true);

		$info = $model->getUpdateInformation();

		$io->setData('latest', $info['latest']);

		if (!$info['hasUpdate'] && !$io->getOption('reinstall'))
		{
			$io->setData('updated', false);
			$io->success('You already have the latest Joomla! version ' . $info['latest'] . '.');

			return self::SUCCESS;
		}

		if (!is_object($info['object']) || empty($info['object']->downloadurl->_data))
		{
			$io->error('The update feed doesn\'t give a download URL for version ' . $info['latest'] . '.');

			return self::FAILURE;
		}

		$this->warnAboutDatabase($io);

		$package = $this->download($io, $info['object']);

		if (!$package)
		{
			return self::FAILURE;
		}

		return $this->install($io, $package, true, $oldVersion);
	}

	/**
	 * Report what the update would do, after the same update check.
	 *
	 * @param   CommandIO  $io    The input values and the output
	 * @param   string     $file  The package given with --file, if any
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	private function planUpdate(CommandIO $io, $file)
	{
		$installed = (new Version)->getShortVersion();
		$io->setData('installed', $installed);

		if ($file !== '')
		{
			$io->plan(sprintf('Install Joomla from %s over the site (version %s now)', realpath($file), $installed), array('action' => 'install', 'file' => realpath($file)));
		}
		else
		{
			$model = $this->getAdministratorModel('com_joomlaupdate', 'Default', 'JoomlaupdateModel');
			$model->applyUpdateSite();
			$model->purge();
			$model->refreshUpdates(true);
			$info = $model->getUpdateInformation();
			$io->setData('latest', $info['latest']);

			if (!$info['hasUpdate'] && !$io->getOption('reinstall'))
			{
				$io->success('You already have the latest Joomla! version ' . $info['latest'] . '; nothing would be updated.');

				return self::SUCCESS;
			}

			$io->plan(sprintf('Download Joomla %s and verify its checksum', $info['latest']), array('action' => 'download', 'version' => $info['latest']));
			$io->plan(sprintf('Copy its files over the site (version %s now), leaving out installation/', $installed), array('action' => 'install', 'version' => $info['latest']));
		}

		$io->plan('Finalise: database changes, the update script, removal of obsolete files' . ($io->getOption('restore-core') ? ', restoring uninstalled core extensions' : ''),
			array('action' => 'finalise', 'restoreCore' => (bool) $io->getOption('restore-core')));
		$this->warnAboutDatabase($io);

		return self::SUCCESS;
	}

	/**
	 * Install a package's files and finalise the update.
	 *
	 * @param   CommandIO  $io          The input values and the output
	 * @param   string     $package     The package file
	 * @param   boolean    $downloaded  Whether the package was downloaded, so it's deleted afterwards
	 * @param   string     $oldVersion  The version before the update
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	protected function install(CommandIO $io, $package, $downloaded, $oldVersion)
	{
		$folder = Factory::getConfig()->get('tmp_path') . '/' . uniqid('jupdate_');

		try
		{
			$io->text('Extracting the update package ...');

			if (!$this->extract($io, $package, $folder))
			{
				return self::FAILURE;
			}

			$root     = $this->findPackageRoot($folder);
			$manifest = $root ? @simplexml_load_file($root . '/administrator/manifests/files/joomla.xml') : false;

			if (!$manifest)
			{
				$io->error('The package is not a Joomla package: it has no administrator/manifests/files/joomla.xml.');

				return self::FAILURE;
			}

			$newVersion = (string) $manifest->version;
			$io->setData('version', $newVersion);

			// Database changes can't be undone, so an older version's code would run on a newer database
			if (version_compare($newVersion, $oldVersion, '<'))
			{
				$io->error(sprintf('The package is Joomla %s, older than the installed %s. Downgrades are not supported.', $newVersion, $oldVersion));

				return self::FAILURE;
			}

			Log::add(Text::_('COM_JOOMLAUPDATE_UPDATE_LOG_INSTALL'), Log::INFO, 'Update');
			$io->text(sprintf('Copying the files of Joomla %s ...', $newVersion));

			$errors = array();
			$count  = $this->copyTree($root, JPATH_ROOT, $errors, true);

			foreach ($errors as $error)
			{
				$io->error($error);
			}

			if ($errors)
			{
				$io->error('Some files could not be copied; the site may be partly updated. Fix the permissions and run the command again.');

				return self::FAILURE;
			}

			$io->setData('files', $count);
		}
		finally
		{
			if (is_dir($folder))
			{
				\JFolder::delete($folder);
			}

			if ($downloaded)
			{
				@unlink($package);
			}
		}

		Log::add(Text::_('COM_JOOMLAUPDATE_UPDATE_LOG_FINALISE'), Log::INFO, 'Update');
		$io->text('Finalising the update ...');

		$exitCode = $this->finalise($io, $oldVersion, (bool) $io->getOption('restore-core'));

		if ($exitCode !== self::SUCCESS)
		{
			$io->error('The update was not finalised. Run "php cli/joomla.php maintenance:database --fix" and check the log folder\'s joomla_update.php.');

			return $exitCode;
		}

		$io->setData('updated', true);
		$io->success(sprintf('Joomla updated from %s to %s.', $oldVersion, $newVersion));

		return self::SUCCESS;
	}

	/**
	 * Report the database problems Extensions: Database shows. Joomla Update doesn't refuse to update a site with them, and an
	 * update often fixes them.
	 *
	 * @param   CommandIO  $io  The output
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function warnAboutDatabase(CommandIO $io)
	{
		foreach (MaintenanceDatabaseCommand::getProblems() as $problem)
		{
			$io->warning('Database: ' . $problem);
		}
	}

	/**
	 * Find the folder holding the Joomla files: the extraction folder, or its only subfolder for archives with a top folder
	 * (e.g. a GitHub download).
	 *
	 * @param   string  $folder  The extraction folder
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	protected function findPackageRoot($folder)
	{
		if (is_file($folder . '/administrator/manifests/files/joomla.xml'))
		{
			return $folder;
		}

		$entries = array_values(array_diff((array) scandir($folder), array('.', '..')));

		if (count($entries) === 1 && is_file($folder . '/' . $entries[0] . '/administrator/manifests/files/joomla.xml'))
		{
			return $folder . '/' . $entries[0];
		}

		return null;
	}

	/**
	 * Log to joomla_update.php in the log folder, like Joomla Update.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public static function addLogger()
	{
		static $added = false;

		if (!$added)
		{
			$added = true;
			Factory::getLanguage()->load('com_joomlaupdate', JPATH_ADMINISTRATOR);
			Log::addLogger(
				array('format' => '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}', 'text_file' => 'joomla_update.php'),
				Log::INFO,
				array('Update', 'databasequery', 'jerror')
			);
		}
	}

	/**
	 * Download the update package, trying the mirrors of the update feed if needed, and verify its checksum.
	 *
	 * @param   CommandIO  $io      The output
	 * @param   \JUpdate   $update  The update
	 *
	 * @return  string|false  The package file
	 *
	 * @since   3.17.0
	 */
	protected function download(CommandIO $io, $update)
	{
		$urls = array(trim((string) $update->downloadurl->_data));

		foreach ((array) $update->get('downloadSources', array()) as $source)
		{
			$urls[] = trim((string) $source->url);
		}

		$tmpPath = Factory::getConfig()->get('tmp_path');

		foreach (array_filter($urls) as $url)
		{
			$io->text('Downloading ' . $url . ' ...');
			Log::add(Text::sprintf('COM_JOOMLAUPDATE_UPDATE_LOG_URL', $url), Log::INFO, 'Update');

			$file = InstallerHelper::downloadPackage($url);

			if (!$file)
			{
				$io->warning('The download failed.');

				continue;
			}

			$package = $tmpPath . '/' . $file;

			Log::add(Text::sprintf('COM_JOOMLAUPDATE_UPDATE_LOG_FILE', $file), Log::INFO, 'Update');

			if (InstallerHelper::isChecksumValid($package, $update) === InstallerHelper::HASH_NOT_VALIDATED)
			{
				@unlink($package);
				$io->error('The checksum of the downloaded package doesn\'t match the one of the update feed.');

				return false;
			}

			return $package;
		}

		$io->error('The update package could not be downloaded.');

		return false;
	}

	/**
	 * Extract a ZIP package.
	 *
	 * @param   CommandIO  $io       The output
	 * @param   string     $package  The package file
	 * @param   string     $folder   The folder to extract to
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected function extract(CommandIO $io, $package, $folder)
	{
		if (preg_match('/\.(tar|tar\.gz|tgz|tar\.bz2|tbz2)$/i', $package))
		{
			// Joomla's archive library decompresses and reads tar files in memory, so give it room
			$limit = trim((string) ini_get('memory_limit'));
			$units = array('K' => 1024, 'M' => 1048576, 'G' => 1073741824);
			$unit  = strtoupper(substr($limit, -1));
			$bytes = (int) $limit * (isset($units[$unit]) ? $units[$unit] : 1);

			if ($limit !== '-1' && $bytes < 268435456)
			{
				@ini_set('memory_limit', '256M');
			}

			// It also refuses entries which would be written outside the folder
			try
			{
				$archive = new \Joomla\Archive\Archive(array('tmp_path' => Factory::getConfig()->get('tmp_path')));

				if (\JFolder::create($folder) && $archive->extract($package, $folder))
				{
					return true;
				}
			}
			catch (\Exception $e)
			{
				$io->error('The update package could not be extracted: ' . $e->getMessage());

				return false;
			}

			$io->error('The update package could not be extracted to ' . $folder . '.');

			return false;
		}

		if (!class_exists('ZipArchive'))
		{
			$io->error('The PHP zip extension is needed to extract the update package.');

			return false;
		}

		$archive = new \ZipArchive;

		if ($archive->open($package) !== true)
		{
			$io->error('The update package is not a valid ZIP file.');

			return false;
		}

		for ($i = 0; $i < $archive->numFiles; $i++)
		{
			$name = str_replace('\\', '/', $archive->getNameIndex($i));

			if ($name === '' || $name[0] === '/' || preg_match('#(^|/)\.\.(/|$)#', $name) || strpos($name, ':') !== false)
			{
				$archive->close();
				$io->error(sprintf('The update package contains an unsafe path: %s', $name));

				return false;
			}
		}

		if (!\JFolder::create($folder) || !$archive->extractTo($folder))
		{
			$archive->close();
			$io->error('The update package could not be extracted to ' . $folder . '.');

			return false;
		}

		$archive->close();

		return true;
	}

	/**
	 * Copy a folder's files over another folder, leaving out the installation folder of a full package.
	 *
	 * @param   string   $source  The source folder
	 * @param   string   $target  The target folder
	 * @param   array    $errors  The files which could not be copied
	 * @param   boolean  $root    Whether this is the package's root
	 *
	 * @return  integer  The number of files copied
	 *
	 * @since   3.17.0
	 */
	protected function copyTree($source, $target, array &$errors, $root = false)
	{
		$count = 0;

		foreach (new \DirectoryIterator($source) as $item)
		{
			if ($item->isDot())
			{
				continue;
			}

			$name = $item->getFilename();

			// Joomla Update deletes it after extracting; it's never needed on an installed site
			if ($root && $name === 'installation')
			{
				continue;
			}

			$to = $target . '/' . $name;

			if ($item->isDir())
			{
				if (!is_dir($to) && !@mkdir($to, 0755, true))
				{
					$errors[] = 'Cannot create the folder ' . $to;

					continue;
				}

				$count += $this->copyTree($item->getPathname(), $to, $errors);

				continue;
			}

			if (!@copy($item->getPathname(), $to))
			{
				$errors[] = 'Cannot write ' . $to;

				continue;
			}

			$count++;
		}

		return $count;
	}

	/**
	 * Run core:update:finalise in a new PHP process, which loads the new files.
	 *
	 * @param   CommandIO  $io           The output
	 * @param   string     $oldVersion   The version before the update
	 * @param   boolean    $restoreCore  Install again the uninstalled core extensions
	 *
	 * @return  integer  The exit code
	 *
	 * @since   3.17.0
	 */
	protected function finalise(CommandIO $io, $oldVersion, $restoreCore)
	{
		$disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

		if (!function_exists('proc_open') || in_array('proc_open', $disabled, true) || !PHP_BINARY)
		{
			$io->warning('PHP can\'t start a new process here (proc_open), so the update is finalised by this process, which still has some of the old code loaded.');

			$options = array('from-version' => $oldVersion, 'restore-core' => $restoreCore, 'quiet' => $io->isQuiet());

			return Factory::getApplication()->runCommand('core:update:finalise', array(), $options);
		}

		$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(JPATH_ROOT . '/cli/joomla.php') . ' core:update:finalise --format=json'
			. ' --from-version=' . escapeshellarg($oldVersion) . ($restoreCore ? ' --restore-core' : '');

		$process = proc_open($command, array(1 => array('pipe', 'w'), 2 => STDERR), $pipes, JPATH_ROOT);

		if (!is_resource($process))
		{
			$io->error('Cannot start the finalisation process.');

			return self::FAILURE;
		}

		$output   = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$exitCode = proc_close($process);
		$result   = json_decode($output, true);

		if (!is_array($result))
		{
			$io->error('The finalisation process gave no result: ' . trim(substr($output, 0, 2000)));

			return self::FAILURE;
		}

		foreach (isset($result['messages']) ? $result['messages'] : array() as $message)
		{
			switch ($message['type'])
			{
				case 'error':
					$io->error($message['text']);
					break;

				case 'warning':
					$io->warning($message['text']);
					break;

				default:
					$io->text($message['text']);
			}
		}

		if (isset($result['data']['version']))
		{
			$io->setData('version', $result['data']['version']);
		}

		return isset($result['exitCode']) ? (int) $result['exitCode'] : ($exitCode ?: self::FAILURE);
	}
}
