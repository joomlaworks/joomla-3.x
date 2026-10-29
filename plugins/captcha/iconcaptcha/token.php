<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Captcha
 *
 * @copyright   (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('_JEXEC') or die;

use IconCaptcha\Token\AbstractToken;
use IconCaptcha\Token\TokenInterface;

/**
 * IconCaptcha's token is Joomla's form token: the page carries it in the "csrf.token" script option, which the page cache
 * replaces for each visitor, so cached pages work. Loaded only where IconCaptcha runs (PHP 7.4+).
 *
 * @since  3.18.0
 */
class PlgCaptchaIconcaptchaToken implements TokenInterface
{
    /**
     * The field, filled in by the plugin's script from the "csrf.token" script option
     *
     * @return  string
     *
     * @since   3.18.0
     */
    public static function render(): string
    {
        return '<input type="hidden" name="' . AbstractToken::TOKEN_FIELD_NAME . '" value="" />';
    }

    /**
     * @return  string
     *
     * @since   3.18.0
     */
    public function get(): string
    {
        return JSession::getFormToken();
    }

    /**
     * @param   string       $payloadToken  The token sent with the request or the form
     * @param   string|null  $headerToken   The token of the X-IconCaptcha-Token header, when the widget sent one
     *
     * @return  boolean
     *
     * @since   3.18.0
     */
    public function validate(string $payloadToken, ?string $headerToken = null): bool
    {
        $token = JSession::getFormToken();

        return hash_equals($token, $payloadToken) && ($headerToken === null || $headerToken === '' || hash_equals($token, $headerToken));
    }
}
