<?php

/**
 * DateTimeFormatInfo class file.
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
 * Defines how DateTime values are formatted and displayed, depending
 * on the culture.
 *
 * This class contains information, such as date patterns, time patterns,
 * and AM/PM designators.
 *
 * To create a DateTimeFormatInfo for a specific culture, create a
 * CultureInfo for that culture and retrieve the CultureInfo.DateTimeFormat
 * property. For example:
 * <code>
 * $culture = new CultureInfo('en_AU');
 * $dtfi = $culture->DateTimeFormat;
 * </code>
 *
 * To create a DateTimeFormatInfo for the invariant culture, use
 * <code>
 * DateTimeFormatInfo::getInstance($culture=null);
 * </code>
 * you may pass a CultureInfo parameter $culture to get the DateTimeFormatInfo
 * for a specific culture.
 *
 * DateTime values are formatted using standard or custom patterns stored in
 * the properties of a DateTimeFormatInfo.
 *
 * The standard patterns can be replaced with custom patterns by setting the
 * associated properties of DateTimeFormatInfo.
 *
 * The following table lists the standard format characters for each standard
 * pattern and the associated DateTimeFormatInfo property that can be set to
 * modify the standard pattern. The format characters are case-sensitive;
 * for example, 'g' and 'G' represent slightly different patterns.
 *
 * <code>
 *  Format Character    Associated Property     Example Format Pattern (en-US)
 *  --------------------------------------------------------------------------
 *  d                   ShortDatePattern        MM/dd/yyyy
 *  D                   LongDatePattern         dddd, dd MMMM yyyy
 *  F                   FullDateTimePattern     dddd, dd MMMM yyyy HH:mm:ss
 *  m, M                MonthDayPattern         MMMM dd
 *  r, R                RFC1123Pattern          ddd, dd MMM yyyy HH':'mm':'ss 'GMT'
 *  s                   SortableDateTimePattern yyyy'-'MM'-'dd'T'HH':'mm':'ss
 *  t                   ShortTimePattern        HH:mm
 *  T                   LongTimePattern         HH:mm:ss
 *  Y                   YearMonthPattern        yyyy MMMM
 *  --------------------------------------------------------------------------
 * </code>
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\I18N\core
 * @deprecated since 4.0.0
 */
class DateTimeFormatInfo
{
	/**
	 * Parent instance containing data
	 * @var CultureInfo
	 */
	private $cultureInfo = [];

	/**
	 * A list of properties that are accessable/writable.
	 * @var array
	 */
	protected $properties = [];

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
	 * Initializes a new writable instance of the DateTimeFormatInfo class
	 * that is dependent on the ICU data for date time formatting
	 * information. <b>N.B.</b>You should not initialize this class directly
	 * unless you know what you are doing. Please use use
	 * DateTimeFormatInfo::getInstance() to create an instance.
	 * @param array $data ICU data for date time formatting.
	 * @see getInstance()
	 */
	public function __construct($cultureInfo)
	{
		$this->properties = get_class_methods($this);

		if(!($cultureInfo instanceof CultureInfo))
			throw new Exception('Please provide a CultureInfo instance.');

		$this->cultureInfo = $cultureInfo;
	}

	/**
	 * Gets the default DateTimeFormatInfo that is culture-independent
	 * (invariant).
	 * @return DateTimeFormatInfo default DateTimeFormatInfo.
	 */
	static public function getInvariantInfo()
	{
		static $invariant;
		if($invariant === null)
		{
			$culture = CultureInfo::getInvariantCulture();
			$invariant = $culture->getDateTimeFormat();
		}
		return $invariant;
	}

	/**
	 * Returns the DateTimeFormatInfo associated with the specified culture.
	 * @param null|CultureInfo|string $culture the culture that gets the DateTimeFormat property.
	 * @return DateTimeFormatInfo DateTimeFormatInfo for the specified
	 * culture.
	 */
	public static function getInstance($culture=null)
	{

		if ($culture instanceof CultureInfo)
		{
			return $culture->getDateTimeFormat();
		} elseif(is_string($culture)) {
			$cultureInfo = CultureInfo::getInstance($culture);
			return $cultureInfo->getDateTimeFormat();
		} else {
			$cultureInfo = CultureInfo::getInvariantCulture();
			return $cultureInfo->getDateTimeFormat();
		}
	}

	public function getInfoByPath($path)
	{
		static $basePath = null;
		if($basePath === null)
		{
			$basePath = 'calendar/' . $this->cultureInfo->getCalendar() . '/';
		}

		return $this->cultureInfo->findInfo($basePath . $path);
	}

	/**
	 * A one-dimensional array of type String containing
	 * the culture-specific abbreviated names of the days
	 * of the week. The array for InvariantInfo contains
	 * "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", and "Sat".
	 * @return array abbreviated day names
	 */
	public function getAbbreviatedDayNames()
	{
		return $this->getInfoByPath('dayNames/format/abbreviated');
	}

	/**
	 * A one-dimensional array of type String containing
	 * the culture-specific narrow names of the days
	 * of the week. The array for InvariantInfo contains
	 * "S", "M", "T", "W", "T", "F", and "S".
	 * @return array narrow day names
	 */
	public function getNarrowDayNames()
	{
		return $this->getInfoByPath('dayNames/format/narrow');
	}

	/**
	 * A one-dimensional array of type String containing the
	 * culture-specific full names of the days of the week.
	 * The array for InvariantInfo contains "Sunday", "Monday",
	 * "Tuesday", "Wednesday", "Thursday", "Friday", and "Saturday".
	 * @return array day names
	 */
	public function getDayNames()
	{
		return $this->getInfoByPath('dayNames/format/wide');
	}

	/**
	 * A one-dimensional array of type String containing the
	 * culture-specific narrow names of the months. The array
	 * for InvariantInfo contains "J", "F", "M", "A", "M", "J",
	 * "J", "A", "S", "O", "N", and "D".
	 * @return array narrow month names.
	 */
	public function getNarrowMonthNames()
	{
		return $this->getInfoByPath('monthNames/format/narrow');
	}

	/**
	 * A one-dimensional array of type String containing the
	 * culture-specific abbreviated names of the months. The array
	 * for InvariantInfo contains "Jan", "Feb", "Mar", "Apr", "May",
	 * "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", and "Dec".
	 * Returns wide names if abbreviated names doesn't exist.
	 * @return array abbreviated month names.
	 */
	public function getAbbreviatedMonthNames()
	{
		$info = $this->getInfoByPath('monthNames/format/abbreviated');
		if ($info)
			return $info;
		else
			return $this->getMonthNames();
	}

	/**
	 * A one-dimensional array of type String containing the
	 * culture-specific full names of the months. The array for
	 * InvariantInfo contains "January", "February", "March", "April",
	 * "May", "June", "July", "August", "September", "October", "November",
	 * and "December"
	 * @return array month names.
	 */
	public function getMonthNames()
	{
		return $this->getInfoByPath('monthNames/format/wide');
	}

	/**
	 * A string containing the name of the era.
	 * @param int $era era The integer representing the era.
	 * @return string the era name.
	 */
	public function getEra($era)
	{
		$eraName = $this->getInfoByPath('eras/abbreviated');
		return $eraName[$era];
	}

	/**
	 * The string designator for hours that are "ante meridiem" (before noon).
	 * The default for InvariantInfo is "AM".
	 * @return string AM designator.
	 */
	public function getAMDesignator()
	{
		$result = $this->getAMPMMarkers();
		return $result[0];
	}

	/**
	 * The string designator for hours that are "post meridiem" (after noon).
	 * The default for InvariantInfo is "PM".
	 * @return string PM designator.
	 */
	public function getPMDesignator()
	{
		$result = $this->getAMPMMarkers();
		return $result[1];
	}

	/**
	 * Get the AM and PM markers array.
	 * Default InvariantInfo for AM and PM is <code>array('AM','PM');</code>
	 * @return array AM and PM markers
	 */
	public function getAMPMMarkers()
	{
		return $this->getInfoByPath('AmPmMarkers');
	}

	/**
	 * Returns the full time pattern "HH:mm:ss z" (default).
	 * This is culture sensitive.
	 * @return string pattern "HH:mm:ss z".
	 */
	public function getFullTimePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[0];
	}

	/**
	 * Returns the long time pattern "HH:mm:ss z" (default).
	 * This is culture sensitive.
	 * @return string pattern "HH:mm:ss z".
	 */
	public function getLongTimePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[1];
	}

	/**
	 * Returns the medium time pattern "HH:mm:ss" (default).
	 * This is culture sensitive.
	 * @return string pattern "HH:mm:ss".
	 */
	public function getMediumTimePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[2];
	}

	/**
	 * Returns the short time pattern "HH:mm" (default).
	 * This is culture sensitive.
	 * @return string pattern "HH:mm".
	 */
	public function getShortTimePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[3];
	}

	/**
	 * Returns the full date pattern "EEEE, yyyy MMMM dd" (default).
	 * This is culture sensitive.
	 * @return string pattern "EEEE, yyyy MMMM dd".
	 */
	public function getFullDatePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[4];
	}

	/**
	 * Returns the long date pattern "yyyy MMMM d" (default).
	 * This is culture sensitive.
	 * @return string pattern "yyyy MMMM d".
	 */
	public function getLongDatePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[5];
	}

	/**
	 * Returns the medium date pattern "yyyy MMMM d" (default).
	 * This is culture sensitive.
	 * @return string pattern "yyyy MMM d".
	 */
	public function getMediumDatePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[6];
	}

	/**
	 * Returns the short date pattern "yy/MM/dd" (default).
	 * This is culture sensitive.
	 * @return string pattern "yy/MM/dd".
	 */
	public function getShortDatePattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[7];
	}

	/**
	 * Returns the date time order pattern, "{1} {0}" (default).
	 * This is culture sensitive.
	 * @return string pattern "{1} {0}".
	 */
	public function getDateTimeOrderPattern()
	{
		return $this->getInfoByPath('DateTimePatterns')[8];
	}

	/**
	 * Formats the date and time in a culture sensitive paterrn.
	 * The default is "Date Time".
	 * @param mixed $date
	 * @param mixed $time
	 * @return string date and time formated
	 */
	public function formatDateTime($date, $time)
	{
		$pattern = $this->getDateTimeOrderPattern();
		return str_replace(['{0}', '{1}'], [$time, $date], $pattern);
	}
}
