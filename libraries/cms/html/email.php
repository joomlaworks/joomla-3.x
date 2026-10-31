<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.md
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class for cloaking email addresses
 *
 * @since  1.5
 */
abstract class JHtmlEmail
{
    /**
     * Simple JavaScript email cloaker
     *
     * By default replaces an email with a mailto link with email cloaked
     *
     * @param   string   $mail    The -mail address to cloak.
     * @param   boolean  $mailto  True if text and mailing address differ
     * @param   string   $text    Text for the link
     * @param   boolean  $email   True if text is an email address
     *
     * @return  string  The cloaked email.
     *
     * @since   1.5
     */
    public static function cloak($mail, $mailto = true, $text = '', $email = true)
    {
        // Handle IDN addresses: punycode for href but utf-8 for text displayed.
        if ($mailto && (empty($text) || $email)) {
            // Use dedicated $text whereas $mail is used as href and must be punycoded.
            $text = JStringPunycode::emailToUTF8($text ?: $mail);
        } elseif (!$mailto) {
            // In that case we don't use link - so convert $mail back to utf-8.
            $mail = JStringPunycode::emailToUTF8($mail);
        }

        // Convert mail
        $mail = static::convertEncoding($mail);

        // Random hash
        $rand = md5($mail . mt_rand(1, 100000));

        // The address in parts (CMS.cloak in global.js and frontend.js puts them together): the user and the domain's parts
        $mail       = explode('@', $mail);
        $attributes = array(
            'data-cloak'        => '',
            'data-cloak-user'   => (string) $mail[0],
            'data-cloak-domain' => json_encode(explode('.', isset($mail[1]) ? $mail[1] : '')),
        );

        if ($mailto) {
            $attributes['data-cloak-link'] = '';

            // The link's text, when it isn't the address
            if ($text) {
                $text = static::convertEncoding($text);

                if ($email) {
                    $text = explode('@', $text);
                    $attributes['data-cloak-text-user']   = (string) $text[0];
                    $attributes['data-cloak-text-domain'] = json_encode(explode('.', isset($text[1]) ? $text[1] : ''));
                } else {
                    $attributes['data-cloak-text'] = $text;
                }
            }
        }

        JHtml::_('behavior.core');

        $html = '<span id="cloak' . $rand . '"';

        foreach ($attributes as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return $html . '>' . JText::_('JLIB_HTML_CLOAKING') . '</span>';
    }

    /**
     * Convert encoded text
     *
     * @param   string  $text  Text to convert
     *
     * @return  string  The converted text.
     *
     * @since   1.5
     */
    protected static function convertEncoding($text)
    {
        $text = html_entity_decode($text);

        // Replace vowels with character encoding
        $text = str_replace('a', '&#97;', $text);
        $text = str_replace('e', '&#101;', $text);
        $text = str_replace('i', '&#105;', $text);
        $text = str_replace('o', '&#111;', $text);
        $text = str_replace('u', '&#117;', $text);
        $text = htmlentities($text, ENT_QUOTES, 'UTF-8', false);

        return $text;
    }
}
