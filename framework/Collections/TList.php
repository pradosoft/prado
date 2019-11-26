<?php
/**
 * TList class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TList class
 *
 * TList implements an integer-indexed collection class.
 *
 * You can access, append, insert, remove an item by using
 * {@link itemAt}, {@link add}, {@link insertAt}, {@link remove}, and {@link removeAt}.
 * To get the number of the items in the list, use {@link getCount}.
 * TList can also be used like a regular array as follows,
 * <code>
 * $list[]=$item;  // append at the end
 * $list[$index]=$item; // $index must be between 0 and $list->Count
 * unset($list[$index]); // remove the item at $index
 * if(isset($list[$index])) // if the list has an item at $index
 * foreach($list as $index=>$item) // traverse each item in the list
 * $n=count($list); // returns the number of items in the list
 * </code>
 *
 * To extend TList by doing additional operations with each addition or removal
 * operation, override {@link insertAt()}, and {@link removeAt()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TList extends \Prado\TComponent implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * internal data storage
	 * @var array
	 */
	private $_d = [];
	/**
	 * number of items
	 * @var int
	 */
	private $_c = 0;
	/**
	 * @var bool whether this list is read-only
	 */
	private $_r = false;

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param null|array|Iterator $data the initial data. Default is null, meaning no initialization.
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
	 * @return bool whether this list is read-only or not. Defaults to false.
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
	 * Returns the number of items in the list.
	 * This method is required by \Countable interface.
	 * @return int number of items in the list.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * @return int the number of items in the list
	 */
	public function getCount()
	{
		return $this->_c;
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param int $index the index of the item
	 * @throws TInvalidDataValueException if the index is out of the range
	 * @return mixed the item at the index
	 */
	public function itemAt($index)
	{
		if ($index >= 0 && $index < $this->_c) {
			return $this->_d[$index];
		} else {
			throw new TInvalidDataValueException('list_index_invalid', $index);
		}
	}

	/**
	 * Appends an item at the end of the list.
	 * @param mixed $item new item
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the zero-based index at which the item is added
	 */
	public function add($item)
	{
		$this->insertAt($this->_c, $item);
		return $this->_c - 1;
	}

	/**
	 * Inserts an item at the specified position.
	 * Original item at the position and the next items
	 * will be moved one step towards the end.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function insertAt($index, $item)
	{
		if (!$this->_r) {
			if ($index === $this->_c) {
				$this->_d[$this->_c++] = $item;
			} elseif ($index >= 0 && $index < $this->_c) {
				array_splice($this->_d, $index, 0, [$item]);
				$this->_c++;
			} else {
				throw new TInvalidDataValueException('list_index_invalid', $index);
			}
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * Removes an item from the list.
	 * The list will first search for the item.
	 * The first item found will be removed from the list.
	 * @param mixed $item the item to be removed.
	 * @throws TInvalidDataValueException If the item does not exist
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the index at which the item is being removed
	 */
	public function remove($item)
	{
		if (!$this->_r) {
			if (($index = $this->indexOf($item)) >= 0) {
				$this->removeAt($index);
				return $index;
			} else {
				throw new TInvalidDataValueException('list_item_inexistent');
			}
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * Removes an item at the specified position.
	 * @param int $index the index of the item to be removed.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		if (!$this->_r) {
			if ($index >= 0 && $index < $this->_c) {
				$this->_c--;
				if ($index === $this->_c) {
					return array_pop($this->_d);
				} else {
					$item = $this->_d[$index];
					array_splice($this->_d, $index, 1);
					return $item;
				}
			} else {
				throw new TInvalidDataValueException('list_index_invalid', $index);
			}
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * Removes all items in the list.
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function clear()
	{
		for ($i = $this->_c - 1;$i >= 0;--$i) {
			$this->removeAt($i);
		}
	}

	/**
	 * @param mixed $item the item
	 * @return bool whether the list contains the item
	 */
	public function contains($item)
	{
		return $this->indexOf($item) >= 0;
	}

	/**
	 * @param mixed $item the item
	 * @return int the index of the item in the list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		if (($index = array_search($item, $this->_d, true)) === false) {
			return -1;
		} else {
			return $index;
		}
	}

	/**
	 * Finds the base item.  If found, the item is inserted before it.
	 * @param mixed $baseitem the base item which will be pushed back by the second parameter
	 * @param mixed $item the item
	 * @throws TInvalidDataValueException if the base item is not within this list
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the index where the item is inserted
	 * @since 3.2a
	 */
	public function insertBefore($baseitem, $item)
	{
		if (!$this->_r) {
			if (($index = $this->indexOf($baseitem)) == -1) {
				throw new TInvalidDataValueException('list_item_inexistent');
			}

			$this->insertAt($index, $item);

			return $index;
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * Finds the base item.  If found, the item is inserted after it.
	 * @param mixed $baseitem the base item which comes before the second parameter when added to the list
	 * @param mixed $item the item
	 * @throws TInvalidDataValueException if the base item is not within this list
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the index where the item is inserted
	 * @since 3.2a
	 */
	public function insertAfter($baseitem, $item)
	{
		if (!$this->_r) {
			if (($index = $this->indexOf($baseitem)) == -1) {
				throw new TInvalidDataValueException('list_item_inexistent');
			}

			$this->insertAt($index + 1, $item);

			return $index + 1;
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Copies iterable data into the list.
	 * Note, existing data in the list will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor a Traversable.
	 */
	public function copyFrom($data)
	{
		if (is_array($data) || ($data instanceof \Traversable)) {
			if ($this->_c > 0) {
				$this->clear();
			}
			foreach ($data as $item) {
				$this->add($item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('list_data_not_iterable');
		}
	}

	/**
	 * Merges iterable data into the map.
	 * New data will be appended to the end of the existing data.
	 * @param mixed $data the data to be merged with, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data)
	{
		if (is_array($data) || ($data instanceof \Traversable)) {
			foreach ($data as $item) {
				$this->add($item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('list_data_not_iterable');
		}
	}

	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to check on
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return ($offset >= 0 && $offset < $this->_c);
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to retrieve item.
	 * @throws TInvalidDataValueException if the offset is invalid
	 * @return mixed the item at the offset
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}

	/**
	 * Sets the item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to set item
	 * @param mixed $item the item value
	 */
	public function offsetSet($offset, $item)
	{
		if ($offset === null || $offset === $this->_c) {
			$this->insertAt($this->_c, $item);
		} else {
			$this->removeAt($offset);
			$this->insertAt($offset, $item);
		}
	}

	/**
	 * Unsets the item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to unset item
	 */
	public function offsetUnset($offset)
	{
		$this->removeAt($offset);
	}
}
