<?php
/**
 * TNumberFromat component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\I18N
 */

namespace Prado\I18N;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Util\TUtf8Converter;

/**
 * To format numbers in locale sensitive manner use
 * <code>
 * <com:TNumberFormat Pattern="0.##" value="2.0" />
 * </code>
 *
 * The format used for numbers can be selected by specifying the Type attribute.
 * The known types are "decimal", "currency", "percentage", "scientific",
 * "spellout", "ordinal" and "duration"
 *
 * If someone from US want to see sales figures from a store in
 * Germany (say using the EURO currency), formatted using the german
 * currency, you would need to use the attribute Culture="de_DE" to get
 * the currency right, e.g. 100,00. The decimal and grouping separator is
 * then also from the de_DE locale. This may lead to some confusion because
 * people from US know the "," as thousand separator. Therefore a "Currency"
 * attribute is available, so that the output from the following example
 * results in 100.00.
 * <code>
 * <com:TNumberFormat Type="currency" Culture="en_US" Currency="EUR" Value="100" />
 * </code>
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\I18N
 */
class TNumberFormat extends TI18NControl implements \Prado\IDataRenderer
{
	/**
	 * Cached NumberFormatters set to the application culture.
	 * @var NumberFormatter
	 */
	protected static $formatters;

	/**
	 * Get the number formatting pattern.
	 * @return string format pattern.
	 */
	public function getPattern()
	{
		return $this->getViewState('Pattern', '');
	}

	/**
	 * Set the number format pattern.
	 * @param string $pattern format pattern.
	 */
	public function setPattern($pattern)
	{
		$this->setViewState('Pattern', $pattern, '');
	}

	/**
	 * Get the numberic value for this control.
	 * @return string number
	 */
	public function getValue()
	{
		return $this->getViewState('Value', '');
	}

	/**
	 * Set the numberic value for this control.
	 * @param string $value the number value
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
	 * Get the numberic value for this control.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getValue()}.
	 * @return string number
	 * @see getValue
	 * @since 3.1.2
	 */
	public function getData()
	{
		return $this->getValue();
	}

	/**
	 * Set the numberic value for this control.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setValue()}.
	 * @param string $value the number value
	 * @see setValue
	 * @since 3.1.2
	 */
	public function setData($value)
	{
		$this->setValue($value);
	}

	/**
	 * Get the formatting type for this control.
	 * @return string formatting type.
	 */
	public function getType()
	{
		return $this->getViewState('Type', \NumberFormatter::DECIMAL);
	}

	/**
	 * Set the formatting type for this control.
	 * @param string $type formatting type, either "decimal", "currency", "percentage", "scientific", "spellout", "ordinal" or "duration"
	 * @throws TPropertyTypeInvalidException
	 */
	public function setType($type)
	{
		$type = strtolower($type);

		switch ($type) {
			case 'decimal':
				$this->setViewState('Type', \NumberFormatter::DECIMAL); break;
			case 'currency':
				$this->setViewState('Type', \NumberFormatter::CURRENCY); break;
			case 'percentage':
				$this->setViewState('Type', \NumberFormatter::PERCENT); break;
			case 'scientific':
				$this->setViewState('Type', \NumberFormatter::SCIENTIFIC); break;
			case 'spellout':
				$this->setViewState('Type', \NumberFormatter::SPELLOUT); break;
			case 'ordinal':
				$this->setViewState('Type', \NumberFormatter::ORDINAL); break;
			case 'duration':
				$this->setViewState('Type', \NumberFormatter::DURATION); break;
			default:
				throw new TInvalidDataValueException('numberformat_type_invalid', $type);
		}
	}

	/**
	 * @return string 3 letter currency code. Defaults to 'USD'.
	 */
	public function getCurrency()
	{
		return $this->getViewState('Currency', 'USD');
	}

	/**
	 * Set the 3-letter ISO 4217 code. For example, the code
	 * "USD" represents the US Dollar and "EUR" represents the Euro currency.
	 * @param string $currency currency code.
	 */
	public function setCurrency($currency)
	{
		$this->setViewState('Currency', $currency, '');
	}

	/**
	 * Formats the localized number, be it currency or decimal, or percentage.
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * @param string $culture
	 * @param mixed $type
	 * @return NumberFormatter
	 */
	protected function getFormatter($culture, $type)
	{
		if (!isset(self::$formatters[$culture])) {
			self::$formatters[$culture] = [];
		}
		if (!isset(self::$formatters[$culture][$type])) {
			self::$formatters[$culture][$type] = new \NumberFormatter($culture, $type);
		}

		return self::$formatters[$culture][$type];
	}

	/**
	 * Formats the localized number, be it currency or decimal, or percentage.
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * @return string formatted number
	 */
	protected function getFormattedValue()
	{
		$value = $this->getValue();
		$defaultText = $this->getDefaultText();
		if (empty($value) && !empty($defaultText)) {
			return $this->getDefaultText();
		}

		$culture = $this->getCulture();
		$type = $this->getType();
		$pattern = $this->getPattern();

		if (empty($pattern)) {
			$formatter = $this->getFormatter($culture, $type);
		} else {
			$formatter = new \NumberFormatter($culture, \NumberFormatter::PATTERN_DECIMAL);
			$formatter->setPattern($pattern);
		}

		if ($type === \NumberFormatter::CURRENCY) {
			$result = $formatter->formatCurrency($value, $this->getCurrency());
		} else {
			$result = $formatter->format($value);
		}

		return TUtf8Converter::fromUTF8($result, $this->getCharset());
	}

	public function render($writer)
	{
		$writer->write($this->getFormattedValue());
	}
}
