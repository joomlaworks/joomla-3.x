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
 * MySQL functions which the "MySQL on SQLite" layer lacks or gets wrong, registered as SQLite functions. The layer passes
 * calls of functions it doesn't translate to SQLite unchanged, so they reach these.
 *
 * @since  3.17.0
 */
abstract class JDatabaseMysqlonsqliteFunctions
{
	/**
	 * The value LAST_INSERT_ID() returns, set by the driver before a statement which uses it
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	public static $lastInsertId = 0;

	/**
	 * The longest result LPAD() and RPAD() build, in characters: MySQL returns NULL past its packet size, and a length from a
	 * query (e.g. LPAD(x, 2000000000, 'y')) would otherwise exhaust PHP's memory
	 *
	 * @var    integer
	 * @since  3.17.0
	 */
	const MAX_PAD_LENGTH = 1048576;

	/**
	 * Register the functions on a connection.
	 *
	 * @param   WP_MySQL_On_SQLite  $connection  The connection
	 *
	 * @return  void
	 *
	 * @since   3.17.0
	 */
	public static function register(WP_MySQL_On_SQLite $connection)
	{
		$sqlite = $connection->get_sqlite_pdo();

		$functions = array(
			// MySQL's truth is non-zero and not NULL; the layer's own IF() compares with PHP's true, which SQLite never passes
			'if'              => array(array(__CLASS__, 'ifFunction'), 3),

			// SQLite's own only change the case of ASCII letters
			'lower'           => array(array(__CLASS__, 'lower'), 1),
			'upper'           => array(array(__CLASS__, 'upper'), 1),
			'lcase'           => array(array(__CLASS__, 'lower'), 1),
			'ucase'           => array(array(__CLASS__, 'upper'), 1),

			'find_in_set'     => array(array(__CLASS__, 'findInSet'), 2),
			'substring_index' => array(array(__CLASS__, 'substringIndex'), 3),
			'right'           => array(array(__CLASS__, 'right'), 2),
			'lpad'            => array(array(__CLASS__, 'lpad'), 3),
			'rpad'            => array(array(__CLASS__, 'rpad'), 3),
			'uuid'            => array(array(__CLASS__, 'uuid'), 0),

			// The layer's own REGEXP ends its pattern at an escaped slash (\/), and an invalid pattern prints PHP warnings
			'regexp'          => array(array(__CLASS__, 'regexp'), 2),

			// The layer's own bookkeeping inserts change SQLite's last row ID, and the layer resets its own when a statement starts
			'last_insert_id'  => array(function () {
				return JDatabaseMysqlonsqliteFunctions::$lastInsertId;
			}, 0),
		);

		foreach ($functions as $name => $function)
		{
			if ($sqlite instanceof Pdo\Sqlite)
			{
				// PHP 8.4+; PDO::sqliteCreateFunction() is deprecated since PHP 8.5
				$sqlite->createFunction($name, $function[0], $function[1]);
			}
			else
			{
				$sqlite->sqliteCreateFunction($name, $function[0], $function[1]);
			}
		}
	}

	/**
	 * @param   mixed  $condition  The condition
	 * @param   mixed  $then       The value when it's true
	 * @param   mixed  $else       The value otherwise
	 *
	 * @return  mixed
	 *
	 * @since   3.17.0
	 */
	public static function ifFunction($condition, $then, $else)
	{
		return $condition !== null && (float) $condition != 0 ? $then : $else;
	}

	/**
	 * @param   string|null  $string  The text
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function lower($string)
	{
		return $string === null ? null : mb_strtolower((string) $string, 'UTF-8');
	}

	/**
	 * @param   string|null  $string  The text
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function upper($string)
	{
		return $string === null ? null : mb_strtoupper((string) $string, 'UTF-8');
	}

	/**
	 * @param   string|null  $needle  The value to find
	 * @param   string|null  $list    A comma-separated list
	 *
	 * @return  integer|null  The 1-based position, 0 if not found
	 *
	 * @since   3.17.0
	 */
	public static function findInSet($needle, $list)
	{
		if ($needle === null || $list === null)
		{
			return null;
		}

		$position = array_search((string) $needle, explode(',', (string) $list), true);

		return $position === false ? 0 : $position + 1;
	}

	/**
	 * @param   string|null   $string     The text
	 * @param   string|null   $delimiter  The delimiter
	 * @param   integer|null  $count      Parts to keep: from the left if positive, from the right if negative
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function substringIndex($string, $delimiter, $count)
	{
		if ($string === null || $delimiter === null || $count === null)
		{
			return null;
		}

		if ((string) $delimiter === '' || (int) $count === 0)
		{
			return '';
		}

		$parts = explode((string) $delimiter, (string) $string);
		$parts = (int) $count > 0 ? array_slice($parts, 0, (int) $count) : array_slice($parts, (int) $count);

		return implode((string) $delimiter, $parts);
	}

	/**
	 * @param   string|null   $string  The text
	 * @param   integer|null  $length  The number of characters
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function right($string, $length)
	{
		if ($string === null || $length === null)
		{
			return null;
		}

		return (int) $length > 0 ? mb_substr((string) $string, -(int) $length, null, 'UTF-8') : '';
	}

	/**
	 * @param   string|null   $string  The text
	 * @param   integer|null  $length  The length
	 * @param   string|null   $pad     The padding
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function lpad($string, $length, $pad)
	{
		return static::pad($string, $length, $pad, true);
	}

	/**
	 * @param   string|null   $string  The text
	 * @param   integer|null  $length  The length
	 * @param   string|null   $pad     The padding
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function rpad($string, $length, $pad)
	{
		return static::pad($string, $length, $pad, false);
	}

	/**
	 * SQLite's REGEXP operator: "value REGEXP pattern" calls regexp(pattern, value). Case-insensitive, unless the layer marks
	 * the pattern as binary with a leading NUL byte (REGEXP BINARY).
	 *
	 * @param   string|null  $pattern  The regular expression
	 * @param   string|null  $value    The text
	 *
	 * @return  integer|null  1 or 0, or NULL for NULL arguments or a pattern PCRE can't compile
	 *
	 * @since   3.17.0
	 */
	public static function regexp($pattern, $value)
	{
		if ($pattern === null || $value === null)
		{
			return null;
		}

		$pattern = (string) $pattern;
		$flags   = 'i';

		if ($pattern !== '' && $pattern[0] === "\0")
		{
			$pattern = substr($pattern, 1);
			$flags   = '';
		}

		// A delimiter no pattern holds as such (an \x01 in it is written as an escape), so the pattern can't end early
		$result = @preg_match("\x01" . str_replace("\x01", '\\x01', $pattern) . "\x01" . $flags, (string) $value);

		return $result === false ? null : $result;
	}

	/**
	 * @return  string  A version 1 style UUID, e.g. "6ccd780c-baba-1026-9564-5b8c656024db"
	 *
	 * @since   3.17.0
	 */
	public static function uuid()
	{
		$bytes    = random_bytes(16);
		$bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x10);
		$bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
	}

	/**
	 * @param   string|null   $string  The text
	 * @param   integer|null  $length  The length
	 * @param   string|null   $pad     The padding
	 * @param   boolean       $left    Pad on the left
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	private static function pad($string, $length, $pad, $left)
	{
		if ($string === null || $length === null || $pad === null)
		{
			return null;
		}

		$string  = (string) $string;
		$length  = (int) $length;
		$current = mb_strlen($string, 'UTF-8');

		if ($length > static::MAX_PAD_LENGTH)
		{
			return null;
		}

		// Like MySQL, a longer text is cut to the length
		if ($length <= $current || (string) $pad === '')
		{
			return $length < 0 ? null : mb_substr($string, 0, $length, 'UTF-8');
		}

		$fill = mb_substr(str_repeat((string) $pad, (int) ceil(($length - $current) / mb_strlen((string) $pad, 'UTF-8'))), 0, $length - $current, 'UTF-8');

		return $left ? $fill . $string : $string . $fill;
	}
}
