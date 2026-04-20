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
	 * Formats the localized date and/or time
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * @param string $culture The Culture to get the format information about.
	 * @param int $datetype The format of the date components, eg \IntlDateFormatter::LONG
	 * @param int $timetype The format of the date components, eg \IntlDateFormatter::SHORT
	 * @return \IntlDateFormatter
	 * @see Format Types - Constants @ https://www.php.net/manual/en/class.intldateformatter.php
	 */
	protected function getIntlDateFormatter($culture, $datetype, $timetype)
	{
		if (!class_exists('IntlDateFormatter')) {
			return null;
		}

		if (!isset(self::$formatters[$culture])) {
			self::$formatters[$culture] = [];
		}
		if (!isset(self::$formatters[$culture][$datetype])) {
			self::$formatters[$culture][$datetype] = [];
		}
		if (!isset(self::$formatters[$culture][$datetype][$timetype])) {
			self::$formatters[$culture][$datetype][$timetype] = new \IntlDateFormatter($culture, $datetype, $timetype);
		}

		return self::$formatters[$culture][$datetype][$timetype];
	}
}
