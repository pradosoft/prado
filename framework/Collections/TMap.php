<?php
/**
 * TMap class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TPropertyValue;
use Traversable;

/**
 * TMap class
 *
 * TMap implements a collection that takes key-value pairs.
 *
 * You can access, add or remove an item with a key by using
 * {@link itemAt}, {@link add}, and {@link remove}.
 * To get the number of the items in the map, use {@link getCount}.
 * TMap can also be used like a regular array as follows,
 * <code>
 * $map[$key]=$value; // add a key-value pair
 * unset($map[$key]); // remove the value with the specified key
 * if(isset($map[$key])) // if the map contains the key
 * foreach($map as $key=>$value) // traverse the items in the map
 * $n=count($map);  // returns the number of items in the map
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TMap extends \Prado\TComponent implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * @var array internal data storage
	 */
	protected $_d = [];
	/**
	 * @var bool whether this list is read-only
	 */
	protected $_r = false;

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
		if ($this->_d === []) {
			$exprops[] = "\0*\0_d";
		}
		if ($this->_r === false) {
			$exprops[] = "\0*\0_r";
		}
	}

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param null|array|Iterator $data the intial data. Default is null, meaning no initialization.
	 * @param bool $readOnly whether the list is read-only
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data = null, $readOnly = false)
	{
		if ($data !== null) {
			$this->copyFrom($data);
		}
		$this->setReadOnly($readOnly);
	}

	/**
	 * @return bool whether this map is read-only or not. Defaults to false.
	 */
	public function getReadOnly()
	{
		return $this->_r;
	}

	/**
	 * @param bool $value whether this list is read-only or not
	 */
	protected function setReadOnly($value)
	{
		$this->_r = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface \IteratorAggregate.
	 * @return \Iterator an iterator for traversing the items in the list.
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->_d);
	}

	/**
	 * Returns the number of items in the map.
	 * This method is required by \Countable interface.
	 * @return int number of items in the map.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * @return int the number of items in the map
	 */
	public function getCount()
	{
		return count($this->_d);
	}

	/**
	 * @return array the key list
	 */
	public function getKeys()
	{
		return array_keys($this->_d);
	}

	/**
	 * Returns the item with the specified key.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param mixed $key the key
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAt($key)
	{
		return isset($this->_d[$key]) ? $this->_d[$key] : null;
	}

	/**
	 * Adds an item into the map.
	 * Note, if the specified key already exists, the old value will be overwritten.
	 * @param mixed $key
	 * @param mixed $value
	 * @throws TInvalidOperationException if the map is read-only
	 */
	public function add($key, $value)
	{
		if (!$this->_r) {
			$this->_d[$key] = $value;
		} else {
			throw new TInvalidOperationException('map_readonly', get_class($this));
		}
	}

	/**
	 * Removes an item from the map by its key.
	 * @param mixed $key the key of the item to be removed
	 * @throws TInvalidOperationException if the map is read-only
	 * @return mixed the removed value, null if no such key exists.
	 */
	public function remove($key)
	{
		if (!$this->_r) {
			if (isset($this->_d[$key]) || array_key_exists($key, $this->_d)) {
				$value = $this->_d[$key];
				unset($this->_d[$key]);
				return $value;
			} else {
				return null;
			}
		} else {
			throw new TInvalidOperationException('map_readonly', get_class($this));
		}
	}

	/**
	 * Removes all items in the map.
	 */
	public function clear()
	{
		foreach (array_keys($this->_d) as $key) {
			$this->remove($key);
		}
	}

	/**
	 * @param mixed $key the key
	 * @return bool whether the map contains an item with the specified key
	 */
	public function contains($key)
	{
		return isset($this->_d[$key]) || array_key_exists($key, $this->_d);
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Copies iterable data into the map.
	 * Note, existing data in the map will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function copyFrom($data)
	{
		if (is_array($data) || $data instanceof Traversable) {
			if ($this->getCount() > 0) {
				$this->clear();
			}
			foreach ($data as $key => $value) {
				$this->add($key, $value);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
	}

	/**
	 * Merges iterable data into the map.
	 * Existing data in the map will be kept and overwritten if the keys are the same.
	 * @param mixed $data the data to be merged with, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data)
	{
		if (is_array($data) || $data instanceof Traversable) {
			foreach ($data as $key => $value) {
				$this->add($key, $value);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param mixed $offset the offset to check on
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->contains($offset);
	}

	/**
	 * Returns the element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}

	/**
	 * Sets the element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to set element
	 * @param mixed $item the element value
	 */
	public function offsetSet($offset, $item)
	{
		$this->add($offset, $item);
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param mixed $offset the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		$this->remove($offset);
	}
}
