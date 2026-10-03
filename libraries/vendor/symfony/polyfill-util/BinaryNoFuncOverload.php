<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Util;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
class BinaryNoFuncOverload
{
    public static function strlen($s)
    {
        return \strlen((string) $s);
    }

    public static function strpos($haystack, $needle, $offset = 0)
    {
        return strpos((string) $haystack, (string) $needle, $offset);
    }

    public static function strrpos($haystack, $needle, $offset = 0)
    {
        return strrpos((string) $haystack, (string) $needle, $offset);
    }

    public static function substr($string, $start, $length = PHP_INT_MAX)
    {
        return substr((string) $string, $start, $length);
    }

    public static function stripos($s, $needle, $offset = 0)
    {
        return stripos((string) $s, (string) $needle, $offset);
    }

    public static function stristr($s, $needle, $part = false)
    {
        return stristr((string) $s, (string) $needle, $part);
    }

    public static function strrchr($s, $needle, $part = false)
    {
        return strrchr((string) $s, (string) $needle, $part);
    }

    public static function strripos($s, $needle, $offset = 0)
    {
        return strripos((string) $s, (string) $needle, $offset);
    }

    public static function strstr($s, $needle, $part = false)
    {
        return strstr((string) $s, (string) $needle, $part);
    }
}
