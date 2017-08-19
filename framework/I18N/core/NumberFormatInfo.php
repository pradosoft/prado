<?php

/**
 * NumberFormatInfo class file.
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
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\I18N\core
 */

namespace Prado\I18N\core;
use Exception;

/**
 * NumberFormatInfo class
 *
 * Defines how numeric values are formatted and displayed,
 * depending on the culture. Numeric values are formatted using
 * standard or custom patterns stored in the properties of a
 * NumberFormatInfo.
 *
 * This class contains information, such as currency, decimal
 * separators, and other numeric symbols.
 *
 * To create a NumberFormatInfo for a specific culture,
 * create a CultureInfo for that culture and retrieve the
 * CultureInfo->NumberFormat property. Or use
 * NumberFormatInfo::getInstance($culture).
 * To create a NumberFormatInfo for the invariant culture, use the
 * InvariantInfo::getInvariantInfo().
 *
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\I18N\core
 */
class NumberFormatInfo
{
	/**
	 * Parent instance containing data
	 * @var CultureInfo
	 */
	private $cultureInfo = array();

	/**
	 * ICU number formatting data.
	 * @var array
	 */
	private $data = [];

	/**
	 * A list of properties that are accessable/writable.
	 * @var array
	 */
	protected $properties = [];

	/**
	 * The number pattern.
	 * @var array
	 */
	protected $pattern = [];

	const DECIMAL = 'decimalFormat';
	const CURRENCY = 'currencyFormat';
	const PERCENTAGE = 'percentFormat';
	const SCIENTIFIC = 'scientificFormat';
	const ACCOUNTING = 'accountingFormat';

	/**
	 * Allow functions that begins with 'set' to be called directly
	 * as an attribute/property to retrieve the value.
	 * @param mixed $name
	 * @return mixed
	 */
	public function __get($name)
	{
		$getProperty = 'get' . $name;
		if (in_array($getProperty, $this->properties)) {
			return $this->$getProperty();
		} else {
			throw new Exception('Property ' . $name . ' does not exists.');
		}
	}

	/**
	 * Allow functions that begins with 'set' to be called directly
	 * as an attribute/property to set the value.
	 * @param mixed $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$setProperty = 'set' . $name;
		if (in_array($setProperty, $this->properties)) {
			$this->$setProperty($value);
		} else {
			throw new Exception('Property ' . $name . ' can not be set.');
		}
	}

	/**
	 * Initializes a new writable instance of the NumberFormatInfo class
	 * that is dependent on the ICU data for number, decimal, and currency
	 * formatting information. <b>N.B.</b>You should not initialize this
	 * class directly unless you know what you are doing. Please use use
	 * NumberFormatInfo::getInstance() to create an instance.
	 * @param array $data ICU data for date time formatting.
	 * @param mixed $type
	 * @see getInstance()
	 */
	public function __construct($cultureInfo, $type = NumberFormatInfo::DECIMAL)
	{
		$this->properties = get_class_methods($this);

		if(!($cultureInfo instanceof CultureInfo))
			throw new Exception('Please provide a CultureInfo instance.');

		$this->cultureInfo = $cultureInfo;

		$this->setPattern($type);
	}

	public function getInfoByPath($path)
	{
		static $basePath = null;
		if($basePath === null)
		{
			$basePath = 'NumberElements/' . $this->cultureInfo->getDefaultNumberFormat() . '/';
		}

		return $this->cultureInfo->findInfo($basePath . $path);
	}

	/**
	 * Set the pattern for a specific number pattern. The validate patterns
	 * NumberFormatInfo::DECIMAL, NumberFormatInfo::CURRENCY,
	 * NumberFormatInfo::PERCENTAGE, NumberFormatInfo::SCIENTIFIC or
	 * NumberFormatInfo::ACCOUNTING
	 * @param int pattern type.
	 */
	public function setPattern($type = NumberFormatInfo::DECIMAL)
	{
		$info = $this->getInfoByPath('patterns/'.$type);
		$this->pattern = $this->parsePattern($info);
	}

	public function getPattern()
	{
		return $this->pattern;
	}

	/**
	 * Gets the default NumberFormatInfo that is culture-independent
	 * (invariant).
	 * @param mixed $type
	 * @return NumberFormatInfo default NumberFormatInfo.
	 */
	public static function getInvariantInfo($type=NumberFormatInfo::DECIMAL)
	{
		static $invariant;
		if($invariant === null)
		{
			$culture = CultureInfo::getInvariantCulture();
			$invariant = $culture->NumberFormat;
			$invariant->setPattern($type);
		}
		return $invariant;
	}

	/**
	 * Returns the NumberFormatInfo associated with the specified culture.
	 * @param null|CultureInfo $culture the culture that gets the NumberFormat property.
	 * @param int $type the number formatting type, it should be
	 *   NumberFormatInfo::DECIMAL, NumberFormatInfo::CURRENCY,
	 *   NumberFormatInfo::PERCENTAGE, or NumberFormatInfo::SCIENTIFIC
	 * @return NumberFormatInfo NumberFormatInfo for the specified culture.
	 * @see getCurrencyInstance();
	 * @see getPercentageInstance();
	 * @see getScientificInstance();
	 */
	public static function getInstance($culture=null, $type=NumberFormatInfo::DECIMAL)
	{
		if ($culture instanceof CultureInfo)
		{
			$formatInfo = $culture->NumberFormat;
			$formatInfo->setPattern($type);
			return $formatInfo;
		} elseif(is_string($culture)) {
			$cultureInfo = new CultureInfo($culture);
			$formatInfo = $cultureInfo->NumberFormat;
			$formatInfo->setPattern($type);
			return $formatInfo;
		} else {
			$cultureInfo = new CultureInfo();
			$formatInfo = $cultureInfo->NumberFormat;
			$formatInfo->setPattern($type);
			return $formatInfo;
		}
	}

	/**
	 * Returns the currency format info associated with the specified culture.
	 * @param null|CultureInfo $culture the culture that gets the NumberFormat property.
	 * @return NumberFormatInfo NumberFormatInfo for the specified
	 * culture.
	 */
	public static function getCurrencyInstance($culture = null)
	{
		return self::getInstance($culture, self::CURRENCY);
	}

	/**
	 * Returns the percentage format info associated with the specified culture.
	 * @param null|CultureInfo $culture the culture that gets the NumberFormat property.
	 * @return NumberFormatInfo NumberFormatInfo for the specified
	 * culture.
	 */
	public static function getPercentageInstance($culture = null)
	{
		return self::getInstance($culture, self::PERCENTAGE);
	}

	/**
	 * Returns the scientific format info associated with the specified culture.
	 * @param null|CultureInfo $culture the culture that gets the NumberFormat property.
	 * @return NumberFormatInfo NumberFormatInfo for the specified
	 * culture.
	 */
	public static function getScientificInstance($culture = null)
	{
		return self::getInstance($culture, self::SCIENTIFIC);
	}

	/**
	 * Parse the given pattern and return a list of known properties.
	 * @param string $pattern a number pattern.
	 * @return array list of pattern properties.
	 */
	protected function parsePattern($pattern)
	{
		$pattern = explode(';', $pattern);
		$negative = null;
		if (count($pattern) > 1) {
			$negative = $pattern[1];
		}
		$pattern = $pattern[0];

		$comma = ',';
		$dot = '.';
		$digit = '0';
		$hash = '#';

		//find the first group point, and decimal point
		$groupPos1 = strrpos($pattern, $comma);
		$decimalPos = strrpos($pattern, $dot);

		$groupPos2 = false;
		$groupSize1 = false;
		$groupSize2 = false;
		$decimalPoints = is_int($decimalPos) ? -1 : false;

		$info['negPref'] = $this->getNegativeSign();
		$info['negPost'] = '';

		$info['negative'] = $negative;
		$info['positive'] = $pattern;

		//find the negative prefix and postfix
		if ($negative) {
			$prefixPostfix = $this->getPrePostfix($negative);
			$info['negPref'] = $prefixPostfix[0];
			$info['negPost'] = $prefixPostfix[1];
		}

		$posfix = $this->getPrePostfix($pattern);
		$info['posPref'] = $posfix[0];
		$info['posPost'] = $posfix[1];

		//var_dump($pattern);
		//var_dump($decimalPos);
		if (is_int($groupPos1)) {
			//get the second group
			$groupPos2 = strrpos(substr($pattern, 0, $groupPos1), $comma);

			//get the number of decimal digits
			if (is_int($decimalPos)) {
				$groupSize1 = $decimalPos - $groupPos1 - 1;
			} else {
				//no decimal point, so traverse from the back
				//to find the groupsize 1.
				for ($i = strlen($pattern) - 1; $i >= 0; $i--) {
					if ($pattern{$i} == $digit || $pattern{$i} == $hash) {
						$groupSize1 = $i - $groupPos1;
						break;
					}
				}
			}

			//get the second group size
			if (is_int($groupPos2)) {
				$groupSize2 = $groupPos1 - $groupPos2 - 1;
			}
		}

		if (is_int($decimalPos)) {
			for ($i = strlen($pattern) - 1; $i >= 0; $i--) {
				if ($pattern{$i} == $dot) {
					break;
				}
				if ($pattern{$i} == $digit) {
					$decimalPoints = $i - $decimalPos;
					break;
				}
			}
		}

		if (is_int($decimalPos)) {
			$digitPattern = substr($pattern, 0, $decimalPos);
		} else {
			$digitPattern = $pattern;
		}

		$digitPattern = preg_replace('/[^0]/', '', $digitPattern);

		$info['groupPos1'] = $groupPos1;
		$info['groupSize1'] = $groupSize1;
		$info['groupPos2'] = $groupPos2;
		$info['groupSize2'] = $groupSize2;
		$info['decimalPos'] = $decimalPos;
		$info['decimalPoints'] = $decimalPoints;
		$info['digitSize'] = strlen($digitPattern);

		return $info;
	}

	/**
	 * Get the prefix and postfix of a pattern.
	 * @param string $pattern pattern
	 * @return array of prefix and postfix, array(prefix,postfix).
	 */
	protected function getPrePostfix($pattern)
	{
		$regexp = '/[#,\.0]+/';
		$result = preg_split($regexp, $pattern);
		return [$result[0], $result[1]];
	}

	/**
	 * Indicates the number of decimal places.
	 * @return int number of decimal places.
	 */
	public function getDecimalDigits()
	{
		return $this->pattern['decimalPoints'];
	}

	public function getDigitSize()
	{
		return $this->pattern['digitSize'];
	}

	/**
	 * Gets the string to use as the decimal separator.
	 * @return string decimal separator.
	 */
	public function getDecimalSeparator()
	{
		return $this->getInfoByPath('symbols/decimal');
	}

	/**
	 * Gets the string that separates groups of digits to the left
	 * of the decimal in currency values.
	 * @return string currency group separator.
	 */
	public function getGroupSeparator()
	{
		return $this->getInfoByPath('symbols/group');
	}

	/**
	 * Gets the number of digits in each group to the left of the decimal
	 * There can be two grouping sizes, this fucntion
	 * returns <b>array(group1, group2)</b>, if there is only 1 grouping size,
	 * group2 will be false.
	 * @return array grouping size(s).
	 */
	public function getGroupSizes()
	{
		$group1 = $this->pattern['groupSize1'];
		$group2 = $this->pattern['groupSize2'];
		return [$group1, $group2];
	}

	/**
	 * Gets the format pattern for negative values.
	 * The negative pattern is composed of a prefix, and postfix.
	 * This function returns <b>array(prefix, postfix)</b>.
	 * @return arary negative pattern.
	 */
	public function getNegativePattern()
	{
		$prefix = $this->pattern['negPref'];
		$postfix = $this->pattern['negPost'];
		return [$prefix, $postfix];
	}

	/**
	 * Gets the format pattern for positive values.
	 * The positive pattern is composed of a prefix, and postfix.
	 * This function returns <b>array(prefix, postfix)</b>.
	 * @return array positive pattern.
	 */
	public function getPositivePattern()
	{
		$prefix = $this->pattern['posPref'];
		$postfix = $this->pattern['posPost'];
		return [$prefix, $postfix];
	}

	/**
	 * Gets the string that represents negative infinity.
	 * @return string negative infinity.
	 */
	public function getNegativeInfinitySymbol()
	{
		return $this->getNegativeSign() . $this->getInfoByPath('symbols/infinity');
	}

	/**
	 * Gets the string that represents positive infinity.
	 * @return string positive infinity.
	 */
	public function getPositiveInfinitySymbol()
	{
		return $this->getPositiveSign() . $this->getInfoByPath('symbols/infinity');
	}

	/**
	 * Gets the string that denotes that the associated number is negative.
	 * @return string negative sign.
	 */
	public function getNegativeSign()
	{
		return $this->getInfoByPath('symbols/minusSign');
	}

	/**
	 * Gets the string that denotes that the associated number is positive.
	 * @return string positive sign.
	 */
	public function getPositiveSign()
	{
		return $this->getInfoByPath('symbols/plusSign');
	}

	/**
	 * Gets the string that represents the IEEE NaN (not a number) value.
	 * @return string NaN symbol.
	 */
	public function getNaNSymbol()
	{
		return $this->getInfoByPath('symbols/nan');
	}

	/**
	 * Gets the string to use as the percent symbol.
	 * @return string percent symbol.
	 */
	public function getPercentSymbol()
	{
		return $this->getInfoByPath('symbols/percentSign');
	}

	/**
	 * Gets the string to use as the per mille symbol.
	 * @return string percent symbol.
	 */
	public function getPerMilleSymbol()
	{
		return $this->getInfoByPath('symbols/perMille');
	}

	/**
	 * Gets the string to use as the currency symbol.
	 * @return string currency symbol.
	 */
	public function getCurrencySymbol($currency='USD')
	{
		$fmt = new \NumberFormatter( $this->cultureInfo->getName()."@currency=$currency", \NumberFormatter::CURRENCY );
		return $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
	}
}
