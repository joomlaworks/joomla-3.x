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
 * hCaptcha: the checkbox (normal or compact) and the invisible check run when the form is sent. Answers are verified with
 * hCaptcha's siteverify API (CaptchaHttp: cURL).
 *
 * @since  3.18.0
 */
class PlgCaptchaHcaptcha extends JPlugin
{
    /**
     * hCaptcha's verification address
     *
     * @var    string
     * @since  3.18.0
     */
    public const VERIFY_URL = 'https://api.hcaptcha.com/siteverify';

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
            JText::_('PLG_CAPTCHA_HCAPTCHA') => array(
                JText::_('PLG_CAPTCHA_HCAPTCHA_PRIVACY_CAPABILITY'),
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
    public function onInit($id = 'dynamic_hcaptcha_1')
    {
        if (trim((string) $this->params->get('public_key', '')) === '') {
            throw new \RuntimeException(JText::_('PLG_CAPTCHA_HCAPTCHA_ERROR_NO_PUBLIC_KEY'));
        }

        // Not deferred: hCaptcha's script (async) calls it when it has loaded, which can be before deferred scripts run
        JHtml::_('script', 'plugins/captcha/hcaptcha/js/hcaptcha.min.js', array('version' => 'auto'));
        JHtml::_(
            'script',
            'https://js.hcaptcha.com/1/api.js?onload=JoomlaInitHcaptcha&render=explicit&hl=' . rawurlencode(substr(JFactory::getLanguage()->getTag(), 0, 2)),
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
    public function onDisplay($name = null, $id = 'dynamic_hcaptcha_1', $class = '')
    {
        $theme = $this->params->get('theme', 'light');
        $size  = $this->getSize();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $ele = $dom->createElement('div');
        $ele->setAttribute('id', $id);
        $ele->setAttribute('class', trim($class . ' joomla-hcaptcha'));
        $ele->setAttribute('data-sitekey', trim((string) $this->params->get('public_key', '')));
        $ele->setAttribute('data-theme', $theme === 'dark' ? 'dark' : 'light');
        $ele->setAttribute('data-size', $size);
        $dom->appendChild($ele);

        return $dom->saveHTML($ele);
    }

    /**
     * Hides the field's label for the invisible check.
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
        if ($this->getSize() === 'invisible') {
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
        $response = $code ? (string) $code : JFactory::getApplication()->input->post->get('h-captcha-response', '', 'string');

        if ($secret === '') {
            throw new \RuntimeException(JText::_('PLG_CAPTCHA_HCAPTCHA_ERROR_NO_PRIVATE_KEY'));
        }

        if (trim($response) === '') {
            throw new \RuntimeException(JText::_('PLG_CAPTCHA_HCAPTCHA_ERROR_EMPTY_SOLUTION'));
        }

        $data = array('secret' => $secret, 'response' => $response, 'sitekey' => trim((string) $this->params->get('public_key', '')));
        $ip   = IpHelper::getIp();

        if ($ip) {
            $data['remoteip'] = $ip;
        }

        $result = CaptchaHttp::verify(self::VERIFY_URL, $data);

        if ($result === null) {
            throw new \RuntimeException(JText::_('PLG_CAPTCHA_HCAPTCHA_ERROR_NOT_REACHABLE'));
        }

        if (empty($result['success'])) {
            $errors = isset($result['error-codes']) ? (array) $result['error-codes'] : array();

            if (array_intersect($errors, array('missing-input-secret', 'invalid-input-secret', 'sitekey-secret-mismatch'))) {
                throw new \RuntimeException(JText::_('PLG_CAPTCHA_HCAPTCHA_ERROR_INVALID_SECRET'));
            }

            if (array_intersect($errors, array('invalid-or-already-seen-response', 'expired-input-response'))) {
                throw new \RuntimeException(JText::_('PLG_CAPTCHA_HCAPTCHA_ERROR_EXPIRED'));
            }

            throw new \RuntimeException(JText::_('PLG_CAPTCHA_HCAPTCHA_ERROR_INCORRECT'));
        }

        return true;
    }

    /**
     * @return  string  normal, compact or invisible
     *
     * @since   3.18.0
     */
    private function getSize()
    {
        $size = $this->params->get('size', 'normal');

        return in_array($size, array('normal', 'compact', 'invisible'), true) ? $size : 'normal';
    }
}
