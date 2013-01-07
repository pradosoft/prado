<?php
/**
 * TPriorityList, TPriorityListIterator classes
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TPriorityList.php 2541 2008-10-21 15:05:13Z javalizard $
 * @package System.Collections
 */

/**
 * TPriorityList class
 *
 * TPriorityList implements a priority ordered list collection class.  It allows you to specify 
 * any numeric for priorities down to a specific precision.  The lower the numeric, the high the priority of the item in the 
 * list.  Thus -10 has a higher priority than -5, 0, 10 (the default), 18, 10005, etc.  Per {@link round}, precision may be negative and 
 * thus rounding can go by 10, 100, 1000, etc, instead of just .1, .01, .001, etc. The default precision allows for 8 decimal 
 * places. There is also a default priority of 10, if no different default priority is specified or no item specific priority is indicated.  
 * If you replace TList with this class it will  work exactly the same with items inserted set to the default priority, until you start 
 * using different priorities than the default priority.
 *
 * As you access the PHP array features of this class, it flattens and caches the results.  If at all possible, this
 * will keep the cache fresh even when manipulated.  If this is not possible the cache is cleared.
 * When an array of items are needed and the cache is outdated, the cache is recreated from the items and their priorities
 *
 * You can access, append, insert, remove an item by using
 * {@link itemAt}, {@link add}, {@link insertAt}, and {@link remove}.
 * To get the number of the items in the list, use {@link getCount}.
 * TPriorityList can also be used like a regular array as follows,
 * <code>
 * $list[]=$item;  // append with the default priority.  It may not be the last item if other items in the list are prioritized after the default priority
 * $list[$index]=$item; // $index must be between 0 and $list->Count-1.  This sets the element regardless of priority.  Priority stays the same.
 * $list[$index]=$item; // $index is $list->Count.  This appends the item to the end of the list with the same priority as the last item in the list.
 * unset($list[$index]); // remove the item at $index
 * if(isset($list[$index])) // if the list has an item at $index
 * foreach($list as $index=>$item) // traverse each item in the list in proper priority order and add/insert order
 * $n=count($list); // returns the number of items in the list
 * </code>
 *
 * To extend TPriorityList for doing your own operations with each addition or removal, 
 * override {@link insertAtIndexInPriority()} and {@link removeAtIndexInPriority()} and then call the parent.
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
	 * @var boolean indicates if the _d is currently ordered.
	 */
	private $_o=false;
	/**
	 * @var array cached flattened internal data storage
	 */
	private $_fd=null;
	/**
	 * @var integer number of items contain within the list
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
	 * Initializes the list with an array or an iterable object.
	 * @param array|Iterator the intial data. Default is null, meaning no initial data.
	 * @param boolean whether the list is read-only
	 * @param numeric the default priority of items without specified priorities.
	 * @param integer the precision of the numeric priorities
	 * @throws TInvalidDataTypeException If data is not null and is neither an array nor an iterator.
	 */
	public function __construct($data=null,$readOnly=false,$defaultPriority=10,$precision=8)
	{
		parent::__construct();
		if($data!==null)
			$this->copyFrom($data);
		$this->setReadOnly($readOnly);
		$this->setPrecision($precision);
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
	 * Returns the total number of items in the list
	 * @return integer the number of items in the list
	 */
	public function getCount()
	{
		return $this->_c;
	}
	
	/**
	 * Gets the number of items at a priority within the list
	 * @param numeric optional priority at which to count items.  if no parameter, it will be set to the default {@link getDefaultPriority}
	 * @return integer the number of items in the list at the specified priority
	 */
	public function getPriorityCount($priority=null)
	{
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
		
		if(!isset($this->_d[$priority]) || !is_array($this->_d[$priority]))
			return false;
		return count($this->_d[$priority]);
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
		$this->_dp=(string)round(TPropertyValue::ensureFloat($value),$this->_p);
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
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface IteratorAggregate.
	 * @return Iterator an iterator for traversing the items in the list.
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->flattenPriorities());
	}
	
	/**
	 * This returns a list of the priorities within this list, ordered lowest to highest.
	 * @return array the array of priority numerics in decreasing priority order
	 */
	public function getPriorities()
	{
		$this->sortPriorities();
		return array_keys($this->_d);
	}
	
	
	/**
	 * This orders the priority list internally.
	 */
	protected function sortPriorities() {
		if(!$this->_o) {
			ksort($this->_d,SORT_NUMERIC);
			$this->_o=true;
		}
	}

	/**
	 * This flattens the priority list into a flat array [0,...,n-1] 
	 * @return array array of items in the list in priority and index order
	 */
	protected function flattenPriorities() {
		if(is_array($this->_fd))
			return $this->_fd;
		
		$this->sortPriorities();
		$this->_fd=array();
		foreach($this->_d as $priority => $itemsatpriority)
			$this->_fd=array_merge($this->_fd,$itemsatpriority);
		return $this->_fd;
	}
	

	/**
	 * Returns the item at the index of a flattened priority list.
	 * {@link offsetGet} calls this method.
	 * @param integer the index of the item to get
	 * @return mixed the element at the offset
	 * @throws TInvalidDataValueException Issued when the index is invalid
	 */
	public function itemAt($index)
	{
		if($index>=0&&$index<$this->getCount()) {
			$arr=$this->flattenPriorities();
			return $arr[$index];
		} else
			throw new TInvalidDataValueException('list_index_invalid',$index);
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
	 * Returns the item at an index within a priority
	 * @param integer the index into the list of items at priority
	 * @param numeric the priority which to index.  no parameter or null will result in the default priority
	 * @return mixed the element at the offset, false if no element is found at the offset
	 */
	public function itemAtIndexInPriority($index,$priority=null)
	{
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority), $this->_p);
		
		return !isset($this->_d[$priority])?false:(
				isset($this->_d[$priority][$index])?$this->_d[$priority][$index]:false
			);
	}

	/**
	 * Appends an item into the list at the end of the specified priority.  The position of the added item may 
	 * not be at the end of the list.
	 * @param mixed item to add into the list at priority
	 * @param numeric priority blank or null for the default priority
	 * @return int the index within the flattened array
	 * @throws TInvalidOperationException if the map is read-only
	 */
	public function add($item,$priority=null)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		return $this->insertAtIndexInPriority($item,false,$priority,true);
	}

	/**
	 * Inserts an item at an index.  It reads the priority of the item at index within the flattened list
	 * and then inserts the item at that priority-index.
	 * @param integer the specified position in the flattened list.
	 * @param mixed new item to add
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function insertAt($index,$item)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		if(($priority=$this->priorityAt($index,true))!==false)
			$this->insertAtIndexInPriority($item,$priority[1],$priority[0]);
		else
			throw new TInvalidDataValueException('list_index_invalid',$index);
	}

	/**
	 * Inserts an item at the specified index within a priority.  Override and call this method to 
	 * insert your own functionality.
	 * @param mixed item to add within the list.
	 * @param integer index within the priority to add the item, defaults to false which appends the item at the priority
	 * @param numeric priority priority of the item.  defaults to null, which sets it to the default priority
	 * @param boolean preserveCache specifies if this is a special quick function or not. This defaults to false.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function insertAtIndexInPriority($item,$index=false,$priority=null,$preserveCache=false)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority), $this->_p);
		
		if($preserveCache) {
			$this->sortPriorities();
			$cc=0;
			foreach($this->_d as $prioritykey=>$items)
				if($prioritykey>=$priority)
					break;
				else
					$cc+=count($items);
			
			if($index===false&&isset($this->_d[$priority])) {
				$c=count($this->_d[$priority]);
				$c+=$cc;
				$this->_d[$priority][]=$item;
			} else if(isset($this->_d[$priority])) {
				$c=$index+$cc;
				array_splice($this->_d[$priority],$index,0,array($item));
			} else {
				$c = $cc;
				$this->_o = false;
				$this->_d[$priority]=array($item);
			}
			
			if($this->_fd&&is_array($this->_fd)) // if there is a flattened array cache
				array_splice($this->_fd,$c,0,array($item));
		} else {
			$c=null;
			if($index===false&&isset($this->_d[$priority])) {
				$cc=count($this->_d[$priority]);
				$this->_d[$priority][]=$item;
			} else if(isset($this->_d[$priority])) {
				$cc=$index;
				array_splice($this->_d[$priority],$index,0,array($item));
			} else {
				$cc=0;
				$this->_o=false;
				$this->_d[$priority]=array($item);
			}
			if($this->_fd&&is_array($this->_fd)&&count($this->_d)==1)
				array_splice($this->_fd,$cc,0,array($item));
			else
				$this->_fd=null;
		}
		
		$this->_c++;
		
		return $c;
		
	}
	

	/**
	 * Removes an item from the priority list.
	 * The list will search for the item.  The first matching item found will be removed from the list.
	 * @param mixed item the item to be removed.
	 * @param numeric priority of item to remove. without this parameter it defaults to false.
	 * A value of false means any priority. null will be filled in with the default priority.
	 * @return integer index within the flattened list at which the item is being removed
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function remove($item,$priority=false)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		if(($p=$this->priorityOf($item,true))!==false)
		{
			if($priority!==false) {
				if($priority===null)
					$priority=$this->getDefaultPriority();
				$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
				
				if($p[0]!=$priority)
					throw new TInvalidDataValueException('list_item_inexistent');
			}
			$this->removeAtIndexInPriority($p[1],$p[0]);
			return $p[2];
		}
		else
			throw new TInvalidDataValueException('list_item_inexistent');
	}

	/**
	 * Removes an item at the specified index in the flattened list.
	 * @param integer index of the item to be removed.
	 * @return mixed the removed item.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function removeAt($index)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		if(($priority=$this->priorityAt($index, true))!==false)
			return $this->removeAtIndexInPriority($priority[1],$priority[0]);
		throw new TInvalidDataValueException('list_index_invalid',$index);
	}

	/**
	 * Removes the item at a specific index within a priority.  Override 
	 * and call this method to insert your own functionality.
	 * @param integer index of item to remove within the priority.
	 * @param numeric priority of the item to remove, defaults to null, or left blank, it is then set to the default priority
	 * @return mixed the removed item.
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function removeAtIndexInPriority($index, $priority=null)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
			
		if($priority===null)
			$priority=$this->getDefaultPriority();
		$priority=(string)round(TPropertyValue::ensureFloat($priority),$this->_p);
		
		if(!isset($this->_d[$priority])||$index<0||$index>=count($this->_d[$priority]))
			throw new TInvalidDataValueException('list_item_inexistent');
		
		// $value is an array of elements removed, only one
		$value=array_splice($this->_d[$priority],$index,1);
		$value=$value[0];
		
		if(!count($this->_d[$priority]))
			unset($this->_d[$priority]);
		
		$this->_c--;
		$this->_fd=null;
		return $value;
	}

	/**
	 * Removes all items in the priority list by calling removeAtIndexInPriority from the last item to the first.
	 */
	public function clear()
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
		
		$d=array_reverse($this->_d,true);
		foreach($this->_d as $priority=>$items) {
			for($index=count($items)-1;$index>=0;$index--)
				$this->removeAtIndexInPriority($index,$priority);
			unset($this->_d[$priority]);
		}
	}

	/**
	 * @param mixed item
	 * @return boolean whether the list contains the item
	 */
	public function contains($item)
	{
		return $this->indexOf($item)>=0;
	}

	/**
	 * @param mixed item
	 * @return integer the index of the item in the flattened list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		if(($index=array_search($item,$this->flattenPriorities(),true))===false)
			return -1;
		else
			return $index;
	}

	/**
	 * Returns the priority of a particular item
	 * @param mixed the item to look for within the list
	 * @param boolean withindex this specifies if the full positional data of the item within the list is returned.
	 * 		This defaults to false, if no parameter is provided, so only provides the priority number of the item by default.
	 * @return numeric|array the priority of the item in the list, false if not found.
	 *   if withindex is true, an array is returned of [0 => $priority, 1 => $priorityIndex, 2 => flattenedIndex,
	 * 'priority' => $priority, 'index' => $priorityIndex, 'absindex' => flattenedIndex]
	 */
	public function priorityOf($item,$withindex = false)
	{
		$this->sortPriorities();
		
		$absindex = 0;
		foreach($this->_d as $priority=>$items) {
			if(($index=array_search($item,$items,true))!==false) {
				$absindex+=$index;
				return $withindex?array($priority,$index,$absindex, 
						'priority'=>$priority,'index'=>$index,'absindex'=>$absindex):$priority;
			} else
				$absindex+=count($items);
		}
		
		return false;
	}

	/**
	 * Retutrns the priority of an item at a particular flattened index.
	 * @param integer index of the item within the list
	 * @param boolean withindex this specifies if the full positional data of the item within the list is returned.
	 * 		This defaults to false, if no parameter is provided, so only provides the priority number of the item by default.
	 * @return numeric|array the priority of the item in the list, false if not found.
	 *   if withindex is true, an array is returned of [0 => $priority, 1 => $priorityIndex, 2 => flattenedIndex,
	 * 'priority' => $priority, 'index' => $priorityIndex, 'absindex' => flattenedIndex]
	 */
	public function priorityAt($index,$withindex = false)
	{
		if($index<0||$index>=$this->getCount())
			throw new TInvalidDataValueException('list_index_invalid',$index);
		
		$absindex=$index;
		$this->sortPriorities();
		foreach($this->_d as $priority=>$items) {
			if($index>=($c=count($items)))
				$index-=$c;
			else
				return $withindex?array($priority,$index,$absindex, 
						'priority'=>$priority,'index'=>$index,'absindex'=>$absindex):$priority;
		}
		return false;
	}

	/**
	 * This inserts an item before another item within the list.  It uses the same priority as the 
	 * found index item and places the new item before it.
	 * @param mixed indexitem the item to index
	 * @param mixed the item to add before indexitem
	 * @return integer where the item has been inserted in the flattened list
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function insertBefore($indexitem, $item)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
			
		if(($priority=$this->priorityOf($indexitem,true))===false)
			throw new TInvalidDataValueException('list_item_inexistent');
		
		$this->insertAtIndexInPriority($item,$priority[1],$priority[0]);
		
		return $priority[2];
	}

	/**
	 * This inserts an item after another item within the list.  It uses the same priority as the 
	 * found index item and places the new item after it.
	 * @param mixed indexitem the item to index
	 * @param mixed the item to add after indexitem
	 * @return integer where the item has been inserted in the flattened list
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function insertAfter($indexitem, $item)
	{
		if($this->getReadOnly())
			throw new TInvalidOperationException('list_readonly',get_class($this));
			
		if(($priority=$this->priorityOf($indexitem,true))===false)
			throw new TInvalidDataValueException('list_item_inexistent');
		
		$this->insertAtIndexInPriority($item,$priority[1]+1,$priority[0]);
		
		return $priority[2]+1;
	}

	/**
	 * @return array the priority list of items in array
	 */
	public function toArray()
	{
		return $this->flattenPriorities();
	}

	/**
	 * @return array the array of priorities keys with values of arrays of items.  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toPriorityArray()
	{
		$this->sortPriorities();
		return $this->_d;
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
	 * Copies iterable data into the priority list.
	 * Note, existing data in the map will be cleared first.
	 * @param mixed the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function copyFrom($data)
	{
		if($data instanceof TPriorityList)
		{
			if($this->getCount()>0)
				$this->clear();
			foreach($data->getPriorities() as $priority)
			{
				foreach($data->itemsAtPriority($priority) as $index=>$item)
					$this->insertAtIndexInPriority($item,$index,$priority);
			}
		} else if(is_array($data)||$data instanceof Traversable) {
			if($this->getCount()>0)
				$this->clear();
			foreach($data as $key=>$item)
				$this->add($item);
		} else if($data!==null)
			throw new TInvalidDataTypeException('map_data_not_iterable');
	}

	/**
	 * Merges iterable data into the priority list.
	 * New data will be appended to the end of the existing data.  If another TPriorityList is merged,
	 * the incoming parameter items will be appended at the priorities they are present.  These items will be added 
	 * to the end of the existing items with equal priorities, if there are any.
	 * @param mixed the data to be merged with, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data)
	{
		if($data instanceof TPriorityList)
		{
			foreach($data->getPriorities() as $priority)
			{
				foreach($data->itemsAtPriority($priority) as $index=>$item)
					$this->insertAtIndexInPriority($item,false,$priority);
			}
		}
		else if(is_array($data)||$data instanceof Traversable)
		{
			foreach($data as $priority=>$item)
				$this->add($item);
			
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
		return ($offset>=0&&$offset<$this->getCount());
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
	 * Sets the element at the specified offset. This method is required by the interface ArrayAccess.
	 * Setting elements in a priority list is not straight forword when appending and setting at the 
	 * end boundary.  When appending without an offset (a null offset), the item will be added at
	 * the default priority.  The item may not be the last item in the list.  When appending with an
	 * offset equal to the count of the list, the item will get be appended with the last items priority.
	 *
	 * All together, when setting the location of an item, the item stays in that location, but appending 
	 * an item into a priority list doesn't mean the item is at the end of the list.
	 * @param integer the offset to set element
	 * @param mixed the element value
	 */
	public function offsetSet($offset,$item)
	{
		if($offset===null)
			return $this->add($item);
		if($offset===$this->getCount()) {
			$priority=$this->priorityAt($offset-1,true);
			$priority[1]++;
		} else {
			$priority=$this->priorityAt($offset,true);
			$this->removeAtIndexInPriority($priority[1],$priority[0]);
		}
		$this->insertAtIndexInPriority($item,$priority[1],$priority[0]);
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
