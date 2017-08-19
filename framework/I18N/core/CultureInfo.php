<?php

/**
 * CultureInfo class file.
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
 * CultureInfo class.
 *
 * Represents information about a specific culture including the
 * names of the culture, the calendar used, as well as access to
 * culture-specific objects that provide methods for common operations,
 * such as formatting dates, numbers, and currency.
 *
 * The CultureInfo class holds culture-specific information, such as the
 * associated language, sublanguage, country/region, calendar, and cultural
 * conventions. This class also provides access to culture-specific
 * instances of DateTimeFormatInfo and NumberFormatInfo. These objects
 * contain the information required for culture-specific operations,
 * such as formatting dates, numbers and currency.
 *
 * The culture names follow the format "<languagecode>_<country/regioncode>",
 * where <languagecode> is a lowercase two-letter code derived from ISO 639
 * codes. You can find a full list of the ISO-639 codes at
 * http://www.ics.uci.edu/pub/ietf/http/related/iso639.txt
 *
 * The <country/regioncode2> is an uppercase two-letter code derived from
 * ISO 3166. A copy of ISO-3166 can be found at
 * http://www.chemie.fu-berlin.de/diverse/doc/ISO_3166.html
 *
 * For example, Australian English is "en_AU".
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\I18N\core
 */
class CultureInfo
{
	/**
	 * The ICU data array.
	 * @var array
	 */
	private $data = [];

	/**
	 * The current culture.
	 * @var string
	 */
	private $culture;

	/**
	 * A list of CLDR resource bundles loaded
	 * @var array
	 */
	private $resourceBundles = array();

	/**
	 * A list of resource bundles keys
	 * @var array
	 */
	protected static $bundleNames = [
		'Core' => null,
		'Currencies' => 'ICUDATA-curr',
		'Languages' => 'ICUDATA-lang',
		'Countries' => 'ICUDATA-region',
		'zoneStrings' => 'ICUDATA-zone',
	];

	/**
	 * The current date time format info.
	 * @var DateTimeFormatInfo
	 */
	private $dateTimeFormat;

	/**
	 * The current number format info.
	 * @var NumberFormatInfo
	 */
	private $numberFormat;

	/**
	 * A list of properties that are accessable/writable.
	 * @var array
	 */
	protected $properties = [];

	/**
	 * Culture type, all.
	 * @see getCultures()
	 * @var int
	 */
	const ALL = 0;

	/**
	 * Culture type, neutral.
	 * @see getCultures()
	 * @var int
	 */
	const NEUTRAL = 1;

	/**
	 * Culture type, specific.
	 * @see getCultures()
	 * @var int
	 */
	const SPECIFIC = 2;

	/**
	 * Display the culture name.
	 * @return string the culture name.
	 * @see getName()
	 */
	public function __toString()
	{
		return $this->getName();
	}


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
	 * Initializes a new instance of the CultureInfo class based on the
	 * culture specified by name. E.g. <code>new CultureInfo('en_AU');</cdoe>
	 * The culture indentifier must be of the form
	 * "language_(country/region/variant)".
	 * @param string $culture a culture name, e.g. "en_AU".
	 * @return return new CultureInfo.
	 */
	public function __construct($culture = 'en')
	{
		$this->properties = get_class_methods($this);

		if (empty($culture)) {
			$culture = 'en';
		}

		$this->setCulture($culture);

		$this->loadCultureData('root');
		$this->loadCultureData($culture);
	}

	/**
<<<<<<< HEAD
	 * Get the default directory for the ICU data.
	 * The default is the "data" directory for this class.
	 * @return string directory containing the ICU data.
	 */
	protected static function dataDir()
	{
		return __DIR__ . '/data/';
	}

	/**
	 * Get the filename extension for ICU data. Default is ".dat".
	 * @return string filename extension for ICU data.
	 */
	protected static function fileExt()
	{
		return '.dat';
	}

	/**
	 * Gets the CultureInfo that for this culture string
	 * @param mixed $culture
	 * @return CultureInfo invariant culture info is "en".
	 */
	public static function getInstance($culture)
	{
		static $instances = [];
		if (!isset($instances[$culture])) {
			$instances[$culture] = new CultureInfo($culture);
		}
		return $instances[$culture];
	}

	/**
	 * Determine if a given culture is valid. Simply checks that the
	 * culture data exists.
	 * @param string $culture a culture
	 * @return bool true if valid, false otherwise.
	 */
	public static function validCulture($culture)
	{
		return in_array($culture, self::getCultures());
	}

	/**
	 * Set the culture for the current instance. The culture indentifier
	 * must be of the form "<language>_(country/region)".
	 * @param string $culture culture identifier, e.g. "fr_FR_EURO".
	 */
	protected function setCulture($culture)
	{
		if (!empty($culture)) {
			if (!preg_match('/^[_\\w]+$/', $culture)) {
				throw new Exception('Invalid culture supplied: ' . $culture);
			}
		}

		$this->culture = $culture;
	}

	/**
	 * Load the ICU culture data for the specific culture identifier.
	 * @param string $culture the culture identifier.
	 */
	protected function loadCultureData($cultureName)
	{
		$culture_parts = explode('_', $cultureName);
		$current_part = $culture_parts[0];

		$culturesToLoad = [$current_part];
		for($i = 1, $k = count($culture_parts); $i < $k; ++$i)
		{
			$current_part .= '_'.$culture_parts[$i];
			$culturesToLoad[] = $current_part;
		}

		foreach(self::$bundleNames as $key => $bundleName)
		{
			if(!array_key_exists($key, $this->data))
				$this->data[$key] = [];
		}
		foreach($culturesToLoad as $culture)
		{
			if(in_array($culture, $this->resourceBundles))
				continue;

			array_unshift($this->resourceBundles, $culture);
			foreach(self::$bundleNames as $key => $bundleName)
			{
				$this->data[$key][$culture] = \ResourceBundle::create($culture, $bundleName, false);
			}
		}
	}

	/**
	 * Find the specific ICU data information from the data.
	 * The path to the specific ICU data is separated with a slash "/".
	 * E.g. To find the default calendar used by the culture, the path
	 * "calendar/default" will return the corresponding default calendar.
	 * Use merge=true to return the ICU including the parent culture.
	 * E.g. The currency data for a variant, say "en_AU" contains one
	 * entry, the currency for AUD, the other currency data are stored
	 * in the "en" data file. Thus to retrieve all the data regarding
	 * currency for "en_AU", you need to use findInfo("Currencies,true);.
	 * @param string $path the data you want to find.
	 * @param bool $merge merge the data from its parents.
	 * @return mixed the specific ICU data.
	 */
	public function findInfo($path='/', $merge=false, $key = null)
	{
		$result = [];

		if($key === null)
		{
			// try to guess the bundle from the path. Always defaults to "Core".
			$key = 'Core';
			foreach(self::$bundleNames as $bundleName => $icuBundleName)
			{
				if(strpos($path, $bundleName) === 0)
				{
					$key = $bundleName;
					break;
				}
			}
		}

		if(!array_key_exists($key, $this->data))
			return $result;
		foreach($this->resourceBundles as $culture)
		{
			$res = $this->data[$key][$culture];
			if($res === null)
				continue;
			$info = $this->searchResources($res, $path);
			if($info)
			{
				if($merge)
					$result = array_merge($result, $info);
				else
					return $info;
				}
			}
		}

		return $result;
	}

	/**
	 * Search the array for a specific value using a path separated using
	 * slash "/" separated path. e.g to find $info['hello']['world'],
	 * the path "hello/world" will return the corresponding value.
	 * @param array $info the array for search
	 * @param string $path slash "/" separated array path.
	 * @return mixed the value array using the path
	 */
	private function searchResources($info, $path='/')
	{
		$index = explode('/', $path);

		$resource = $info;
		for($i = 0, $k = count($index); $i < $k; ++$i)
		{

			$resource = $resource->get($index[$i], false);
			if($resource === null)
				return null;
		}

		return $this->simplify($resource);
	}

	/**
	 * Gets the culture name in the format
	 * "<languagecode2>_(country/regioncode2)".
	 * @return string culture name.
	 */
	public function getName()
	{
		return $this->culture;
	}

	/**
	 * Gets the DateTimeFormatInfo that defines the culturally appropriate
	 * format of displaying dates and times.
	 * @return DateTimeFormatInfo date time format information for the culture.
	 */
	public function getDateTimeFormat()
	{
		if($this->dateTimeFormat === null)
		{
			$this->setDateTimeFormat(new DateTimeFormatInfo($this));
		}

		return $this->dateTimeFormat;
	}

	/**
	 * Set the date time format information.
	 * @param DateTimeFormatInfo $dateTimeFormat the new date time format info.
	 */
	public function setDateTimeFormat($dateTimeFormat)
	{
		$this->dateTimeFormat = $dateTimeFormat;
	}

	/**
	 * Gets the default calendar used by the culture, e.g. "gregorian".
	 * @return string the default calendar.
	 */
	public function getCalendar()
	{
		return $this->findInfo('calendar/default');
	}

	/**
	 * Gets the culture name in the language that the culture is set
	 * to display. Returns <code>array('Language','Country');</code>
	 * 'Country' is omitted if the culture is neutral.
	 * @return array array with language and country as elements, localized.
	 */
	public function getNativeName()
	{
		$lang = substr($this->culture, 0, 2);
		$reg = substr($this->culture, 3, 2);
		$language = $this->findInfo("Languages/{$lang}");
		$region = $this->findInfo("Countries/{$reg}");
		if($region)
			return $language.' ('.$region.')';
		else
			return $language;
	}

	/**
	 * Gets the culture name in English.
	 * Returns <code>array('Language','Country');</code>
	 * 'Country' is omitted if the culture is neutral.
	 * @return string language (country), it may locale code string if english name does not exist.
	 */
	public function getEnglishName()
	{
		$lang = substr($this->culture, 0, 2);
		$reg = substr($this->culture, 3, 2);
		$culture = $this->getInvariantCulture();

		$language = $culture->findInfo("Languages/{$lang}");
		if (count($language) == 0) {
			return $this->culture;
		}

		$region = $culture->findInfo("Countries/{$reg}");
		if($region)
			return $language.' ('.$region.')';
		else
			return $language;
	}

	/**
	 * Gets the CultureInfo that is culture-independent (invariant).
	 * Any changes to the invariant culture affects all other
	 * instances of the invariant culture.
	 * The invariant culture is assumed to be "en";
	 * @return CultureInfo invariant culture info is "en".
	 */
	public static function getInvariantCulture()
	{
		static $invariant;
		if ($invariant === null) {
			$invariant = new CultureInfo();
		}
		return $invariant;
	}

	/**
	 * Gets a value indicating whether the current CultureInfo
	 * represents a neutral culture. Returns true if the culture
	 * only contains two characters.
	 * @return bool true if culture is neutral, false otherwise.
	 */
	public function getIsNeutralCulture()
	{
		return strlen($this->culture) == 2;
	}

	/**
	 * Gets the NumberFormatInfo that defines the culturally appropriate
	 * format of displaying numbers, currency, and percentage.
	 * @return NumberFormatInfo the number format info for current culture.
	 */
	public function getNumberFormat()
	{
		if($this->numberFormat === null)
		{
			$this->setNumberFormat(new NumberFormatInfo($this));
		}
		return $this->numberFormat;
	}

	/**
	 * Set the number format information.
	 * @param NumberFormatInfo $numberFormat the new number format info.
	 */
	public function setNumberFormat($numberFormat)
	{
		$this->numberFormat = $numberFormat;
	}

	/**
	 * Gets the default number format used by the culture, e.g. "latn".
	 * @return string the default number format.
	 */
	public function getDefaultNumberFormat()
	{
		return $this->findInfo('NumberElements/default');
	}

	/**
	 * Gets the CultureInfo that represents the parent culture of the
	 * current CultureInfo
	 * @return CultureInfo parent culture information.
	 */
	public function getParent()
	{
		if (strlen($this->culture) == 2) {
			return $this->getInvariantCulture();
		}

		$lang = substr($this->culture, 0, 2);
		return new CultureInfo($lang);
	}

	/**
	 * Gets the list of supported cultures filtered by the specified
	 * culture type. This is an EXPENSIVE function, it needs to traverse
	 * a list of ICU files in the data directory.
	 * This function can be called statically.
	 * @param int $type culture type, CultureInfo::ALL, CultureInfo::NEUTRAL
	 * or CultureInfo::SPECIFIC.
	 * @return array list of culture information available.
	 */
	public static function getCultures($type=CultureInfo::ALL)
	{
		$all = \ResourceBundle::getLocales('');

		switch($type)
		{
			case CultureInfo::ALL :
				return $all;
			case CultureInfo::NEUTRAL :
				foreach($all as $key => $culture)
				{
					if(strlen($culture) != 2)
						unset($all[$key]);
				}
				return $all;
			case CultureInfo::SPECIFIC :
				foreach($all as $key => $culture)
				{
					if(strlen($culture) == 2)
						unset($all[$key]);
				}
				return $all;
		}
	}

	/**
	 * Simplify a single element array into its own value.
	 * E.g. <code>array(0 => array('hello'), 1 => 'world');</code>
	 * becomes <code>array(0 => 'hello', 1 => 'world');</code>
	 * @param array $array with single elements arrays
	 * @return array simplified array.
	 */
	protected function simplify($obj)
	{
		if(is_scalar($obj)) {
			return $obj;
		} elseif($obj instanceof \ResourceBundle) {
			$array = array();
			foreach($obj as $k => $v)
				$array[$k] = $v;
		} else {
			$array = $obj;
		}

		for($i = 0, $k = count($array); $i<$k; ++$i)
		{
			$key = key($array);
			if (is_array($array[$key])
				&& count($array[$key]) == 1) {
				$array[$key] = $array[$key][0];
			}
			next($array);
		}
		return $array;
	}

	/**
	 * Get a list of countries in the language of the localized version.
	 * @return array a list of localized country names.
	 */
	public function getCountries()
	{
		return $this->simplify($this->findInfo('Countries', true, 'Countries'));
	}

	/**
	 * Get a list of currencies in the language of the localized version.
	 * @return array a list of localized currencies.
	 */
	public function getCurrencies()
	{
		static $arr;
		if($arr === null)
		{
			$arr = $this->findInfo('Currencies', true, 'Currencies');
			foreach($arr as $k => $v)
				$arr[$k] = $this->simplify($v);
		}
		return $arr;
	}

	/**
	 * Get a list of languages in the language of the localized version.
	 * @return array list of localized language names.
	 */
	public function getLanguages()
	{
		return $this->simplify($this->findInfo('Languages', true, 'Languages'));
	}

	/**
	 * Get a list of scripts in the language of the localized version.
	 * @return array list of localized script names.
	 */
	public function getScripts()
	{
		return $this->simplify($this->findInfo('Scripts', true, 'Languages'));
	}

	/**
	 * Get a list of timezones in the language of the localized version.
	 * @return array list of localized timezones.
	 */
	public function getTimeZones()
	{
		static $arr;
		if($arr === null)
		{
			$validPrefixes = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Etc', 'Europe', 'Indian', 'Pacific');
			$tmp = $this->findInfo('zoneStrings', true, 'zoneStrings');
			foreach($tmp as $k => $v)
			{
				foreach($validPrefixes as $prefix)
				{
					if(strpos($k, $prefix) === 0)
					{
						$arr[] = str_replace(':', '/', $k);
						break;
					}
				}
			}
		}
		return $arr;
	}
}
