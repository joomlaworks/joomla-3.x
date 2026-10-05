<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Console;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Extension\ExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Version;

/**
 * Installs an extension again over itself, from the same version's package on its update site or from a given package, so its
 * files match their source again (e.g. on a site which may have been hacked), and lists the files in its folders the package
 * doesn't have.
 *
 * @since  3.17.0
 */
class ExtensionReinstallCommand extends AbstractExtensionCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'extension:reinstall';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Install an extension again over itself, to restore its files';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Installs an extension again over itself, so every file of its package is written again: e.g. to make sure its files '
		. 'match the originals on a site which may have been hacked. By default the package comes from the extension\'s update site, in the '
		. 'version installed (with the site\'s download key, if it has one, and checked against the checksum the update site gives); --file '
		. 'or --url give the package instead (--sha256 checks it against a known checksum). The package must be the same extension, and the '
		. 'same version unless --allow-version-change is given. Afterwards it lists the files in the extension\'s folders which the package '
		. 'doesn\'t have (added later, e.g. by an attacker, or by the extension itself, such as caches or uploads: check them) and deletes '
		. 'them with --remove-extra. Settings and database content are kept. Core extensions are restored with core:update --reinstall.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addArgument('extensionId', self::ARGUMENT_REQUIRED, 'ID of the extension (see extension:list)');
		$this->addOption('file', null, self::OPTION_REQUIRED, 'Install from this package file (.zip, .tar.gz, ...) or extracted folder');
		$this->addOption('url', null, self::OPTION_REQUIRED, 'Install from the package at this URL');
		$this->addOption('sha256', null, self::OPTION_REQUIRED, 'The package\'s known SHA-256 checksum, checked before installing (with --file or --url)');
		$this->addOption('allow-version-change', null, self::OPTION_NONE, 'Accept a package of another version (from the update site: the latest compatible one when the installed version isn\'t there)');
		$this->addOption('remove-extra', null, self::OPTION_NONE, 'Delete the files in the extension\'s folders which its package doesn\'t have');
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
		$io->title('Reinstall Extension');

		$extension = $this->loadExtension($io, $io->getArgument('extensionId'));

		if (!$extension)
		{
			return self::NOT_FOUND;
		}

		$file = (string) $io->getOption('file');
		$url  = (string) $io->getOption('url');

		if ($file !== '' && $url !== '')
		{
			$io->error('Give either --file or --url, not both.');

			return self::INVALID;
		}

		if ((string) $io->getOption('sha256') !== '' && $file === '' && $url === '')
		{
			$io->error('--sha256 is for a package given with --file or --url; packages from the update site are checked against its checksum.');

			return self::INVALID;
		}

		if (ExtensionHelper::checkIfCoreExtension($extension->type, $extension->element, $extension->client_id, $extension->folder))
		{
			$io->error($this->describe($extension) . ' is part of Joomla. Restore the core files with core:update --reinstall.');

			return self::REFUSED;
		}

		$manifest  = json_decode((string) $extension->manifest_cache, true);
		$installed = is_array($manifest) && isset($manifest['version']) ? (string) $manifest['version'] : '';

		Factory::getLanguage()->load('com_installer', JPATH_ADMINISTRATOR);
		Factory::getLanguage()->load('lib_joomla', JPATH_ADMINISTRATOR);

		// Get the package
		$copy     = null;
		$checksum = null;

		if ($file !== '')
		{
			if (!file_exists($file))
			{
				$io->error(sprintf('The file %s does not exist.', $file));

				return self::NOT_FOUND;
			}

			if (is_file($file))
			{
				// Packages are extracted next to the package file, so work on a copy in the temporary folder, like an upload
				$copy = Factory::getConfig()->get('tmp_path') . '/' . uniqid('reinstall_') . '_' . basename($file);

				if (!@copy($file, $copy))
				{
					$io->error('The package could not be copied to the temporary folder.');

					return self::FAILURE;
				}
			}

			$source = realpath($file);
		}
		else
		{
			if ($url === '')
			{
				$release = $this->findRelease($io, $extension, $installed);

				if ($release === false)
				{
					return self::NOT_FOUND;
				}

				$url      = $release['url'];
				$checksum = $release['checksums'];
			}

			$io->text('Downloading ' . preg_replace('/([?&](?:dlid|key|download_id|token)=)[^&]+/i', '$1***', $url) . ' ...');
			$download = InstallerHelper::downloadPackage($url);

			if (!$download)
			{
				$io->error('The package could not be downloaded.');

				return self::FAILURE;
			}

			$copy   = $this->withArchiveExtension(Factory::getConfig()->get('tmp_path') . '/' . $download);
			$source = preg_replace('/([?&](?:dlid|key|download_id|token)=)[^&]+/i', '$1***', $url);
		}

		if ((string) $io->getOption('sha256') !== '')
		{
			$checksum = array('sha256' => strtolower(trim((string) $io->getOption('sha256'))));
		}

		if ($copy && $checksum)
		{
			foreach ($checksum as $algorithm => $expected)
			{
				if (!hash_equals($expected, hash_file($algorithm, $copy)))
				{
					@unlink($copy);
					$io->error(sprintf('The package\'s %s checksum doesn\'t match: it isn\'t the expected package. Nothing was installed.', strtoupper($algorithm)));

					return self::REFUSED;
				}
			}

			$io->text('Checksum verified (' . implode(', ', array_map('strtoupper', array_keys($checksum))) . ').');
		}
		elseif ($copy || $file !== '')
		{
			$io->warning($file !== '' || (string) $io->getOption('url') !== ''
				? 'The package wasn\'t checked against a checksum; give --sha256 to check it against its publisher\'s.'
				: 'The update site gives no checksum, so the downloaded package couldn\'t be checked against one.');
		}

		$package = $copy ? InstallerHelper::unpack($copy, true) : array('dir' => $file, 'extractdir' => null, 'packagefile' => null);

		try
		{
			if (!is_array($package) || empty($package['dir']))
			{
				$io->error('The package could not be unpacked.');

				return self::INVALID;
			}

			// The package must be this extension
			$identity = $this->identify($package['dir']);

			if ($identity === false)
			{
				$io->error('The package has no valid installation manifest.');

				return self::INVALID;
			}

			$expected = array('type' => $extension->type, 'element' => strtolower($extension->element), 'client' => (int) $extension->client_id,
				'folder' => (string) $extension->folder);
			$found    = array_intersect_key($identity, $expected);

			if ($found != $expected)
			{
				$io->error(sprintf('The package is %s "%s" (client %d%s), not %s. Nothing was installed.', $identity['type'], $identity['element'], $identity['client'],
					$identity['folder'] !== '' ? ', group ' . $identity['folder'] : '', $this->describe($extension)));

				return self::REFUSED;
			}

			if ($installed !== '' && version_compare($identity['version'], $installed, '!=') && !$io->getOption('allow-version-change'))
			{
				$io->error(sprintf('The package is version %s, but %s is installed. Give --allow-version-change to install it anyway.', $identity['version'], $installed));

				return self::REFUSED;
			}

			$io->setData('extension', array('id' => (int) $extension->extension_id, 'name' => $extension->name, 'type' => $extension->type, 'element' => $extension->element));
			$io->setData('installedVersion', $installed);
			$io->setData('packageVersion', $identity['version']);
			$io->setData('source', $source);

			$folders = $this->getFolders($extension);

			if ($io->isDryRun())
			{
				$io->plan(sprintf('Install %s %s again from %s, overwriting its files', $this->describe($extension), $identity['version'], $source),
					array('action' => 'reinstall', 'version' => $identity['version'], 'source' => $source));
				$io->plan(sprintf('List the files in %s which the package doesn\'t have%s', implode(', ', $folders) ?: 'its folders',
					$io->getOption('remove-extra') ? ', and delete them' : ''), array('action' => 'extra-files', 'folders' => $folders));

				return self::SUCCESS;
			}

			// Every file the package has is written now; what is older afterwards isn't in the package
			$start = time();
			sleep(1);

			$installer = Installer::getInstance();

			if (!$installer->update($package['dir']))
			{
				$io->error(sprintf('%s could not be installed again.', $this->describe($extension)));

				return self::FAILURE;
			}
		}
		finally
		{
			if ($copy || !empty($package['extractdir']))
			{
				InstallerHelper::cleanupInstall($copy ?: '', is_array($package) && isset($package['extractdir']) ? $package['extractdir'] : '');
			}
		}

		$io->success(sprintf('%s %s installed again from %s.', $this->describe($extension), $identity['version'], $source));

		// Files in the extension's folders which the installation didn't write
		$extra = $this->findOlderFiles($folders, $start);
		$io->setData('extraFiles', $extra);

		if (!$extra)
		{
			$io->text('Every file in ' . implode(', ', $folders) . ' comes from the package.');

			return self::SUCCESS;
		}

		$io->warning(sprintf('%d file(s) in the extension\'s folders aren\'t in its package: added later, by someone or by the extension itself (e.g. caches, uploads). Check them:', count($extra)));

		foreach ($extra as $path)
		{
			$io->writeln('  ' . $path);
		}

		if ($io->getOption('remove-extra'))
		{
			$removed = array();

			foreach ($extra as $path)
			{
				if (@unlink(JPATH_ROOT . '/' . $path))
				{
					$removed[] = $path;
				}
				else
				{
					$io->warning('Could not delete ' . $path);
				}
			}

			$io->setData('removedFiles', $removed);
			$io->success(sprintf('%d file(s) deleted.', count($removed)));

			return count($removed) === count($extra) ? self::SUCCESS : self::FAILURE;
		}

		$io->text('Give --remove-extra to delete them.');

		return self::SUCCESS;
	}

	/**
	 * Give a downloaded package the extension of its archive type when its name has none (a download URL such as
	 * download.php?id=5 sent without a file name), as the unpacking goes by the extension.
	 *
	 * @param   string  $path  The downloaded file
	 *
	 * @return  string  Its path, renamed when needed
	 *
	 * @since   3.17.0
	 */
	private function withArchiveExtension($path)
	{
		if (preg_match('/\.(zip|tar|tgz|gz|bz2|tbz2)$/i', $path))
		{
			return $path;
		}

		$head  = (string) @file_get_contents($path, false, null, 0, 4);
		$types = array("PK\x03\x04" => '.zip', "\x1f\x8b" => '.tar.gz', 'BZh' => '.tar.bz2');

		foreach ($types as $signature => $extension)
		{
			if (strpos($head, $signature) === 0 && @rename($path, $path . $extension))
			{
				return $path . $extension;
			}
		}

		return $path;
	}

	/**
	 * Find the release of the installed version (or, with --allow-version-change, the latest compatible one) on the extension's
	 * update sites.
	 *
	 * @param   CommandIO  $io         The input values and the output
	 * @param   object     $extension  The extension
	 * @param   string     $installed  The installed version
	 *
	 * @return  array|false  url and checksums, or false (the reason shown)
	 *
	 * @since   3.17.0
	 */
	private function findRelease(CommandIO $io, $extension, $installed)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('s.location', 's.extra_query', 's.name')))
			->from($db->quoteName('#__update_sites', 's'))
			->join('INNER', $db->quoteName('#__update_sites_extensions', 'x') . ' ON ' . $db->quoteName('x.update_site_id') . ' = ' . $db->quoteName('s.update_site_id'))
			->where($db->quoteName('x.extension_id') . ' = ' . (int) $extension->extension_id);
		$sites = $db->setQuery($query)->loadObjectList();

		// A package's children are updated through the package
		if (!$sites && !empty($extension->package_id))
		{
			$io->error(sprintf('%s is part of a package: reinstall the package (ID %d) instead, or give --file.', $this->describe($extension), $extension->package_id));

			return false;
		}

		if (!$sites)
		{
			$io->error(sprintf('%s has no update site to get its package from. Give the package with --file or --url.', $this->describe($extension)));

			return false;
		}

		$releases = array();

		foreach ($sites as $site)
		{
			foreach ($this->readUpdates($site->location, $extension) as $release)
			{
				if ($site->extra_query)
				{
					$release['url'] .= (strpos($release['url'], '?') === false ? '?' : '&') . $site->extra_query;
				}

				$releases[] = $release;
			}
		}

		$compatible = array_filter($releases, function ($release)
		{
			return $release['compatible'];
		});

		foreach ($compatible as $release)
		{
			if ($installed !== '' && version_compare($release['version'], $installed, '=='))
			{
				return $release;
			}
		}

		if ($io->getOption('allow-version-change') && $compatible)
		{
			usort($compatible, function ($a, $b)
			{
				return version_compare($b['version'], $a['version']);
			});

			return reset($compatible);
		}

		$versions = array_unique(array_map(function ($release)
		{
			return $release['version'] . ($release['compatible'] ? '' : ' (not for this Joomla version)');
		}, $releases));

		$io->error(sprintf('The update site doesn\'t offer version %s of %s%s. Give the package with --file, or --allow-version-change to install the latest compatible version.',
			$installed, $this->describe($extension), $versions ? ' (it offers ' . implode(', ', $versions) . ')' : ''));

		return false;
	}

	/**
	 * Read the releases of an extension from an update site: an "extension" feed (<updates>), or a "collection" (<extensionset>)
	 * pointing to one.
	 *
	 * @param   string  $location   The update site URL
	 * @param   object  $extension  The extension
	 * @param   integer $depth      Collections followed so far
	 *
	 * @return  array[]  Releases: version, url, checksums, compatible
	 *
	 * @since   3.17.0
	 */
	private function readUpdates($location, $extension, $depth = 0)
	{
		try
		{
			$response = HttpFactory::getHttp()->get($location, array(), 20);
		}
		catch (\Exception $e)
		{
			return array();
		}

		if ((int) $response->code !== 200)
		{
			return array();
		}

		$xml = @simplexml_load_string($response->body);

		if (!$xml)
		{
			return array();
		}

		$releases = array();

		if ($xml->getName() === 'extensionset' && $depth < 2)
		{
			foreach ($xml->extension as $entry)
			{
				if ($this->matches($entry, $extension, true) && (string) $entry['detailsurl'] !== '')
				{
					$releases = array_merge($releases, $this->readUpdates((string) $entry['detailsurl'], $extension, $depth + 1));
				}
			}

			return $releases;
		}

		foreach ($xml->update as $update)
		{
			if (!$this->matches($update, $extension, false) || !isset($update->downloads->downloadurl))
			{
				continue;
			}

			$checksums = array();

			foreach (array('sha256', 'sha384', 'sha512') as $algorithm)
			{
				if (trim((string) $update->$algorithm) !== '')
				{
					$checksums[$algorithm] = strtolower(trim((string) $update->$algorithm));
				}
			}

			$compatible = true;

			if (isset($update->targetplatform))
			{
				$platform   = $update->targetplatform;
				$compatible = (string) $platform['name'] === 'joomla'
					&& preg_match('/^' . str_replace('/', '\/', (string) $platform['version']) . '/', JVERSION) === 1;
			}

			$releases[] = array('version' => trim((string) $update->version), 'url' => trim((string) $update->downloads->downloadurl), 'checksums' => $checksums,
				'compatible' => $compatible);
		}

		return $releases;
	}

	/**
	 * Whether an update (or collection entry) is for this extension: element, type, client and plugin group.
	 *
	 * @param   \SimpleXMLElement  $entry       The <update> or <extension> element
	 * @param   object             $extension   The extension
	 * @param   boolean            $attributes  The values are attributes (collections) rather than child elements
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	private function matches(\SimpleXMLElement $entry, $extension, $attributes)
	{
		$value = function ($name) use ($entry, $attributes)
		{
			return strtolower(trim($attributes ? (string) $entry[$name] : (string) $entry->$name));
		};

		if ($value('element') !== strtolower($extension->element) || $value('type') !== $extension->type)
		{
			return false;
		}

		$client = $value('client');

		if ($client !== '' && (int) ($client === 'administrator' || $client === '1') !== (int) $extension->client_id)
		{
			return false;
		}

		return $extension->type !== 'plugin' || $value('folder') === '' || $value('folder') === strtolower($extension->folder);
	}

	/**
	 * What a package is, from its manifest, as the installer works it out.
	 *
	 * @param   string  $folder  The extracted package
	 *
	 * @return  array|false  type, element, client, folder and version
	 *
	 * @since   3.17.0
	 */
	private function identify($folder)
	{
		$installer = new Installer;
		$installer->setPath('source', $folder);

		if (!$installer->findManifest())
		{
			return false;
		}

		$manifest = $installer->getManifest();
		$type     = strtolower((string) $manifest['type']);
		$client   = strtolower((string) $manifest['client']);

		if ($type === 'language')
		{
			$element = (string) $manifest->tag;
		}
		else
		{
			$adapter = $installer->getAdapter($type, array('manifest' => $manifest, 'route' => 'update'));

			if (!$adapter)
			{
				return false;
			}

			$element = (string) $adapter->getElement();
		}

		return array(
			'type'    => $type,
			'element' => strtolower($element),
			'client'  => $type === 'component' ? 1 : (int) ($client === 'administrator'),
			'folder'  => $type === 'plugin' ? strtolower((string) $manifest['group']) : '',
			'version' => trim((string) $manifest->version),
		);
	}

	/**
	 * The folders whose files the extension's package provides, relative to the site's root (packages: those of their extensions).
	 *
	 * @param   object  $extension  The extension
	 *
	 * @return  string[]
	 *
	 * @since   3.17.0
	 */
	private function getFolders($extension)
	{
		$base    = (int) $extension->client_id === 1 ? 'administrator/' : '';
		$folders = array();

		switch ($extension->type)
		{
			case 'component':
				$folders = array('administrator/components/' . $extension->element, 'components/' . $extension->element);
				break;

			case 'module':
				$folders = array($base . 'modules/' . $extension->element);
				break;

			case 'plugin':
				$folders = array('plugins/' . $extension->folder . '/' . $extension->element);
				break;

			case 'template':
				$folders = array($base . 'templates/' . $extension->element);
				break;

			case 'library':
				$folders = array('libraries/' . $extension->element);
				break;

			case 'package':
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('*')
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('package_id') . ' = ' . (int) $extension->extension_id);

				foreach ($db->setQuery($query)->loadObjectList() as $child)
				{
					$folders = array_merge($folders, $this->getFolders($child));
				}
				break;
		}

		// Its media folder, by Joomla's convention named after the element (a manifest may put media elsewhere)
		if (in_array($extension->type, array('component', 'module', 'plugin', 'template'), true))
		{
			$folders[] = 'media/' . $extension->element;
		}

		return array_values(array_unique(array_filter($folders, function ($folder)
		{
			return is_dir(JPATH_ROOT . '/' . $folder);
		})));
	}

	/**
	 * Files in the folders not written since a moment (the installation writes every file of the package).
	 *
	 * @param   string[]  $folders  Folders relative to the site's root
	 * @param   integer   $since    The moment
	 *
	 * @return  string[]  Paths relative to the site's root
	 *
	 * @since   3.17.0
	 */
	private function findOlderFiles(array $folders, $since)
	{
		clearstatcache();
		$files = array();

		foreach ($folders as $folder)
		{
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(JPATH_ROOT . '/' . $folder, \FilesystemIterator::SKIP_DOTS));

			foreach ($iterator as $file)
			{
				if ($file->isFile() && $file->getMTime() < $since)
				{
					$files[] = $folder . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen(JPATH_ROOT . '/' . $folder) + 1));
				}
			}
		}

		sort($files);

		return $files;
	}
}
