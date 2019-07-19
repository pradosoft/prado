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
 * conventions.
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
	 * The ICU data array, shared by all instances of this class.
	 * @var array
	 */
	protected static $data = [];

	/**
	 * The current culture.
	 * @var string
	 */
	protected $culture;

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
	}

	/**
	 * Gets the CultureInfo that for this culture string
	 * @param mixed $culture
	 * @return CultureInfo invariant culture info is "en".
	 */
	public static function getInstance($culture)
	{
		if (!isset(self::$instances[$culture])) {
			self::$instances[$culture] = new CultureInfo($culture);
		}
		return self::$instances[$culture];
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
	 * @param mixed $key
	 */
	protected function loadCultureData($key)
	{
		foreach (self::$bundleNames as $bundleKey => $bundleName) {
			if ($key == $bundleKey) {
				if (!array_key_exists($this->culture, self::$data)) {
					self::$data[$this->culture] = [];
				}

				self::$data[$this->culture][$bundleKey] = \ResourceBundle::create($this->culture, $bundleName, true);
				break;
			}
		}
	}

	/**
	 * Find the specific ICU data information from the data.
	 * The path to the specific ICU data is separated with a slash "/".
	 * E.g. To find the default calendar used by the culture, the path
	 * "calendar/default" will return the corresponding default calendar.
	 * @param string $path the data you want to find.
	 * @param string $key bundle name.
	 * @return mixed the specific ICU data.
	 */
	public function findInfo($path = '/', $key = null)
	{
		if ($key === null) {
			// try to guess the bundle from the path. Always defaults to "Core".
			$key = 'Core';
			foreach (self::$bundleNames as $bundleName => $icuBundleName) {
				if (strpos($path, $bundleName) === 0) {
					$key = $bundleName;
					break;
				}
			}
		}

		if (!array_key_exists($this->culture, self::$data)) {
			$this->loadCultureData($key);
		}
		if (!array_key_exists($this->culture, self::$data) || !array_key_exists($key, self::$data[$this->culture])) {
			return [];
		}

		return $this->searchResources(self::$data[$this->culture][$key], $path);
	}

	/**
	 * Search the array for a specific value using a path separated using
	 * slash "/" separated path. e.g to find $info['hello']['world'],
	 * the path "hello/world" will return the corresponding value.
	 * @param array $info the array for search
	 * @param string $path slash "/" separated array path.
	 * @param mixed $resource
	 * @return mixed the value array using the path
	 */
	private function searchResources($resource, $path = '/')
	{
		$index = explode('/', $path);
		for ($i = 0, $k = count($index); $i < $k; ++$i) {
			if (is_object($resource)) {
				$resource = $resource->get($index[$i]);
			}
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
		if ($region) {
			return $language . ' (' . $region . ')';
		} else {
			return $language;
		}
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
		if ($language === null) {
			return $this->culture;
		}

		$region = $culture->findInfo("Countries/{$reg}");
		if ($region) {
			return $language . ' (' . $region . ')';
		} else {
			return $language;
		}
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
	 * Gets the list of supported cultures filtered by the specified
	 * culture type. This is an EXPENSIVE function, it needs to traverse
	 * a list of ICU files in the data directory.
	 * This function can be called statically.
	 * @param int $type culture type, CultureInfo::ALL, CultureInfo::NEUTRAL
	 * or CultureInfo::SPECIFIC.
	 * @return array list of culture information available.
	 */
	public static function getCultures($type = CultureInfo::ALL)
	{
		$all = \ResourceBundle::getLocales('');

		switch ($type) {
			case CultureInfo::ALL:
				return $all;
			case CultureInfo::NEUTRAL:
				foreach ($all as $key => $culture) {
					if (strlen($culture) != 2) {
						unset($all[$key]);
					}
				}
				return $all;
			case CultureInfo::SPECIFIC:
				foreach ($all as $key => $culture) {
					if (strlen($culture) == 2) {
						unset($all[$key]);
					}
				}
				return $all;
		}
	}

	/**
	 * Simplify a single element array into its own value.
	 * E.g. <code>array(0 => array('hello'), 1 => 'world');</code>
	 * becomes <code>array(0 => 'hello', 1 => 'world');</code>
	 * @param array $array with single elements arrays
	 * @param mixed $obj
	 * @return array simplified array.
	 */
	protected function simplify($obj)
	{
		if (is_scalar($obj)) {
			return $obj;
		} elseif ($obj instanceof \ResourceBundle) {
			$array = [];
			foreach ($obj as $k => $v) {
				$array[$k] = $v;
			}
		} else {
			$array = $obj;
		}

		if (is_array($array)) {
			for ($i = 0, $k = count($array); $i < $k; ++$i) {
				$key = key($array);
				if ($key !== null
					&& is_array($array[$key])
					&& count($array[$key]) == 1) {
					$array[$key] = $array[$key][0];
				}
				next($array);
			}
		}
		return $array;
	}

	/**
	 * Get a list of countries in the language of the localized version.
	 * @return array a list of localized country names.
	 */
	public function getCountries()
	{
		return $this->simplify($this->findInfo('Countries', 'Countries'));
	}

	/**
	 * Get a list of currencies in the language of the localized version.
	 * @return array a list of localized currencies.
	 */
	public function getCurrencies()
	{
		static $arr;
		if ($arr === null) {
			$arr = $this->findInfo('Currencies', 'Currencies');
			foreach ($arr as $k => $v) {
				$arr[$k] = $this->simplify($v);
			}
		}
		return $arr;
	}

	/**
	 * Get a list of languages in the language of the localized version.
	 * @return array list of localized language names.
	 */
	public function getLanguages()
	{
		return $this->simplify($this->findInfo('Languages', 'Languages'));
	}

	/**
	 * Get a list of scripts in the language of the localized version.
	 * @return array list of localized script names.
	 */
	public function getScripts()
	{
		return $this->simplify($this->findInfo('Scripts', 'Languages'));
	}

	/**
	 * Get a list of timezones in the language of the localized version.
	 * @return array list of localized timezones.
	 */
	public function getTimeZones()
	{
		static $arr;
		if ($arr === null) {
			$validPrefixes = ['Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Etc', 'Europe', 'Indian', 'Pacific'];
			$tmp = $this->findInfo('zoneStrings', 'zoneStrings');
			foreach ($tmp as $k => $v) {
				foreach ($validPrefixes as $prefix) {
					if (strpos($k, $prefix) === 0) {
						$arr[] = str_replace(':', '/', $k);
						break;
					}
				}
			}
		}
		return $arr;
	}
}
