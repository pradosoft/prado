<?php

/**
 * TSimpleDateFormatter class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\I18N\core\TIntlDateFormatterTrait;
use Prado\Util\TUtf8Converter;

/**
 * TSimpleDateFormatter class.
 *
 * Formats and parses dates using the SimpleDateFormat pattern.
 * This pattern is compatible with the I18N and java's SimpleDateFormatter.
 * ```
 * Pattern |      Description
 * ----------------------------------------------------
 * d       | Day of month 1 to 31, no padding
 * dd      | Day of month 01 to 31, zero padding
 * M       | Month 1 to 12, no padding
 * MM      | Month 01 to 12, zero padding
 * MMM     | Month abbreviated (Jan, Feb, etc.)
 * MMMM    | Full month name (January, etc.)
 * y       | Year (2 or 4 digits)
 * yy      | 2 digit year, e.g., 96, 05
 * yyyy    | 4 digit year, e.g., 2005
 * H       | Hour 0-23, no padding
 * HH      | Hour 00-23, zero padding
 * k       | Hour 1-24, no padding
 * kk      | Hour 01-24, zero padding
 * h       | Hour 1-12 (am/pm), no padding
 * hh      | Hour 01-12 (am/pm), zero padding
 * K       | Hour 0-11 (am/pm), no padding
 * KK      | Hour 00-11 (am/pm), zero padding
 * m       | Minute 0-59, no padding
 * mm      | Minute 00-59, zero padding
 * s       | Second 0-59, no padding
 * ss      | Second 00-59, zero padding
 * S       | Fractional second, 1 digit, tenths
 * SS      | Fractional second, 2 digits, hundredths
 * SSS     | Fractional second, 3 digits, milliseconds
 * SSSS    | Fractional second, 4 digits,
 * SSSSS   | Fractional second, 5 digits,
 * SSSSSS  | Fractional second, 6 digits, microseconds
 * a       | AM/PM marker
 * E       | Day abbreviation (Mon, Tue)
 * EEEE    | Full day name (Monday, Tuesday)
 * D       | Day in year (1-366)
 * F       | Day of week in month
 * w       | Week in year
 * W       | Week in month
 * ----------------------------------------------------
 * ```
 *
 * Text literals can be included in patterns using single quotes.
 * The quoted text is preserved in formatted output and matched during parsing.
 * To include a literal single quote, use two single quotes ('').
 * ```php
 * $formatter = new TSimpleDateFormatter("yyyy 'Year' MM 'Month' dd 'Day'");
 * echo $formatter->format(time()); // "2026 Year 04 Month 17 Day"
 * ```
 *
 * Parsing behavior:
 * - Supports Java style Date Time patterns. (see link below)
 * - Integer or float timestamps are returned unchanged.
 * - Locally Aware Months and Days of the Week.
 * - Literal text when quoted with `'`.
 * - Missing components default to the current date/time (unless $defaultToCurrentTime is false).
 * - Two-digit years 00-70 become 2000-2070, 71-99 become 1971-1999.
 * - Invalid dates (e.g., Feb 30) return null.
 * - DateTimeInterface objects (including DateTimeImmutable) are passed through.
 * - Supports fractions of seconds up to microsecond precision.
 *
 * Usage example, to format a date
 * ```php
 * $formatter = new TSimpleDateFormatter("dd/MM/yyyy");
 * echo $formatter->format(time());
 * ```
 *
 * To parse the date string into a date timestamp.
 * ```php
 * $formatter = new TSimpleDateFormatter("d-M-yyyy");
 * echo $formatter->parse("24-6-2005");
 * ```
 *
 * To format with fractional seconds and locale-aware month names:
 * ```
 * $formatter = new TSimpleDateFormatter("MMMM d, yyyy 'at' HH:mm:ss.SSS", "UTF-8", "de_DE");
 * echo $formatter->format($dateTimeWithMicroseconds);
 * ```
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com> 2026 update
 * @since 3.0
 * @see https://docs.oracle.com/javase/8/docs/api/java/text/SimpleDateFormat.html
 */
class TSimpleDateFormatter
{
	use TIntlDateFormatterTrait;

	/**
	 * Formatting pattern.
	 * @var string
	 */
	private $pattern;

	/**
	 * Charset, default is 'UTF-8'
	 * @var string
	 */
	private $charset = 'UTF-8';

	/**
	 * The culture of the date formatter. default to invariant
	 * @var string
	 */
	private $culture = '';

	/**
	 * Constructor, create a new date time formatter.
	 * @param string $pattern formatting pattern.
	 * @param string $charset pattern and value charset
	 * @param null|mixed $culture
	 */
	public function __construct($pattern, $charset = 'UTF-8', $culture = null)
	{
		$this->setPattern($pattern);
		$this->setCharset($charset);
		if ($culture !== null) {
			$this->setCulture($culture);
		}
	}

	/**
	 * @return string formatting pattern.
	 */
	public function getPattern()
	{
		return $this->pattern;
	}

	/**
	 * @param string $pattern formatting pattern.
	 */
	public function setPattern($pattern)
	{
		$this->pattern = $pattern;
	}

	/**
	 * @return string formatting charset.
	 */
	public function getCharset()
	{
		return $this->charset;
	}

	/**
	 * @param string $charset formatting charset.
	 */
	public function setCharset($charset)
	{
		$this->charset = $charset;
	}

	/**
	 * @return string culture
	 */
	public function getCulture()
	{
		return $this->culture;
	}

	/**
	 * @param string $value culture
	 */
	public function setCulture($value)
	{
		$this->culture = (string) $value;
	}

	/**
	 * Format the date according to the pattern.
	 * Uses IntlDateFormatter for internationalization when culture is set.
	 * Falls back to PHP DateTime for patterns not supported by ICU (e.g., k/kk hour formats).
	 * @param int|string $value the date to format, either integer or a string readable by strtotime.
	 * @return string formatted date.
	 */
	public function format($value)
	{
		$dt = $this->getDate($value);
		$culture = $this->getCulture();

		$map = [
			'yyyy' => 'Y', 'yy' => 'y', 'y' => 'Y',
			'MMMM' => 'F', 'MMM' => 'M', 'MM' => 'm', 'M' => 'n',
			'dd' => 'd', 'd' => 'j',
			'kk' => 'G', 'k' => 'G',
			'KK' => 'H', 'K' => 'H',
			'HH' => 'H', 'H' => 'G',
			'hh' => 'h', 'h' => 'g',
			'mm' => 'i',
			'ss' => 's',
			'a' => 'A',
			'D' => 'z',
			'F' => 't',
			'w' => 'W',
			'W' => 'W',
		];

		$bits = [];
		foreach ($map as $pattern => $phpFormat) {
			$bits[$pattern] = $dt->format($phpFormat);
		}

		$bits['m'] = $dt->format('i');
		if (strlen($bits['m']) > 1 && $bits['m'][0] === '0') {
			$bits['m'] = substr($bits['m'], 1);
		}

		$bits['s'] = $dt->format('s');
		if (strlen($bits['s']) > 1 && $bits['s'][0] === '0') {
			$bits['s'] = substr($bits['s'], 1);
		}

		$kHour = (int) $dt->format('G');
		$kHour = $kHour === 0 ? 1 : $kHour;
		$bits['k'] = (string) $kHour;
		$bits['kk'] = sprintf('%02d', $kHour);

		$KHour = (int) $dt->format('G');
		$KHour = $KHour > 11 ? $KHour - 12 : $KHour;
		$bits['K'] = (string) $KHour;
		$bits['KK'] = sprintf('%02d', $KHour);

		$bits['E'] = $dt->format('D');
		$bits['EEEE'] = $dt->format('l');

		$sortedTokens = $bits;
		uksort($sortedTokens, function ($a, $b) {
			return $this->length($b) - $this->length($a);
		});

		$pattern = $this->pattern;
		$result = '';
		$idx = 0;
		$pattern_length = $this->length($pattern);
		while ($idx < $pattern_length) {
			if ($this->charAt($pattern, $idx) === "'") {
				$idx++;
				while ($idx < $pattern_length && $this->charAt($pattern, $idx) !== "'") {
					$result .= $this->charAt($pattern, $idx);
					$idx++;
				}
				if ($idx < $pattern_length) {
					$idx++;
				}
				continue;
			}
			if ($this->charAt($pattern, $idx) === 'S') {
				$sCount = 0;
				while ($idx + $sCount < $pattern_length && $this->charAt($pattern, $idx + $sCount) === 'S') {
					$sCount++;
				}
				$microseconds = (int) $dt->format('u');

				if ($sCount < 6) {
					$divisor = pow(10, 6 - $sCount);
					$value = (string) round($microseconds / $divisor);
				} else {
					$value = substr(sprintf('%06d', $microseconds), 0, $sCount);
				}
				$result .= $value;
				$idx += $sCount;
				continue;
			}
			$matched = false;
			foreach ($sortedTokens as $token => $replacement) {
				$tokenLen = $this->length($token);
				if ($idx + $tokenLen <= $pattern_length && $this->substring($pattern, $idx, $tokenLen) === $token) {
					$result .= $replacement;
					$idx += $tokenLen;
					$matched = true;
					break;
				}
			}
			if (!$matched) {
				$result .= $this->charAt($pattern, $idx);
				$idx++;
			}
		}

		$result = $this->applyCultureLocalization($result, $culture);

		return $result;
	}

	/**
	 * Apply culture-specific month and day name localization.
	 * @param string $result formatted result
	 * @param string $culture culture code
	 * @return string result with localized month/day names
	 */
	private function applyCultureLocalization($result, $culture)
	{
		$hasLocalizedMonth = preg_match('/M{3,4}/', $this->pattern) === 1;
		$hasLocalizedWeekday = strpos($this->pattern, 'EEEE') !== false || (strpos($this->pattern, 'E') !== false && strpos($this->pattern, 'EEEE') === false);

		if ($hasLocalizedMonth) {
			$monthNames = $this->getLocalizedMonthNames($culture, $this->getMonthPattern() === 'MMM' ? 'short' : 'full');
			if ($monthNames) {
				$englishMonths = $this->getLocalizedMonthNames('en', $this->getMonthPattern() === 'MMM' ? 'short' : 'full');
				foreach ($englishMonths as $idx => $engMonth) {
					$result = str_replace($engMonth, $monthNames[$idx], $result);
				}
			}
		}

		if ($hasLocalizedWeekday) {
			$weekdayNames = $this->getLocalizedWeekdayNames($culture, strpos($this->pattern, 'EEEE') !== false ? 'full' : 'short');
			if ($weekdayNames) {
				$englishWeekdays = $this->getLocalizedWeekdayNames('en', strpos($this->pattern, 'EEEE') !== false ? 'full' : 'short');
				foreach ($englishWeekdays as $idx => $engDay) {
					$result = str_replace($engDay, $weekdayNames[$idx], $result);
				}
			}
		}

		return $result;
	}

	/**
	 * Get the month pattern from the formatting pattern.
	 * @return false|string M, MM, MMM, or MMMM if found, false otherwise.
	 */
	public function getMonthPattern()
	{
		if (is_int(strpos($this->pattern, 'MMMM'))) {
			return 'MMMM';
		}
		if (is_int(strpos($this->pattern, 'MMM'))) {
			return 'MMM';
		}
		if (is_int(strpos($this->pattern, 'MM'))) {
			return 'MM';
		}
		if (is_int(strpos($this->pattern, 'M'))) {
			return 'M';
		}
		return false;
	}

	/**
	 * Get the day pattern from the formatting pattern.
	 * @return false|string d or dd if found, false otherwise.
	 */
	public function getDayPattern()
	{
		if (is_int(strpos($this->pattern, 'dd'))) {
			return 'dd';
		}
		if (is_int(strpos($this->pattern, 'd'))) {
			return 'd';
		}
		return false;
	}

	/**
	 * Get the year pattern from the formatting pattern.
	 * @return false|string yy or yyyy if found, false otherwise.
	 */
	public function getYearPattern()
	{
		if (is_int(strpos($this->pattern, 'yyyy'))) {
			return 'yyyy';
		}
		if (is_int(strpos($this->pattern, 'yy'))) {
			return 'yy';
		}
		return false;
	}

	/**
	 * Get the day, month, year ordering based on their positions in the pattern.
	 * @return array list of day, month, year in their pattern order.
	 */
	public function getDayMonthYearOrdering()
	{
		$ordering = [];
		if (is_int($day = strpos($this->pattern, 'd'))) {
			$ordering['day'] = $day;
		}
		if (is_int($month = strpos($this->pattern, 'M'))) {
			$ordering['month'] = $month;
		}
		if (is_int($year = strpos($this->pattern, 'yy'))) {
			$ordering['year'] = $year;
		}
		asort($ordering);
		return array_keys($ordering);
	}

	/**
	 * Gets the time stamp from string or integer.
	 * @param int|string $value date to parse
	 * @return \DateTime date info
	 */
	private function getDate($value)
	{
		if ($value instanceof \DateTimeInterface) {
			return $value;
		}
		if (is_numeric($value)) {
			$date = new \DateTime();
			$date->setTimeStamp($value);
		} else {
			$date = new \DateTime($value);
		}
		return $date;
	}

	/**
	 * Validate if a date string matches the pattern and is a valid date.
	 * @param mixed $value date string to validate.
	 * @return bool true if valid and matches pattern, false otherwise.
	 */
	public function isValidDate($value)
	{
		return $this->parse($value, false) !== null;
	}

	/**
	 * Parse the string according to the pattern.
	 * @param int|string $value date string or integer to parse
	 * @param bool $defaultToCurrentTime
	 * @throws TInvalidDataValueException if date string is malformed.
	 * @return ?int date time stamp, null when $defaultToCurrentTime is false
	 */
	public function parse($value, $defaultToCurrentTime = true)
	{
		$result = $this->parseExact($value, $defaultToCurrentTime);
		return $result !== null ? (int) floor($result) : null;
	}


	/**
	 * Parse the string according to the pattern.
	 * @param int|string $value date string or integer to parse
	 * @param bool $defaultToCurrentTime
	 * @throws TInvalidDataValueException if date string is malformed.
	 * @return ?float exact date time stamp, null when $defaultToCurrentTime is false
	 */
	public function parseExact($value, $defaultToCurrentTime = true): ?float
	{
		if (is_int($value)) {
			return $value;
		}
		if (is_float($value)) {
			return (int) $value;
		}
		if (!is_string($value)) {
			throw new TInvalidDataValueException('date_to_parse_must_be_string');
		}
		if (empty($this->pattern)) {
			return time();
		}
		if ($this->length(trim($value)) < 1) {
			return $defaultToCurrentTime ? time() : null;
		}

		$hasLocalizedMonth = preg_match('/M{3,4}/', $this->pattern) === 1;
		$hasLocalizedWeekday = strpos($this->pattern, 'EEEE') !== false || (strpos($this->pattern, 'E') !== false && strpos($this->pattern, 'EEEE') === false);

		if ($hasLocalizedMonth || $hasLocalizedWeekday) {
			$culture = $this->getCulture() ?: 'en';

			if ($hasLocalizedMonth) {
				$monthNames = $this->getLocalizedMonthNames($culture, $this->getMonthPattern() === 'MMM' ? 'short' : 'full');
				if ($monthNames) {
					$monthIdx = $this->findStringInArray($value, $monthNames);
					if ($monthIdx !== null) {
						$month = $monthIdx + 1;
						$escaped = preg_quote($monthNames[$monthIdx], '/');
						$value = preg_replace('/' . $escaped . '/', sprintf('%02d', $month), $value, 1);
					}
				}
			}

			if ($hasLocalizedWeekday) {
				$weekdayNames = $this->getLocalizedWeekdayNames($culture, strpos($this->pattern, 'EEEE') !== false ? 'full' : 'short');
				if ($weekdayNames) {
					$weekdayIdx = $this->findStringInArray($value, $weekdayNames);
					if ($weekdayIdx !== null) {
						$escaped = preg_quote($weekdayNames[$weekdayIdx], '/');
						$placeholder = str_repeat('?', $this->length($weekdayNames[$weekdayIdx]));
						$value = preg_replace('/' . $escaped . '/', $placeholder, $value, 1);
					}
				}
			}
		}

		$i_val = 0;
		$i_format = 0;
		$pattern_length = $this->length($this->pattern);
		$year = $month = $day = $hour = $minute = $second = null;
		$ampm = null;
		$hourMode = null;
		$fraction = 0.0;

		while ($i_format < $pattern_length) {
			if ($this->charAt($this->pattern, $i_format) === "'") {
				$startQuote = $i_format + 1;
				$i_format++;
				while ($i_format < $pattern_length && $this->charAt($this->pattern, $i_format) !== "'") {
					$i_format++;
				}
				$literalText = $this->substring($this->pattern, $startQuote, $i_format - $startQuote);
				if ($this->substring($value, $i_val, $this->length($literalText)) !== $literalText) {
					return null;
				}
				$i_val += $this->length($literalText);
				if ($i_format < $pattern_length) {
					$i_format++;
				}
				continue;
			}

			$c = $this->charAt($this->pattern, $i_format);
			$token = '';
			while ($i_format < $pattern_length && $this->charAt($this->pattern, $i_format) === $c) {
				$token .= $this->charAt($this->pattern, $i_format++);
			}

			switch ($token) {
				case 'yyyy':
					$year = $this->getInteger($value, $i_val, 4, 4);
					if ($year === null) {
						return null;
					}
					$i_val += $this->length($year);
					break;
				case 'yy':
					$year = $this->getInteger($value, $i_val, 2, 2);
					if ($year === null) {
						return null;
					}
					$i_val += $this->length($year);
					$yInt = (int) $year;
					$year = ($yInt > 70) ? $yInt + 1900 : $yInt + 2000;
					break;
				case 'y':
					$year = $this->getInteger($value, $i_val, 2, 4);
					if ($year === null) {
						return null;
					}
					$i_val += $this->length($year);
					if ($this->length($year) <= 2) {
						$yInt = (int) $year;
						$year = ($yInt > 70) ? $yInt + 1900 : $yInt + 2000;
					}
					break;
				case 'MMMM': case 'MMM': case 'MM': case 'M':
					$monthMinLen = ($token === 'MMMM' || $token === 'MMM') ? 1 : $this->length($token);
					$month = $this->getInteger($value, $i_val, $monthMinLen, 2);
					if ($month === null || $month < 1 || $month > 12) {
						return null;
					}
					$i_val += $this->length((string) $month);
					break;
				case 'dd': case 'd':
					$day = $this->getInteger($value, $i_val, $this->length($token), 2);
					if ($day === null || $day < 1 || $day > 31) {
						return null;
					}
					$i_val += $this->length($day);
					break;
				case 'kk': case 'k':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour < 1 || $hour > 24) {
						return null;
					}
					$i_val += $this->length($hour);
					$hourMode = 'k';
					break;
				case 'HH': case 'H':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour > 23) {
						return null;
					}
					$i_val += $this->length($hour);
					$hourMode = 'H';
					break;
				case 'KK': case 'K':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour > 11) {
						return null;
					}
					$i_val += $this->length($hour);
					$hourMode = 'K';
					break;
				case 'hh': case 'h':
					$hour = $this->getInteger($value, $i_val, 1, 2);
					if ($hour === null || $hour < 1 || $hour > 12) {
						return null;
					}
					$i_val += $this->length($hour);
					$hourMode = 'h';
					break;
				case 'mm': case 'm':
					$minute = $this->getInteger($value, $i_val, 1, 2);
					if ($minute === null || $minute > 59) {
						return null;
					}
					$i_val += $this->length($minute);
					break;
				case 'ss': case 's':
					$second = $this->getInteger($value, $i_val, 1, 2);
					if ($second === null || $second > 59) {
						return null;
					}
					$i_val += $this->length($second);
					break;
				case 'S':
					$fractionLen = 1;
					$fraction = $this->getInteger($value, $i_val, 1, 1);
					if ($fraction === null) {
						return null;
					}
					$fraction = (int) $fraction;
					$fraction = $fraction / 10;
					$i_val += $fractionLen;
					break;
				case 'SS':
					$fractionLen = 2;
					$fraction = $this->getInteger($value, $i_val, 2, 2);
					if ($fraction === null) {
						return null;
					}
					$fraction = (int) $fraction;
					$fraction = $fraction / 100;
					$i_val += $fractionLen;
					break;
				case 'SSS':
					$fractionLen = 3;
					$fraction = $this->getInteger($value, $i_val, 3, 3);
					if ($fraction === null) {
						return null;
					}
					$fraction = (int) $fraction;
					$fraction = $fraction / 1000;
					$i_val += $fractionLen;
					break;
				case 'SSSS':
					$fractionLen = 4;
					$fraction = $this->getInteger($value, $i_val, 4, 4);
					if ($fraction === null) {
						return null;
					}
					$fraction = (int) $fraction;
					$fraction = $fraction / 10000;
					$i_val += $fractionLen;
					break;
				case 'SSSSS':
					$fractionLen = 5;
					$fraction = $this->getInteger($value, $i_val, 5, 5);
					if ($fraction === null) {
						return null;
					}
					$fraction = (int) $fraction;
					$fraction = $fraction / 100000;
					$i_val += $fractionLen;
					break;
				case 'SSSSSS':
					$fractionLen = 6;
					$fraction = $this->getInteger($value, $i_val, 6, 6);
					if ($fraction === null) {
						return null;
					}
					$fraction = (int) $fraction;
					$fraction = $fraction / 1000000;
					$i_val += $fractionLen;
					break;
				case 'D':
					$dayInYear = $this->getInteger($value, $i_val, 1, 3);
					if ($dayInYear === null) {
						return null;
					}
					$i_val += $this->length($dayInYear);
					break;
				case 'a':
					$sub = $this->substring($value, $i_val, 2);
					$ampm = strtoupper($sub);
					if ($ampm !== 'AM' && $ampm !== 'PM') {
						return null;
					}
					$i_val += 2;
					break;
				case 'EEEE': case 'E':
					$placeholderLen = $this->length($value);
					while ($placeholderLen > 0) {
						$check = $this->substring($value, $i_val, $placeholderLen);
						if ($check !== null) {
							$allQuestion = true;
							for ($j = 0; $j < $placeholderLen; $j++) {
								$c = $this->substring($check, $j, 1);
								if ($c !== '?') {
									$allQuestion = false;
									break;
								}
							}
							if ($allQuestion) {
								$i_val += $placeholderLen;
								break;
							}
						}
						$placeholderLen--;
					}
					if ($placeholderLen <= 0) {
						$sub = $this->substring($value, $i_val, 1);
						if ($sub === null || $this->length($sub) < 1) {
							return null;
						}
						$i_val += $this->length($sub);
					}
					break;
				default:
					if ($this->substring($value, $i_val, $this->length($token)) !== $token) {
						return null;
					}
					$i_val += $this->length($token);
					break;
			}
		}

		if ($i_val != $this->length($value)) {
			return null;
		}

		$year = ($year === null) ? (int) date('Y') : (int) $year;
		$month = ($month === null) ? ($defaultToCurrentTime ? (int) date('m') : 1) : (int) $month;
		$day = ($day === null) ? ($defaultToCurrentTime ? (int) date('d') : 1) : (int) $day;

		if (!checkdate($month, $day, $year)) {
			return null;
		}

		$hour = $hour !== null ? (int) $hour : 0;

		if ($hourMode === 'k') {
			$hour = $hour - 1;
		} elseif ($hourMode === 'K') {
		} elseif ($hourMode === 'h' && $ampm !== null) {
			if ($ampm === 'PM' && $hour < 12) {
				$hour += 12;
			}
			if ($ampm === 'AM' && $hour === 12) {
				$hour = 0;
			}
		} elseif ($ampm !== null) {
			if ($ampm === 'PM' && $hour < 12) {
				$hour += 12;
			}
			if ($ampm === 'AM' && $hour === 12) {
				$hour = 0;
			}
		}

		$s = new \DateTime();
		$s->setDate($year, $month, $day);
		$s->setTime($hour, (int) $minute, (int) $second);
		return $s->getTimestamp() + $fraction;
	}

	/**
	 * Calculate the length of a string using iconv_strlen.
	 * @param mixed $string
	 * @return int
	 */
	private function length($string)
	{
		return iconv_strlen($string, $this->getCharset());
	}

	/**
	 * Get the char at a position.
	 * @param mixed $string
	 * @param mixed $pos
	 * @return string
	 */
	private function charAt($string, $pos)
	{
		return $this->substring($string, $pos, 1);
	}

	/**
	 * Gets a portion of a string using iconv_substr.
	 * @param mixed $string
	 * @param mixed $start
	 * @param mixed $length
	 * @return string
	 */
	private function substring($string, $start, $length)
	{
		return iconv_substr($string, $start, $length, $this->getCharset());
	}

	/**
	 * Returns true if char at position equals a particular char.
	 * @param mixed $string
	 * @param mixed $pos
	 * @param mixed $char
	 */
	private function charEqual($string, $pos, $char)
	{
		return $this->charAt($string, $pos) == $char;
	}

	/**
	 * Get integer from a string at a given position.
	 * @param string $str string to extract integer from.
	 * @param int $i starting position.
	 * @param int $minlength minimum length of integer.
	 * @param int $maxlength maximum length of integer.
	 * @return false|string integer string if found, false otherwise.
	 */
	private function getInteger($str, $i, $minlength, $maxlength)
	{
		//match for digits backwards
		for ($x = $maxlength; $x >= $minlength; $x--) {
			$token = $this->substring($str, $i, $x);
			if ($this->length($token) < $minlength) {
				return null;
			}
			if (preg_match('/^\d+$/', $token)) {
				return $token;
			}
		}
		return null;
	}

	/**
	 * Get localized weekday names for a culture using IntlDateFormatter.
	 * @param string $culture the culture code
	 * @param string $type 'short' for abbreviated, 'full' for full names
	 * @return null|array array of weekday names (0=Sunday) or null if unavailable
	 * @since 4.3.3
	 */
	private function getLocalizedWeekdayNames($culture, $type = 'full')
	{
		$formatter = $this->getIntlDateFormatter($culture, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		if (!$formatter) {
			return null;
		}

		$weekdayNames = [];
		$date = new \DateTime('2026-04-05');
		$pattern = $type === 'short' ? 'EEE' : 'EEEE';
		for ($d = 0; $d < 7; $d++) {
			$formatter->setPattern($pattern);
			$weekdayNames[$d] = $formatter->format($date);
			$date->modify('+1 day');
		}
		return $weekdayNames;
	}

	/**
	 * Get localized month names for a culture using IntlDateFormatter.
	 * @param string $culture the culture code
	 * @param string $type 'short' for abbreviated, 'full' for full names
	 * @return null|array array of month names (0-indexed) or null if unavailable
	 * @since 4.3.3
	 */
	private function getLocalizedMonthNames($culture, $type = 'full')
	{
		$formatter = $this->getIntlDateFormatter($culture, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
		if (!$formatter) {
			return null;
		}

		$monthNames = [];
		$date = new \DateTime('2026-01-15');
		for ($m = 0; $m < 12; $m++) {
			$date->setDate(2026, $m + 1, 15);
			$pattern = $type === 'short' ? 'MMM' : 'MMMM';
			$formatter->setPattern($pattern);
			$monthNames[$m] = $formatter->format($date);
		}
		return $monthNames;
	}

	/**
	 * Find the localized month or weekday index in the value string.
	 * @param string $value the date string to search
	 * @param array $stringArray array of localized names
	 * @return null|int index or null if not found
	 * @since 4.3.3
	 */
	private function findStringInArray($value, $stringArray)
	{
		foreach ($stringArray as $idx => $name) {
			if (strpos($value, $name) !== false) {
				return $idx;
			}
		}
		return null;
	}
}
