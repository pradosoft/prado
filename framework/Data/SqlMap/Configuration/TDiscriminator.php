<?php
/**
 * TDiscriminator and TSubMap classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

use Prado\TPropertyValue;

/**
 * The TDiscriminator corresponds to the <discriminator> tag within a <resultMap>.
 *
 * TDiscriminator allows inheritance logic in SqlMap result mappings.
 * SqlMap compares the data found in the discriminator column to the different
 * <submap> values using the column value's string equivalence. When the string values
 * matches a particular <submap>, SqlMap will use the <resultMap> defined by
 * {@link resultMapping TSubMap::setResultMapping()} property for loading
 * the object data.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TDiscriminator extends \Prado\TComponent
{
	private $_column;
	private $_type;
	private $_typeHandler;
	private $_columnIndex;
	private $_nullValue;
	private $_mapping;
	private $_resultMaps = [];
	private $_subMaps = [];

	/**
	 * @return string the name of the column in the result set from which the
	 * value will be used to populate the property.
	 */
	public function getColumn()
	{
		return $this->_column;
	}

	/**
	 * @param string $value the name of the column in the result set from which the
	 * value will be used to populate the property.
	 */
	public function setColumn($value)
	{
		$this->_column = $value;
	}

	/**
	 * @return string property type of the parameter to be set.
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * The type attribute is used to explicitly specify the property type of the
	 * parameter to be set. If the attribute type is not set and the framework
	 * cannot otherwise determine the type, the type is assumed from the default
	 * value of the property.
	 * @param mixed $value
	 * @return string property type of the parameter to be set.
	 */
	public function setType($value)
	{
		$this->_type = $value;
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
	 * @return int index of the column in the ResultSet
	 */
	public function getColumnIndex()
	{
		return $this->_columnIndex;
	}

	/**
	 * The columnIndex attribute value is the index of the column in the
	 * ResultSet from which the value will be used to populate the object property.
	 * @param int $value index of the column in the ResultSet
	 */
	public function setColumnIndex($value)
	{
		$this->_columnIndex = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return mixed outgoing null value replacement.
	 */
	public function getNullValue()
	{
		return $this->_nullValue;
	}

	/**
	 * @param mixed $value outgoing null value replacement.
	 */
	public function setNullValue($value)
	{
		$this->_nullValue = $value;
	}

	/**
	 * @return TResultProperty result property for the discriminator column.
	 */
	public function getMapping()
	{
		return $this->_mapping;
	}

	/**
	 * @param TSubMap $subMap add new sub mapping.
	 */
	public function addSubMap($subMap)
	{
		$this->_subMaps[] = $subMap;
	}

	/**
	 * @param string $value database value
	 * @return TResultMap result mapping.
	 */
	public function getSubMap($value)
	{
		if (isset($this->_resultMaps[$value])) {
			return $this->_resultMaps[$value];
		}
	}

	/**
	 * Copies the discriminator properties to a new TResultProperty.
	 * @param TResultMap $resultMap result map holding the discriminator.
	 */
	public function initMapping($resultMap)
	{
		$this->_mapping = new TResultProperty($resultMap);
		$this->_mapping->setColumn($this->getColumn());
		$this->_mapping->setColumnIndex($this->getColumnIndex());
		$this->_mapping->setType($this->getType());
		$this->_mapping->setTypeHandler($this->getTypeHandler());
		$this->_mapping->setNullValue($this->getNullValue());
	}

	/**
	 * Set the result maps for particular sub-mapping values.
	 * @param TSqlMapManager $manager sql map manager instance.
	 */
	public function initialize($manager)
	{
		foreach ($this->_subMaps as $subMap) {
			$this->_resultMaps[$subMap->getValue()] =
				$manager->getResultMap($subMap->getResultMapping());
		}
	}
}
