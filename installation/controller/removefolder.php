<?php
/**
 * @package     Joomla.Installation
 * @subpackage  Controller
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

/**
 * Controller class to remove the installation folder for the Joomla Installer.
 *
 * @since  3.1
 */
class InstallationControllerRemovefolder extends JControllerBase
{
	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function execute()
	{
		// Get the application.
		/** @var InstallationApplicationWeb $app */
		$app = $this->getApplication();

		// Only a POST with the form token. Nothing in the request chooses what's removed: the paths below are fixed.
		if (strtoupper($app->input->getMethod()) !== 'POST' || !JSession::checkToken())
		{
			$this->sendJsonResponse(new Exception(JText::_('JINVALID_TOKEN'), 403));
		}

		// Only once Joomla is installed: before that, removing the folder would break an installation in progress
		$configuration = JPATH_CONFIGURATION . '/configuration.php';

		if (!is_file($configuration) || filesize($configuration) <= 10)
		{
			$this->sendJsonResponse(new Exception($this->text('INSTL_COMPLETE_ERROR_NOT_INSTALLED', 'Joomla! isn\'t installed yet, so the "%s" folder can\'t be removed.'), 403));
		}

		$path = JPATH_INSTALLATION;
		$name = basename($path);

		// Check whether the folder still exists.
		if (!file_exists($path))
		{
			$this->sendJsonResponse(new Exception(JText::sprintf('INSTL_COMPLETE_ERROR_FOLDER_ALREADY_REMOVED', $name), 500));
		}

		// Only the folder of this installer, directly in the site's root, and never a link to somewhere else
		$real = realpath($path);
		$root = realpath(JPATH_ROOT);

		if (is_link($path) || $real === false || $root === false || !is_dir($real) || dirname($real) !== $root
			|| strpos(realpath(__FILE__), $real . DIRECTORY_SEPARATOR) !== 0)
		{
			$this->sendJsonResponse(new Exception(JText::sprintf('INSTL_COMPLETE_ERROR_FOLDER_DELETE', $name), 500));
		}

		// With opcache.validate_timestamps off, PHP could otherwise keep running the deleted scripts from its cache
		if (function_exists('opcache_invalidate'))
		{
			foreach (JFolder::files($real, '\.php$', true, true) as $file)
			{
				@opcache_invalidate($file, true);
			}
		}

		/*
		 * Try to delete the folder.
		 * We use output buffering so that any error message echoed JFolder::delete
		 * doesn't land in our JSON output.
		 */
		ob_start();

		// The framework's File::delete() throws instead of returning false
		try
		{
			$return = JFolder::delete($real) && (!file_exists(JPATH_ROOT . '/joomla.xml') || JFile::delete(JPATH_ROOT . '/joomla.xml'));

			// Rename the robots.txt.dist file if robots.txt doesn't exist
			if ($return && !file_exists(JPATH_ROOT . '/robots.txt') && file_exists(JPATH_ROOT . '/robots.txt.dist'))
			{
				$return = JFile::move(JPATH_ROOT . '/robots.txt.dist', JPATH_ROOT . '/robots.txt');
			}
		}
		catch (Exception $e)
		{
			$return = false;
		}

		ob_end_clean();

		// If an error was encountered return an error.
		if (!$return)
		{
			$this->sendJsonResponse(new Exception(JText::sprintf('INSTL_COMPLETE_ERROR_FOLDER_DELETE', $name), 500));
		}

		// Create a response body.
		$r = new stdClass;
		$r->text = JText::sprintf('INSTL_COMPLETE_FOLDER_REMOVED', $name);

		/*
		 * Send the response.
		 * This is a hack since by now, the rest of the folder is deleted and we can't make a new request
		 */
		$this->sendJsonResponse($r);
	}

	/**
	 * A translated text with a "%s" for the folder name, in English when the language pack doesn't have it yet.
	 *
	 * @param   string  $key      The language key
	 * @param   string  $english  The English text
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	protected function text($key, $english)
	{
		return sprintf(JFactory::getLanguage()->hasKey($key) ? JText::_($key) : $english, basename(JPATH_INSTALLATION));
	}

	/**
	 * Method to send a JSON response. The data parameter
	 * can be an Exception object for when an error has occurred or
	 * a stdClass for a good response.
	 *
	 * @param   mixed  $response  stdClass on success, Exception on failure.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function sendJsonResponse($response)
	{
		// Check if we need to send an error code.
		if ($response instanceof Exception)
		{
			// Send the appropriate error code response.
			$app = $this->getApplication();
			$app->setHeader('status', $response->getCode());
			$app->setHeader('Content-Type', 'application/json; charset=utf-8');
			$app->sendHeaders();
		}

		// Send the JSON response.
		JLoader::register('InstallationResponseJson', __FILE__);

		echo json_encode(new InstallationResponseJson($response));

		// Close the application.
		exit;
	}
}

/**
 * JSON Response class for the Joomla Installer.
 *
 * @since  3.1
 */
#[\AllowDynamicProperties]
class InstallationResponseJson
{
	/**
	 * Constructor for the JSON response
	 *
	 * @param   mixed  $data  Exception if there is an error, otherwise, the session data
	 *
	 * @since   3.1
	 */
	public function __construct($data)
	{
		// The old token is invalid so send a new one.
		$this->token = JSession::getFormToken(true);

		// Get the language and send it's tag along.
		$this->lang = JFactory::getLanguage()->getTag();

		// Get the message queue
		$messages = JFactory::getApplication()->getMessageQueue();

		// Build the sorted message list.
		if (is_array($messages) && count($messages))
		{
			foreach ($messages as $msg)
			{
				if (isset($msg['type'], $msg['message']))
				{
					$lists[$msg['type']][] = $msg['message'];
				}
			}
		}

		// If messages exist add them to the output.
		if (isset($lists) && is_array($lists))
		{
			$this->messages = $lists;
		}

		// Check if we are dealing with an error.
		if ($data instanceof Exception)
		{
			// Prepare the error response.
			$this->error   = true;
			$this->header  = JText::_('INSTL_HEADER_ERROR');
			$this->message = $data->getMessage();
		}
		else
		{
			// Prepare the response data.
			$this->error = false;
			$this->data  = $data;
		}
	}
}
