<?php

/**
 * TDateFromat formatting component.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Xiang Wei Zhuo. 
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.11 $  $Date: 2005/12/16 04:33:02 $
 * @package System.I18N
 */
 
/**
 * Get the DateFormat class.
 */
Prado::using('System.I18N.core.DateFormat');

/**
 * To format dates and/or time according to the current locale use 
 * <code>
 * <com:TDateFormat Pattern="dd:MMM:yyyy" Value="01/01/2001" />
 *</code> 
 * The date will be formatted according to the current locale (or culture) 
 * using the format specified by 'Pattern' attribute.
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
 * Namespace: System.I18N
 *
 * Properties
 * - <b>Value</b>, date, 
 *   <br>Gets or sets the date to format. The tag content is used as Value
 *   if the Value property is not specified.
 * - <b>Pattern</b>, string,
 *   <br>Gets or sets the formatting pattern. The predefined patterns are
 *   'full date', 'long date', 'medium date', 'short date', 'full time',
 *   'long time', 'medium time', and 'short time'. Custom patterns can
 *   specified when the Pattern property does not match the predefined
 *   patterns.
 * 
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v1.0, last update on Sat Dec 11 15:25:11 EST 2004
 * @package System.I18N
 */
class TDateFormat extends TI18NControl
{
	/**
	 * Default DateFormat, set to the application culture.
	 * @var DateFormat 
	 */
	protected static $formatter;
	
	/**
	 * A set of pattern presets and their respective formatting shorthand.
	 * @var array 
	 */
	protected $patternPresets = array(
			'fulldate'=>'P','full'=>'P',
			'longdate'=>'D','long'=>'d',
			'mediumdate'=>'p','medium'=>'p',
			'shortdate'=>'d','short'=>'d',
			'fulltime'=>'Q', 'longtime'=>'T',
			'mediumtime'=>'q', 'shorttime'=>'t');
	
	/**
	 * Sets the date time formatting pattern.
	 * @param string format pattern.
	 */
	function setPattern($value)
	{
		$this->setViewState('Pattern',$value,'');
	}
	
	/**
	 * Gets the date time format pattern.
	 * @return string format pattern.
	 */
	function getPattern()
	{	
		$string = $this->getViewState('Pattern','');
		
		$pattern = null;

		//try the subpattern of "date time" presets
		$subpatterns = explode(' ',$string,2);
		$datetime = array();
		if(count($subpatterns)==2)
		{
			$datetime[] = $this->getPreset($subpatterns[0]);
			$datetime[] = $this->getPreset($subpatterns[1]);
		}
		
		//we have a good subpattern
		if(count($datetime) == 2 
			&& strlen($datetime[0]) == 1
			&& strlen($datetime[1]) == 1)
		{
			$pattern = $datetime;
		}
		else //no subpattern, try the presets
			$pattern = $this->getPreset($string);
				
		//no presets found, use the string as the pattern
		//and let the DateFormat handle it.
		if(is_null($pattern))
			$pattern = $string;
		if (!is_array($pattern) && strlen($pattern) == 0) 
			$pattern = null;
		return $pattern;
	}
	
	/**
	 * For a given string, try and find a preset pattern.
	 * @param string the preset pattern name
	 * @return string a preset pattern if found, null otherwise. 
	 */
	protected function getPreset($string)
	{
		$string = strtolower($string);
		foreach($this->patternPresets as $pattern => $preset)
		{
			if($string == $pattern)
				return $preset;
		}
	}
		
	/**
	 * Get the date-time value for this control.
	 * @return string date time value. 
	 */
	function getValue()
	{
		$value = $this->getViewState('Value','');
		if(empty($value))
			return time();
		return $value;
	}
	
	/**
	 * Set the date-time value for this control.
	 * @param string the date-time value.
	 */
	function setValue($value)
	{
		$this->setViewState('Value',$value,'');
	}
	
	/**
	 * Renders the localized version of the date-time value.
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * This method overrides parent's implementation.
	 */	
	protected function getFormattedDate()
	{
		$app = $this->Application->getGlobalization();
		
		//initialized the default class wide formatter
		if(is_null(self::$formatter))
			self::$formatter = new DateFormat($app->Culture);
	
		$culture = $this->getCulture();

		//return the specific cultural formatted date time
		if(strlen($culture) && $app->Culture !== $culture)
		{
			$formatter = new DateFormat($culture);
			return $formatter->format($this->getValue(), 
									  $this->getPattern(), 
									  $this->getCharset());
		}
		//return the application wide culture formatted date time.
		$result = self::$formatter->format($this->getValue(), 
										$this->getPattern(), 
										$this->getCharset());
		return $result;
	}

	protected function render($writer)
	{
		$writer->write($this->getFormattedDate());
	}
	
}
?>