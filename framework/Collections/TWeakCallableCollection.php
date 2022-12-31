<?php
/**
 * TWeakCallableCollection class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TWeakCallableCollection class
 *
 * TWeakCallableCollection implements a priority ordered list collection of callables.  This extends
 * {@link TPriorityList}.  This holds the callables for object event handlers and global event handlers by
 * converting all callable objects into a WeakReference (for PHP 7.4+).  TWeakCallableCollection prevents circular
 * references in global events that would otherwise block object destruction, and thus removal of the callable
 * in __destruct. All data out has the callable objects converted back to the regular object reference in a callable.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TWeakCallableCollection extends TPriorityList
{
	/**
	 * @var bool wether or not WeakReference is available
	 */
	private static $_weak;


	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object. Discovers the availability of the
	 * {@link WeakReference} object in PHP 7.4.0+.
	 * @param null|array|\Iterator $data the initial data. Default is null, meaning no initial data.
	 * @param bool $readOnly whether the list is read-only
	 * @param numeric $defaultPriority the default priority of items without specified priorities.
	 * @param int $precision the precision of the numeric priorities
	 * @throws \Prado\Exceptions\TInvalidDataTypeException If data is not null and is neither an array nor an iterator.
	 */
	public function __construct($data = null, $readOnly = false, $defaultPriority = 10, $precision = 8)
	{
		if (self::$_weak === null) {
			self::$_weak = class_exists('\WeakReference', false);
		}
		parent::__construct($data, $readOnly, $defaultPriority, $precision);
	}


	/**
	 * TWeakCallableCollection cannot auto listen to global events or there will be a loop.
	 *
	 * @return bool returns false
	 */
	public function getAutoGlobalListen()
	{
		return false;
	}

	/**
	 * returns whether or not WeakReference is enabled, thus the PHP version is over 7.4.0
	 * @return bool is WeakReference available
	 */
	public static function getWeakReferenceEnabled()
	{
		return self::$_weak;
	}


	/**
	 * If WeakReference is available, converts the $items array of callable that
	 * has WeakReferences rather than the actual object back into a regular callable.
	 * @param array $items an array of callable where objects are WeakReference
	 * @return array array of callable where WeakReference are converted back to the object
	 */
	protected function filterItemsForOutput($items)
	{
		if (!self::$_weak) {
			return $items;
		}
		if (!is_array($items)) {
			return $items;
		}
		$result = [];
		foreach ($items as $i => $handler) {
			if (is_array($handler) && is_object($handler[0]) && is_a($handler[0], '\WeakReference')) {
				$result[] = [$handler[0]->get(), $handler[1]];
			} elseif (is_object($handler) && is_a($handler, '\WeakReference')) {
				$result[] = $handler->get();
			} else {
				$result[] = $handler;
			}
		}
		return $result;
	}


	/**
	 * Converts the $handler callable that has WeakReferences rather than the actual object
	 * back into a regular callable.
	 * @param callable $handler but the $handler or $handler[0] is a WeakReference
	 * @return mixed callable by removing the WeakReferences
	 */
	protected function filterItemForOutput($handler)
	{
		if (!self::$_weak) {
			return $handler;
		}
		if (is_array($handler) && is_object($handler[0]) && is_a($handler[0], '\WeakReference')) {
			return [$handler[0]->get(), $handler[1]];
		} elseif (is_object($handler) && is_a($handler, '\WeakReference')) {
			return $handler->get();
		}
		return $handler;
	}


	/**
	 * Converts the $handler callable into a WeakReference version for storage
	 * @param callable $handler callable to convert into a WeakReference version
	 * @param bool $validate whether or not to validate the input as a callable
	 * @return mixed callable but with the objects as WeakReferences
	 */
	protected function filterItemForInput($handler, $validate = false)
	{
		if ($validate && !is_callable($handler, false)) {
			throw new TInvalidDataValueException('weakcallablecollection_callable_required');
		}
		if (!self::$_weak) {
			return $handler;
		}
		if (is_array($handler) && is_object($handler[0]) && !is_a($handler[0], '\WeakReference')) {
			return [\WeakReference::create($handler[0]), $handler[1]];
		} elseif (is_object($handler) && !is_a($handler, '\WeakReference')) {
			return \WeakReference::create($handler);
		}

		return $handler;
	}


	/**
	 * This flattens the priority list into a flat array [0,...,n-1]. This is needed to filter the output.
	 * @return array array of items in the list in priority and index order
	 */
	protected function flattenPriorities()
	{
		return $this->filterItemsForOutput(parent::flattenPriorities());
	}


	/**
	 * This flattens the priority list into a flat array [0,...,n-1], but
	 * leaves the objects in the array as WeakReference rather than standard
	 * callable.
	 * @return array array of items in the list in priority and index order,
	 *    where objects are WeakReference
	 */
	protected function flattenPrioritiesWeak()
	{
		return parent::flattenPriorities();
	}


	/**
	 * Returns the item at the index of a flattened priority list. This is needed to filter the output.
	 * {@link offsetGet} calls this method.
	 * @param int $index the index of the item to get
	 * @throws TInvalidDataValueException Issued when the index is invalid
	 * @return mixed the element at the offset
	 */
	public function itemAt($index)
	{
		if ($index >= 0 && $index < $this->getCount()) {
			$arr = $this->flattenPrioritiesWeak();
			return $this->filterItemForOutput($arr[$index]);
		} else {
			throw new TInvalidDataValueException('list_index_invalid', $index);
		}
	}

	/**
	 * Gets all the items at a specific priority. This is needed to filter the output.
	 * @param null|numeric $priority priority of the items to get.  Defaults to null, filled
	 * in with the default priority, if left blank.
	 * @return array all items at priority in index order, null if there are no items at that priority
	 */
	public function itemsAtPriority($priority = null)
	{
		return $this->filterItemsForOutput(parent::itemsAtPriority($priority));
	}

	/**
	 * Returns the item at an index within a priority. This is needed to filter the output.
	 * @param int $index the index into the list of items at priority
	 * @param numeric $priority the priority which to index.  no parameter or null will result in the default priority
	 * @return mixed the element at the offset, false if no element is found at the offset
	 */
	public function itemAtIndexInPriority($index, $priority = null)
	{
		return $this->filterItemForOutput(parent::itemAtIndexInPriority($index, $priority));
	}

	/**
	 * Inserts an item at the specified index within a priority.  This is needed to filter the input.
	 * @param mixed $item item to add within the list.
	 * @param false|int $index index within the priority to add the item, defaults to false which appends the item at the priority
	 * @param null|numeric $priority priority of the item.  defaults to null, which sets it to the default priority
	 * @param bool $preserveCache preserveCache specifies if this is a special quick function or not. This defaults to false.
	 * @throws \Prado\Exceptions\TInvalidDataValueException If the index specified exceeds the bound
	 * @throws \Prado\Exceptions\TInvalidOperationException if the list is read-only
	 */
	public function insertAtIndexInPriority($item, $index = false, $priority = null, $preserveCache = false)
	{
		$itemPriority = null;
		if (($isPriorityItem = $item instanceof IPriorityItem) && ($priority === null || !is_numeric($priority))) {
			$itemPriority = $priority = $item->getPriority();
		}
		$priority = $this->ensurePriority($priority);
		if (($item instanceof IPriorityCapture) && (!$isPriorityItem || $itemPriority !== $priority)) {
			$item->setPriority($priority);
		}
		return parent::insertAtIndexInPriority($this->filterItemForInput($item, true), $index, $priority, $preserveCache);
	}

	/**
	 * Removes the item at a specific index within a priority.  This is needed to filter the output.
	 * @param int $index index of item to remove within the priority.
	 * @param null|numeric $priority priority of the item to remove, defaults to null, or left blank, it is then set to the default priority
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return mixed the removed item.
	 */
	public function removeAtIndexInPriority($index, $priority = null)
	{
		return $this->filterItemForOutput(parent::removeAtIndexInPriority($index, $priority));
	}

	/**
	 * This is needed to filter the input.
	 * @param mixed $item item being indexed.
	 * @return int the index of the item in the flattened list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		if (($index = array_search($this->filterItemForInput($item), $this->flattenPrioritiesWeak(), true)) === false) {
			return -1;
		} else {
			return $index;
		}
	}

	/**
	 * Returns the priority of a particular item.  This is needed to filter the input.
	 * @param mixed $item the item to look for within the list.
	 * @param bool $withindex this specifies if the full positional data of the item within the list is returned.
	 * 		This defaults to false, if no parameter is provided, so only provides the priority number of the item by default.
	 * @return array|numeric the priority of the item in the list, false if not found.
	 *   if withindex is true, an array is returned of [0 => $priority, 1 => $priorityIndex, 2 => flattenedIndex,
	 * 'priority' => $priority, 'index' => $priorityIndex, 'absindex' => flattenedIndex]
	 */
	public function priorityOf($item, $withindex = false)
	{
		return parent::priorityOf($this->filterItemForInput($item), $withindex);
	}


	/**
	 *  This is needed to filter the output.
	 * @return array the array of priorities keys with values of arrays of callables.
	 * The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toPriorityArray()
	{
		$result = [];
		foreach (parent::toPriorityArray() as $i => $v) {
			$result[$i] = $this->filterItemsForOutput($v);
		}
		return $result;
	}


	/**
	 * @return array the array of priorities keys with values of arrays of callables with
	 * WeakReference rather than objects.  The priorities are sorted so important priorities,
	 * lower numerics, are first.
	 */
	public function toPriorityArrayWeak()
	{
		return parent::toPriorityArray();
	}

	/**
	 * Combines the map elements which have a priority below the parameter value.  This is needed to filter the output.
	 * @param numeric $priority the cut-off priority.  All items of priority less than this are returned.
	 * @param bool $inclusive whether or not the input cut-off priority is inclusive.  Default: false, not inclusive.
	 * @return array the array of priorities keys with values of arrays of items that are below a specified priority.
	 *  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toArrayBelowPriority($priority, $inclusive = false)
	{
		return $this->filterItemsForOutput(parent::toArrayBelowPriority($priority, $inclusive));
	}

	/**
	 * Combines the map elements which have a priority above the parameter value. This is needed to filter the output.
	 * @param numeric $priority the cut-off priority.  All items of priority greater than this are returned.
	 * @param bool $inclusive whether or not the input cut-off priority is inclusive.  Default: true, inclusive.
	 * @return array the array of priorities keys with values of arrays of items that are above a specified priority.
	 *  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toArrayAbovePriority($priority, $inclusive = true)
	{
		return $this->filterItemsForOutput(parent::toArrayAbovePriority($priority, $inclusive));
	}
}
