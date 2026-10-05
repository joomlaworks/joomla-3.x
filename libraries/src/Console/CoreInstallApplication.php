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
 * Stands in for the installation application while core:install runs the installer's models: it collects their messages
 * (the first database step's request to delete a file is expected, and isn't shown) and offers the methods they call on the
 * application. Anything else goes to the console application.
 *
 * @since  3.17.0
 */
class CoreInstallApplication
{
	/**
	 * @var    object
	 * @since  3.17.0
	 */
	protected $application;

	/**
	 * @var    array
	 * @since  3.17.0
	 */
	protected $messages = array();

	/**
	 * @param   object  $application  The console application
	 *
	 * @since   3.17.0
	 */
	public function __construct($application)
	{
		$this->application = $application;
	}

	/**
	 * @param   string  $message  The message
	 * @param   string  $type     Its type
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function enqueueMessage($message, $type = 'message')
	{
		$this->messages[] = array('message' => $message, 'type' => strtolower((string) $type));
	}

	/**
	 * @param   boolean  $clear  Clear the queue
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	public function getMessageQueue($clear = false)
	{
		$messages = $this->messages;

		if ($clear)
		{
			$this->messages = array();
		}

		return $messages;
	}

	/**
	 * The installed languages, as InstallationApplicationWeb::getLocaliseAdmin() returns them.
	 *
	 * @param   mixed  $db  A database connection, to read them from #__extensions, or false for the language folders
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	public function getLocaliseAdmin($db = false)
	{
		$languages = array('site' => array(), 'admin' => array());

		if ($db)
		{
			foreach (\JLanguageHelper::getInstalledLanguages() as $clientId => $installed)
			{
				foreach ($installed as $language)
				{
					$languages[$clientId === 0 ? 'site' : 'admin'][] = $language->element;
				}
			}

			return $languages;
		}

		$languages['site']  = \JFolder::folders(\JLanguageHelper::getLanguagePath(JPATH_SITE));
		$languages['admin'] = \JFolder::folders(\JLanguageHelper::getLanguagePath(JPATH_ADMINISTRATOR));

		return $languages;
	}

	/**
	 * The installer's localise.xml settings; there are none to force here.
	 *
	 * @return  array
	 *
	 * @since   3.17.0
	 */
	public function getLocalise()
	{
		return array('language' => 'en-GB', 'debug' => 0, 'sampledata' => '');
	}

	/**
	 * @param   string  $name       A method of the console application
	 * @param   array   $arguments  Its arguments
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function __call($name, array $arguments)
	{
		return call_user_func_array(array($this->application, $name), $arguments);
	}

	/**
	 * @param   string  $name  A property of the console application, e.g. input
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public function __get($name)
	{
		return $this->application->$name;
	}
}
