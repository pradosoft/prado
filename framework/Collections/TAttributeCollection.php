<?php
/**
 * TAttributeCollection classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

use Prado\TPropertyValue;

/**
 * TAttributeCollection class
 *
 * TAttributeCollection implements a collection for storing attribute names and values.
 *
 * Besides all functionalities provided by {@link TMap}, TAttributeCollection
 * allows you to get and set attribute values like getting and setting
 * properties. For example, the following usages are all valid for a
 * TAttributeCollection object:
 * <code>
 * $collection->Text='text';
 * echo $collection->Text;
 * </code>
 * They are equivalent to the following:
 * <code>
 * $collection->add('Text','text');
 * echo $collection->itemAt('Text');
 * </code>
 *
 * Note, attribute names are case-insensitive. They are converted to lower-case
 * in the collection storage.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TAttributeCollection extends TMap
{
	protected $_caseSensitive = false;

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array &$exprops
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_caseSensitive === false) {
			$exprops[] = "\0*\0_caseSensitive";
		}
	}

	/**
	 * Returns a property value or an event handler list by property or event name.
	 * This method overrides the parent implementation by returning
	 * a key value if the key exists in the collection.
	 * @param string $name the property name or the event name
	 * @throws TInvalidOperationException if the property/event is not defined.
	 * @return mixed the property value or the event handler list
	 */
	public function __get($name)
	{
		return $this->contains($name) ? $this->itemAt($name) : parent::__get($name);
	}

	/**
	 * Sets value of a component property.
	 * This method overrides the parent implementation by adding a new key value
	 * to the collection.
	 * @param string $name the property name or event name
	 * @param mixed $value the property value or event handler
	 * @throws TInvalidOperationException If the property is not defined or read-only.
	 */
	public function __set($name, $value)
	{
		$this->add($name, $value);
	}

	/**
	 * @return bool whether the keys are case-sensitive. Defaults to false.
	 */
	public function getCaseSensitive()
	{
		return $this->_caseSensitive;
	}

	/**
	 * @param bool $value whether the keys are case-sensitive.
	 */
	public function setCaseSensitive($value)
	{
		$this->_caseSensitive = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Returns the item with the specified key.
	 * This overrides the parent implementation by converting the key to lower case first if CaseSensitive is false.
	 * @param mixed $key the key
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAt($key)
	{
		return parent::itemAt($this->_caseSensitive ? $key : strtolower($key));
	}


	/**
	 * Adds an item into the map.
	 * This overrides the parent implementation by converting the key to lower case first if CaseSensitive is false.
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function add($key, $value)
	{
		parent::add($this->_caseSensitive ? $key : strtolower($key), $value);
	}

	/**
	 * Removes an item from the map by its key.
	 * This overrides the parent implementation by converting the key to lower case first if CaseSensitive is false.
	 * @param mixed $key the key of the item to be removed
	 * @return mixed the removed value, null if no such key exists.
	 */
	public function remove($key)
	{
		return parent::remove($this->_caseSensitive ? $key : strtolower($key));
	}

	/**
	 * Returns whether the specified is in the map.
	 * This overrides the parent implementation by converting the key to lower case first if CaseSensitive is false.
	 * @param mixed $key the key
	 * @return bool whether the map contains an item with the specified key
	 */
	public function contains($key)
	{
		return parent::contains($this->_caseSensitive ? $key : strtolower($key));
	}

	/**
	 * Determines whether a property is defined.
	 * This method overrides parent implementation by returning true
	 * if the collection contains the named key.
	 * @param string $name the property name
	 * @return bool whether the property is defined
	 */
	public function hasProperty($name)
	{
		return $this->contains($name) || parent::canGetProperty($name) || parent::canSetProperty($name);
	}

	/**
	 * Determines whether a property can be read.
	 * This method overrides parent implementation by returning true
	 * if the collection contains the named key.
	 * @param string $name the property name
	 * @return bool whether the property can be read
	 */
	public function canGetProperty($name)
	{
		return $this->contains($name) || parent::canGetProperty($name);
	}

	/**
	 * Determines whether a property can be set.
	 * This method overrides parent implementation by always returning true
	 * because you can always add a new value to the collection.
	 * @param string $name the property name
	 * @return bool true
	 */
	public function canSetProperty($name)
	{
		return true;
	}
}
