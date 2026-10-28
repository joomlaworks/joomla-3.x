<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

namespace Joomla\CMS\Date;

defined('JPATH_PLATFORM') or die;

/**
 * Conversion between the Gregorian and the Jalali (Solar Hijri) calendar, for languages using the Jalali calendar
 * (metadata calendar "jalali"). Kazimierz M. Borkowski's algorithm, as jalaali-js implements it; the same as the date
 * field's script (CMS.dateField.toJalali(), toGregorian()), and checked against ICU's Persian calendar for 1900-2100.
 *
 * @since  3.18.0
 */
abstract class Jalali
{
	/**
	 * Years where the 33-year leap cycle breaks
	 *
	 * @var    integer[]
	 * @since  3.18.0
	 */
	const BREAKS = array(-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178);

	/**
	 * A Gregorian date as a Jalali one
	 *
	 * @param   integer  $year   Gregorian year
	 * @param   integer  $month  1-12
	 * @param   integer  $day    1-31
	 *
	 * @return  integer[]  Jalali year, month, day
	 *
	 * @since   3.18.0
	 */
	public static function toJalali($year, $month, $day)
	{
		$dayNumber = static::dayNumber((int) $year, (int) $month, (int) $day);
		$jy        = (int) $year - 621;
		$calendar  = static::calendar($jy);
		$k         = $dayNumber - static::dayNumber((int) $year, 3, $calendar['march']);

		if ($k >= 0)
		{
			if ($k <= 185)
			{
				return array($jy, 1 + intdiv($k, 31), static::mod($k, 31) + 1);
			}

			$k -= 186;
		}
		else
		{
			$jy--;
			$k += 179;

			if ($calendar['leap'] === 1)
			{
				$k++;
			}
		}

		return array($jy, 7 + intdiv($k, 30), static::mod($k, 30) + 1);
	}

	/**
	 * A Jalali date as a Gregorian one
	 *
	 * @param   integer  $year   Jalali year
	 * @param   integer  $month  1-12
	 * @param   integer  $day    1-31
	 *
	 * @return  integer[]  Gregorian year, month, day
	 *
	 * @since   3.18.0
	 */
	public static function toGregorian($year, $month, $day)
	{
		$calendar  = static::calendar((int) $year);
		$dayNumber = static::dayNumber($calendar['gy'], 3, $calendar['march']) + ((int) $month - 1) * 31
			- intdiv((int) $month, 7) * ((int) $month - 7) + (int) $day - 1;

		$j  = 4 * $dayNumber + 139361631;
		$j += intdiv(intdiv(4 * $dayNumber + 183187720, 146097) * 3, 4) * 4 - 3908;
		$i  = intdiv(static::mod($j, 1461), 4) * 5 + 308;
		$gd = intdiv(static::mod($i, 153), 5) + 1;
		$gm = static::mod(intdiv($i, 153), 12) + 1;

		return array(intdiv($j, 1461) - 100100 + intdiv(8 - $gm, 6), $gm, $gd);
	}

	/**
	 * The Jalali year's leap state, its Gregorian year and the day of March its year starts on
	 *
	 * @param   integer  $jy  Jalali year
	 *
	 * @return  array  leap, gy, march
	 *
	 * @since   3.18.0
	 */
	protected static function calendar($jy)
	{
		$gy    = $jy + 621;
		$leapJ = -14;
		$jp    = static::BREAKS[0];
		$jump  = 0;

		for ($i = 1, $count = count(static::BREAKS); $i < $count; $i++)
		{
			$jm   = static::BREAKS[$i];
			$jump = $jm - $jp;

			if ($jy < $jm)
			{
				break;
			}

			$leapJ += intdiv($jump, 33) * 8 + intdiv(static::mod($jump, 33), 4);
			$jp     = $jm;
		}

		$n      = $jy - $jp;
		$leapJ += intdiv($n, 33) * 8 + intdiv(static::mod($n, 33) + 3, 4);

		if (static::mod($jump, 33) === 4 && $jump - $n === 4)
		{
			$leapJ++;
		}

		$leapG = intdiv($gy, 4) - intdiv((intdiv($gy, 100) + 1) * 3, 4) - 150;

		if ($jump - $n < 6)
		{
			$n = $n - $jump + intdiv($jump + 4, 33) * 33;
		}

		$leap = static::mod(static::mod($n + 1, 33) - 1, 4);

		return array('leap' => $leap === -1 ? 4 : $leap, 'gy' => $gy, 'march' => 20 + $leapJ - $leapG);
	}

	/**
	 * The Julian day number of a Gregorian date
	 *
	 * @param   integer  $gy
	 * @param   integer  $gm
	 * @param   integer  $gd
	 *
	 * @return  integer
	 *
	 * @since   3.18.0
	 */
	protected static function dayNumber($gy, $gm, $gd)
	{
		$d = intdiv(($gy + intdiv($gm - 8, 6) + 100100) * 1461, 4) + intdiv(153 * static::mod($gm + 9, 12) + 2, 5) + $gd - 34840408;

		return $d - intdiv(intdiv($gy + 100100 + intdiv($gm - 8, 6), 100) * 3, 4) + 752;
	}

	/**
	 * The remainder with the sign of the dividend, as the algorithm expects
	 *
	 * @param   integer  $a
	 * @param   integer  $b
	 *
	 * @return  integer
	 *
	 * @since   3.18.0
	 */
	protected static function mod($a, $b)
	{
		return $a - intdiv($a, $b) * $b;
	}
}
