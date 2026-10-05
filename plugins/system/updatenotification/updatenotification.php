<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.updatenotification
 *
 * @copyright   (C) 2015 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

// Uncomment the following line to enable debug mode (update notification email sent every single time)
// define('PLG_SYSTEM_UPDATENOTIFICATION_DEBUG', 1);

/**
 * Joomla! Update Notification plugin
 *
 * Sends out an email to all Super Users or a predefined email address when a new Joomla! version is available.
 *
 * This plugin is a direct adaptation of the corresponding plugin in Akeeba Ltd's Admin Tools. The author has
 * consented to relicensing their plugin's code under GPLv2 or later (the original version was licensed under
 * GPLv3 or later) to allow its inclusion in the Joomla! CMS.
 *
 * @since  3.5
 */
class PlgSystemUpdatenotification extends JPlugin
{
	/**
	 * Load plugin language files automatically
	 *
	 * @var    boolean
	 * @since  3.6.3
	 */
	protected $autoloadLanguage = true;

	/**
	 * The update check and notification email code is triggered after the page has fully rendered.
	 *
	 * @return  void
	 *
	 * @since   3.5
	 */
	public function onAfterRender()
	{
		// Get the timeout for Joomla! updates, as configured in com_installer's component parameters
		$component = JComponentHelper::getComponent('com_installer');

		/** @var \Joomla\Registry\Registry $params */
		$params        = $component->params;
		$cache_timeout = (int) $params->get('cachetimeout', 6);
		$cache_timeout = 3600 * $cache_timeout;

		$debug = defined('PLG_SYSTEM_UPDATENOTIFICATION_DEBUG');

		// This is the extension ID for Joomla! itself
		$eid = 700;

		// Check for updates every "Updates Caching" interval of the Installer options
		$now = time();

		$update  = null;
		$checked = $this->claimRun('lastrun', function ($last) use ($now, $cache_timeout, $debug) {
			return $debug || abs($now - $last) >= $cache_timeout;
		});

		if ($checked)
		{
			JUpdater::getInstance()->findUpdates(array($eid), $cache_timeout);
		}

		// The version the last check found, kept in the plugin's options, so most page loads decide without the database
		$pending = $this->params->get('pendingversion', null);

		if ($checked || $pending === null)
		{
			$update  = $this->getPendingUpdate($eid);
			$pending = $update ? (string) $update->version : '';
			$this->setOption('pendingversion', $pending);
		}

		/*
		 * Email once a day while an update is pending. From the chosen hour on (in the site's time zone), the first visit
		 * which finds an update pending sends the email; then the next one waits for the chosen hour of the next day. An update
		 * found after the chosen hour is reported straight away, on the first visit after the check which found it.
		 */
		$slot  = $this->getNotificationTime($now);
		$isDue = function ($last) use ($now, $slot, $debug) {
			return $debug || ($now >= $slot && $last < $slot);
		};

		if ($pending === '' || !$isDue((int) $this->params->get('lastnotified', 0)))
		{
			return;
		}

		// Confirm it, e.g. the site may have been updated since the check
		$update = $update ?: $this->getPendingUpdate($eid);

		if (!$update)
		{
			$this->setOption('pendingversion', '');

			return;
		}

		// Lock until the chosen hour of the next day; simultaneous visits can't both get here
		if (!$this->claimRun('lastnotified', $isDue))
		{
			return;
		}

		// If we're here, we have updates. First, get a link to the Joomla! Update component.
		$baseURL  = JUri::base();
		$baseURL  = rtrim($baseURL, '/');
		$baseURL .= (substr($baseURL, -13) !== 'administrator') ? '/administrator/' : '/';
		$baseURL .= 'index.php?option=com_joomlaupdate';
		$uri      = new JUri($baseURL);

		/**
		 * Some third party security solutions require a secret query parameter to allow log in to the administrator
		 * backend of the site. The link generated above will be invalid and could probably block the user out of their
		 * site, confusing them (they can't understand the third party security solution is not part of Joomla! proper).
		 * So, we're calling the onBuildAdministratorLoginURL system plugin event to let these third party solutions
		 * add any necessary secret query parameters to the URL. The plugins are supposed to have a method with the
		 * signature:
		 *
		 * public function onBuildAdministratorLoginURL(JUri &$uri);
		 *
		 * The plugins should modify the $uri object directly and return null.
		 */

		JEventDispatcher::getInstance()->trigger('onBuildAdministratorLoginURL', array(&$uri));

		// Let's find out the email addresses to notify
		$superUsers    = array();
		$specificEmail = $this->params->get('email', '');

		if (!empty($specificEmail))
		{
			$superUsers = $this->getSuperUsers($specificEmail);
		}

		if (empty($superUsers))
		{
			$superUsers = $this->getSuperUsers();
		}

		if (empty($superUsers))
		{
			return;
		}

		/*
		 * Load the appropriate language. We try to load English (UK), the current user's language and the forced
		 * language preference, in this order. This ensures that we'll never end up with untranslated strings in the
		 * update email which would make Joomla! seem bad. So, please, if you don't fully understand what the
		 * following code does DO NOT TOUCH IT. It makes the difference between a hobbyist CMS and a professional
		 * solution!
		 */
		$jLanguage = JFactory::getLanguage();
		$jLanguage->load('plg_system_updatenotification', JPATH_ADMINISTRATOR, 'en-GB', true, true);
		$jLanguage->load('plg_system_updatenotification', JPATH_ADMINISTRATOR, null, true, false);

		// Then try loading the preferred (forced) language
		$forcedLanguage = $this->params->get('language_override', '');

		if (!empty($forcedLanguage))
		{
			$jLanguage->load('plg_system_updatenotification', JPATH_ADMINISTRATOR, $forcedLanguage, true, false);
		}

		// Set up the email subject and body

		$email_subject = JText::_('PLG_SYSTEM_UPDATENOTIFICATION_EMAIL_SUBJECT');
		$email_body    = JText::_('PLG_SYSTEM_UPDATENOTIFICATION_EMAIL_BODY');

		// Replace merge codes with their values
		$newVersion = $update->version;

		$jVersion       = new JVersion;
		$currentVersion = $jVersion->getShortVersion();

		$jConfig  = JFactory::getConfig();
		$sitename = $jConfig->get('sitename');
		$mailFrom = $jConfig->get('mailfrom');
		$fromName = $jConfig->get('fromname');

		$substitutions = array(
			'[NEWVERSION]'  => $newVersion,
			'[CURVERSION]'  => $currentVersion,
			'[SITENAME]'    => $sitename,
			'[URL]'         => JUri::base(),
			'[LINK]'        => $uri->toString(),
			'[RELEASENEWS]' => 'https://github.com/joomlaworks/joomla-3.x',
			'\\n'           => "\n",
		);

		foreach ($substitutions as $k => $v)
		{
			$email_subject = str_replace($k, $v, $email_subject);
			$email_body    = str_replace($k, $v, $email_body);
		}

		// Send the emails to the Super Users
		foreach ($superUsers as $superUser)
		{
			$mailer = JFactory::getMailer();
			$mailer->setSender(array($mailFrom, $fromName));
			$mailer->addRecipient($superUser->email);
			$mailer->setSubject($email_subject);
			$mailer->setBody($email_body);
			$mailer->Send();
		}
	}

	/**
	 * Returns the Super Users email information. If you provide a comma separated $email list
	 * we will check that these emails do belong to Super Users and that they have not blocked
	 * system emails.
	 *
	 * @param   null|string  $email  A list of Super Users to email
	 *
	 * @return  array  The list of Super User emails
	 *
	 * @since   3.5
	 */
	private function getSuperUsers($email = null)
	{
		// Get a reference to the database object
		$db = JFactory::getDbo();

		// Convert the email list to an array
		if (!empty($email))
		{
			$temp   = explode(',', $email);
			$emails = array();

			foreach ($temp as $entry)
			{
				$entry    = trim($entry);
				$emails[] = $db->q($entry);
			}

			$emails = array_unique($emails);
		}
		else
		{
			$emails = array();
		}

		// Get a list of groups which have Super User privileges
		$ret = array();

		try
		{
			$rootId    = JTable::getInstance('Asset', 'JTable')->getRootId();
			$rules     = JAccess::getAssetRules($rootId)->getData();
			$rawGroups = $rules['core.admin']->getData();
			$groups    = array();

			if (empty($rawGroups))
			{
				return $ret;
			}

			foreach ($rawGroups as $g => $enabled)
			{
				if ($enabled)
				{
					$groups[] = $db->q($g);
				}
			}

			if (empty($groups))
			{
				return $ret;
			}
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		// Get the user IDs of users belonging to the SA groups
		try
		{
			$query = $db->getQuery(true)
						->select($db->qn('user_id'))
						->from($db->qn('#__user_usergroup_map'))
						->where($db->qn('group_id') . ' IN(' . implode(',', $groups) . ')');
			$db->setQuery($query);
			$rawUserIDs = $db->loadColumn(0);

			if (empty($rawUserIDs))
			{
				return $ret;
			}

			$userIDs = array();

			foreach ($rawUserIDs as $id)
			{
				$userIDs[] = $db->q($id);
			}
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		// Get the user information for the Super Administrator users
		try
		{
			$query = $db->getQuery(true)
						->select(
							array(
								$db->qn('id'),
								$db->qn('username'),
								$db->qn('email'),
							)
						)->from($db->qn('#__users'))
						->where($db->qn('id') . ' IN(' . implode(',', $userIDs) . ')')
						->where($db->qn('block') . ' = 0')
						->where($db->qn('sendEmail') . ' = ' . $db->q('1'));

			if (!empty($emails))
			{
				$query->where('LOWER(' . $db->qn('email') . ') IN(' . implode(',', array_map('strtolower', $emails)) . ')');
			}

			$db->setQuery($query);
			$ret = $db->loadObjectList();
		}
		catch (Exception $exc)
		{
			return $ret;
		}

		return $ret;
	}

	/**
	 * Get the pending Joomla update, from the update information stored by the last update check.
	 *
	 * @param   integer  $eid  The extension ID of Joomla itself
	 *
	 * @return  object|null  The update, null if there is no newer version
	 *
	 * @since   3.17.0
	 */
	private function getPendingUpdate($eid)
	{
		// Unfortunately Joomla! MVC doesn't allow us to autoload classes
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models', 'InstallerModel');

		// Get the update model and retrieve the Joomla! core updates
		$model = JModelLegacy::getInstance('Update', 'InstallerModel');
		$model->setState('filter.extension_id', $eid);
		$updates = $model->getItems();

		if (empty($updates))
		{
			return null;
		}

		$update = array_pop($updates);

		// If it's the same or less than the installed version we have no updates to notify about
		return version_compare($update->version, JVERSION, 'le') ? null : $update;
	}

	/**
	 * Store a value in the plugin's options, when it changed.
	 *
	 * @param   string  $key    The option
	 * @param   mixed   $value  The value
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	private function setOption($key, $value)
	{
		if ($this->params->get($key, null) !== $value)
		{
			$this->claimRun($key, function () {
				return true;
			}, $value);
		}
	}

	/**
	 * Today's email time, as a timestamp: the "Email Time" hour in the time zone of the Global Configuration.
	 *
	 * @param   integer  $now  The current timestamp
	 *
	 * @return  integer
	 *
	 * @since   3.17.0
	 */
	private function getNotificationTime($now)
	{
		$hour = min(23, max(0, (int) $this->params->get('notification_hour', 10)));

		try
		{
			$timezone = new DateTimeZone((string) JFactory::getConfig()->get('offset', 'UTC'));
		}
		catch (Exception $e)
		{
			$timezone = new DateTimeZone('UTC');
		}

		$time = new DateTime('@' . $now);
		$time->setTimezone($timezone);
		$time->setTime($hour, 0, 0);

		return $time->getTimestamp();
	}

	/**
	 * Record that a task runs now, if it's due. The time is read and written while the table is locked, so simultaneous
	 * requests can't both run it, and only this value is written, so the other values stored in the plugin's options
	 * aren't overwritten with older ones. setOption() uses it to store other values the same way.
	 *
	 * @param   string    $key    The option holding the task's last run time, e.g. "lastrun"
	 * @param   callable  $isDue  Gets the last run time and tells whether the task is due
	 * @param   mixed     $value  The value to store instead of the current time
	 *
	 * @return  boolean  True if the task should run now
	 *
	 * @since   3.17.0
	 */
	private function claimRun($key, $isDue, $value = null)
	{
		$stored = $value === null ? time() : $value;

		// Most requests stop here, without touching the database
		if (!$isDue((int) $this->params->get($key, 0)))
		{
			return false;
		}

		$db = JFactory::getDbo();

		$where = array(
			$db->qn('type') . ' = ' . $db->q('plugin'),
			$db->qn('folder') . ' = ' . $db->q('system'),
			$db->qn('element') . ' = ' . $db->q('updatenotification'),
		);

		try
		{
			// Lock the tables to prevent multiple plugin executions causing a race condition
			$db->lockTable('#__extensions');
		}
		catch (Exception $e)
		{
			// If we can't lock the tables it's too risky to continue execution
			return false;
		}

		try
		{
			$query = $db->getQuery(true)
				->select($db->qn('params'))
				->from($db->qn('#__extensions'))
				->where($where);
			$params = new Registry((string) $db->setQuery($query)->loadResult());
			$claimed = $isDue((int) $params->get($key, 0));

			if ($claimed)
			{
				$params->set($key, $stored);

				$query = $db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('params') . ' = ' . $db->q($params->toString('JSON')))
					->where($where);
				$claimed = (bool) $db->setQuery($query)->execute();
			}
		}
		catch (Exception $exc)
		{
			$claimed = false;
		}

		try
		{
			// Unlock the tables after writing
			$db->unlockTables();
		}
		catch (Exception $e)
		{
			// If we can't unlock the tables assume we have somehow failed
			return false;
		}

		if ($claimed)
		{
			$this->params->set($key, $stored);
			$this->clearCacheGroups(array('com_plugins'), array(0, 1));
		}

		return $claimed;
	}

	/**
	 * Clears cache groups. We use it to clear the plugins cache after we update the last run timestamp.
	 *
	 * @param   array  $clearGroups   The cache groups to clean
	 * @param   array  $cacheClients  The cache clients (site, admin) to clean
	 *
	 * @return  void
	 *
	 * @since   3.5
	 */
	private function clearCacheGroups(array $clearGroups, array $cacheClients = array(0, 1))
	{
		$conf = JFactory::getConfig();

		foreach ($clearGroups as $group)
		{
			foreach ($cacheClients as $client_id)
			{
				try
				{
					$options = array(
						'defaultgroup' => $group,
						'cachebase'    => $client_id ? JPATH_ADMINISTRATOR . '/cache' :
							$conf->get('cache_path', JPATH_SITE . '/cache')
					);

					$cache = JCache::getInstance('callback', $options);
					$cache->clean();
				}
				catch (Exception $e)
				{
					// Ignore it
				}
			}
		}
	}
}
