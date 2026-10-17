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
	 * The LIKE patterns of like(), compiled to regular expressions (a query compares every row with the same pattern)
	 *
	 * @var    string[]
	 * @since  3.18.0
	 */
	private static $likePatterns = array();

	/**
	 * Case-folded letters and their forms without accents, for foldText() without PHP's intl extension (foldmap.php)
	 *
	 * @var    string[]|null
	 * @since  3.18.0
	 */
	private static $foldMap;

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

			// SQLite has it from 3.44.0 only (the layer runs from 3.37.0); Joomla's query builder writes it for the slugs ("id:alias")
			// of articles and categories (CONCAT() the layer turns into ||, which gives NULL for a NULL value as MySQL's does)
			'concat_ws'       => array(array(__CLASS__, 'concatWs'), -1),

			'find_in_set'     => array(array(__CLASS__, 'findInSet'), 2),
			'substring_index' => array(array(__CLASS__, 'substringIndex'), 3),
			'right'           => array(array(__CLASS__, 'right'), 2),
			'lpad'            => array(array(__CLASS__, 'lpad'), 3),
			'rpad'            => array(array(__CLASS__, 'rpad'), 3),
			'uuid'            => array(array(__CLASS__, 'uuid'), 0),

			// Text as MySQL's utf8mb4_unicode_ci compares it (see fold()), for uniqueness checks of e.g. usernames
			'joomla_fold'     => array(array(__CLASS__, 'fold'), 1),

			// The layer's own REGEXP ends its pattern at an escaped slash (\/), and an invalid pattern prints PHP warnings
			'regexp'          => array(array(__CLASS__, 'regexp'), 2),

			// The layer's own bookkeeping inserts change SQLite's last row ID, and the layer resets its own when a statement starts
			'last_insert_id'  => array(function () {
				return JDatabaseMysqlonsqliteFunctions::$lastInsertId;
			}, 0),
		);

		// SQLite has SOUNDEX() only when built with it (most packages are): this one gives the same codes, and fills in only when
		// it's missing
		try
		{
			$sqlite->query("SELECT SOUNDEX('a')");
		}
		catch (PDOException $e)
		{
			$functions['soundex'] = array(array(__CLASS__, 'soundex'), 1);
		}

		/*
		 * The LIKE operator: SQLite's own ignores the case of ASCII letters only, so 'Αθήνα' LIKE '%αθηνα%' and 'École' LIKE
		 * 'école' were false. "x LIKE y" calls like(y, x), and like(y, x, z) with ESCAPE z, which the layer always adds.
		 */
		$functions['like']   = array(array(__CLASS__, 'like'), 3);
		$functions['like/2'] = array(array(__CLASS__, 'like'), 2);

		foreach ($functions as $name => $function)
		{
			$name = strtok($name, '/');

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
	 * SQLite's SOUNDEX(), for builds without it: the same four-character code.
	 *
	 * @param   string|null  $string  The text
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function soundex($string)
	{
		if ($string === null)
		{
			return null;
		}

		// SQLite's algorithm (func.c), byte by byte: the first ASCII letter, then the codes of the bytes after it (bytes above 127
		// taken as their lower 7 bits, as SQLite does), a repeated code once, a byte without a code ending the repeat
		$codes  = '00000000000000000000000000000000000000000000000000000000000000000012301200224550126230102020000000123012002245501262301020200000';
		$string = (string) $string;
		$length = strlen($string);

		// ASCII letters only, as SQLite's (ctype_alpha() follows the locale)
		for ($i = 0; $i < $length && strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', $string[$i]) === false; $i++)
		{
		}

		if ($i === $length)
		{
			return '?000';
		}

		$result   = strtoupper($string[$i]);
		$previous = $codes[ord($string[$i]) & 0x7f];

		for (; $i < $length && strlen($result) < 4; $i++)
		{
			$code = $codes[ord($string[$i]) & 0x7f];

			if ($code !== '0')
			{
				if ($code !== $previous)
				{
					$previous = $code;
					$result  .= $code;
				}
			}
			else
			{
				$previous = '0';
			}
		}

		return str_pad($result, 4, '0');
	}

	/**
	 * MySQL's CONCAT_WS(): the values joined by the separator, NULL values left out; NULL only when the separator is.
	 *
	 * @param   mixed  $separator  The separator
	 * @param   mixed  ...$values  The values
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function concatWs($separator = null, ...$values)
	{
		if ($separator === null)
		{
			return null;
		}

		return implode((string) $separator, array_filter($values, function ($value)
		{
			return $value !== null;
		}));
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
	 * Text as MySQL's utf8mb4_unicode_ci compares it, near enough: without trailing spaces, accents or case (full case folding,
	 * so "ß" is "ss"), and with compatibility forms such as full-width letters made plain. SQLite's own NOCASE only folds the
	 * case of ASCII letters, so "àdmin" or "ａｄｍｉｎ" would pass for a new username next to "admin".
	 *
	 * @param   string|null  $string  The text
	 *
	 * @return  string|null
	 *
	 * @since   3.17.0
	 */
	public static function fold($string)
	{
		if ($string === null)
		{
			return null;
		}

		return static::foldText(rtrim((string) $string, ' '));
	}

	/**
	 * MySQL's LIKE as utf8mb4_unicode_ci compares, near enough: % for any characters, _ for one, the ESCAPE character before a
	 * literal one (a trailing one is literal itself, as in MySQL), and text compared without case or accents (foldText()).
	 * Unlike "=", trailing spaces count, and characters are compared one by one ("ß" isn't "ss", which "=" finds equal).
	 * Plain ASCII text and patterns take a quicker way, case-insensitive for A-Z.
	 *
	 * @param   string|null  $pattern  The pattern
	 * @param   mixed        $value    The value
	 * @param   string|null  $escape   The ESCAPE character, if any
	 *
	 * @return  integer|null  1 or 0, or NULL for a NULL pattern or value
	 *
	 * @since   3.18.0
	 */
	public static function like($pattern, $value, $escape = null)
	{
		if ($pattern === null || $value === null)
		{
			return null;
		}

		$pattern = (string) $pattern;
		$value   = (string) $value;
		$escape  = $escape === null || $escape === '' ? null : (string) $escape;
		$ascii   = !preg_match('/[\x80-\xFF]/', $pattern) && !preg_match('/[\x80-\xFF]/', $value);
		$key     = ($ascii ? 'a' : 'u') . $escape . "\0" . $pattern;

		if (!isset(static::$likePatterns[$key]))
		{
			if (count(static::$likePatterns) >= 200)
			{
				static::$likePatterns = array();
			}

			static::$likePatterns[$key] = static::compileLike($pattern, $escape, $ascii);
		}

		return preg_match(static::$likePatterns[$key], $ascii ? $value : static::foldText($value, true)) === 1 ? 1 : 0;
	}

	/**
	 * Text without case or accents: decomposed (NFKD) with its combining marks removed, so "ά" is "α", "é" is "e" and "ｅ" is
	 * "e", then case-folded ("ß" is "ss", "ς" is "σ"). The decomposition needs PHP's intl extension (Normalizer); without it,
	 * a table of the letters of the Latin, Greek and Cyrillic scripts (foldmap.php, generated from the same rules) is used.
	 * Always valid UTF-8 (invalid bytes become "?").
	 *
	 * @param   string   $string  The text
	 * @param   boolean  $simple  Simple case folding, one character for one ("ß" stays), as LIKE compares
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	private static function foldText($string, $simple = false)
	{
		$mode = $simple ? MB_CASE_FOLD_SIMPLE : MB_CASE_FOLD;

		if (class_exists('Normalizer'))
		{
			$decomposed = Normalizer::normalize($string, Normalizer::FORM_KD);

			if (is_string($decomposed))
			{
				return mb_convert_case(preg_replace('/\p{Mn}+/u', '', $decomposed) ?? $decomposed, $mode, 'UTF-8');
			}
		}

		if (static::$foldMap === null)
		{
			static::$foldMap = require __DIR__ . '/foldmap.php';
		}

		return strtr(mb_convert_case($string, $mode, 'UTF-8'), static::$foldMap);
	}

	/**
	 * A LIKE pattern as a regular expression for like(): unanchored on a side which starts or ends with %.
	 *
	 * @param   string       $pattern  The pattern
	 * @param   string|null  $escape   The ESCAPE character, if any
	 * @param   boolean      $ascii    Plain ASCII pattern and value: compared byte by byte, ignoring the case of A-Z
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	private static function compileLike($pattern, $escape, $ascii)
	{
		$chars  = $ascii ? str_split($pattern) : preg_split('//u', static::validUtf8($pattern), -1, PREG_SPLIT_NO_EMPTY);
		$tokens = array();
		$text   = '';
		$count  = count($chars);

		for ($i = 0; $i < $count; $i++)
		{
			$char = $chars[$i];

			if ($escape !== null && $char === $escape)
			{
				// The next character as it is; a trailing escape character stands for itself
				$text .= $i + 1 < $count ? $chars[++$i] : $char;

				continue;
			}

			if ($char !== '%' && $char !== '_')
			{
				$text .= $char;

				continue;
			}

			if ($text !== '')
			{
				$tokens[] = preg_quote($ascii ? $text : static::foldText($text, true), '/');
				$text     = '';
			}

			if ($char === '_')
			{
				$tokens[] = '.';
			}
			elseif (end($tokens) !== '.*')
			{
				$tokens[] = '.*';
			}
		}

		if ($text !== '')
		{
			$tokens[] = preg_quote($ascii ? $text : static::foldText($text, true), '/');
		}

		$start = '\\A';
		$end   = '\\z';

		if (reset($tokens) === '.*')
		{
			array_shift($tokens);
			$start = '';
		}

		if (end($tokens) === '.*')
		{
			array_pop($tokens);
			$end = '';
		}

		return '/' . $start . implode('', $tokens) . $end . '/s' . ($ascii ? 'i' : 'u');
	}

	/**
	 * @param   string  $string  Text which may hold invalid UTF-8
	 *
	 * @return  string  The text with invalid bytes replaced ("?"), as mb_convert_case() replaces them in foldText()
	 *
	 * @since   3.18.0
	 */
	private static function validUtf8($string)
	{
		return mb_check_encoding($string, 'UTF-8') ? $string : mb_convert_encoding($string, 'UTF-8', 'UTF-8');
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
