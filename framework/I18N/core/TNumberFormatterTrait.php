<?php

/**
 * TNumberFormatterTrait component.
 *
 * @author Brad Anderson <belisoful[at]icloud[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\I18N\core;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Util\TUtf8Converter;
use IntlException;

/**
 * TNumberFormatterTrait
 *
 *	This php trait is included in classes that require \NumberFormatter from
 * a culture of a type.  This provides the caching mechanism for the \NumberFormatter
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.3.3
 */
trait TNumberFormatterTrait
{
	/**
	 * Cached NumberFormatters set to the application culture.
	 * @var array
	 */
	protected static array $formatters = [];

	/**
	 * Formats the localized number, be it currency or decimal, or percentage.
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * @param string $culture The Culture to get the format information about.
	 * @param int $format The format of the numbers, eg \NumberFormatter::PERCENT
	 * @throws \IntlException when format is bad.
	 * @return null|\NumberFormatter
	 */
	protected function getFormatter($culture, $format)
	{
		if (!class_exists('NumberFormatter')) {
			return null;
		}

		if (!isset(self::$formatters[$culture])) {
			self::$formatters[$culture] = [];
		}
		if (!isset(self::$formatters[$culture][$format])) {
			self::$formatters[$culture][$format] = new \NumberFormatter($culture, $format);
		}

		return self::$formatters[$culture][$format];
	}
}
