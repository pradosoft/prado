<?php
/**
 * TParameterPropert class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TParameterProperty corresponds to the <property> tag and defines
 * one object property for the <parameterMap>
 *
 * The {@link NullValue setNullValue()} attribute can be set to any valid
 * value (based on property type). The {@link NullValue setNullValue()} attribute
 * is used to specify an inbound null value replacement. What this means is
 * that when the value is detected in the object property, a NULL will be written
 * to the database (the opposite behavior of an inbound null value replacement).
 * This allows you to use a magic null number in your application for types that
 * do not support null values (such as int, double, float). When these types of
 * properties contain a matching null value (for example, say, -9999), a NULL
 * will be written to the database instead of the value.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TParameterProperty extends \Prado\TComponent
{
	private $_typeHandler;
	private $_type;
	private $_column;
	private $_dbType;
	private $_property;
	private $_nullValue;

	/**
	 * @return string class name of a custom type handler.
	 */
	public function getTypeHandler()
	{
		return $this->_typeHandler;
	}

	/**
	 * @param string $value class name of a custom type handler.
	 */
	public function setTypeHandler($value)
	{
		$this->_typeHandler = $value;
	}

	/**
	 * @return string type of the parameter's property
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param string $value type of the parameter's property
	 */
	public function setType($value)
	{
		$this->_type = $value;
	}

	/**
	 * @return string name of a parameter to be used in the SQL statement.
	 */
	public function getColumn()
	{
		return $this->_column;
	}

	/**
	 * @param string $value name of a parameter to be used in the SQL statement.
	 */
	public function setColumn($value)
	{
		$this->_column = $value;
	}

	/**
	 * @return string the database column type of the parameter to be set by this property.
	 */
	public function getDbType()
	{
		return $this->_dbType;
	}

	/**
	 * @param string $value the database column type of the parameter to be set by this property.
	 */
	public function setDbType($value)
	{
		$this->_dbType = $value;
	}

	/**
	 * @return string name of a property of the parameter object.
	 */
	public function getProperty()
	{
		return $this->_property;
	}

	/**
	 * @param string $value name of a property of the parameter object.
	 */
	public function setProperty($value)
	{
		$this->_property = $value;
	}

	/**
	 * @return mixed null value replacement
	 */
	public function getNullValue()
	{
		return $this->_nullValue;
	}

	/**
	 * The nullValue attribute is used to specify an outgoing null value replacement.
	 * @param mixed $value null value replacement.
	 */
	public function setNullValue($value)
	{
		$this->_nullValue = $value;
	}

	public function __sleep()
	{
		$exprops = [];
		$cn = 'TParameterProperty';
		if ($this->_typeHandler === null) {
			$exprops[] = "\0$cn\0_typeHandler";
		}
		if ($this->_type === null) {
			$exprops[] = "\0$cn\0_type";
		}
		if ($this->_column === null) {
			$exprops[] = "\0$cn\0_column";
		}
		if ($this->_dbType === null) {
			$exprops[] = "\0$cn\0_dbType";
		}
		if ($this->_property === null) {
			$exprops[] = "\0$cn\0_property";
		}
		if ($this->_nullValue === null) {
			$exprops[] = "\0$cn\0_nullValue";
		}
		return array_diff(parent::__sleep(), $exprops);
	}
}
