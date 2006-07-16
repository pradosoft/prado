<?php

class TTypeHandlerFactory
{
	private $_typeHandlerMap;
	
	const NullDbType = '__NULL__';

	public function __construct()
	{
		$this->_typeHandlerMap = new TMap;
	}

	public function getTypeHandler($type, $dbType=null)
	{
		$dbTypeHandlerMap = $this->_typeHandlerMap[$type];
		$handler = null;
		if(!is_null($dbTypeHandlerMap))
		{
			if(is_null($dbType))
				$handler = $dbTypeHandlerMap[self::NullDbType];
			else
			{
				$handler = $dbTypeHandlerMap[$dbType];
				if(is_null($handler))
					$handler = $dbTypeHandlerMap[self::NullDbType];
			}
		}
		return $handler;
	}

	public function register($type, $handler, $dbType=null)
	{
		$map = $this->_typeHandlerMap[$type];
		if(is_null($map))
		{
			$map = new TMap;
			$this->_typeHandlerMap->add($type, $map);
		}
		if(is_null($dbType))
			$map->add(self::NullDbType, $handler);
		else
			$map->add($dbType, $handler);
	}

	public static function createInstanceOf($type)
	{
		if(strlen($type) > 0)
		{
			switch(strtolower($type))
			{
				case 'string': return '';
				case 'array': return array();
				case 'float': case 'double': case 'decimal': return 0.0;
				case 'integer': case 'int': return 0;
				case 'bool': case 'boolean': return false;
			}
			
			if(class_exists('Prado', false))
				return Prado::createComponent($type);
			else if(class_exists($type, false)) //NO auto loading
				return new $type;
			else
				throw new TDataMapperException('sqlmap_unable_to_find_class', $type);
		}
		return null;
	}

	public static function convertToType($type, $value)
	{
		switch(strtolower($type))
		{
			case 'integer': case 'int': 
				$type = 'integer'; break;
			case 'float': case 'double': case 'decimal':
				$type = 'float'; break;
			case 'boolean': case 'bool':
				$type = 'boolean'; break;
			case 'string' :
				$type = 'string'; break;
			default: 
				return $value;
		}
		settype($value, $type);
		return $value;
	}
}

/**
 * A simple interface for implementing custom type handlers.
 * 
 * Using this interface, you can implement a type handler that
 * will perform customized processing before parameters are set
 * on and after values are retrieved from the database.  
 * Using a custom type handler you can extend
 * the framework to handle types that are not supported, or
 * handle supported types in a different way.  For example,
 * you might use a custom type handler to implement proprietary
 * BLOB support (e.g. Oracle), or you might use it to handle
 * booleans using "Y" and "N" instead of the more typical 0/1.
 */
interface ITypeHandlerCallback
{
	/**
	 * Performs processing on a value before it is used to set
	 * the parameter of a IDbCommand.
	 * @param object The interface for setting the value.
	 * @param object The value to be set.
	 */
	public function getParameter($object);


	/**
	 * Performs processing on a value before after it has been retrieved
	 * from a database
	 * @param object The interface for getting the value.
	 * @return mixed The processed value.
	 */
	public function getResult($string);


	/**
	 * Casts the string representation of a value into a type recognized by
	 * this type handler.  This method is used to translate nullValue values
	 * into types that can be appropriately compared.  If your custom type handler
	 * cannot support nullValues, or if there is no reasonable string representation
	 * for this type (e.g. File type), you can simply return the String representation
	 * as it was passed in.  It is not recommended to return null, unless null was passed
	 * in.
	 * @param array result row.
	 * @return mixed
	 */
	public function createNewInstance($row=null);
}

?>