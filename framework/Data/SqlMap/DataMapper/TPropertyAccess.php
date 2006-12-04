<?php

class TPropertyAccess
{
	private $_obj;
	private $_performance=false;

	public function __construct($obj,$performance=false)
	{
		$this->_obj = $obj;
		$this->_performance=$performance;
	}

	public function __get($name)
	{
		return self::get($this->_obj,$name,$this->_performance);
	}

	public function __set($name,$value)
	{
		self::set($this->_obj,$name,$value,$this->_performance);
	}

	/**
	 * Evaluates the data value at the specified field.
	 * - If the data is an array, then the field is treated as an array index
	 *   and the corresponding element value is returned;
	 * - If the data is a TMap or TList object, then the field is treated as a key
	 *   into the map or list, and the corresponding value is returned.
	 * - If the data is an object, the field is treated as a property or subproperty
	 *   defined with getter methods. For example, if the object has a method called
	 *   getMyValue(), then field 'MyValue' will retrive the result of this method call.
	 *   If getMyValue() returns an object which contains a method getMySubValue(),
	 *   then field 'MyValue.MySubValue' will return that method call result.
	 * @param mixed data containing the field value, can be an array, TMap, TList or object.
	 * @param mixed field value
	 * @return mixed value at the specified field
	 * @throw TInvalidDataValueException if field or data is invalid
	 */
	public static function get($object,$path)
	{
		if(!is_array($object) && !is_object($object))
			return $object;
		$properties = explode('.', $path);
		foreach($properties as $prop)
		{
			if(is_array($object) || $object instanceof ArrayAccess)
			{
				if(array_key_exists($prop, $object))
					$object = $object[$prop];
				else
					throw new TInvalidPropertyException('sqlmap_invalid_property',$path);
			}
			else if(is_object($object))
			{
				$getter = 'get'.$prop;
				if(is_callable(array($object,$getter)))
					$object = $object->{$getter}();
				else if(in_array($prop, array_keys(get_object_vars($object))))
					$object = $object->{$prop};
				else
					throw new TInvalidPropertyException('sqlmap_invalid_property',$path);
			}
			else
				throw new TInvalidPropertyException('sqlmap_invalid_property',$path);
		}
		return $object;
	}

	public static function has($object, $path)
	{
		if(!is_array($object) && !is_object($object))
			return false;
		$properties = explode('.', $path);
		foreach($properties as $prop)
		{
			if(is_array($object) || $object instanceof ArrayAccess)
			{
				if(array_key_exists($prop, $object))
					$object = $object[$prop];
				else
					return false;
			}
			else if(is_object($object))
			{
				$getter = 'get'.$prop;
				if(is_callable(array($object,$getter)))
					$object = $object->{$getter}();
				else if(in_array($prop, array_keys(get_object_vars($object))))
					$object = $object->{$prop};
				return false;
			}
			else
				return false;
		}
		return true;
	}

	public static function set(&$originalObject, $path, $value)
	{
		$properties = explode('.', $path);
		$prop = array_pop($properties);
		if(count($properties) > 0)
			$object = self::get($originalObject, implode('.',$properties));
		else
			$object = &$originalObject;

		//var_dump($object);
		if(is_array($object) || $object instanceof ArrayAccess)
		{
			$object[$prop] = $value;
		}
		else if(is_object($object))
		{
			$setter = 'set'.$prop;
			if(is_callable(array($object, $setter)))
			{
				if($object->{$setter}($value) === null)
					$object->{$prop} = $value;
			}
			else
				$object->{$prop} = $value;
		}
		else
			throw new TInvalidPropertyException('sqlmap_invalid_property_type',$path);
	}

}

?>