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
 * Installs a new site on SQLite from the command line, with the installer's own code (the "installation" folder), as the web
 * installer would with the same choices.
 *
 * @since  3.17.0
 */
class CoreInstallCommand extends AbstractCommand
{
	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $name = 'core:install';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $description = 'Install a new site on SQLite';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	protected $help = 'Installs Joomla on a new SQLite database, from the files of a Joomla 3.x UTD package which wasn\'t installed yet '
		. '(no configuration.php), using the installer of its "installation" folder. Only the site\'s name, the administrator\'s email '
		. 'address and username are needed; when they aren\'t given, they\'re asked for one by one. The administrator\'s password is '
		. 'generated (16 letters and digits) and shown at the end. --sample-data also installs one of the installer\'s sample data '
		. 'sets (news or blog). The site\'s template is Hammond, with no sample data or the news set, and Finch with the '
		. 'blog set. The database file gets an unguessable name in the "database" '
		. 'folder, which is protected from web access, and the "installation" folder is removed afterwards. Like the web installer, '
		. 'it checks that whoever installs can change the site\'s files, by deleting a file it creates in the "installation" folder. '
		. 'Run it as the user the web server runs PHP as, so that the site can write to the files it creates. Needs PHP 7.4 or newer '
		. 'with the pdo_sqlite extension.';

	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $dryRun = true;

	/**
	 * Before installing there's no database to log in
	 *
	 * @var    boolean
	 * @since  3.17.0
	 */
	protected $logged = false;

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function configure()
	{
		$this->addOption('site-name', null, self::OPTION_REQUIRED, 'The site\'s name');
		$this->addOption('admin-email', null, self::OPTION_REQUIRED, 'The administrator\'s email address');
		$this->addOption('admin-username', null, self::OPTION_REQUIRED, 'The administrator\'s username');
		$this->addOption('sample-data', null, self::OPTION_OPTIONAL, 'Also install sample data: news or blog (without a name, it asks which)');
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
		$io->title('Install Joomla');

		$configuration = JPATH_CONFIGURATION . '/configuration.php';

		if (is_file($configuration) && filesize($configuration) > 10)
		{
			$io->error('Joomla is already installed here (configuration.php exists).');

			return self::REFUSED;
		}

		if (!is_file(JPATH_INSTALLATION . '/sql/mysql/joomla.sql') || !is_file(JPATH_INSTALLATION . '/model/database.php'))
		{
			$io->error('The "installation" folder of the Joomla package is missing, so Joomla can\'t be installed from these files.');

			return self::FAILURE;
		}

		if (!\JDatabaseDriverMysqlonsqlite::isSupported())
		{
			$io->error('Installing on SQLite needs PHP 7.4 or newer with the pdo_sqlite extension (this is PHP ' . PHP_VERSION
				. (extension_loaded('pdo_sqlite') ? '' : ', without pdo_sqlite') . ').');

			return self::FAILURE;
		}

		$this->loadInstaller();

		$missing = array();

		foreach ((new \InstallationModelSetup)->getPhpOptions() as $option)
		{
			if ($option->notice === null && !$option->state)
			{
				$missing[] = strip_tags($option->label);
			}
		}

		if ($missing)
		{
			$io->error('This PHP doesn\'t meet Joomla\'s requirements: ' . implode('; ', $missing) . '.');

			return self::FAILURE;
		}

		$siteName = $this->getValue($io, 'site-name', 'What is the site\'s name?', array($this, 'checkSiteName'));
		$email    = $siteName === null ? null : $this->getValue($io, 'admin-email', 'What is the administrator\'s email address?', array($this, 'checkEmail'));
		$username = $email === null ? null : $this->getValue($io, 'admin-username', 'What username should the administrator log in with?', array($this, 'checkUsername'));

		if ($username === null)
		{
			return self::INVALID;
		}

		// Optional, so only asked for when --sample-data is given without a name
		$sampleData = '';

		if ($io->getOption('sample-data') === true && !$io->isInteractive())
		{
			$io->error('--sample-data needs the name of a set: ' . implode(', ', array_keys($this->getSampleDataSets())) . '.');

			return self::INVALID;
		}

		if ($io->getOption('sample-data') !== null)
		{
			$sampleData = $this->getValue($io, 'sample-data', 'Which sample data should be installed (' . implode(', ', array_keys($this->getSampleDataSets()))
				. ' or none)?', array($this, 'checkSampleData'));

			if ($sampleData === null)
			{
				return self::INVALID;
			}

			$sampleData = strtolower($sampleData) === 'none' ? '' : strtolower($sampleData);
		}

		$sampleSets = $this->getSampleDataSets();

		if (!is_writable(JPATH_CONFIGURATION))
		{
			$io->error(sprintf('The site\'s folder (%s) isn\'t writable, so configuration.php can\'t be written.', JPATH_CONFIGURATION));

			return self::FAILURE;
		}

		// The ownership check writes a file there, and the folder is removed at the end
		if (!is_writable(JPATH_INSTALLATION))
		{
			$io->error(sprintf('The "installation" folder (%s) isn\'t writable for the user running this command.', JPATH_INSTALLATION));

			return self::FAILURE;
		}

		$this->warnAboutOwner($io);

		$database = 'database/joomla-' . bin2hex(random_bytes(8)) . '.sqlite';
		$prefix   = $this->generatePrefix();

		if ($io->isDryRun())
		{
			$io->plan(sprintf('Create the SQLite database %s (table prefix %s) and its tables', $database, $prefix),
				array('action' => 'database', 'database' => $database, 'prefix' => $prefix));
			if ($sampleData !== '')
			{
				$io->plan(sprintf('Install the sample data "%s" (%s)', $sampleData, $sampleSets[$sampleData]['label']),
					array('action' => 'sampleData', 'sampleData' => $sampleData));
			}

			$io->plan(sprintf('Write configuration.php for "%s"', $siteName), array('action' => 'configuration', 'siteName' => $siteName));
			$io->plan(sprintf('Create the Super User %s (%s) with a generated password', $username, $email),
				array('action' => 'user', 'username' => $username, 'email' => $email));
			$io->plan('Remove the "installation" folder', array('action' => 'remove', 'folder' => 'installation'));

			return self::SUCCESS;
		}

		$password = $this->generatePassword();
		$options  = array(
			'language'             => 'en-GB',
			'helpurl'              => 'https://help.joomla.org/proxy?keyref=Help{major}{minor}:{keyref}&lang={langcode}',
			'site_name'            => $siteName,
			'site_offline'         => 0,
			'admin_email'          => $email,
			'admin_user'           => $username,
			'admin_password_plain' => $password,
			'db_type'              => 'mysqlonsqlite',
			'db_host'              => '',
			'db_user'              => '',
			'db_pass_plain'        => '',
			'db_name'              => $database,
			'db_prefix'            => $prefix,
			'db_select'            => true,
			'db_old'               => 'remove',
			'sample_file'          => $sampleData === '' ? '' : $sampleSets[$sampleData]['file'],
		);

		$result = $this->install($io, $options);

		if ($result !== self::SUCCESS)
		{
			return $result;
		}

		$removed = $this->removeInstallationFolder();

		if (!$removed)
		{
			$io->warning('The "installation" folder couldn\'t be removed. Remove it yourself: until then the site shows a notice and '
				. 'its backend can\'t be used.');
		}

		$io->setData('siteName', $siteName);
		$io->setData('adminUsername', $username);
		$io->setData('adminEmail', $email);
		$io->setData('adminPassword', $password);
		$io->setData('database', $database);
		$io->setData('prefix', $prefix);
		$io->setData('sampleData', $sampleData === '' ? null : $sampleData);
		$io->setData('installationFolderRemoved', $removed);

		$io->success(sprintf('Joomla is installed: "%s", on the SQLite database %s.', $siteName, $database));

		// In JSON, the data above has them
		if (!$io->isJson())
		{
			$io->definitionList(array(
				'Administrator' => $username,
				'Email'         => $email,
				'Password'      => $password,
			));
		}

		$io->text('Keep the password somewhere safe: it isn\'t stored anywhere readable and isn\'t shown again. Log in to the '
			. 'administrator at /administrator/ on the site, and change it there if you like.');

		return self::SUCCESS;
	}

	/**
	 * Get a value from its option or, in a terminal, by asking for it until it's valid.
	 *
	 * @param   CommandIO  $io        The input values and the output
	 * @param   string     $name      The option
	 * @param   string     $question  The question
	 * @param   callable   $check     Returns an error message, or null when the value is fine
	 *
	 * @return  string|null  The value, or null when there's none
	 *
	 * @since   3.17.0
	 */
	protected function getValue(CommandIO $io, $name, $question, callable $check)
	{
		$value = $io->getOption($name);

		if ($value !== null && $value !== true)
		{
			$value = trim((string) $value);
			$error = call_user_func($check, $value);

			if ($error === null)
			{
				return $value;
			}

			$io->error(sprintf('--%s: %s', $name, $error));

			return null;
		}

		if (!$io->isInteractive())
		{
			$io->error(sprintf('--%s is needed (when it isn\'t given, it\'s only asked for in a terminal).', $name));

			return null;
		}

		for ($attempt = 0; $attempt < 5; $attempt++)
		{
			$value = trim((string) $io->ask($question));
			$error = call_user_func($check, $value);

			if ($error === null)
			{
				return $value;
			}

			$io->warning($error);
		}

		$io->error('No valid answer was given.');

		return null;
	}

	/**
	 * @param   string  $value  The site's name
	 *
	 * @return  string|null  What's wrong with it
	 *
	 * @since   3.17.0
	 */
	public function checkSiteName($value)
	{
		if ($value === '')
		{
			return 'The site needs a name.';
		}

		if (preg_match('/[\x00-\x1F\x7F]/', $value))
		{
			return 'The site\'s name can\'t contain control characters.';
		}

		return null;
	}

	/**
	 * @param   string  $value  An email address
	 *
	 * @return  string|null  What's wrong with it
	 *
	 * @since   3.17.0
	 */
	public function checkEmail($value)
	{
		return \JMailHelper::isEmailAddress($value) ? null : sprintf('"%s" isn\'t a valid email address.', $value);
	}

	/**
	 * The web installer's rules for the username (InstallationFormRuleUsername, with the form's size of 30)
	 *
	 * @param   string  $value  A username
	 *
	 * @return  string|null  What's wrong with it
	 *
	 * @since   3.17.0
	 */
	public function checkUsername($value)
	{
		$rule = new \InstallationFormRuleUsername;

		if ($rule->test(new \SimpleXMLElement('<field name="admin_user" size="30" />'), $value))
		{
			return null;
		}

		return 'A username needs 2 to 30 characters, without spaces at either end and without < > " \' % ; ( ) & \\ or "../".';
	}

	/**
	 * @param   string  $value  A sample data set's name, or "none"
	 *
	 * @return  string|null  What's wrong with it
	 *
	 * @since   3.17.0
	 */
	public function checkSampleData($value)
	{
		$sets = $this->getSampleDataSets();

		if (strtolower($value) === 'none' || isset($sets[strtolower($value)]))
		{
			return null;
		}

		return sprintf('"%s" isn\'t a sample data set; choose %s or none.', $value, implode(', ', array_keys($sets)));
	}

	/**
	 * The installer's sample data sets (installation/sql/mysql/sample_*.sql), by the name used here: the part after "sample_".
	 *
	 * @return  array  name => array(file, label)
	 *
	 * @since   3.17.0
	 */
	protected function getSampleDataSets()
	{
		$sets = array();

		foreach (glob(JPATH_INSTALLATION . '/sql/mysql/sample_*.sql') as $file)
		{
			$file = basename($file);
			$name = substr($file, 7, -4);
			$key  = 'INSTL_' . strtoupper(substr($file, 0, -4)) . '_SET';

			$sets[$name] = array(
				'file'  => $file,
				'label' => Factory::getLanguage()->hasKey($key) ? \JText::_($key) : $file,
			);
		}

		ksort($sets);

		// The news set first, as in the installer
		if (isset($sets['news']))
		{
			$sets = array('news' => $sets['news']) + $sets;
		}

		return $sets;
	}

	/**
	 * Load the installer's code from the "installation" folder.
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function loadInstaller()
	{
		// Defined by the web entry points (installation/index.php), used by the installer's PHP checks
		if (!defined('JOOMLA_MINIMUM_PHP'))
		{
			define('JOOMLA_MINIMUM_PHP', '7.1.0');
		}

		\JLoader::registerPrefix('Installation', JPATH_INSTALLATION);
		\JLoader::register('InstallationFormRuleUsername', JPATH_INSTALLATION . '/form/rule/username.php');

		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		Factory::getLanguage()->load('joomla', JPATH_INSTALLATION, 'en-GB', true);
	}

	/**
	 * Run the installer's steps: database, tables and data, configuration.php and the Super User.
	 *
	 * @param   CommandIO  $io       The input values and the output
	 * @param   array      $options  The installer's options
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	protected function install(CommandIO $io, array $options)
	{
		$application = Factory::$application;
		$installer   = new CoreInstallApplication($application);
		$directory   = getcwd();
		$session     = Factory::getSession();

		// The installer's code works as it does on the web: through the installation application, from its folder
		Factory::$application = $installer;
		chdir(JPATH_INSTALLATION);

		try
		{
			$database = new \InstallationModelDatabase;

			$io->text('Creating the database ...');

			/*
			 * Like on the web, the installer first asks whoever installs to delete a file it writes in the "installation" folder,
			 * to show that they can change the site's files (SQLite needs no credentials). Deleting it here is that proof.
			 */
			if (!$database->createDatabase($options))
			{
				$file = (string) $session->get('remoteDbFile', '');

				if ($file === '' || !$session->get('remoteDbFileWrittenByJoomla', false) || !is_file(JPATH_INSTALLATION . '/' . $file)
					|| !unlink(JPATH_INSTALLATION . '/' . $file))
				{
					$this->reportErrors($io, $installer, 'The database couldn\'t be created.');

					return self::FAILURE;
				}

				$installer->getMessageQueue(true);

				if (!$database->createDatabase($options))
				{
					$this->reportErrors($io, $installer, 'The database couldn\'t be created.');

					return self::FAILURE;
				}
			}

			$options = (array) $session->get('setup.options', array());

			$io->text('Creating the tables and data ...');

			if (!$database->installCmsData($options))
			{
				$this->reportErrors($io, $installer, 'The tables couldn\'t be created.');

				return self::FAILURE;
			}

			if ($options['sample_file'] !== '')
			{
				$io->text('Installing the sample data ...');

				if (!$database->installSampleData($options))
				{
					$this->reportErrors($io, $installer, 'The sample data couldn\'t be installed.');

					return self::FAILURE;
				}
			}

			$io->text('Writing the configuration and creating the Super User ...');

			if (!(new \InstallationModelConfiguration)->setup($options) || !is_file(JPATH_CONFIGURATION . '/configuration.php'))
			{
				$this->reportErrors($io, $installer, 'The configuration couldn\'t be written.');

				return self::FAILURE;
			}
		}
		finally
		{
			chdir($directory);
			Factory::$application = $application;

			// The installer keeps the passwords in the setup options
			$session->set('setup.options', null);
		}

		return self::SUCCESS;
	}

	/**
	 * Show the installer's error messages.
	 *
	 * @param   CommandIO               $io         The input values and the output
	 * @param   CoreInstallApplication  $installer  The application the installer reported to
	 * @param   string                  $fallback   The message when the installer gave none
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function reportErrors(CommandIO $io, CoreInstallApplication $installer, $fallback)
	{
		$messages = array();

		foreach ($installer->getMessageQueue(true) as $message)
		{
			$text = trim(html_entity_decode(strip_tags((string) $message['message']), ENT_QUOTES, 'UTF-8'));

			if ($text !== '' && !in_array($text, $messages, true))
			{
				$messages[] = $text;
			}
		}

		$io->error($messages ? implode("\n", $messages) : $fallback);
	}

	/**
	 * Remove the "installation" folder, with the web installer's checks (installation/controller/removefolder.php).
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	protected function removeInstallationFolder()
	{
		$path = JPATH_INSTALLATION;
		$real = realpath($path);
		$root = realpath(JPATH_ROOT);

		// Only the folder directly in the site's root, never a link to somewhere else
		if (is_link($path) || $real === false || $root === false || !is_dir($real) || dirname($real) !== $root)
		{
			return false;
		}

		if (function_exists('opcache_invalidate'))
		{
			foreach (\JFolder::files($real, '\.php$', true, true) as $file)
			{
				@opcache_invalidate($file, true);
			}
		}

		try
		{
			$removed = \JFolder::delete($real) && (!file_exists(JPATH_ROOT . '/joomla.xml') || \JFile::delete(JPATH_ROOT . '/joomla.xml'));

			if ($removed && !file_exists(JPATH_ROOT . '/robots.txt') && file_exists(JPATH_ROOT . '/robots.txt.dist'))
			{
				$removed = \JFile::move(JPATH_ROOT . '/robots.txt.dist', JPATH_ROOT . '/robots.txt');
			}
		}
		catch (\Exception $e)
		{
			$removed = false;
		}

		return $removed;
	}

	/**
	 * Files created here belong to the user running the command; the web server must be able to write the database and the
	 * folders Joomla writes to.
	 *
	 * @param   CommandIO  $io  The input values and the output
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	protected function warnAboutOwner(CommandIO $io)
	{
		if (!function_exists('posix_geteuid'))
		{
			return;
		}

		$user  = posix_geteuid();
		$owner = @fileowner(JPATH_ROOT);

		if ($user === 0)
		{
			$io->warning('This runs as root, so the database and configuration.php will belong to root, and the web server probably '
				. 'can\'t write to them. Run it as the web server\'s user instead (e.g. sudo -u www-data php cli/joomla.php core:install), '
				. 'or change their owner afterwards.');
		}
		elseif ($owner !== false && $owner !== $user)
		{
			$io->warning('The site\'s files belong to another user than the one running this, so the web server may not be able to '
				. 'write to the database and configuration.php created now. Check their owner afterwards.');
		}
	}

	/**
	 * A table prefix like the web installer's: a letter, four letters or digits, and an underscore.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function generatePrefix()
	{
		$letters = 'abcdefghijklmnopqrstuvwxyz';
		$symbols = $letters . '0123456789';
		$prefix  = $letters[random_int(0, 25)];

		for ($i = 0; $i < 4; $i++)
		{
			$prefix .= $symbols[random_int(0, 35)];
		}

		return $prefix . '_';
	}

	/**
	 * A 16 character password of letters (both cases) and digits, with at least one of each.
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function generatePassword()
	{
		$sets = array('abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789');
		$all  = implode('', $sets);

		do
		{
			$password = '';

			for ($i = 0; $i < 16; $i++)
			{
				$password .= $all[random_int(0, strlen($all) - 1)];
			}
		}
		while (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password));

		return $password;
	}
}
