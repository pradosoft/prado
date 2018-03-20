<?php
/**
 * DateFormat class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\I18N\core
 */

namespace Prado\I18N\core;

/**
 * Get the DateTimeFormatInfo class.
 */
use Exception;
use Prado\Prado;

require_once(__DIR__ . '/DateTimeFormatInfo.php');

/**
 * Get the encoding utilities
 */
require_once(__DIR__ . '/util.php');

/**
 * DateFormat class.
 *
 * The DateFormat class allows you to format dates and times with
 * predefined styles in a locale-sensitive manner. Formatting times
 * with the DateFormat class is similar to formatting dates.
 *
 * Formatting dates with the DateFormat class is a two-step process.
 * First, you create a formatter with the getDateInstance method.
 * Second, you invoke the format method, which returns a string containing
 * the formatted date.
 *
 * DateTime values are formatted using standard or custom patterns stored
 * in the properties of a DateTimeFormatInfo.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\I18N\core
 */
class DateFormat
{
	/**
	 * A list of tokens and their function call.
	 * @var array
	 */
	protected $tokens = [
			'G' => 'Era',
			'y' => 'Year',
			'M' => 'Month',
			'd' => 'Day',
			'h' => 'Hour12',
			'H' => 'Hour24',
			'm' => 'Minutes',
			's' => 'Seconds',
			'E' => 'DayInWeek',
			'D' => 'DayInYear',
			'F' => 'DayInMonth',
			'w' => 'WeekInYear',
			'W' => 'WeekInMonth',
			'a' => 'AMPM',
			'k' => 'HourInDay',
			'K' => 'HourInAMPM',
			'z' => 'TimeZone'
			];

	/**
	 * A list of methods, to be used by the token function calls.
	 * @var array
	 */
	protected $methods = [];

	/**
	 * The DateTimeFormatInfo, containing culture specific patterns and names.
	 * @var DateTimeFormatInfo
	 */
	protected $formatInfo;

	/**
	 * Initialize a new DateFormat.
	 * @param null|CultureInfo|DateTimeFormatInfo|string $formatInfo either, null, a CultureInfo instance,
	 * a DateTimeFormatInfo instance, or a locale.
	 * @return DateFormat instance
	 */
	public function __construct($formatInfo = null)
	{
		if ($formatInfo === null) {
			$this->formatInfo = DateTimeFormatInfo::getInvariantInfo();
		} elseif ($formatInfo instanceof CultureInfo) {
			$this->formatInfo = $formatInfo->DateTimeFormat;
		} elseif ($formatInfo instanceof DateTimeFormatInfo) {
			$this->formatInfo = $formatInfo;
		} else {
			$this->formatInfo = DateTimeFormatInfo::getInstance($formatInfo);
		}

		$this->methods = get_class_methods($this);
	}

	/**
	 * Format a date according to the pattern.
	 * @param mixed $time the time as integer or string in strtotime format.
	 * @param mixed $pattern
	 * @param mixed $charset
	 * @return string formatted date time.
	 */
	public function format($time, $pattern = 'F', $charset = 'UTF-8')
	{
		if (is_numeric($time)) { //assumes unix epoch
			$time = (float) $time;
		} elseif (is_string($time)) {
			$time = @strtotime($time);
		}

		if ($pattern === null) {
			$pattern = 'F';
		}

		$date = new \DateTime;
		$date->setTimestamp($time);

		$pattern = $this->getPattern($pattern);

		$tokens = $this->getTokens($pattern);

		for ($i = 0, $k = count($tokens); $i < $k; ++$i) {
			$pattern = $tokens[$i];
			if ($pattern{0} == "'"
				&& $pattern{strlen($pattern) - 1} == "'") {
				$sub = preg_replace('/(^\')|(\'$)/', '', $pattern);
				$tokens[$i] = str_replace('``````', '\'', $sub);
			} elseif ($pattern == '``````') {
				$tokens[$i] = '\'';
			} else {
				$function = $this->getFunctionName($pattern);
				if ($function != null) {
					$fName = 'get' . $function;
					if (in_array($fName, $this->methods)) {
						$rs = $this->$fName($date, $pattern);
						$tokens[$i] = $rs;
					} else {
						throw new
						Exception('function ' . $function . ' not found.');
					}
				}
			}
		}

		return I18N_toEncoding(implode('', $tokens), $charset);
	}

	/**
	 * For a particular token, get the corresponding function to call.
	 * @param string $token token
	 * @return mixed the function if good token, null otherwise.
	 */
	protected function getFunctionName($token)
	{
		if (isset($this->tokens[$token{0}])) {
			return $this->tokens[$token{0}];
		}
	}

	/**
	 * Get the pattern from DateTimeFormatInfo or some predefined patterns.
	 * If the $pattern parameter is an array of 2 element, it will assume
	 * that the first element is the date, and second the time
	 * and try to find an appropriate pattern and apply
	 * DateTimeFormatInfo::formatDateTime
	 * See the tutorial documentation for futher details on the patterns.
	 * @param mixed $pattern a pattern.
	 * @return string a pattern.
	 * @see DateTimeFormatInfo::formatDateTime()
	 */
	protected function getPattern($pattern)
	{
		if (is_array($pattern) && count($pattern) == 2) {
			return $this->formatInfo->formatDateTime(
							$this->getPattern($pattern[0]),
							$this->getPattern($pattern[1])
			);
		}

		switch ($pattern) {
			case 'd':
				return $this->formatInfo->ShortDatePattern;
				break;
			case 'D':
				return $this->formatInfo->LongDatePattern;
				break;
			case 'p':
				return $this->formatInfo->MediumDatePattern;
				break;
			case 'P':
				return $this->formatInfo->FullDatePattern;
				break;
			case 't':
				return $this->formatInfo->ShortTimePattern;
				break;
			case 'T':
				return $this->formatInfo->LongTimePattern;
				break;
			case 'q':
				return $this->formatInfo->MediumTimePattern;
				break;
			case 'Q':
				return $this->formatInfo->FullTimePattern;
				break;
			case 'f':
				return $this->formatInfo->formatDateTime(
					$this->formatInfo->LongDatePattern,
					$this->formatInfo->ShortTimePattern
				);
				break;
			case 'F':
				return $this->formatInfo->formatDateTime(
					$this->formatInfo->LongDatePattern,
					$this->formatInfo->LongTimePattern
				);
				break;
			case 'g':
				return $this->formatInfo->formatDateTime(
					$this->formatInfo->ShortDatePattern,
					$this->formatInfo->ShortTimePattern
				);
				break;
			case 'G':
				return $this->formatInfo->formatDateTime(
					$this->formatInfo->ShortDatePattern,
					$this->formatInfo->LongTimePattern
				);
				break;
			case 'M':
			case 'm':
				return 'MMMM dd';
				break;
			case 'R':
			case 'r':
				return 'EEE, dd MMM yyyy HH:mm:ss';
				break;
			case 's':
				return 'yyyy-MM-ddTHH:mm:ss';
				break;
			case 'u':
				return 'yyyy-MM-dd HH:mm:ss z';
				break;
			case 'U':
				return 'EEEE dd MMMM yyyy HH:mm:ss';
				break;
			case 'Y':
			case 'y':
				return 'yyyy MMMM';
				break;
			default:
				return $pattern;
		}
	}

	/**
	 * Tokenize the pattern. The tokens are delimited by group of
	 * similar characters, e.g. 'aabb' will form 2 tokens of 'aa' and 'bb'.
	 * Any substrings, starting and ending with a single quote (')
	 * will be treated as a single token.
	 * @param string $pattern pattern.
	 * @return array string tokens in an array.
	 */
	protected function getTokens($pattern)
	{
		$char = null;
		$tokens = [];
		$token = null;

		$text = false;
		$pattern = preg_replace("/''/", '``````', $pattern);

		for ($i = 0; $i < strlen($pattern); $i++) {
			if ($char == null || $pattern{$i} == $char || $text) {
				$token .= $pattern{$i};
			} else {
				$tokens[] = str_replace("", "'", $token);
				$token = $pattern{$i};
			}

			if ($pattern{$i} == "'" && $text == false) {
				$text = true;
			} elseif ($text && $pattern{$i} == "'" && $char == "'") {
				$text = true;
			} elseif ($text && $char != "'" && $pattern{$i} == "'") {
				$text = false;
			}

			$char = $pattern{$i};
		}
		$tokens[] = $token;
		return $tokens;
	}

	/**
	 * Get the year.
	 * "yy" will return the last two digits of year.
	 * "yyyy" will return the full integer year.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string year
	 */
	protected function getYear($date, $pattern = 'yyyy')
	{
		switch ($pattern) {
			case 'yy':
				return $date->format('y');
			case 'yyyy':
				return $date->format('Y');
			default:
				throw new Exception('The pattern for year is either "yy" or "yyyy".');
		}
	}

	/**
	 * Get the month.
	 * "M" will return integer 1 through 12
	 * "MM" will return the narrow month name, e.g. "J"
	 * "MMM" will return the abrreviated month name, e.g. "Jan"
	 * "MMMM" will return the month name, e.g. "January"
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string month name
	 */
	protected function getMonth($date, $pattern = 'M')
	{
		switch ($pattern) {
			case 'M':
				return $date->format('n');
			case 'MM':
				return $date->format('m');
			case 'MMM':
				return $this->formatInfo->AbbreviatedMonthNames[$date->format('n') - 1];
			case 'MMMM':
				return $this->formatInfo->MonthNames[$date->format('n') - 1];
			default:
				throw new Exception('The pattern for month is "M", "MM", "MMM", or "MMMM".');
		}
	}

	/**
	 * Get the day of the week.
	 * "E" will return integer 0 (for Sunday) through 6 (for Saturday).
	 * "EE" will return the narrow day of the week, e.g. "M"
	 * "EEE" will return the abrreviated day of the week, e.g. "Mon"
	 * "EEEE" will return the day of the week, e.g. "Monday"
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string day of the week.
	 */
	protected function getDayInWeek($date, $pattern = 'EEEE')
	{
		$day = $date->format('w');
		switch ($pattern) {
			case 'E':
				return $day;
			case 'EE':
				return $this->formatInfo->NarrowDayNames[$day];
			case 'EEE':
				return $this->formatInfo->AbbreviatedDayNames[$day];
			case 'EEEE':
				return $this->formatInfo->DayNames[$day];
			default:
				throw new Exception('The pattern for day of the week is "E", "EE", "EEE", or "EEEE".');
		}
	}

	/**
	 * Get the day of the month.
	 * "d" for non-padding, "dd" will always return 2 characters.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string day of the month
	 */
	protected function getDay($date, $pattern = 'd')
	{
		switch ($pattern) {
			case 'd':
				return $date->format('j');
			case 'dd':
				return $date->format('d');
			default:
				throw new Exception('The pattern for day of the month is "d" or "dd".');
		}
	}


	/**
	 * Get the era. i.e. in gregorian, year > 0 is AD, else BC.
	 * @todo How to support multiple Eras?, e.g. Japanese.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string era
	 */
	protected function getEra($date, $pattern = 'G')
	{
		if ($pattern != 'G') {
			throw new Exception('The pattern for era is "G".');
		}

		$year = $date->format('Y');
		if ($year > 0) {
			return $this->formatInfo->getEra(1);
		} else {
			return $this->formatInfo->getEra(0);
		}
	}

	/**
	 * Get the hours in 24 hour format, i.e. [0-23].
	 * "H" for non-padding, "HH" will always return 2 characters.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string hours in 24 hour format.
	 */
	protected function getHour24($date, $pattern = 'H')
	{
		switch ($pattern) {
			case 'H':
				return $date->format('G');
			case 'HH':
				return $date->format('H');
			default:
				throw new Exception('The pattern for 24 hour format is "H" or "HH".');
		}
	}

	/**
	 * Get the AM/PM designator, 12 noon is PM, 12 midnight is AM.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string AM or PM designator
	 */
	protected function getAMPM($date, $pattern = 'a')
	{
		if ($pattern != 'a') {
			throw new Exception('The pattern for AM/PM marker is "a".');
		}

		$hour = $date->format('G');
		$ampm = (int) ($hour / 12);
		return $this->formatInfo->AMPMMarkers[$ampm];
	}

	/**
	 * Get the hours in 12 hour format.
	 * "h" for non-padding, "hh" will always return 2 characters.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string hours in 12 hour format.
	 */
	protected function getHour12($date, $pattern = 'h')
	{
		switch ($pattern) {
			case 'h':
				return $date->format('g');
			case 'hh':
				return $date->format('h');
			default:
				throw new Exception('The pattern for 24 hour format is "H" or "HH".');
		}
	}

	/**
	 * Get the minutes.
	 * "m" for non-padding, "mm" will always return 2 characters.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string minutes.
	 */
	protected function getMinutes($date, $pattern = 'm')
	{
		switch ($pattern) {
			case 'm':
				return (int) $date->format('i');
			case 'mm':
				return $date->format('i');
			default:
				throw new Exception('The pattern for minutes is "m" or "mm".');
		}
	}

	/**
	 * Get the seconds.
	 * "s" for non-padding, "ss" will always return 2 characters.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string seconds
	 */
	protected function getSeconds($date, $pattern = 's')
	{
		switch ($pattern) {
			case 's':
				return (int) $date->format('s');
			case 'ss':
				return $date->format('s');
			default:
				throw new Exception('The pattern for seconds is "s" or "ss".');
		}
	}

	/**
	 * Get the timezone from the server machine.
	 * @todo How to get the timezone for a different region?
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return string time zone
	 */
	protected function getTimeZone($date, $pattern = 'z')
	{
		if ($pattern != 'z') {
			throw new Exception('The pattern for time zone is "z".');
		}

		return $date->format('T');
	}

	/**
	 * Get the day in the year, e.g. [1-366]
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return int hours in AM/PM format.
	 */
	protected function getDayInYear($date, $pattern = 'D')
	{
		if ($pattern != 'D') {
			throw new Exception('The pattern for day in year is "D".');
		}

		return $date->format('z');
	}

	/**
	 * Get day in the month.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return int day in month
	 */
	protected function getDayInMonth($date, $pattern = 'FF')
	{
		switch ($pattern) {
			case 'F':
				return $date->format('j');
			case 'FF':
				return $date->format('d');
			default:
				throw new Exception('The pattern for day in month is "F" or "FF".');
		}
	}

	/**
	 * Get the week in the year.
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return int week in year
	 */
	protected function getWeekInYear($date, $pattern = 'w')
	{
		if ($pattern != 'w') {
			throw new Exception('The pattern for week in year is "w".');
		}

		return $date->format('W');
	}

	/**
	 * Get week in the month.
	 * @param array $date getdate format.
	 * @param string $pattern
	 * @return int week in month
	 */
	protected function getWeekInMonth($date, $pattern = 'W')
	{
		if ($pattern != 'W') {
			throw new Exception('The pattern for week in month is "W".');
		}

		$firstInMonth = clone($date);
		$firstInMonth->setDate($firstInMonth->format('Y'), $firstInMonth->format('m'), 1);
		return $date->format('W') - $firstInMonth->format('W');
	}

	/**
	 * Get the hours [1-24].
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return int hours [1-24]
	 */
	protected function getHourInDay($date, $pattern = 'k')
	{
		if ($pattern != 'k') {
			throw new Exception('The pattern for hour in day is "k".');
		}

		return $date->format('G') + 1;
	}

	/**
	 * Get the hours in AM/PM format, e.g [1-12]
	 * @param array $date getdate format.
	 * @param string $pattern a pattern.
	 * @return int hours in AM/PM format.
	 */
	protected function getHourInAMPM($date, $pattern = 'K')
	{
		if ($pattern != 'K') {
			throw new Exception('The pattern for hour in AM/PM is "K".');
		}

		return $date->format('g') + 1;
	}
}
