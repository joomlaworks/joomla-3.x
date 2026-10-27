<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Captcha\CaptchaHttp;
use Joomla\Utilities\IpHelper;

/**
 * Cloudflare Turnstile. Answers are verified with Cloudflare's siteverify API (CaptchaHttp: cURL).
 *
 * @since  3.18.0
 */
class PlgCaptchaTurnstile extends JPlugin
{
	/**
	 * Cloudflare's verification address
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

	/**
	 * @var    boolean
	 * @since  3.18.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   3.18.0
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		$this->loadLanguage();

		return array(
			JText::_('PLG_CAPTCHA_TURNSTILE') => array(
				JText::_('PLG_CAPTCHA_TURNSTILE_PRIVACY_CAPABILITY'),
			)
		);
	}

	/**
	 * @param   string  $id  The id of the field.
	 *
	 * @return  boolean
	 *
	 * @since   3.18.0
	 * @throws  \RuntimeException
	 */
	public function onInit($id = 'dynamic_turnstile_1')
	{
		if (trim((string) $this->params->get('public_key', '')) === '')
		{
			throw new \RuntimeException(JText::_('PLG_CAPTCHA_TURNSTILE_ERROR_NO_PUBLIC_KEY'));
		}

		// Not deferred: Cloudflare's script (async) calls it when it has loaded, which can be before deferred scripts run
		JHtml::_('script', 'plg_captcha_turnstile/turnstile.min.js', array('version' => 'auto', 'relative' => true));
		JHtml::_(
			'script',
			'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=JoomlaInitTurnstile&render=explicit',
			array(),
			array('async' => true, 'defer' => true)
		);

		return true;
	}

	/**
	 * @param   string  $name   The name of the field. Not used.
	 * @param   string  $id     The id of the field.
	 * @param   string  $class  The class of the field.
	 *
	 * @return  string  The HTML to be embedded in the form.
	 *
	 * @since   3.18.0
	 */
	public function onDisplay($name = null, $id = 'dynamic_turnstile_1', $class = '')
	{
		$theme      = $this->params->get('theme', 'auto');
		$size       = $this->params->get('size', 'normal');
		$appearance = $this->params->get('appearance', 'always');

		$dom = new \DOMDocument('1.0', 'UTF-8');
		$ele = $dom->createElement('div');
		$ele->setAttribute('id', $id);
		$ele->setAttribute('class', trim($class . ' joomla-turnstile'));
		$ele->setAttribute('data-sitekey', trim((string) $this->params->get('public_key', '')));
		$ele->setAttribute('data-theme', in_array($theme, array('auto', 'light', 'dark'), true) ? $theme : 'auto');
		$ele->setAttribute('data-size', in_array($size, array('normal', 'flexible', 'compact'), true) ? $size : 'normal');
		$ele->setAttribute('data-appearance', in_array($appearance, array('always', 'interaction-only'), true) ? $appearance : 'always');
		$ele->setAttribute('data-language', $this->getLanguage());
		$dom->appendChild($ele);

		return $dom->saveHTML($ele);
	}

	/**
	 * The page's language as Turnstile names it: a two-letter code, with the country only for the languages it has in
	 * several forms (Chinese, Brazilian Portuguese).
	 *
	 * @return  string
	 *
	 * @since   3.18.0
	 */
	private function getLanguage()
	{
		$tag = strtolower(JFactory::getLanguage()->getTag());

		if (in_array($tag, array('zh-cn', 'zh-tw', 'pt-br'), true))
		{
			return $tag;
		}

		$code = substr($tag, 0, 2);

		return preg_match('/^[a-z]{2}$/', $code) ? $code : 'auto';
	}

	/**
	 * Hides the field's label when the widget only shows when needed.
	 *
	 * @param   \Joomla\CMS\Form\Field\CaptchaField  $field    Captcha field instance
	 * @param   \SimpleXMLElement                    $element  XML form definition
	 *
	 * @return  void
	 *
	 * @since   3.18.0
	 */
	public function onSetupField(\Joomla\CMS\Form\Field\CaptchaField $field, \SimpleXMLElement $element)
	{
		if ($this->params->get('appearance', 'always') === 'interaction-only')
		{
			$element['hiddenLabel'] = true;
		}
	}

	/**
	 * @param   string  $code  The token, when the caller has it (else it's read from the request)
	 *
	 * @return  boolean  True if the answer is correct
	 *
	 * @since   3.18.0
	 * @throws  \RuntimeException
	 */
	public function onCheckAnswer($code = null)
	{
		$secret   = trim((string) $this->params->get('private_key', ''));
		$response = $code ? (string) $code : JFactory::getApplication()->input->post->get('cf-turnstile-response', '', 'string');

		if ($secret === '')
		{
			throw new \RuntimeException(JText::_('PLG_CAPTCHA_TURNSTILE_ERROR_NO_PRIVATE_KEY'));
		}

		if (trim($response) === '')
		{
			throw new \RuntimeException(JText::_('PLG_CAPTCHA_TURNSTILE_ERROR_EMPTY_SOLUTION'));
		}

		$data = array('secret' => $secret, 'response' => $response);
		$ip   = IpHelper::getIp();

		if ($ip)
		{
			$data['remoteip'] = $ip;
		}

		$result = CaptchaHttp::verify(self::VERIFY_URL, $data);

		if ($result === null)
		{
			throw new \RuntimeException(JText::_('PLG_CAPTCHA_TURNSTILE_ERROR_NOT_REACHABLE'));
		}

		if (empty($result['success']))
		{
			$errors = isset($result['error-codes']) ? (array) $result['error-codes'] : array();

			if (array_intersect($errors, array('missing-input-secret', 'invalid-input-secret')))
			{
				throw new \RuntimeException(JText::_('PLG_CAPTCHA_TURNSTILE_ERROR_INVALID_SECRET'));
			}

			if (in_array('timeout-or-duplicate', $errors, true))
			{
				throw new \RuntimeException(JText::_('PLG_CAPTCHA_TURNSTILE_ERROR_EXPIRED'));
			}

			throw new \RuntimeException(JText::_('PLG_CAPTCHA_TURNSTILE_ERROR_INCORRECT'));
		}

		return true;
	}
}
