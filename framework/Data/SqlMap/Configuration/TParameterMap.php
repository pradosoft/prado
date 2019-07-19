<?php
/**
 * TParameterMap class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Data\SqlMap\DataMapper\TInvalidPropertyException;
use Prado\Data\SqlMap\DataMapper\TPropertyAccess;
use Prado\Data\SqlMap\DataMapper\TSqlMapException;
use Prado\Prado;

/**
 * TParameterMap corresponds to the <parameterMap> element.
 *
 * TParameterMap holds one or more parameter child elements that map object
 * properties to placeholders in a SQL statement.
 *
 * A TParameterMap defines an ordered list of values that match up with the
 * placeholders of a parameterized query statement. While the attributes
 * specified by the map still need to be in the correct order, each parameter
 * is named. You can populate the underlying class in any order, and the
 * TParameterMap ensures each value is passed in the correct order.
 *
 * Parameter Maps can be provided as an external element and inline.
 * The <parameterMap> element accepts two attributes: id (required) and extends (optional).
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TParameterMap extends \Prado\TComponent
{
	private $_extend;
	private $_properties;
	private $_propertyMap;
	private $_extendMap;
	private $_ID;

	/**
	 * Initialize the properties and property map collections.
	 */
	public function __construct()
	{
		$this->_properties = new TList;
		$this->_propertyMap = new TMap;
	}

	/**
	 * @return string a unique identifier for the <parameterMap>.
	 */
	public function getID()
	{
		return $this->_ID;
	}

	/**
	 * @param string $value a unique identifier for the <parameterMap>.
	 */
	public function setID($value)
	{
		$this->_ID = $value;
	}

	/**
	 * @return TParameterProperty[] list of properties for the parameter map.
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * @return string name of another <parameterMap> upon which to base this TParameterMap.
	 */
	public function getExtends()
	{
		return $this->_extend;
	}

	/**
	 * @param string $value name of another <parameterMap> upon which to base this TParameterMap.
	 */
	public function setExtends($value)
	{
		$this->_extend = $value;
	}

	/**
	 * @param int|string $index name of a parameter property.
	 * @throws TSqlMapException if index is not string nor integer.
	 * @return TParameterProperty parameter property.
	 */
	public function getProperty($index)
	{
		if (is_string($index)) {
			return $this->_propertyMap->itemAt($index);
		} elseif (is_int($index)) {
			return $this->_properties->itemAt($index);
		} else {
			throw new TSqlMapException('sqlmap_index_must_be_string_or_int', $index);
		}
	}

	/**
	 * @param TParameterProperty $property new parameter property
	 */
	public function addProperty(TParameterProperty $property)
	{
		$this->_propertyMap->add($property->getProperty(), $property);
		$this->_properties->add($property);
	}

	/**
	 * @param int $index parameter property index
	 * @param TParameterProperty $property new parameter property.
	 */
	public function insertProperty($index, TParameterProperty $property)
	{
		$this->_propertyMap->add($property->getProperty(), $property);
		$this->_properties->insertAt($index, $property);
	}

	/**
	 * @return array list of property names.
	 */
	public function getPropertyNames()
	{
		return $this->_propertyMap->getKeys();
	}

	/**
	 * Get the value of a property from the the parameter object.
	 * @param TSqlMapTypeHandlerRegistry $registry type handler registry.
	 * @param TParameterProperty $property parameter proproperty.
	 * @param mixed $parameterValue parameter object to get the value from.
	 * @return unknown
	 */
	public function getPropertyValue($registry, $property, $parameterValue)
	{
		$value = $this->getObjectValue($parameterValue, $property);

		if (($handler = $this->createTypeHandler($property, $registry)) !== null) {
			$value = $handler->getParameter($value);
		}

		$value = $this->nullifyDefaultValue($property, $value);

		if (($type = $property->getType()) !== null) {
			$value = $registry->convertToType($type, $value);
		}

		return $value;
	}


	/**
	 * Create type handler from {@link Type setType()} or {@link TypeHandler setTypeHandler}.
	 * @param TParameterProperty $property parameter property
	 * @param TSqlMapTypeHandlerRegistry $registry type handler registry
	 * @return TSqlMapTypeHandler type handler.
	 */
	protected function createTypeHandler($property, $registry)
	{
		$type = $property->getTypeHandler() ? $property->getTypeHandler() : $property->getType();
		$handler = $registry->getTypeHandler($type);
		if ($handler === null && $property->getTypeHandler()) {
			$handler = Prado::createComponent($type);
		}
		return $handler;
	}


	/**
	 * @param mixed $object object to obtain the property from.
	 * @param TParameterProperty $property parameter property.
	 * @throws TSqlMapException if property access is invalid.
	 * @return mixed property value.
	 */
	protected function getObjectValue($object, $property)
	{
		try {
			return TPropertyAccess::get($object, $property->getProperty());
		} catch (TInvalidPropertyException $e) {
			throw new TSqlMapException(
				'sqlmap_unable_to_get_property_for_parameter',
				$this->getID(),
				$property->getProperty(),
				(is_object($object) ? get_class($object) : gettype($object))
			);
		}
	}

	/**
	 * When the actual value matches the {@link NullValue TParameterProperty::setNullValue()},
	 * set the current value to null.
	 * @param TParameterProperty $property parameter property.
	 * @param mixed $value current property value
	 * @return mixed null if NullValue matches currrent value.
	 */
	protected function nullifyDefaultValue($property, $value)
	{
		if (($nullValue = $property->getNullValue()) !== null) {
			if ($nullValue === $value) {
				$value = null;
			}
		}
		return $value;
	}
}
