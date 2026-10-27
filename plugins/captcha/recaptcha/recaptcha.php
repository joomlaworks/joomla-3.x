<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Captcha\CaptchaHttp;
use Joomla\Utilities\IpHelper;

/**
 * Google reCAPTCHA: the v2 checkbox, v2 invisible and v3 (score) types. Answers are verified with Google's siteverify
 * API (CaptchaHttp: cURL).
 *
 * @since  2.5
 */
class PlgCaptchaRecaptcha extends JPlugin
{
	/**
	 * Google's verification address
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * The action v3 tokens are requested and checked for
	 *
	 * @var    string
	 * @since  3.18.0
	 */
	const ACTION = 'submit';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   3.9.0
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		$this->loadLanguage();

		return array(
			JText::_('PLG_CAPTCHA_RECAPTCHA') => array(
				JText::_('PLG_RECAPTCHA_PRIVACY_CAPABILITY_IP_ADDRESS'),
			)
		);
	}

	/**
	 * Initialise the captcha
	 *
	 * @param   string  $id  The id of the field.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.5
	 * @throws  \RuntimeException
	 */
	public function onInit($id = 'dynamic_recaptcha_1')
	{
		$pubkey = $this->params->get('public_key', '');

		if ($pubkey === '')
		{
			throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_NO_PUBLIC_KEY'));
		}

		// Not deferred: Google's script (async) calls it when it has loaded, which can be before deferred scripts run
		JHtml::_('script', 'plg_captcha_recaptcha/recaptcha.min.js', array('version' => 'auto', 'relative' => true));

		$render = $this->getType() === 'score' ? rawurlencode($pubkey) : 'explicit';

		JHtml::_(
			'script',
			'https://www.google.com/recaptcha/api.js?onload=JoomlaInitReCaptcha2&render=' . $render . '&hl=' . JFactory::getLanguage()->getTag(),
			array(),
			array('async' => true, 'defer' => true)
		);

		return true;
	}

	/**
	 * Gets the challenge HTML
	 *
	 * @param   string  $name   The name of the field. Not Used.
	 * @param   string  $id     The id of the field.
	 * @param   string  $class  The class of the field.
	 *
	 * @return  string  The HTML to be embedded in the form.
	 *
	 * @since   2.5
	 */
	public function onDisplay($name = null, $id = 'dynamic_recaptcha_1', $class = '')
	{
		$type = $this->getType();
		$dom  = new \DOMDocument('1.0', 'UTF-8');
		$ele  = $dom->createElement('div');
		$ele->setAttribute('id', $id);
		$ele->setAttribute('class', trim($class . ($type === 'score' ? ' joomla-recaptcha' : ' g-recaptcha joomla-recaptcha')));
		$ele->setAttribute('data-joomla-type', $type);
		$ele->setAttribute('data-sitekey', $this->params->get('public_key', ''));

		if ($type === 'score')
		{
			// Filled with the token when the form is sent
			$input = $dom->createElement('input');
			$input->setAttribute('type', 'hidden');
			$input->setAttribute('name', 'g-recaptcha-response');
			$input->setAttribute('value', '');
			$ele->appendChild($input);
			$ele->setAttribute('data-action', self::ACTION);
		}
		else
		{
			if ($type === 'checkbox')
			{
				$ele->setAttribute('data-theme', $this->params->get('theme', 'light') === 'dark' ? 'dark' : 'light');
				$ele->setAttribute('data-size', $this->params->get('size', 'normal') === 'compact' ? 'compact' : 'normal');
			}
			else
			{
				$badge = $this->params->get('badge', 'bottomright');
				$ele->setAttribute('data-size', 'invisible');
				$ele->setAttribute('data-badge', in_array($badge, array('bottomright', 'bottomleft', 'inline'), true) ? $badge : 'bottomright');
			}

			$ele->setAttribute('data-tabindex', (string) (int) $this->params->get('tabindex', 0));

			foreach (array('callback', 'expired_callback', 'error_callback') as $callback)
			{
				$value = trim((string) $this->params->get($callback, ''));

				if ($value !== '')
				{
					$ele->setAttribute('data-' . str_replace('_', '-', $callback), $value);
				}
			}
		}

		$dom->appendChild($ele);

		return $dom->saveHTML($ele);
	}

	/**
	 * Hides the field's label for the types without a visible widget.
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
		if ($this->getType() !== 'checkbox')
		{
			$element['hiddenLabel'] = true;
		}
	}

	/**
	 * Verifies the answer with Google
	 *
	 * @param   string  $code  The token, when the caller has it (else it's read from the request)
	 *
	 * @return  boolean  True if the answer is correct
	 *
	 * @since   2.5
	 * @throws  \RuntimeException
	 */
	public function onCheckAnswer($code = null)
	{
		$privatekey = trim((string) $this->params->get('private_key', ''));
		$remoteip   = IpHelper::getIp();
		$response   = $code ? (string) $code : JFactory::getApplication()->input->get('g-recaptcha-response', '', 'string');

		if ($privatekey === '')
		{
			throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_NO_PRIVATE_KEY'));
		}

		if (empty($remoteip))
		{
			throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_NO_IP'));
		}

		if (trim($response) === '')
		{
			throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_EMPTY_SOLUTION'));
		}

		$result = $this->verify($privatekey, $response, $remoteip);

		if (empty($result['success']))
		{
			$errors = isset($result['error-codes']) ? (array) $result['error-codes'] : array();

			if (array_intersect($errors, array('invalid-input-secret', 'missing-input-secret')))
			{
				throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_INVALID_SITE_PRIVATE_KEY'));
			}

			if (in_array('timeout-or-duplicate', $errors, true))
			{
				throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_TIMEOUT_OR_DUPLICATE'));
			}

			throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_INCORRECT_CAPTCHA_SOL'));
		}

		if ($this->getType() === 'score')
		{
			$score     = isset($result['score']) ? (float) $result['score'] : 0.0;
			$threshold = min(1.0, max(0.0, (float) $this->params->get('score', 0.5)));

			if ((isset($result['action']) && $result['action'] !== self::ACTION) || $score < $threshold)
			{
				throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_LOW_SCORE'));
			}
		}

		return true;
	}

	/**
	 * @return  string  checkbox, invisible or score
	 *
	 * @since   3.18.0
	 */
	private function getType()
	{
		$type = $this->params->get('type', 'checkbox');

		return in_array($type, array('checkbox', 'invisible', 'score'), true) ? $type : 'checkbox';
	}

	/**
	 * Asks Google's siteverify API about a token.
	 *
	 * @param   string  $secret    The secret key
	 * @param   string  $response  The token
	 * @param   string  $remoteip  The visitor's IP address
	 *
	 * @return  array  Google's answer
	 *
	 * @since   3.18.0
	 * @throws  \RuntimeException
	 */
	private function verify($secret, $response, $remoteip)
	{
		$result = CaptchaHttp::verify(self::VERIFY_URL, array('secret' => $secret, 'response' => $response, 'remoteip' => $remoteip));

		if ($result === null)
		{
			throw new \RuntimeException(JText::_('PLG_RECAPTCHA_ERROR_RECAPTCHA_NOT_REACHABLE'));
		}

		return $result;
	}
}
