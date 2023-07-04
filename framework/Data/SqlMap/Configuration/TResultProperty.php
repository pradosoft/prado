<?php
/**
 * TResultProperty class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Configuration;

use Prado\Collections\TList;
use Prado\Data\SqlMap\DataMapper\TPropertyAccess;
use Prado\Prado;
use Prado\TPropertyValue;
use ReflectionClass;

/**
 * TResultProperty corresponds a <property> tags inside a <resultMap> tag.
 *
 * The {@see NullValue setNullValue()} attribute can be set to any valid
 * value (based on property type). The {@see NullValue setNullValue()} attribute
 * is used to specify an outgoing null value replacement. What this means is
 * that when a null value is detected in the result, the corresponding value of
 * the {@see NullValue getNullValue()} will be used instead.
 *
 * The {@see Select setSelect()} property is used to describe a relationship
 * between objects and to automatically load complex (i.e. user defined)
 * property types. The value of the {@see Select setSelect()} property must be
 * the name of another mapped statement. The value of the database
 * {@see Column setColumn()} that is defined in the same property element as
 * this statement attribute will be passed to the related mapped statement as
 * the parameter. The {@see LazyLoad setLayLoad()} attribute can be specified
 * with the {@see Select setSelect()} .
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TResultProperty extends \Prado\TComponent
{
	private $_nullValue;
	private $_propertyName;
	private $_columnName;
	private $_columnIndex = -1;
	private $_nestedResultMapName;
	private $_nestedResultMap;
	private $_valueType;
	private $_typeHandler;
	private $_isLazyLoad = false;
	private $_select;

	private $_hostResultMapID = 'inplicit internal mapping';

	public const LIST_TYPE = 0;
	public const ARRAY_TYPE = 1;

	/**
	 * Gets the containing result map ID.
	 * @param TResultMap $resultMap containing result map.
	 */
	public function __construct($resultMap = null)
	{
		if ($resultMap instanceof TResultMap) {
			$this->_hostResultMapID = $resultMap->getID();
		}
		parent::__construct();
	}

	/**
	 * @return mixed null value replacement.
	 */
	public function getNullValue()
	{
		return $this->_nullValue;
	}

	/**
	 * @param mixed $value null value replacement.
	 */
	public function setNullValue($value)
	{
		$this->_nullValue = $value;
	}

	/**
	 * @return string name of a property of the result object that will be set to.
	 */
	public function getProperty()
	{
		return $this->_propertyName;
	}

	/**
	 * @param string $value name of a property of the result object that will be set to.
	 */
	public function setProperty($value)
	{
		$this->_propertyName = $value;
	}

	/**
	 * @return string name of the column in the result set from which the value
	 * will be used to populate the property.
	 */
	public function getColumn()
	{
		return $this->_columnName;
	}

	/**
	 * @param string $value name of the column in the result set from which the value
	 * will be used to populate the property.
	 */
	public function setColumn($value)
	{
		$this->_columnName = $value;
	}

	/**
	 * @return int index of the column in the ResultSet from which the value will
	 * be used to populate the object property
	 */
	public function getColumnIndex()
	{
		return $this->_columnIndex;
	}

	/**
	 * @param int $value index of the column in the ResultSet from which the value will
	 * be used to populate the object property
	 */
	public function setColumnIndex($value)
	{
		$this->_columnIndex = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string ID of another <resultMap> used to fill the property.
	 */
	public function getResultMapping()
	{
		return $this->_nestedResultMapName;
	}

	/**
	 * @param string $value ID of another <resultMap> used to fill the property.
	 */
	public function setResultMapping($value)
	{
		$this->_nestedResultMapName = $value;
	}

	/**
	 * @return TResultMap nested result map.
	 */
	public function getNestedResultMap()
	{
		return $this->_nestedResultMap;
	}

	/**
	 * @param TResultMap $value nested result map.
	 */
	public function setNestedResultMap($value)
	{
		$this->_nestedResultMap = $value;
	}

	/**
	 * @return string property type of the object property to be set.
	 */
	public function getType()
	{
		return $this->_valueType;
	}

	/**
	 * @param string $value property type of the object property to be set.
	 */
	public function setType($value)
	{
		$this->_valueType = $value;
	}

	/**
	 * @return string custom type handler class name (may use namespace).
	 */
	public function getTypeHandler()
	{
		return $this->_typeHandler;
	}

	/**
	 * @param string $value custom type handler class name (may use namespace).
	 */
	public function setTypeHandler($value)
	{
		$this->_typeHandler = $value;
	}

	/**
	 * @return string name of another mapped statement
	 */
	public function getSelect()
	{
		return $this->_select;
	}

	/**
	 * The select property is used to describe a relationship between objects
	 * and to automatically load complex (i.e. user defined) property types.
	 * @param string $value name of another mapped statement.
	 */
	public function setSelect($value)
	{
		$this->_select = $value;
	}

	/**
	 * @return bool indicate whether or not the select statement's results should be lazy loaded
	 */
	public function getLazyLoad()
	{
		return $this->_isLazyLoad;
	}

	/**
	 * @param bool $value indicate whether or not the select statement's results should be lazy loaded
	 */
	public function setLazyLoad($value)
	{
		$this->_isLazyLoad = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Gets the value for the current property, converts to applicable type if necessary.
	 * @param \Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandlerRegistry $registry type handler registry
	 * @param array $row result row
	 * @return mixed property value.
	 */
	public function getPropertyValue($registry, $row)
	{
		$value = null;
		$index = $this->getColumnIndex();
		$name = $this->getColumn();
		if ($index > 0 && isset($row[$index])) {
			$value = $this->getTypedValue($registry, $row[$index]);
		} elseif (isset($row[$name])) {
			$value = $this->getTypedValue($registry, $row[$name]);
		}
		if (($value === null) && ($this->getNullValue() !== null)) {
			$value = $this->getTypedValue($registry, $this->getNullValue());
		}
		return $value;
	}

	/**
	 * @param \Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandlerRegistry $registry type handler registry
	 * @param mixed $value raw property value
	 * @return mixed property value casted to specific type.
	 */
	protected function getTypedValue($registry, $value)
	{
		if (($handler = $this->createTypeHandler($registry)) !== null) {
			return $handler->getResult($value);
		} else {
			return $registry->convertToType($this->getType(), $value);
		}
	}

	/**
	 * Create type handler from {@see Type setType()} or {@see TypeHandler setTypeHandler}.
	 * @param \Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandlerRegistry $registry type handler registry
	 * @return \Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler type handler.
	 */
	protected function createTypeHandler($registry)
	{
		$type = $this->getTypeHandler() ? $this->getTypeHandler() : $this->getType();
		$handler = $registry->getTypeHandler($type);
		if ($handler === null && $this->getTypeHandler()) {
			$handler = Prado::createComponent($type);
		}
		return $handler;
	}

	/**
	 * Determines if the type is an instance of \ArrayAccess, TList or an array.
	 * @return int TResultProperty::LIST_TYPE or TResultProperty::ARRAY_TYPE
	 */
	protected function getPropertyValueType()
	{
		if (class_exists($type = $this->getType(), false)) { //NO force autoloading
			if ($type === 'TList') {
				return self::LIST_TYPE;
			}
			$class = new ReflectionClass($type);
			if ($class->isSubclassOf('TList')) {
				return self::LIST_TYPE;
			}
			if ($class->implementsInterface('ArrayAccess')) {
				return self::ARRAY_TYPE;
			}
		}
		if (strtolower($type) == 'array') {
			return self::ARRAY_TYPE;
		}
		return self::LIST_TYPE;
	}

	/**
	 * Returns true if the result property {@see Type getType()} is of TList type
	 * or that the actual result object is an instance of TList.
	 * @param object $target result object
	 * @return bool true if the result object is an instance of TList
	 */
	public function instanceOfListType($target)
	{
		if ($this->getType() === null) {
			return  TPropertyAccess::get($target, $this->getProperty()) instanceof TList;
		}
		return $this->getPropertyValueType() == self::LIST_TYPE;
	}

	/**
	 * Returns true if the result property {@see Type getType()} is of \ArrayAccess
	 * or that the actual result object is an array or implements \ArrayAccess
	 * @param object $target result object
	 * @return bool true if the result object is an instance of \ArrayAccess or is an array.
	 */
	public function instanceOfArrayType($target)
	{
		if ($this->getType() === null) {
			$prop = TPropertyAccess::get($target, $this->getProperty());
			if (is_object($prop)) {
				return $prop instanceof \ArrayAccess;
			}
			return is_array($prop);
		}
		return $this->getPropertyValueType() == self::ARRAY_TYPE;
	}

	public function __sleep()
	{
		$exprops = [];
		$cn = 'TResultProperty';
		if ($this->_nullValue === null) {
			$exprops[] = "\0$cn\0_nullValue";
		}
		if ($this->_propertyName === null) {
			$exprops[] = "\0$cn\0_propertyNama";
		}
		if ($this->_columnName === null) {
			$exprops[] = "\0$cn\0_columnName";
		}
		if ($this->_columnIndex == -1) {
			$exprops[] = "\0$cn\0_columnIndex";
		}
		if ($this->_nestedResultMapName === null) {
			$exprops[] = "\0$cn\0_nestedResultMapName";
		}
		if ($this->_nestedResultMap === null) {
			$exprops[] = "\0$cn\0_nestedResultMap";
		}
		if ($this->_valueType === null) {
			$exprops[] = "\0$cn\0_valueType";
		}
		if ($this->_typeHandler === null) {
			$exprops[] = "\0$cn\0_typeHandler";
		}
		if ($this->_isLazyLoad === false) {
			$exprops[] = "\0$cn\0_isLazyLoad";
		}
		if ($this->_select === null) {
			$exprops[] = "\0$cn\0_select";
		}
		return array_diff(parent::__sleep(), $exprops);
	}
}
