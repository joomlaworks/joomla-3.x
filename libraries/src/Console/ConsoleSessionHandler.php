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
 * Session handler of the command line interface: the session lives in memory only and never reaches the session storage.
 *
 * @since  3.17.0
 */
class ConsoleSessionHandler implements \JSessionHandlerInterface
{
	/**
	 * @var    boolean
	 * @since  3.17.0
	 */
	private $started = false;

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	private $id = '';

	/**
	 * @var    string
	 * @since  3.17.0
	 */
	private $name = 'cli';

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function start()
	{
		if ($this->id === '')
		{
			$this->id = bin2hex(random_bytes(16));
		}

		$this->started = true;

		return true;
	}

	/**
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function isStarted()
	{
		return $this->started;
	}

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param   string  $id  The session ID
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function setId($id)
	{
		$this->id = (string) $id;
	}

	/**
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param   string  $name  The session name
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function setName($name)
	{
		$this->name = (string) $name;
	}

	/**
	 * @param   boolean  $destroy   Unused
	 * @param   integer  $lifetime  Unused
	 *
	 * @return  boolean
	 *
	 * @since   3.17.0
	 */
	public function regenerate($destroy = false, $lifetime = null)
	{
		$this->id = bin2hex(random_bytes(16));

		return true;
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function save()
	{
		$this->started = false;
	}

	/**
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public function clear()
	{
		$this->started = false;
		$this->id      = '';
	}
}
