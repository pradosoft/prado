<?php
/**
 * TNumberFromat component.
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
 * @version $Revision: 1.7 $  $Date: 2005/12/15 07:14:49 $
 * @package System.I18N
 */

 /**
 * Get the NumberFormat class.
 */
Prado::using('System.I18N.core.NumberFormat');


/**
  * To format numbers in locale sensitive manner use 
  * <code>
  * <com:TNumberFormat Pattern="0.##" value="2.0" />
  * </code>
  * 
  * Numbers can be formatted as currency, percentage, decimal or scientific
  * numbers by specifying the Type attribute. The known types are 
  * "currency", "percentage", "decimal" and "scientific".
  *
  * If someone from US want to see sales figures from a store in 
  * Germany (say using the EURO currency), formatted using the german 
  * currency, you would need to use the attribute Culture="de_DE" to get 
  * the currency right, e.g. 100,00 â‚?. The decimal and grouping separator is 
  * then also from the de_DE locale. This may lead to some confusion because 
  * people from US know the "," as thousand separator. Therefore a "Currency"
  * attribute is available, so that the output from the following example
  * results in â‚?100.00
  * <code>
  * <com:TNumberFormat Type="currency" Culture="en_US" Currency="EUR" Value="100" />
  * </code>
  *  
  * Namespace: System.I18N
  *
  * Properties
  * - <b>Value</b>, number, 
  *   <br>Gets or sets the number to format. The tag content is used as Value
  *   if the Value property is not specified.
  * - <b>Type</b>, string,
  *   <br>Gets or sets the formatting type. The valid types are 
  *    'decimal', 'currency', 'percentage' and 'scientific'.
  * - <b>Currency</b>, string, 
  *   <br>Gets or sets the currency symbol for the currency format.
  *   The default is 'USD' if the Currency property is not specified.
  * - <b>Pattern</b>, string,
  *   <br>Gets or sets the custom number formatting pattern.
  *
  * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
  * @version v1.0, last update on Sat Dec 11 17:49:56 EST 2004
  * @package System.I18N
  */ 
class TNumberFormat extends TI18NControl 
{
	/**
	 * Default NumberFormat, set to the application culture.
	 * @var NumberFormat 
	 */
	protected static $formatter;
	
	/**
	 * Get the number formatting pattern.
	 * @return string format pattern.
	 */
	function getPattern()
	{
		return $this->getViewState('Pattern','');
	}
	
	/**
	 * Set the number format pattern.
	 * @param string format pattern.
	 */
	function setPattern($pattern)
	{
		$this->setViewState('Pattern',$pattern,'');
	}
	
	/**
	 * Get the numberic value for this control.
	 * @return string number
	 */
	function getValue()
	{		
		return $this->getViewState('Value','');
	}
	
	/**
	 * Set the numberic value for this control.
	 * @param string the number value
	 */
	function setValue($value)
	{
		$this->setViewState('Value',$value,'');
	}
	
	/**
	 * Get the formatting type for this control.
	 * @return string formatting type.
	 */
	function getType()
	{
		$type = $this->getViewState('Type','');
		if(empty($type))
			return 'd';
		return $type;
	}
	
	/**
	 * Set the formatting type for this control.
	 * @param string formatting type, either "decimal", "currency","percentage" 
	 * or "scientific"
	 * @throws TPropertyTypeInvalidException
	 */
	function setType($type)
	{
		$type = strtolower($type);
		
		switch($type)
		{
			case 'decimal':
				$this->setViewState('Type','d',''); break;
			case 'currency':
				$this->setViewState('Type','c',''); break;
			case 'percentage':
				$this->setViewState('Type','p',''); break;
			case 'scientific':
				$this->setViewState('Type','e',''); break;
			default:
				throw new TPropertyTypeInvalidException($this,'Type',$type);
		}
		
	}
	
	/**
	 * Get the currency for this control.
	 * @param parameter
	 * @return string 3 letter currency code. 
	 */
	function getCurrency()
	{
		$currency = $this->getViewState('Currency','');
		if(empty($currency))
			return 'USD';
		return $currency;
	}
	
	/**
	 * Set the 3-letter ISO 4217 code. For example, the code 
	 * "USD" represents the US Dollar and "EUR" represents the Euro currency.
	 * @param string currency code.
	 */
	function setCurrency($currency)
	{
		$this->setViewState('Currency', $currency,'');
	}	
	
	/**
	 * Formats the localized number, be it currency or decimal, or percentage.
	 * If the culture is not specified, the default application
	 * culture will be used.
	 * @return string formatted number
	 */	
	protected function getFormattedValue()
	{
		$app = $this->Application->getGlobalization();
		//initialized the default class wide formatter
		if(is_null(self::$formatter))
			self::$formatter = new NumberFormat($app->getCulture());
	
		$pattern = strlen($this->getPattern()) > 0 
						? $this->getPattern() : $this->getType();
		
		$culture = $this->getCulture();
		//return the specific cultural formatted number
		if(!empty($culture) && $app->getCulture() != $culture)
		{
			$formatter = new NumberFormat($culture);
			return $formatter->format($this->getValue(),$pattern,
									  $this->getCurrency(),
									  $this->getCharset());
		}
			
		//return the application wide culture formatted number.
		return self::$formatter->format($this->getValue(),$pattern,
										$this->getCurrency(), 
										$this->getCharset());
	}

	protected function render($writer)
	{
		$writer->write($this->getFormattedValue());
	}
}

?>