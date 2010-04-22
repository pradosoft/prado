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
 * TPriorityList implements a priority ordered list collection class.  It allows you to specify 
 * floating numbers for priorities up to a specific precision.  There is also a default priority if
 * no priority is specified.  If you replace TList with this class it will work exactly the same with
 * priorities set to the default.
 *
 * As you access the array features of this class it flattens and caches the results.  It flushes the 
 * cache when elements are changed.
 *
 * You can access, append, insert, remove an item by using
 * {@link itemAt}, {@link add}, {@link insertAt}, {@link insertAtPriority}, and {@link remove}.
 * To get the number of the items in the list, use {@link getCount}.
 * TPriorityList can also be used like a regular array as follows,
 * <code>
 * $list[]=$item;  // append with the default priority
 * $list[$index]=$item; // $index must be between 0 and $list->Count.  This sets the element regardless of priority.  Priority stays the same.
 * unset($list[$index]); // remove the item at $index
 * if(isset($list[$index])) // if the list has an item at $index
 * foreach($list as $index=>$item) // traverse each item in the list in proper priority order and add/insert order
 * $n=count($list); // returns the number of items in the list
 * </code>
 *
 ** To extend TPriorityList by doing additional operations with each addition or removal
 ** operation, override {@link insertAtIndexInPriority()} and {@link removeAtIndexInPriority()}.
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
	 * @var boolean tells if the _d is currently ordered.
	 */
	private $_o=0;
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
	 * @var numeric the precision of the floating point priorities within this priority list
	 */
	private $_p=10;

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param array|Iterator the intial data. Default is null, meaning no initialization.
	 * @param boolean whether the list is read-only
	 * @param float the default priority of items without priorities.
	 * @param numeric the precision of the floating priorities
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data=null,$readOnly=false,$defaultPriority=10,$precision=8)
	{
		parent::__construct();
		if($data!==null)
			$this->copyFrom($data);
		$this->setReadOnly($readOnly);
		$this->setDefaultPriority($defaultPriority);
		$this->setPrecision($precision);
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
	 * @param numeric optional priority at which to count items.  if no parameter, it takes the default {@link getDefaultPriority}
	 * @return int the number of items in the list at the 
	 */
	public function getPriorityCount($priority=null)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		$priority = (string)round(TPropertyValue::ensureFloat($priority), $this->_p);
		
		if(!isset($this->_d[$priority]) || !is_array($this->_d[$priority])) return false;
		return count($this->_d[$priority]);
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
	 * @return int the precision of the floating priorities, defaults with 10
	 */
	public function getPrecision()
	{
		return $this->_p;
	}

	/**
	 * TPriorityList uses php function {@link round} on its priorities and thus it uses precision.
	 * @param int this sets the precision of the floating point priorities.
	 */
	protected function setPrecision($value)
	{
		$this->_p=TPropertyValue::ensureInteger($value);
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
	 * This is ordered lowest to highest.
	 * @return array the array of priorities
	 */
	public function getPriorities()
	{
		$this->flattenPriorities();
		return array_keys($this->_d);
	}
	

	/**
	 * this orders the priority list and flattens it into an array [0,...,n-1] 
	 * @return array of the values in the list in priority order
	 */
	protected function sortPriorities() {
		if(!$this->_o) {
			ksort($this->_d, SORT_NUMERIC);
			$this->_o = true;
		}
	}
	

	/**
	 * this orders the priority list and flattens it into an array [0,...,n-1] 
	 * @return array of the values in the list in priority order
	 */
	protected function flattenPriorities() {
		if(is_array($this->_fd))
			return $this->_fd;
		
		$this->sortPriorities();
		$this->_fd = array();
		foreach($this->_d as $priority => $itemsatpriority)
			$this->_fd = array_merge($this->_fd, $itemsatpriority);
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
	 * @return array the key list
	 */
	public function itemsAtPriority($priority=null)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		$priority = (string)round(TPropertyValue::ensureFloat($priority), $this->_p);
		
		return isset($this->_d[$priority]) ? $this->_d[$priority] : false;
	}

	/**
	 * Returns the item with the specified key.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param mixed the key
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAtIndexPriority($index,$priority=null)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		
		return !isset($this->_d[$priority]) ? false : (
				isset($this->_d[$priority][$index]) ? $this->_d[$priority][$index] : false
			);
	}

	/**
	 * Adds an item into the map.
	 * Note, if the specified key already exists, the old value will be overwritten.
	 * @param mixed key
	 * @param mixed value
	 * @return int the index within the flattened array
	 * @throws TInvalidOperationException if the map is read-only
	 */
	public function add($item, $priority=null)
	{
		return $this->insertAtIndexInPriority($item,false,$priority,true);
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
	public function insertAt($index, $item)
	{
		if($this->ReadOnly)
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		if(($priority=$this->priorityAt($index,true))!==false)
			$this->insertAtIndexInPriority($item,$priority[1],$priority[0]);
		else
			throw new TInvalidDataValueException('list_index_invalid',$index);
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
	public function insertAtIndexInPriority($item,$index=false,$priority=null,$indexItem=false)
	{
		
		if(!$this->ReadOnly)
		{
			if($priority===null)
				$priority=$this->DefaultPriority;
			
			$priority=(string)round(TPropertyValue::ensureFloat($priority), $this->_p);
			
			if($indexItem) {
				$this->sortPriorities();
				$cc=0;
				foreach($this->_d as $prioritykey => $items)
					if($prioritykey >= $priority)
						break;
					else
						$cc+=count($items);
				
				if($index === false) {
					//This string conversion allows floats as keys
					$c = count($this->_d[$priority]);
					if(!$c && $this->_c)
						$this->_o = false;
					$c += $cc;
					$this->_d[$priority][]=$item;
				} else if(isset($this->_d[$priority])) {
					$c = $index + $cc;
					array_splice($this->_d[$priority],$index,0,array($item));
				} else {
					$c = $cc;
					$this->_o = false;
					$this->_d[$priority]=array($item);
				}
				
				if($this->_fd && is_array($this->_fd))
					array_splice($this->_fd,$c,0,array($item));
			} else {
				$c = null;
				if($index === false) {
					$cc = count($this->_d[$priority]);
					if(!$cc && $this->_c)
						$this->_o = false;
					$this->_d[$priority][]=$item;
				} else if(isset($this->_d[$priority])) {
					$cc = $index;
					array_splice($this->_d[$priority],$index,0,array($item));
				} else {
					$cc = 0;
					$this->_o = false;
					$this->_d[$priority]=array($item);
				}
				if($this->_fd && is_array($this->_fd) && count($this->_d) == 1)
					array_splice($this->_fd,$cc,0,array($item));
				else
					$this->_fd = null;
			}
			
			$this->_c++;
			
			return $c;
		}
		else
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
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
		if(($priority=$this->priorityOf($item, true)) !== false)
		{
			$this->removeAtIndexInPriority($priority[1], $priority[0]);
			return $priority[2];
		}
		else
			throw new TInvalidDataValueException('list_item_inexistent');
	}

	/**
	 * Removes an item at the specified position.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function removeAt($index)
	{
		if($this->ReadOnly)
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		if(($priority = $this->priorityAt($index, true)) !== false)
			return $this->removeAtIndexInPriority($priority[1], $priority[0]);
		throw new TInvalidDataValueException('list_index_invalid',$index);
	}

	/**
	 * Removes an item from the list.
	 * The list will first search for the item.
	 * The first item found will be removed from the list.
	 * @param mixed the item to be removed.
	 * @return integer the index at which the item is being removed
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function removeAtPriority($item, $priority=null)
	{
		if($priority === null)
			$priority = $this->DefaultPriority;
		if(isset($this->_d[$priority]) && ($index=array_search($item,$this->_d[$priority],true))!==false)
			return $this->removeAtIndexInPriority($index, $priority);
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
	public function removeAtIndexInPriority($index, $priority=null)
	{
		if(!$this->ReadOnly)
		{
			if($priority === null)
				$priority = $this->DefaultPriority;
			
			$priority = (string)round(TPropertyValue::ensureFloat($priority), $this->_p);
			if(!isset($this->_d[$priority]) || !isset($this->_d[$priority][$index]))
				throw new TInvalidDataValueException('list_item_inexistent');
			
			// $value is an array of elements removed, only one
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
			for($index = count($items) - 1; $index >= 0; $index--)
				$this->removeAtIndexInPriority($index, $priority);
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
		// this is to ensure priority order
		$this->flattenPriorities();
		
		$absindex = 0;
		foreach($this->_d as $priority => $items) {
			if(($index=array_search($item,$items,true))!==false) {
				$absindex += $index;
				return $withindex ? 
					array($priority, $index, $absindex, 
						'priority' => $priority, 'index' => $index, 'absindex' => $absindex) : $priority;
			} else
				$absindex += count($items);
		}
		
		return false;
	}

	/**
	 * @param mixed the item
	 * @return integer the index of the item in the list (0 based), false if not found.
	 */
	public function priorityAt($index, $withindex = false)
	{
		if($index < 0 || $index >= $this->Count)
			throw new TInvalidDataValueException('list_index_invalid',$index);
		
		// this is to ensure priority order
		$absindex = $index;
		$this->flattenPriorities();
		foreach($this->_d as $priority => $items) {
			if($index >= ($c = count($items)))
				$index -= $c;
			else
				return $withindex ? 
					array($priority, $index, $absindex, 
						'priority' => $priority, 'index' => $inde, 'absindex' => $absindexx) : $priority;
		}
		return false;
	}

	/**
	 * @param mixed the item to index
	 * @param mixed the item
	 */
	public function insertBefore($indexitem, $item)
	{
		if(($priority = $this->priorityOf($indexitem, true)) === false)
			throw new TInvalidDataValueException('list_item_inexistent');
		
		$this->insertAtIndexInPriority($item, $priority[1], $priority[0]);
		
		return $priority[2];
	}

	/**
	 * @param mixed the item to index
	 * @param mixed the item
	 */
	public function insertAfter($indexitem, $item)
	{
		if(($priority = $this->priorityOf($indexitem, true)) === false)
			throw new TInvalidDataValueException('list_item_inexistent');
		
		$this->insertAtIndexInPriority($item, $priority[1]+1, $priority[0]);
		
		return $priority[2]+1;
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->flattenPriorities();
	}

	/**
	 * @return array the list of items in array with array keys as priorities and items as arrays of items
	 */
	public function toPriorityArray()
	{
		$this->flattenPriorities();
		return $this->_d;
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
				foreach($data->itemsAtPriority($priority) as $index => $item)
					$this->insertAtIndexInPriority($item, $index, $priority);
			}
			
		} else if(is_array($data) || $data instanceof Traversable) {
			if($this->getCount()>0)
				$this->clear();
			foreach($data as $key=>$item)
				$this->add($item);
				
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
				foreach($data->itemsAtPriority($priority) as $index => $item)
					$this->insertAtIndexInPriority($item, $index, $priority);
			}
		} else if(is_array($data) || $data instanceof Traversable) {
			foreach($data as $priority=>$item)
				$this->add($item);
			
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
		$priority = $this->priorityAt($offset, true);
		$this->removeAtIndexInPriority($priority[1], $priority[0]);
		$this->insertAtIndexInPriority($item, $priority[1], $priority[0]);
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param mixed the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		$this->removeAt($offset);
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
