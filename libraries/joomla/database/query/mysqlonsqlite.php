<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Database
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/**
 * Query for the SQLite driver which runs MySQL SQL: the MySQL (PDO) one.
 *
 * @since  3.17.0
 */
class JDatabaseQueryMysqlonsqlite extends JDatabaseQueryPdomysql
{
	/**
	 * Return the number of the current row. The MySQL query uses user variables (@rownum := ...), which can't be assigned
	 * inside expressions here; the standard window function works in MySQL 8 and SQLite.
	 *
	 * @param   string  $orderBy           An expression of ordering for window function.
	 * @param   string  $orderColumnAlias  An alias for new ordering column.
	 *
	 * @return  JDatabaseQuery  Returns this object to allow chaining.
	 *
	 * @since   3.17.0
	 * @throws  RuntimeException
	 */
	public function selectRowNumber($orderBy, $orderColumnAlias)
	{
		$this->validateRowNumber($orderBy, $orderColumnAlias);
		$this->select("ROW_NUMBER() OVER (ORDER BY $orderBy) AS $orderColumnAlias");

		return $this;
	}
}
