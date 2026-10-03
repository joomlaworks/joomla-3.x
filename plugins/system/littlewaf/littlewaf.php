<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.littlewaf
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Little WAF - a small, opt-in request filter for known attack signatures against
 * abandoned or historically vulnerable third-party extensions. The plugin itself is
 * off by default; once enabled, every protection below is on by default, since
 * turning the plugin on is itself the explicit opt-in.
 *
 * Deliberately not a general-purpose WAF: each filter here targets one specific,
 * previously-observed attack signature against one specific extension. It buys time
 * against a known pattern; it is not a substitute for updating or removing the
 * vulnerable extension it targets.
 *
 * @since  3.16.0
 */
class PlgSystemLittlewaf extends CMSPlugin
{
	/**
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  3.16.0
	 */
	protected $app;

	/**
	 * Runs on every request, as early as possible (before routing/component dispatch),
	 * so a blocked request never reaches any component, plugin, or third-party code.
	 *
	 * @return  void
	 *
	 * @since   3.16.0
	 */
	public function onAfterInitialise()
	{
		// Only ever filters site (frontend) requests. The administrator application is
		// never touched, since legitimate content authoring there (e.g. a trusted editor
		// intentionally using a real Sourcerer {source} tag) must not be blocked by this.
		if ($this->app->isClient('administrator'))
		{
			return;
		}

		$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');

		if ($this->params->get('filter_sourcerer', 1) && $this->matchesSourcererTag($requestUri))
		{
			$this->block('sourcerer', $requestUri);
		}

		if ($this->params->get('filter_modulesanywhere', 1) && $this->matchesModulesAnywhereTag($requestUri))
		{
			$this->block('modulesanywhere', $requestUri);
		}
	}

	/**
	 * Detects the Regular Labs Sourcerer {source}...{/source} raw-code tag in a URL, in
	 * both its literal and percent-encoded forms (matching what has actually been
	 * observed in mass-exploitation traffic against unpatched Sourcerer installs).
	 *
	 * @param   string  $value  The raw request URI to inspect.
	 *
	 * @return  boolean
	 *
	 * @since   3.16.0
	 */
	private function matchesSourcererTag($value)
	{
		return stripos($value, '{source}') !== false
			|| stripos($value, '{/source}') !== false
			|| stripos($value, '%7bsource%7d') !== false
			|| stripos($value, '%7b/source%7d') !== false;
	}

	/**
	 * Detects the Regular Labs Modules Anywhere {module ...}/{modulepos ...} tags in a
	 * URL, in both literal and percent-encoded form. A precautionary filter — unlike the
	 * Sourcerer tag above, this hasn't been observed in live attack traffic against this
	 * project's own sites; it targets the same class of "tag processed from an
	 * unverified source" risk that Regular Labs itself patched for this extension
	 * (SSRF/XSS/restricted-content exposure, July 2026). {modulepos ...} is covered by
	 * the same check, since "{modulepos" contains "{module" as a substring.
	 *
	 * @param   string  $value  The raw request URI to inspect.
	 *
	 * @return  boolean
	 *
	 * @since   3.16.0
	 */
	private function matchesModulesAnywhereTag($value)
	{
		return stripos($value, '{module') !== false
			|| stripos($value, '%7bmodule') !== false;
	}

	/**
	 * Logs (if enabled) and terminates the request with a 403 response.
	 *
	 * @param   string  $filter  The filter key that matched, for logging.
	 * @param   string  $value   The offending request data, for logging.
	 *
	 * @return  void  This method does not return.
	 *
	 * @since   3.16.0
	 */
	private function block($filter, $value)
	{
		if ($this->params->get('log_blocked', 1))
		{
			Log::add(
				sprintf('Little WAF blocked [%s] from %s: %s', $filter, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $value),
				Log::WARNING,
				'littlewaf'
			);
		}

		$this->app->setHeader('status', 403, true);
		$this->app->setBody('Forbidden');
		echo $this->app->toString();
		$this->app->close();
	}
}
