<?php
/**
 * TPriorityList, TPriorityListIterator classes
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TPriorityList.php 2541 2008-10-21 15:05:13Z javalizard $
 * @package System.Collections
 */

/**
 * TPriorityList class
 *
 * TPriorityList implements a priority ordered list collection class.
 *
 ** You can access, append, insert, remove an item by using
 ** {@link itemAt}, {@link add}, {@link insertAt}, and {@link remove}.
 ** To get the number of the items in the list, use {@link getCount}.
 ** TPriorityList can also be used like a regular array as follows,
 ** <code>
 ** $list[]=$item;  // append with the default priority
 ** $list[$index]=$item; // $index must be between 0 and $list->Count.  This sets the element regardless of priority.  Priority stays the same.
 ** unset($list[$index]); // remove the item at $index
 ** if(isset($list[$index])) // if the list has an item at $index
 ** foreach($list as $index=>$item) // traverse each item in the list
 ** $n=count($list); // returns the number of items in the list
 ** </code>
 *
 ** To extend TPriorityList by doing additional operations with each addition or removal
 ** operation, override {@link insertAt()}, and {@link removeAt()}.
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @version $Id: TPriorityList.php 2541 2008-10-21 15:05:13Z javalizard $
 * @package System.Collections
 * @since 3.2a
 */
class TPriorityList extends TList 
{
	/**
	 * @var array internal data storage
	 */
	private $_d=array();
	/**
	 * @var array cached flattened internal data storage
	 */
	private $_fd=null;
	/**
	 * @var array cached flattened internal data storage
	 */
	private $_c=0;
	/**
	 * @var numeric the default priority of items added if not specified
	 */
	private $_dp=10;

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param array|Iterator the intial data. Default is null, meaning no initialization.
	 * @param boolean whether the list is read-only
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data=null,$readOnly=false,$defaultPriority=10)
	{
		if($data!==null)
			$this->copyFrom($data);
		$this->setReadOnly($readOnly);
		$this->setDefaultPriority($defaultPriority);
	}

	/**
	 * Returns the number of items in the list.
	 * This method is required by Countable interface.
	 * @return integer number of items in the list.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * @return integer the number of items in the list
	 */
	public function getCount()
	{
		return $this->_c;
	}

	/**
	 * @return boolean whether this map is read-only or not. Defaults to false.
	 */
	public function getDefaultPriority()
	{
		return $this->_dp;
	}

	/**
	 * @param boolean whether this list is read-only or not
	 */
	protected function setDefaultPriority($value)
	{
		$this->_dp=TPropertyValue::ensureFloat($value);
	}

	/**
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface IteratorAggregate.
	 * @return Iterator an iterator for traversing the items in the list.
	 */
	public function getIterator()
	{
		return new TPriorityListIterator($this->flattenPriorities());
	}

	/**
	 * @return integer the number of items in the list
	 */
	public function getPriorityCount($priority=null)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		$priority = (string)TPropertyValue::ensureFloat($priority);
		
		if(!isset($this->_d[$priority])) return 0;
		return count($this->_d[$priority]);
	}

	/**
	 * @return array the key list
	 */
	public function getPriorities()
	{
		return array_keys($this->_d);
	}

	/**
	 * @return array the key list
	 */
	public function getPriority($priority=null)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		$priority = (string)TPropertyValue::ensureFloat($priority);
		
		return isset($this->_d[$priority]) ? $this->_d[$priority] : false;
	}
	

	/**
	 * this orders the priority list and flattens it into an array [0,...,n-1] 
	 * @return array of the values in the list in priority order
	 */
	protected function flattenPriorities() {
		if(is_array($this->_fd))
			return $this->_fd;
		
		ksort($this->_d, SORT_NUMERIC);
		$this->_fd = array();
		foreach($this->_d as $priority => $atpriority)
			$this->_fd = array_merge($this->_fd, $atpriority);
		return $this->_fd;
	}

	/**
	 * Returns the item with the specified key.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param mixed the key
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAt($index)
	{
		if($index>=0 && $index<$this->_c) {
			$arr = $this->flattenPriorities();
			return $arr[$index];
		} else
			throw new TInvalidDataValueException('list_index_invalid',$index);
	}

	/**
	 * Returns the item with the specified key.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param mixed the key
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAtPriority($priority, $index)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		
		return !isset($this->_d[$priority]) ? null : (
				isset($this->_d[$priority][$index]) ? $this->_d[$priority][$index] : null
			);
	}

	/**
	 * Adds an item into the map.
	 * Note, if the specified key already exists, the old value will be overwritten.
	 * @param mixed key
	 * @param mixed value
	 * @throws TInvalidOperationException if the map is read-only
	 */
	public function add($item, $priority=null, $index=false)
	{
		$this->insertAt($item, $priority, $index);
		return $this->_c-1;
	}

	/**
	 * Inserts an item at the specified position.
	 * Original item at the position and the next items
	 * will be moved one step towards the end.
	 * @param integer the specified position.
	 * @param mixed new item
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function insertAt($item, $priority=null, $index=false)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		
		$priority = (string)TPropertyValue::ensureFloat($priority);
		
		if(!$this->ReadOnly)
		{
			if($index === false) {
				//This string conversion allows floats as keys
				$this->_d[$priority][]=$item;
			} else if(isset($this->_d[$priority]) && is_array($this->_d[$priority]))
				array_splice($this->_d[$priority],$index,0,array($item));
			else
				$this->_d[$priority]=array($item);
				
		}
		else
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		$this->_fd = null;
		
		return ++$this->_c;
	}

	/**
	 * Removes an item from the list.
	 * The list will first search for the item.
	 * The first item found will be removed from the list.
	 * @param mixed the item to be removed.
	 * @return integer the index at which the item is being removed
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function remove($item)
	{
		if(($priority=$this->priorityOf($item, true)) !== null)
		{
			return $this->removeAt($item, $priority[0], $priority[1]);
		}
		else
			throw new TInvalidDataValueException('list_item_inexistent');
	}

	/**
	 * Removes an item from the list.
	 * The list will first search for the item.
	 * The first item found will be removed from the list.
	 * @param mixed the item to be removed.
	 * @return integer the index at which the item is being removed
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function removeAt($item, $priority=null, $index=false)
	{
		if(!$this->ReadOnly)
		{
			if($priority === null)
				$priority = $this->DefaultPriority;
			
			$priority = (string)TPropertyValue::ensureFloat($priority);
			
			if($priority === false) {
				if(($priority=$this->priorityOf($item, true)) !== null) {
					$index = $priority[1];
					$priority = $priority[0];
				}
			} else if($index === false) {
				if(($index=array_search($item,$this->_d[$priority],true))===false)
					return false;
			}
			if(!isset($this->_d[$priority]) || !isset($this->_d[$priority][$index])) return false;
			
			//$value = $this->_d[$priority][$index];
			//unset($this->_d[$priority][$index]);
			$value = array_splice($this->_d[$priority],$index,1);
			$value = $value[0];
			if(!count($this->_d[$priority]))
				unset($this->_d[$priority]);
			$this->_c--;
			$this->_fd = null;
			return $value;
		}
		else
			throw new TInvalidOperationException('list_readonly',get_class($this));
	}

	/**
	 * Removes all items in the priority list.
	 */
	public function clear()
	{
		foreach($this->_d as $priority => $items) {
			$items = array_reverse($items);
			foreach($items as $index => $item)
				$this->removeAt($item, array($priority, $index));
			unset($this->_d[$priority]);
		}
	}

	/**
	 * @param mixed the item
	 * @return boolean whether the list contains the item
	 */
	public function contains($item)
	{
		return $this->indexOf($item)>=0;
	}

	/**
	 * @param mixed the item
	 * @return integer the index of the item in the list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		if(($index=array_search($item,$this->flattenPriorities(),true))===false)
			return -1;
		else
			return $index;
	}

	/**
	 * @param mixed the item
	 * @return integer the index of the item in the list (0 based), false if not found.
	 */
	public function priorityOf($item, $withindex = false)
	{
		foreach($this->_d as $priority => $items)
			if(($index=array_search($item,$items,true))!==false)
				return $withindex ? array($priority, $index, 'priority' => $priority, 'index' => $index) : $priority;
		
		return false;
	}

	/**
	 * @param mixed the item to index
	 * @param mixed the item
	 */
	public function insertBefore($indexitem, $item)
	{
		if(($priority = $this->priorityOf($indexitem, true)) == -1) return -1;
		
		return $this->insertAt($item, $priority[0], $priority[1]);
	}

	/**
	 * @param mixed the item to index
	 * @param mixed the item
	 */
	public function insertAfter($indexitem, $item)
	{
		if(($priority = $this->priorityOf($indexitem, true)) == -1) return -1;
		
		return $this->insertAt($item, $priority[0], $priority[1] + 1);
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->flattenPriorities();
	}
	

	/**
	 * Copies iterable data into the map.
	 * Note, existing data in the map will be cleared first.
	 * @param mixed the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function copyFrom($data)
	{
		if($data instanceof TPriorityList) {
			if($this->getCount()>0)
				$this->clear();
			foreach($data->Priorities as $priority) {
				foreach($data->getPriority($priority) as $index => $item)
					$this->add($item, $priority);
			}
		} else if(is_array($data) || $data instanceof Traversable) {
			if($this->getCount()>0)
				$this->clear();
			foreach($data as $priority=>$item)
				$this->add($item, $priority);
				
		} else if($data!==null)
			throw new TInvalidDataTypeException('map_data_not_iterable');
	}

	/**
	 * Merges iterable data into the map.
	 * Existing data in the map will be kept and overwritten if the keys are the same.
	 * @param mixed the data to be merged with, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data)
	{
		if($data instanceof TPriorityList) {
			foreach($data->Priorities as $priority) {
				foreach($data->getPriority($priority) as $index => $item)
					$this->add($item, $priority);
			}
		} else if(is_array($data) || $data instanceof Traversable) {
			foreach($data as $priority=>$value)
				$this->add($value, $priority);
		} else if($data!==null)
			throw new TInvalidDataTypeException('map_data_not_iterable');
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param mixed the offset to check on
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset>=0 && $offset<$this->_c);
	}

	/**
	 * Returns the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}

	/**
	 * Sets the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer the offset to set element
	 * @param mixed the element value
	 */
	public function offsetSet($offset,$item)
	{
		if($offset === null)
			return $this->add($item);
		$olditem = $this->itemAt($offset);
		$priority = $this->priorityOf($olditem, true);
		$this->removeAt($olditem, $priority[0], $priority[1]);
		$this->add($item, $priority[0], $priority[1]);
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param mixed the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		$this->remove($this->itemAt($offset));
	}
}

/**
 * TPriorityListIterator class
 *
 * TPriorityListIterator implements Iterator interface.
 *
 * TPriorityListIterator is used by TPriorityList. It allows TPriorityList to return a new iterator
 * for traversing the items in the map.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TPriorityList.php 2541 2008-10-21 15:05:13Z qiang.xue $
 * @package System.Collections
 * @since 3.0
 */
class TPriorityListIterator implements Iterator
{
	/**
	 * @var array the data to be iterated through
	 */
	private $_d;
	/**
	 * @var array list of keys in the map
	 */
	private $_keys;
	/**
	 * @var mixed current key
	 */
	private $_key;

	/**
	 * Constructor.
	 * @param array the data to be iterated through
	 */
	public function __construct(&$data)
	{
		$this->_d=&$data;
		$this->_keys=array_keys($data);
	}

	/**
	 * Rewinds internal array pointer.
	 * This method is required by the interface Iterator.
	 */
	public function rewind()
	{
		$this->_key=reset($this->_keys);
	}

	/**
	 * Returns the key of the current array element.
	 * This method is required by the interface Iterator.
	 * @return mixed the key of the current array element
	 */
	public function key()
	{
		return $this->_key;
	}

	/**
	 * Returns the current array element.
	 * This method is required by the interface Iterator.
	 * @return mixed the current array element
	 */
	public function current()
	{
		return $this->_d[$this->_key];
	}

	/**
	 * Moves the internal pointer to the next array element.
	 * This method is required by the interface Iterator.
	 */
	public function next()
	{
		$this->_key=next($this->_keys);
	}

	/**
	 * Returns whether there is an element at current position.
	 * This method is required by the interface Iterator.
	 * @return boolean
	 */
	public function valid()
	{
		return $this->_key!==false;
	}
}
