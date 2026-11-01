<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\IpHelper;

/**
 * IconCaptcha (self-hosted, its library in lib/): visitors pick the icon shown the fewest times.
 * Challenges are made and checked on the site itself, through com_ajax, and kept in the visitor's session; nothing is sent
 * to another service. Needs PHP 7.4 and GD; the library is only loaded where it can run.
 *
 * @since  3.18.0
 */
class PlgCaptchaIconcaptcha extends JPlugin
{
    /**
     * @var    boolean
     * @since  3.18.0
     */
    protected $autoloadLanguage = true;

    /**
     * The widget's texts (IconCaptcha's locale option) and their language strings
     *
     * @var    array
     * @since  3.18.0
     */
    public const LOCALE = array(
        'initialization' => array(
            'loading' => 'PLG_CAPTCHA_ICONCAPTCHA_LOADING',
            'verify'  => 'PLG_CAPTCHA_ICONCAPTCHA_VERIFY',
        ),
        'header'         => 'PLG_CAPTCHA_ICONCAPTCHA_HEADER',
        'correct'        => 'PLG_CAPTCHA_ICONCAPTCHA_CORRECT',
        'incorrect'      => array(
            'title'    => 'PLG_CAPTCHA_ICONCAPTCHA_INCORRECT_TITLE',
            'subtitle' => 'PLG_CAPTCHA_ICONCAPTCHA_INCORRECT_SUBTITLE',
        ),
        'timeout'        => array(
            'title'    => 'PLG_CAPTCHA_ICONCAPTCHA_TIMEOUT_TITLE',
            'subtitle' => 'PLG_CAPTCHA_ICONCAPTCHA_TIMEOUT_SUBTITLE',
        ),
    );

    /**
     * @return  boolean  Whether IconCaptcha can run here (PHP 7.4 and GD)
     *
     * @since   3.18.0
     */
    public static function isSupported()
    {
        return PHP_VERSION_ID >= 70400 && function_exists('imagecreatetruecolor') && function_exists('imagepng');
    }

    /**
     * @param   string  $id  The id of the field.
     *
     * @return  boolean
     *
     * @since   3.18.0
     * @throws  \RuntimeException
     */
    public function onInit($id = 'dynamic_iconcaptcha_1')
    {
        if (!static::isSupported()) {
            throw new \RuntimeException(JText::_('PLG_CAPTCHA_ICONCAPTCHA_ERROR_NOT_SUPPORTED'));
        }

        $locale = $this->translate(self::LOCALE);

        JHtml::_('form.csrf');
        JHtml::_('stylesheet', 'plugins/captcha/iconcaptcha/css/iconcaptcha.min.css', array('version' => 'auto'));
        JHtml::_('script', 'plugins/captcha/iconcaptcha/js/iconcaptcha.min.js', array('version' => 'auto'), array('defer' => true));
        JHtml::_('script', 'plugins/captcha/iconcaptcha/js/iconcaptcha-init.min.js', array('version' => 'auto'), array('defer' => true));
        JFactory::getDocument()->addScriptOptions('plg_captcha_iconcaptcha', array(
            'endpoint'    => Uri::base(true) . '/index.php?option=com_ajax&group=captcha&plugin=iconcaptcha&format=raw',
            'showCredits' => (bool) $this->params->get('credits', 1),
            'locale'      => $locale,
        ));

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
    public function onDisplay($name = null, $id = 'dynamic_iconcaptcha_1', $class = '')
    {
        $theme = $this->params->get('theme', 'auto');

        return '<div id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars(trim($class . ' iconcaptcha-widget joomla-iconcaptcha'), ENT_QUOTES, 'UTF-8')
            . '" data-joomla-theme="' . (in_array($theme, array('auto', 'light', 'dark'), true) ? $theme : 'auto') . '"></div>'
            . '<input type="hidden" name="_iconcaptcha-token" value="" />';
    }

    /**
     * The widget's requests (a challenge, a selection), through com_ajax: IconCaptcha answers and ends the request.
     *
     * @return  void
     *
     * @since   3.18.0
     */
    public function onAjaxIconcaptcha()
    {
        if (!static::isSupported()) {
            return;
        }

        // Its answer is plain text, never stored by a cache
        JFactory::getApplication()->allowCache(false);

        $this->getIconCaptcha()->request()->process();
    }

    /**
     * @param   string  $code  Not used: the widget's fields are read from the request
     *
     * @return  boolean  True if the challenge was solved
     *
     * @since   3.18.0
     * @throws  \RuntimeException
     */
    public function onCheckAnswer($code = null)
    {
        if (!static::isSupported()) {
            throw new \RuntimeException(JText::_('PLG_CAPTCHA_ICONCAPTCHA_ERROR_NOT_SUPPORTED'));
        }

        $input  = JFactory::getApplication()->input->post;
        $fields = array();

        foreach (array('ic-rq', 'ic-wid', 'ic-cid', 'ic-hp', '_iconcaptcha-token') as $name) {
            $value = $input->get($name, null, 'raw');

            if (is_string($value)) {
                $fields[$name] = $value;
            }
        }

        $result = $this->getIconCaptcha()->validate($fields);

        if (!$result->success()) {
            $error = $result->getErrorCode();

            throw new \RuntimeException(JText::_(
                in_array($error, array('empty-request', 'unsolved-challenge'), true) ? 'PLG_CAPTCHA_ICONCAPTCHA_ERROR_EMPTY_SOLUTION'
                    : ($error === 'expired-challenge' ? 'PLG_CAPTCHA_ICONCAPTCHA_ERROR_EXPIRED' : 'PLG_CAPTCHA_ICONCAPTCHA_ERROR_INCORRECT')
            ));
        }

        return true;
    }

    /**
     * IconCaptcha with the site's settings: the visitor's session as storage, Joomla's form token, the visitor's IP address as
     * Joomla reads it (behind proxies too).
     *
     * @return  \IconCaptcha\IconCaptcha
     *
     * @since   3.18.0
     */
    private function getIconCaptcha()
    {
        JLoader::registerNamespace('IconCaptcha', __DIR__ . '/lib/src', false, false, 'psr4');
        JLoader::register('PlgCaptchaIconcaptchaToken', __DIR__ . '/token.php');
        JLoader::register('PlgCaptchaIconcaptchaAttempts', __DIR__ . '/attempts.php');

        // The session IconCaptcha keeps its challenges in
        JFactory::getSession();

        $attempts = max(1, min(20, (int) $this->params->get('attempts', 5)));

        return new \IconCaptcha\IconCaptcha(array(
            'ipAddress'  => function () {
                return (string) IpHelper::getIp();
            },
            'token'      => 'PlgCaptchaIconcaptchaToken',
            'challenge'  => array(
                'iconAmount' => array('min' => 5, 'max' => 8),
            ),
            'validation' => array(
                // Wrong answers per IP address (not per session, which a script renews for every guess): that many within
                // two minutes, then two minutes' wait
                'attempts' => array(
                    'enabled' => true,
                    'amount'  => $attempts,
                    'timeout' => 120,
                    'valid'   => 120,
                    'storage' => array(
                        'driver'  => 'PlgCaptchaIconcaptchaAttempts',
                        'options' => array(),
                    ),
                ),
            ),
        ));
    }

    /**
     * @param   array  $strings  Language strings, nested
     *
     * @return  array  The texts
     *
     * @since   3.18.0
     */
    private function translate(array $strings)
    {
        foreach ($strings as $name => $value) {
            $strings[$name] = is_array($value) ? $this->translate($value) : JText::_($value);
        }

        return $strings;
    }
}
