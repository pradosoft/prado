<?php
/**
 * TSqlMapTypeHandlerRegistry, and abstract TSqlMapTypeHandler classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

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
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
abstract class TSqlMapTypeHandler extends \Prado\TComponent
{
	private $_dbType = 'NULL';
	private $_type;
	/**
	 * @param string $value database field type.
	 */
	public function setDbType($value)
	{
		$this->_dbType = $value;
	}

	/**
	 * @return string database field type.
	 */
	public function getDbType()
	{
		return $this->_dbType;
	}

	public function getType()
	{
		if ($this->_type === null) {
			return get_class($this);
		} else {
			return $this->_type;
		}
	}

	public function setType($value)
	{
		$this->_type = $value;
	}

	/**
	 * Performs processing on a value before it is used to set
	 * the parameter of a IDbCommand.
	 * @param object $object The interface for setting the value.
	 * @return object The value to be set.
	 */
	abstract public function getParameter($object);


	/**
	 * Performs processing on a value before after it has been retrieved
	 * from a database
	 * @param object $string The interface for getting the value.
	 * @return mixed The processed value.
	 */
	abstract public function getResult($string);


	/**
	 * Casts the string representation of a value into a type recognized by
	 * this type handler.  This method is used to translate nullValue values
	 * into types that can be appropriately compared.  If your custom type handler
	 * cannot support nullValues, or if there is no reasonable string representation
	 * for this type (e.g. File type), you can simply return the String representation
	 * as it was passed in.  It is not recommended to return null, unless null was passed
	 * in.
	 * @param null|array $row result row.
	 * @return mixed
	 */
	abstract public function createNewInstance($row = null);
}
