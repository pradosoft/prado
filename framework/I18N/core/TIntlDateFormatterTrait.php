<?php

/**
 * TIntlDateFormatterTrait component.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\I18N\core;

use IntlException;

/**
 * TIntlDateFormatterTrait
 *
 * This php trait is included in classes that require \IntlDateFormatter from a
 * culture of a type.  This provides the caching mechanism for the \IntlDateFormatter
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait TIntlDateFormatterTrait
{
	/**
	 * Cached IntlDateFormatter set to the application culture, date format component,
	 * and time format component.
	 * @var array
	 */
	protected static array $formatters = [];


	/**
	 * Returns a cached `IntlDateFormatter` for the given locale, date type, time type,
	 * and optional ICU pattern.
	 *
	 * When `$pattern` is `null`, the formatter uses the locale's default format for the
	 * given `$datetype`/`$timetype` combination. When `$pattern` is a non-empty string,
	 * `$datetype` and `$timetype` are both treated as `IntlDateFormatter::NONE` and the
	 * pattern is applied via `IntlDateFormatter::setPattern()`. Returns `null` when the
	 * `IntlDateFormatter` extension is unavailable.
	 *
	 * @param string $culture BCP 47 locale tag (e.g. `'en_US'`)
	 * @param int $datetype date component format constant (e.g. `\IntlDateFormatter::LONG`)
	 * @param int $timetype time component format constant (e.g. `\IntlDateFormatter::SHORT`)
	 * @param ?string $pattern ICU datetime pattern (e.g. `'MMMM d, yyyy'`); `null` for locale default. @since 4.4.0
	 * @return ?\IntlDateFormatter cached formatter instance, or `null` when unavailable
	 * @see https://www.php.net/manual/en/class.intldateformatter.php
	 */
	protected function getIntlDateFormatter($culture, $datetype, $timetype, $pattern = null)
	{
		if (!class_exists('IntlDateFormatter')) {
			return null;
		}

		$patternKey = $pattern ?? '';

		if (!isset(self::$formatters[$culture][$datetype][$timetype][$patternKey])) {
			$formatter = new \IntlDateFormatter($culture, $datetype, $timetype);
			if ($pattern !== null) {
				$formatter->setPattern($pattern);
			}
			self::$formatters[$culture][$datetype][$timetype][$patternKey] = $formatter;
		}

		return self::$formatters[$culture][$datetype][$timetype][$patternKey];
	}
}
