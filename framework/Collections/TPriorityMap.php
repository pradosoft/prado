<?php
/**
 * TPriorityMap, TPriorityMapIterator classes
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TPriorityMap.php 2817 2010-04-18 04:25:03Z javalizard $
 * @package System.Collections
 */

/**
 * TPriorityMap class
 *
 * TPriorityMap implements a collection that takes key-value pairs with 
 * a priority to allow key-value pairs to be ordered.  This ordering is 
 * important when flattening the map. When flattening the map, if some
 * key-value pairs are required to be before or after others, use this 
 * class to keep order to your map.
 *
 * You can access, add or remove an item with a key by using
 * {@link itemAt}, {@link add}, and {@link remove}.  These functions 
 * can optionally take a priority parameter to allow access to specific 
 * priorities.  TPriorityMap is functionally backward compatible 
 * with {@link TMap}.
 * 
 * To get the number of the items in the map, use {@link getCount}.
 * TPriorityMap can also be used like a regular array as follows,
 * <code>
 * $map[$key]=$value; // add a key-value pair
 * unset($map[$key]); // remove the value with the specified key
 * if(isset($map[$key])) // if the map contains the key
 * foreach($map as $key=>$value) // traverse the items in the map
 * $n=count($map);  // returns the number of items in the map
 * </code>
 * Using standard array access method like these will always use
 * the default priority.
 *
 * An item that doesn't specify a priority will receive the default
 * priority.  The default priority is set during the instantiation
 * of a new TPriorityMap. If no custom default priority is specified, 
 * the standard default priority of 10 is used.
 *
 * Priorities with significant digits below precision will be rounded.
 *
 * A priority may also be a numeric with decimals.  This is set 
 * during the instantiation of a new TPriorityMap. 
 * The default is 8 decimal places for a priority.  If a negative number
 * is used, rounding occurs into the integer space rather than in
 * the decimal space.  See {@link round}.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id: TPriorityMap.php 2817 2010-04-18 04:25:03Z javalizard $
 * @package System.Collections
 * @since 3.2a
 */
Prado::using('System.Collections.TMap');

class TPriorityMap extends TMap
{
	/**
	 * @var array internal data storage
	 */
	private $_d=array();
	/**
	 * @var boolean whether this list is read-only
	 */
	private $_r=false;
	/**
	 * @var boolean indicates if the _d is currently ordered.
	 */
	private $_o=false;
	/**
	 * @var array cached flattened internal data storage
	 */
	private $_fd=null;
	/**
	 * @var integer number of items contain within the map
	 */
	private $_c=0;
	/**
	 * @var numeric the default priority of items without specified priorities
	 */
	private $_dp=10;
	/**
	 * @var integer the precision of the numeric priorities within this priority list.
	 */
	private $_p=8;

	/**
	 * Constructor.
	 * Initializes the array with an array or an iterable object.
	 * @param map|array|Iterator|TPriorityMap the intial data. Default is null, meaning no initialization.
	 * @param boolean whether the list is read-only
	 * @param numeric the default priority of items without specified priorities.
	 * @param integer the precision of the numeric priorities
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data=null,$readOnly=false,$defaultPriority=10,$precision=8)
	{
		if($data!==null)
			$this->copyFrom($data);
		$this->setReadOnly($readOnly);
		$this->setPrecision($precision);
		$this->setDefaultPriority($defaultPriority);
	}

	/**
	 * @return boolean whether this map is read-only or not. Defaults to false.
	 */
	public function getReadOnly()
	{
		return $this->_r;
	}

	/**
	 * @param boolean whether this list is read-only or not
	 */
	protected function setReadOnly($value)
	{
		$this->_r=TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * @return numeric gets the default priority of inserted items without a specified priority
	 */
	public function getDefaultPriority()
	{
		return $this->_dp;
	}
	
	/**
	 * This must be called internally or when instantiated.
	 * @param numeric sets the default priority of inserted items without a specified priority
	 */
	protected function setDefaultPriority($value)
	{
		$this->_dp = (string)round(TPropertyValue::ensureFloat($value), $this->_p);
	}
	
	/**
	 * @return integer The precision of numeric priorities, defaults to 8
	 */
	public function getPrecision()
	{
		return $this->_p;
	}
	
	/**
	 * This must be called internally or when instantiated.
	 * @param integer The precision of numeric priorities.
	 */
	protected function setPrecision($value)
	{
		$this->_p=TPropertyValue::ensureInteger($value);
	}

	/**
	 * Returns an iterator for traversing the items in the map.
	 * This method is required by the interface IteratorAggregate.
	 * @return Iterator an iterator for traversing the items in the map.
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->flattenPriorities());
	}
	
	
	/**
	 * Orders the priority list internally.
	 */
	protected function sortPriorities() {
		if(!$this->_o) {
			ksort($this->_d, SORT_NUMERIC);
			$this->_o=true;
		}
	}

	/**
	 * This flattens the priority map into a flat array [0,...,n-1]
	 * @return array array of items in the list in priority and index order
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
	 * Returns the number of items in the map.
	 * This method is required by Countable interface.
	 * @return integer number of items in the map.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * @return integer the number of items in the map
	 */
	public function getCount()
	{
		return $this->_c;
	}
	
	/**
	 * Gets the number of items at a priority within the map.
	 * @param numeric optional priority at which to count items.  if no parameter, 
	 * it will be set to the default {@link getDefaultPriority}
	 * @return integer the number of items in the map at the specified priority
	 */
	public function getPriorityCount($priority=null)
	{
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
		
		if(!isset($this->_d[$priority])||!is_array($this->_d[$priority]))
			return false;
		return count($this->_d[$priority]);
	}
	
	/**
	 * This returns a list of the priorities within this map, ordered lowest to highest.
	 * @return array the array of priority numerics in decreasing priority order
	 */
	public function getPriorities()
	{
		$this->sortPriorities();
		return array_keys($this->_d);
	}

	/**
	 * Returns the keys within the map ordered through the priority of each key-value pair
	 * @return array the key list
	 */
	public function getKeys()
	{
		return array_keys($this->flattenPriorities());
	}

	/**
	 * Returns the item with the specified key.  If a priority is specified, only items
	 * within that specific priority will be selected
	 * @param mixed the key
	 * @param mixed the priority.  null is the default priority, false is any priority, 
	 * and numeric is a specific priority.  default: false, any priority.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAt($key,$priority=false)
	{
		if($priority===false){
			$map=$this->flattenPriorities();
			return isset($map[$key])?$map[$key]:null;
		} else {
			if($priority===null)
				$priority=$this->getDefaultPriority();
			$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
			return (isset($this->_d[$priority])&&isset($this->_d[$priority][$key]))?$this->_d[$priority][$key]:null;
		}
	}

	/**
	 * This changes an item's priority.  Specify the item and the new priority.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param mixed the key
	 * @param numeric|null the priority.  default: null, filled in with the default priority numeric.
	 * @return numeric old priority of the item
	 */
	public function setPriorityAt($key,$priority=null)
	{
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
		
		$oldpriority=$this->priorityAt($key);
		if($oldpriority!==false&&$oldpriority!=$priority) {
			$value=$this->remove($key,$oldpriority);
			$this->add($key,$value,$priority);
		}
		return $oldpriority;
	}

	/**
	 * Gets all the items at a specific priority.
	 * @param numeric priority of the items to get.  Defaults to null, filled in with the default priority, if left blank.
	 * @return array all items at priority in index order, null if there are no items at that priority
	 */
	public function itemsAtPriority($priority=null)
	{
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
		
		return isset($this->_d[$priority])?$this->_d[$priority]:null;
	}

	/**
	 * Returns the priority of a particular item within the map.  This searches the map for the item.
	 * @param mixed item to look for within the map
	 * @return numeric priority of the item in the map
	 */
	public function priorityOf($item)
	{
		$this->sortPriorities();
		foreach($this->_d as $priority=>$items)
			if(($index=array_search($item,$items,true))!==false)
				return $priority;
		return false;
	}

	/**
	 * Retutrns the priority of an item at a particular flattened index.
	 * @param integer index of the item within the map
	 * @return numeric priority of the item in the map
	 */
	public function priorityAt($key)
	{
		$this->sortPriorities();
		foreach($this->_d as $priority=>$items)
			if(array_key_exists($key,$items))
				return $priority;
		return false;
	}

	/**
	 * Adds an item into the map.  A third parameter may be used to set the priority
	 * of the item within the map.  Priority is primarily used during when flattening
	 * the map into an array where order may be and important factor of the key-value
	 * pairs within the array.
	 * Note, if the specified key already exists, the old value will be overwritten.
	 * No duplicate keys are allowed regardless of priority.
	 * @param mixed key
	 * @param mixed value
	 * @param numeric|null priority, default: null, filled in with default priority
	 * @return numeric priority at which the pair was added
	 * @throws TInvalidOperationException if the map is read-only
	 */
	public function add($key,$value,$priority=null)
	{
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
		
		if(!$this->_r)
		{
			foreach($this->_d as $innerpriority=>$items)
				if(array_key_exists($key,$items))
				{
					unset($this->_d[$innerpriority][$key]);
					$this->_c--;
					if(count($this->_d[$innerpriority])===0)
						unset($this->_d[$innerpriority]);
				}
			if(!isset($this->_d[$priority])) {
				$this->_d[$priority]=array($key=>$value);
				$this->_o=false;
			}
			else
				$this->_d[$priority][$key]=$value;
			$this->_c++;
			$this->_fd=null;
		}
		else
			throw new TInvalidOperationException('map_readonly',get_class($this));
		return $priority;
	}

	/**
	 * Removes an item from the map by its key. If no priority, or false, is specified
	 * then priority is irrelevant. If null is used as a parameter for priority, then
	 * the priority will be the default priority.  If a priority is specified, or 
	 * the default priority is specified, only key-value pairs in that priority
	 * will be affected.
	 * @param mixed the key of the item to be removed
	 * @param numeric|false|null priority.  False is any priority, null is the 
	 * default priority, and numeric is a specific priority
	 * @return mixed the removed value, null if no such key exists.
	 * @throws TInvalidOperationException if the map is read-only
	 */
	public function remove($key,$priority=false)
	{
		if(!$this->_r)
		{
			if($priority===null)
				$priority=$this->getDefaultPriority();
		
			if($priority===false)
			{
				$this->sortPriorities();
				foreach($this->_d as $priority=>$items)
					if(array_key_exists($key,$items))
					{
						$value=$this->_d[$priority][$key];
						unset($this->_d[$priority][$key]);
						$this->_c--;
						if(count($this->_d[$priority])===0)
						{
							unset($this->_d[$priority]);
							$this->_o=false;
						}
						$this->_fd=null;
						return $value;
					}
				return null;
			}
			else
			{
				$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
				if(isset($this->_d[$priority])&&(isset($this->_d[$priority][$key])||array_key_exists($key,$this->_d[$priority])))
				{
					$value=$this->_d[$priority][$key];
					unset($this->_d[$priority][$key]);
					$this->_c--;
					if(count($this->_d[$priority])===0) {
						unset($this->_d[$priority]);
						$this->_o=false;
					}
					$this->_fd=null;
					return $value;
				}
				else
					return null;
			}
		}
		else
			throw new TInvalidOperationException('map_readonly',get_class($this));
	}

	/**
	 * Removes all items in the map.  {@link remove} is called on all items.
	 */
	public function clear()
	{
		foreach($this->_d as $priority=>$items)
			foreach(array_keys($items) as $key)
				$this->remove($key);
	}

	/**
	 * @param mixed the key
	 * @return boolean whether the map contains an item with the specified key
	 */
	public function contains($key)
	{
		$map=$this->flattenPriorities();
		return isset($map[$key])||array_key_exists($key,$map);
	}

	/**
	 * When the map is flattened into an array, the priorities are taken into 
	 * account and elements of the map are ordered in the array according to 
	 * their priority.
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->flattenPriorities();
	}

	/**
	 * Combines the map elements which have a priority below the parameter value
	 * @param numeric the cut-off priority.  All items of priority less than this are returned.
	 * @param boolean whether or not the input cut-off priority is inclusive.  Default: false, not inclusive.
	 * @return array the array of priorities keys with values of arrays of items that are below a specified priority.  
	 *  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toArrayBelowPriority($priority,$inclusive=false)
	{
		$this->sortPriorities();
		$items=array();
		foreach($this->_d as $itemspriority=>$itemsatpriority)
		{
			if((!$inclusive&&$itemspriority>=$priority)||$itemspriority>$priority)
				break;
			$items=array_merge($items,$itemsatpriority);
		}
		return $items;
	}

	/**
	 * Combines the map elements which have a priority above the parameter value
	 * @param numeric the cut-off priority.  All items of priority greater than this are returned.
	 * @param boolean whether or not the input cut-off priority is inclusive.  Default: true, inclusive.
	 * @return array the array of priorities keys with values of arrays of items that are above a specified priority.  
	 *  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toArrayAbovePriority($priority,$inclusive=true)
	{
		$this->sortPriorities();
		$items=array();
		foreach($this->_d as $itemspriority=>$itemsatpriority)
		{
			if((!$inclusive&&$itemspriority<=$priority)||$itemspriority<$priority)
				continue;
			$items=array_merge($items,$itemsatpriority);
		}
		return $items;
	}

	/**
	 * Copies iterable data into the map.
	 * Note, existing data in the map will be cleared first.
	 * @param mixed the data to be copied from, must be an array, object implementing 
	 * Traversable, or a TPriorityMap
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function copyFrom($data)
	{
		if($data instanceof TPriorityMap)
		{
			if($this->getCount()>0)
				$this->clear();
			foreach($data->getPriorities() as $priority) {
				foreach($data->itemsAtPriority($priority) as $key => $value) {
					$this->add($key,$value,$priority);
				}
			}
		}
		else if(is_array($data)||$data instanceof Traversable)
		{
			if($this->getCount()>0)
				$this->clear();
			foreach($data as $key=>$value)
				$this->add($key,$value);
		}
		else if($data!==null)
			throw new TInvalidDataTypeException('map_data_not_iterable');
	}

	/**
	 * Merges iterable data into the map.
	 * Existing data in the map will be kept and overwritten if the keys are the same.
	 * @param mixed the data to be merged with, must be an array, object implementing 
	 * Traversable, or a TPriorityMap
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data)
	{
		if($data instanceof TPriorityMap)
		{
			foreach($data->getPriorities() as $priority)
			{
				foreach($data->itemsAtPriority($priority) as $key => $value)
					$this->add($key,$value,$priority);
			}
		}
		else if(is_array($data)||$data instanceof Traversable)
		{
			foreach($data as $key=>$value)
				$this->add($key,$value);
		}
		else if($data!==null)
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
		return $this->contains($offset);
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
		$this->add($offset,$item);
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param mixed the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		$this->remove($offset);
	}
}