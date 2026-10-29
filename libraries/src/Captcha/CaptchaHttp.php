<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Captcha;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;

/**
 * Posts a form to a CAPTCHA or anti-spam service's API (reCAPTCHA, Turnstile, hCaptcha, Akismet): with cURL, else with
 * PHP's streams through Joomla's HTTP client; both use Global Configuration's proxy.
 *
 * @since  3.18.0
 */
abstract class CaptchaHttp
{
    /**
     * @param   string   $url      The API's address
     * @param   array    $data     The form's fields
     * @param   integer  $timeout  Seconds to wait for the answer
     *
     * @return  array|null  code (the HTTP status), body and headers (lower-case names), or null when there was no answer
     *
     * @since   3.18.0
     */
    public static function post($url, array $data, $timeout = 10)
    {
        $body = http_build_query($data, '', '&');

        if (function_exists('curl_init')) {
            $headers = array();
            $curl    = curl_init($url);
            $options = array(
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
                CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$headers) {
                    $parts = explode(':', $line, 2);

                    if (count($parts) === 2) {
                        $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }

                    return strlen($line);
                },
            );
            $config = Factory::getConfig();

            if ($config->get('proxy_enable') && $config->get('proxy_host')) {
                $options[CURLOPT_PROXY] = $config->get('proxy_host') . ($config->get('proxy_port') ? ':' . $config->get('proxy_port') : '');

                if ($config->get('proxy_user')) {
                    $options[CURLOPT_PROXYUSERPWD] = $config->get('proxy_user') . ':' . $config->get('proxy_pass');
                }
            }

            curl_setopt_array($curl, $options);
            $answer = curl_exec($curl);
            $code   = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

            // A no-op since PHP 8.0, deprecated in 8.5
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl);
            }

            return is_string($answer) && $code > 0 ? array('code' => $code, 'body' => $answer, 'headers' => $headers) : null;
        }

        // Its streams: Joomla's client would still choose its cURL transport when only curl_init() is disabled
        try {
            $answer = HttpFactory::getHttp(null, 'stream')->post($url, $body, array('Content-Type' => 'application/x-www-form-urlencoded'), $timeout);
        } catch (\Exception $e) {
            return null;
        }

        $headers = array();

        foreach ((array) $answer->headers as $name => $value) {
            $headers[strtolower($name)] = is_array($value) ? end($value) : $value;
        }

        return array('code' => (int) $answer->code, 'body' => (string) $answer->body, 'headers' => $headers);
    }

    /**
     * Posts to a siteverify API (reCAPTCHA, Turnstile, hCaptcha) and decodes its JSON answer.
     *
     * @param   string  $url   The API's address
     * @param   array   $data  The form's fields
     *
     * @return  array|null  The answer, or null when there was no usable one
     *
     * @since   3.18.0
     */
    public static function verify($url, array $data)
    {
        $answer = static::post($url, $data);
        $result = $answer ? json_decode($answer['body'], true) : null;

        // Its JSON, whatever the status: Cloudflare answers a malformed secret key with a 400 and its error codes
        return is_array($result) && isset($result['success']) ? $result : null;
    }
}
