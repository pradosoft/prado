<?php

/**
 * TSqlMapConditionalTag class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Wed Jun  7 07:57:22 EST 2006 $
 * @package System.DataAccess.SQLMap.Configuration
 * @since 3.0
 */
class TSqlMapConditionalTag extends TComponent
{
	public static $ConditionalChecks = array
	(
		'isEqual', 'isNotEqual', 
		'isGreaterThan', 'isGreaterEqual', 
		'isLessThan', 'isLessEqual'
	);
	
	public static $UnaryChecks = array
	(
		'isNull', 'isNotNull', 
		'isEmpty', 'isNotEmpty'
	);
	
	public static $ParameterChecks = array
	(
		'isPropertyAvailable', 'isPropertyNotAvailable',
		'isParameterPresent', 'isParameterNotPresent'
	);
	
	private $_tagName;
	private $_prepend='';
	private $_property;
	private $_propertyType = 'string';
	private $_compareProperty;
	private $_compareValue;

	public function getTagName(){ return $this->_tagName; }
	public function setTagName($value){ $this->_tagName = $value; }

	public function getPrepend(){ return $this->_prepend; }
	public function setPrepend($value){ $this->_prepend = $value; }

	public function getProperty(){ return $this->_property; }
	public function setProperty($value){ $this->_property = $value; }

	public function getCompareProperty(){ return $this->_compareProperty; }
	public function setCompareProperty($value){ $this->_compareProperty = $value; }

	public function getCompareValue(){ return $this->_compareValue; }
	public function setCompareValue($value){ $this->_compareValue = $value; }
	
	public function getPropertyType(){ return $this->_propertyType; }
	public function setPropertyType($value){ $this->_propertyType = $value; }
	
	/**
	 * Evaluates the conditional tag, return true if satisfied, false otherwise.
	 * @param object current conditional tag context.
	 * @param object query parameter object.
	 * @return boolean true if conditions are met, false otherwise.
	 */
	public function evaluate($context, $parameter)
	{
		$value = $this->getPropertyValue($parameter, $this->getProperty());
		if(in_array($this->getTagName(), self::$UnaryChecks))
			return $this->evaluateUnary($context, $value);
		else if(in_array($this->getTagName(), self::$ConditionalChecks))
		{
			$comparee = $this->getComparisonValue($parameter);
			return $this->evaluateConditional($context, $value, $comparee);
		}
		else
			return $this->evaluateParameterCheck($context, $parameter);
	}
	
	protected function evaluateUnary($context, $value)
	{
		switch($this->getTagName())
		{
			case 'isNull':
				return is_null($value);
			case 'isNotNull':
				return !is_null($value);
			case 'isEmpty':
				return empty($value);
			case 'isNotEmpty':
				return !empty($value);
		}
	}
	
	protected function evaluateConditional($context, $valueA, $valueB)
	{
		switch($this->getTagName())
		{
			case 'isEqual': return $valueA == $valueB;
			case 'isNotEqual': return $valueA != $valueB;
			case 'isGreaterThan': return $valueA > $valueB;
			case 'isGreaterEqual': return $valueA >= $valueB;
			case 'isLessThan': return $valueA < $valueB;
			case 'isLessEqual': return $valueA <= $valueB;
		}
	}
	
	protected function evaluateParameterCheck($context, $parameter)
	{
		switch($this->getTagName())
		{
			case 'isPropertyAvailable': return TPropertyAccess::has(
					$parameter, $this->getProperty());
			case 'isPropertyAvailable': return TPropertyAccess::has(
							$parameter, $this->getProperty()) == false;
			case 'isParameterPresent': return !is_null($parameter);
			case 'isParameterNotPresent': return is_null($parameter);
		}
	}
	
	/** 
	 * @param object query parameter object.
	 * @return mixed value for comparison.
	 */
	protected function getComparisonValue($parameter)
	{
		if(strlen($property = $this->getCompareProperty()) > 0
			&& (is_array($parameter) || is_object($parameter)))
		{
			$value = TPropertyAccess::get($parameter, $property);
		}
		else
			$value = $this->getCompareValue();
		return $this->ensureType($value);
	}
		
	/**
	 * @param object query parameter object
	 * @param string name of the property in the parameter object used for comparison.
	 * @return mixed value for comparison.
	 */
	protected function getPropertyValue($parameter, $property)
	{
		if(!is_null($property) && !is_null($parameter))
		{
			if(is_array($parameter) || is_object($parameter))
				return $this->ensureType(TPropertyAccess::get($parameter, $property));
			else
				return $this->ensureType($parameter);
		}
		else	
			return null;
	}

	/** 
	 * @param string property value
	 * @return mixed enforces property value to type given by {@link getPropertyType PropertyType}.
	 */
	protected function ensureType($value)
	{
		switch(strtolower($this->getPropertyType()))
		{
			case 'boolean':
				return TPropertyValue::ensureBoolean($value);
			case 'integer': case 'int':
				return TPropertyValue::ensureInteger($value);
			case 'float': case 'double':
				return TPropertyValue::ensureFloat($value);
			case 'array':
				return TPropertyValue::ensureArray($value);
			case 'object':
				return TPropertyValue::ensureObject($value);
			default:
				return TPropertyValue::ensureString($value);
		}		
	}	
}

?>