<?php
/**
 * TDateFromat formatting component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\I18N
 */

namespace Prado\I18N;

use Prado\Prado;
use Prado\Util\TUtf8Converter;

/**
 * To format dates and/or time according to the current locale use
 * <code>
 * <com:TDateFormat Pattern="dd:MMM:yyyy" Value="01/01/2001" />
 *</code>
 * The date will be formatted according to the current locale (or culture)
 * using the format specified by 'Pattern' attribute.
 * The 'Pattern' attribute can also contain two of the predefined presets,
 * the first one for the date part and the second for the time part:
 * 'full', 'long', 'medium', 'short', 'none'.
 * If only one preset is present, it will be used for both the date and the
 * time parts.
 * <code>
 * <com:TDateFormat Pattern="medium long" Value="01/01/2001 15:30:45" />
 * <com:TDateFormat Pattern="full" Value="01/01/2001 15:30:45" />
 *</code>
 *
 * To format date and/or time for a locale (e.g. de_DE) include a Culture
 * attribute, for example:
 * <code>
 * <com:TDateFormat Culture="de_DE" Value="01/01/2001 12:00" />
 * </code>
 * The date will be formatted according to this format.
 *
 * If no Pattern was specified then the date will be formatted with the
 * default format (both date and time). If no value for the date is specified
 * then the current date will be used. E.g.: <code><com:TDateFormat /></code>
 * will result in the current date, formatted with default localized pattern.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\I18N
 */
class TDateFormat extends TI18NControl implements \Prado\IDataRenderer
{
	/**
	 * Cached IntlDateFormatter set to the application culture.
	 * @var IntlDateFormatter
	 */
	protected static $formatters;

	/**
	 * A set of pattern presets and their respective formatting shorthand.
	 * @var array
	 */
	private static $_patternPresets = [
		'fulldate' => \IntlDateFormatter::FULL,
		'full' => \IntlDateFormatter::FULL,
		'fulltime' => \IntlDateFormatter::FULL,
		'longdate' => \IntlDateFormatter::LONG,
		'long' => \IntlDateFormatter::LONG,
		'longtime' => \IntlDateFormatter::LONG,
		'mediumdate' => \IntlDateFormatter::MEDIUM,
		'medium' => \IntlDateFormatter::MEDIUM,
		'mediumtime' => \IntlDateFormatter::MEDIUM,
		'shortdate' => \IntlDateFormatter::SHORT,
		'short' => \IntlDateFormatter::SHORT,
		'shorttime' => \IntlDateFormatter::SHORT,
		'none' => \IntlDateFormatter::NONE,
	];

	/**
	 * Sets the date time formatting pattern.
	 * @param string $value format pattern.
	 */
	public function setPattern($value)
	{
		$this->setViewState('Pattern', $value, '');
	}

	/**
	 * Gets the date time format pattern.
	 * @return string format pattern.
	 */
	public function getPattern()
	{
		return $this->getViewState('Pattern', '');
	}

	/**
	 * For a given string, try and find a preset pattern.
	 * @param string $string the preset pattern name
	 * @return string a preset pattern if found, null otherwise.
	 */
	protected function getPreset($string)
	{
		$string = strtolower($string);
		foreach (self::$_patternPresets as $pattern => $preset) {
			if ($string == $pattern) {
				return $preset;
			}
		}
		return null;
	}

	/**
	 * Get the date-time value for this control.
	 * @return string date time value.
	 */
	public function getValue()
	{
		$value = $this->getViewState('Value', '');
		if (empty($value)) {
			$defaultText = $this->getDefaultText();
			if (empty($defaultText)) {
				return time();
			}
		}
		return $value;
	}

	/**
	 * Set the date-time value for this control.
	 * @param string $value the date-time value.
	 */
	public function setValue($value)
	{
		$this->setViewState('Value', $value, '');
	}

	/**
	 * Get the default text value for this control.
	 * @return string default text value
	 */
	public function getDefaultText()
	{
		return $this->getViewState('DefaultText', '');
	}

	/**
	 * Set the default text value for this control.
	 * @param string $value default text value
	 */
	public function setDefaultText($value)
	{
		$this->setViewState('DefaultText', $value, '');
	}

	/**
	 * Get the date-time value for this control.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getValue()}.
	 * @return string date time value.
	 * @see getValue
	 * @since 3.1.2
	 */
	public function getData()
	{
		return $this->getValue();
	}

	/**
	 * Set the date-time value for this control.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setValue()}.
	 * @param string $value the date-time value.
	 * @see setValue
	 * @since 3.1.2
	 */
	public function setData($value)
	{
		$this->setValue($value);
	}

	/**
	 * Formats the localized number, be it currency or decimal, or percentage.
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * @param string $culture
	 * @param mixed $datetype
	 * @param mixed $timetype
	 * @return NumberFormatter
	 */
	protected function getFormatter($culture, $datetype, $timetype)
	{
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

	/**
	 * Renders the localized version of the date-time value.
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * This method overrides parent's implementation.
	 */
	protected function getFormattedDate()
	{
		$value = $this->getValue();
		$defaultText = $this->getDefaultText();
		if (empty($value) && !empty($defaultText)) {
			return $this->getDefaultText();
		}

		// get a date object from textual value
		if (is_numeric($value)) { //assumes unix epoch
			$value = (float) $value;
		} elseif (is_string($value)) {
			$value = @strtotime($value);
		}

		$date = new \DateTime;
		$date->setTimestamp($value);

		$culture = $this->getCulture();
		$pattern = $this->getPattern();
		$datetype = \IntlDateFormatter::LONG;
		$timetype = \IntlDateFormatter::LONG;

		// try the "date time" pattern format
		if (!empty($pattern)) {
			$subs = explode(' ', $pattern, 2);
			if (count($subs) == 2) {
				$sub0 = $this->getPreset($subs[0]);
				$sub1 = $this->getPreset($subs[1]);

				if ($sub0 !== null && $sub1 !== null) {
					$datetype = $sub0;
					$timetype = $sub1;
					$pattern = null;
				}
			}
		}

		// try the "date" pattern format
		if (!empty($pattern)) {
			$sub = $this->getPreset($pattern);
			if ($sub !== null) {
				$datetype = $sub;
				$timetype = $sub;
				$pattern = null;
			}
		}

		if (empty($pattern)) {
			$formatter = $this->getFormatter($culture, $datetype, $timetype);
		} else {
			$formatter = new \IntlDateFormatter($culture, $datetype, $timetype);
			$formatter->setPattern($pattern);
		}

		$result = $formatter->format($date);

		return TUtf8Converter::fromUTF8($result, $this->getCharset());
	}

	public function render($writer)
	{
		$writer->write($this->getFormattedDate());
	}
}
