<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Utility;

defined('JPATH_PLATFORM') or die;

/**
 * JUtility is a utility functions class
 *
 * @since  1.7.0
 */
class Utility
{
	/**
	 * Method to extract key/value pairs out of a string with XML style attributes
	 *
	 * @param   string  $string  String containing XML style attributes
	 *
	 * @return  array  Key/Value pairs for the attributes
	 *
	 * @since   1.7.0
	 */
	public static function parseAttributes($string)
	{
		$attr = array();
		$retarray = array();

		// Let's grab all the key/value pairs using a regular expression
		preg_match_all('/([\w:-]+)[\s]?=[\s]?"([^"]*)"/i', $string, $attr);

		if (is_array($attr))
		{
			$numPairs = count($attr[1]);

			for ($i = 0; $i < $numPairs; $i++)
			{
				$retarray[$attr[1][$i]] = $attr[2][$i];
			}
		}

		return $retarray;
	}

	/**
	 * Method to get the maximum allowed file size for the HTTP uploads based on the active PHP configuration
	 *
	 * @param   mixed  $custom  A custom upper limit, if the PHP settings are all above this then this will be used
	 *
	 * @return  integer  Size in number of bytes
	 *
	 * @since   3.7.0
	 */
	public static function getMaxUploadSize($custom = null)
	{
		if ($custom)
		{
			$custom = \JHtml::_('number.bytes', $custom, '');

			if ($custom > 0)
			{
				$sizes[] = $custom;
			}
		}

		/*
		 * Read INI settings which affects upload size limits
		 * and Convert each into number of bytes so that we can compare
		 */
		$sizes[] = \JHtml::_('number.bytes', ini_get('post_max_size'), '');
		$sizes[] = \JHtml::_('number.bytes', ini_get('upload_max_filesize'), '');

		// The minimum of these is the limiting factor
		return min($sizes);
	}

	/**
	 * The name of the server's operating system: its family (Linux, macOS, FreeBSD, Windows, ...), or with $detailed the Linux
	 * distribution (from os-release) or the BSD release. Never php_uname()'s full text, which includes the server's host name.
	 *
	 * @param   boolean  $detailed  Include the distribution or release
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function getOperatingSystem($detailed = false)
	{
		$os = PHP_OS;

		if (stripos($os, 'WIN') === 0)
		{
			return 'Windows';
		}

		if ($os === 'Darwin')
		{
			return 'macOS';
		}

		if ($os === 'SunOS')
		{
			return 'Solaris';
		}

		if (stripos($os, 'BSD') !== false || $os === 'DragonFly')
		{
			$release = $detailed && function_exists('php_uname') ? php_uname('r') : '';

			return $release !== '' ? $os . ' ' . $release : $os;
		}

		if ($os !== 'Linux' || !$detailed)
		{
			return $os;
		}

		// open_basedir may keep the files out of reach
		foreach (array('/etc/os-release', '/usr/lib/os-release') as $file)
		{
			$contents = @is_readable($file) ? @file_get_contents($file) : false;

			foreach (array('PRETTY_NAME', 'NAME') as $key)
			{
				if (is_string($contents) && preg_match('/^' . $key . '=["\']?([^"\'\r\n]*)/m', $contents, $match) && trim($match[1]) !== '')
				{
					return trim($match[1]);
				}
			}
		}

		return 'Linux';
	}

	/**
	 * The name of the web server, from SERVER_SOFTWARE (e.g. "Apache 2.4.58", "nginx", "IIS 10.0"), without the extra details servers
	 * add (operating system, modules). Servers set to hide their version (Apache's ServerTokens Prod, nginx's server_tokens off) give
	 * none. Empty on the command line.
	 *
	 * @param   boolean  $withVersion  Include the version
	 *
	 * @return  string
	 *
	 * @since   3.17.0
	 */
	public static function getWebServer($withVersion = false)
	{
		$software = isset($_SERVER['SERVER_SOFTWARE']) ? trim((string) $_SERVER['SERVER_SOFTWARE']) : '';

		if ($software === '')
		{
			return '';
		}

		if (preg_match('/^PHP ([0-9][0-9.]*)\b.*Development Server/i', $software, $match))
		{
			return 'PHP built-in server' . ($withVersion ? ' ' . $match[1] : '');
		}

		// "Name/version (details) more/1.0" or "Name"
		preg_match('/^([^\/\s(]+)(?:\/([^\s(]+))?/', $software, $match);

		$names   = array('microsoft-iis' => 'IIS', 'apache' => 'Apache', 'nginx' => 'nginx', 'litespeed' => 'LiteSpeed', 'openlitespeed' => 'OpenLiteSpeed',
			'caddy' => 'Caddy', 'lighttpd' => 'lighttpd', 'openresty' => 'OpenResty');
		$name    = isset($match[1]) ? $match[1] : $software;
		$name    = isset($names[strtolower($name)]) ? $names[strtolower($name)] : $name;
		$version = $withVersion && !empty($match[2]) ? ' ' . $match[2] : '';

		return $name . $version;
	}
}
