<?php
/**
 * TPriorityCollectionTrait class
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TPriorityCollectionTrait class
 *
 * This trait implements the common properties and methods of Priority Collection
 * classes.
 *
 * The trait adds a boolean for whether or not _d is ordered, a cached flattened
 * array _fd, a default priority (by default, 10) _dp, and precision of priorities (by
 * default, 8 [decimal places]) _p.
 *
 * The trait adds methods:
 *	- {@link getDefaultPriority} returns the default priority of items without priority.
 *	- {@link setDefaultPriority} sets the default priority. (protected)
 *	- {@link getPrecision} returns the precision of priorities.
 *	- {@link setPrecision} sets the precision of priorities. (protected)
 *	- {@link ensurePriority} standardize and round priorities. (protected)
 *	- {@link sortPriorities} sorts _d and flags as sorted. (protected)
 *	- {@link flattenPriorities} flattens the priority items, in order into cache.. (protected)
 *	- {@link getPriorities} gets the priorities of the collection.
 *	- {@link getPriorityCount} gets the number of items at a priority.
 *	- {@link itemsAtPriority} gets the items at a given priority.
 *	- {@link getIterator} overrides subclasses for an iterator of the flattened array.
 *	- {@link toArray} the flattened collection in order.
 *	- {@link toPriorityArray} the array of priorities (keys) and array of items (value).
 *	- {@link toArrayBelowPriority} the items below a Priority, default is not inclusive
 *	- {@link toArrayAbovePriority} the items above a priority, default is inclusive.
 *	- {@link _priorityZappableSleepProps} to add the excluded trait properties on sleep.
 *
 * The priorities are implemented as numeric strings.
 *
 * Any class using this trait must implement a getPriorityCombineStyle method to
 * determine if arrays are merged or replaced to combine together.
 *
 * For example, something like this is required in your class:
 * <code>
 *		private function getPriorityCombineStyle(): bool
 *		{
 *			return true; // for merge (list style), and false for replace (map style)
 *		}
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
trait TPriorityCollectionTrait
{
	/**
	 * @var bool indicates if the _d is currently ordered.
	 */
	protected bool $_o = false;

	/**
	 * @var null|array cached flattened internal data storage
	 */
	protected ?array $_fd = null;

	/**
	 * @var ?string the default priority of items without specified priorities
	 */
	private ?string $_dp = null;

	/**
	 * @var ?int the precision of the numeric priorities within this priority list.
	 */
	private ?int $_p = null;

	/**
	 * @return numeric gets the default priority of inserted items without a specified priority
	 */
	public function getDefaultPriority()
	{
		if ($this->_dp === null) {
			$this->_dp = '10';
		}
		return $this->_dp;
	}

	/**
	 * This must be called internally or when instantiated.
	 * @param numeric $value sets the default priority of inserted items without a specified priority
	 */
	public function setDefaultPriority($value)
	{
		if ($value === $this->_dp) {
			return;
		}
		if($this->_dp === null || Prado::isCallingSelf()) {
			$this->_dp = (string) round(TPropertyValue::ensureFloat($value), $this->getPrecision());
		} else {
			throw new TInvalidOperationException('prioritytrait_no_set_default_priority');
		}
	}

	/**
	 * @return int The precision of numeric priorities, defaults to 8
	 */
	public function getPrecision(): int
	{
		if ($this->_p === null) {
			$this->_p = 8;
		}
		return $this->_p;
	}

	/**
	 * This must be called internally or when instantiated.
	 * This resets the array priorities to the new precision and adjusts
	 * the DefaultPriority to the new precision as well.
	 * @param int $value The precision of numeric priorities.
	 */
	public function setPrecision($value): void
	{
		if ($value === $this->_p) {
			return;
		}
		if($this->_p !== null && !Prado::isCallingSelf()) {
			throw new TInvalidOperationException('prioritytrait_no_set_precision');
		}
		$this->_p = TPropertyValue::ensureInteger($value);
		$this->setDefaultPriority($this->_dp);
		$_d = [];
		foreach(array_keys($this->_d) as $priority) {
			$newPriority = $this->ensurePriority($priority);
			if (array_key_exists($newPriority, $_d)) {
				if ($this->getPriorityCombineStyle()) {
					$_d[$newPriority] = array_merge($_d[$newPriority], $this->_d[$priority]);
				} else {
					$_d[$newPriority] = array_replace($_d[$newPriority], $this->_d[$priority]);
				}
			} else {
				$_d[$newPriority] = $this->_d[$priority];
			}
		}
		$this->_d = $_d;
		$this->_fd = null;
	}

	/**
	 * Taken an input Priority and ensures its value.
	 * Sets the default $priority when none is set,
	 * then rounds to the proper precision and makes
	 * into a string.
	 * @param mixed $priority
	 * @return string the priority in string format
	 */
	protected function ensurePriority($priority): string
	{
		if ($priority === null || !is_numeric($priority)) {
			$priority = $this->getDefaultPriority();
		}
		return (string) round((float) $priority, $this->getPrecision());
	}


	/**
	 * This orders the priority list internally.
	 */
	protected function sortPriorities(): void
	{
		if (!$this->_o) {
			ksort($this->_d, SORT_NUMERIC);
			$this->_o = true;
		}
	}

	/**
	 * This flattens the priority list into a flat array [0,...,n-1] (with array_merge)
	 * and a priority map into a single flat map of keys and values (with array_replace).
	 */
	protected function flattenPriorities(): void
	{
		if (is_array($this->_fd)) {
			return;
		}
		if (empty($this->_d)) {
			$this->_fd = [];
			return;
		}
		$this->sortPriorities();
		if ($this->getPriorityCombineStyle()) {
			$this->_fd = array_merge(...$this->_d);
		} else {
			$this->_fd = array_replace(...$this->_d);
		}
	}

	/**
	 * This returns a list of the priorities within this list, ordered lowest to highest.
	 * @return array the array of priority numerics in decreasing priority order
	 */
	public function getPriorities(): array
	{
		$this->sortPriorities();
		return array_keys($this->_d);
	}

	/**
	 * Gets the number of items at a priority within the list
	 * @param null|numeric $priority optional priority at which to count items.  if no parameter, it will be set to the default {@link getDefaultPriority}
	 * @return int the number of items in the list at the specified priority
	 */
	public function getPriorityCount($priority = null)
	{
		$priority = $this->ensurePriority($priority);
		if (empty($this->_d[$priority])) {
			return 0;
		}
		return count($this->_d[$priority]);
	}

	/**
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface \IteratorAggregate.
	 * @return \Iterator an iterator for traversing the items in the list.
	 */
	public function getIterator(): \Iterator
	{
		$this->flattenPriorities();
		return new \ArrayIterator($this->_fd);
	}

	/**
	 * Gets all the items at a specific priority.
	 * @param null|numeric $priority priority of the items to get.  Defaults to null, filled in with the default priority, if left blank.
	 * @return ?array all items at priority in index order, null if there are no items at that priority
	 */
	public function itemsAtPriority($priority = null): ?array
	{
		$priority = $this->ensurePriority($priority);
		return $this->_d[$priority] ?? null;
	}

	/**
	 * @return array the priority list of items in array
	 */
	public function toArray(): array
	{
		$this->flattenPriorities();
		return $this->_fd;
	}

	/**
	 * @return array the array of priorities keys with values of arrays of items.  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toPriorityArray(): array
	{
		$this->sortPriorities();
		return $this->_d;
	}

	/**
	 * Combines the map elements which have a priority below the parameter value
	 * @param numeric $priority the cut-off priority.  All items of priority less than this are returned.
	 * @param bool $inclusive whether or not the input cut-off priority is inclusive.  Default: false, not inclusive.
	 * @return array the array of priorities keys with values of arrays of items that are below a specified priority.
	 *  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toArrayBelowPriority($priority, bool $inclusive = false): array
	{
		$this->sortPriorities();
		$items = [];
		foreach ($this->_d as $itemspriority => $itemsatpriority) {
			if ((!$inclusive && $itemspriority >= $priority) || $itemspriority > $priority) {
				break;
			}
			$items[] = $itemsatpriority;
		}
		if(empty($items)) {
			return [];
		}
		if ($this->getPriorityCombineStyle()) {
			return array_merge(...$items);
		} else {
			return array_replace(...$items);
		}
	}

	/**
	 * Combines the map elements which have a priority above the parameter value
	 * @param numeric $priority the cut-off priority.  All items of priority greater than this are returned.
	 * @param bool $inclusive whether or not the input cut-off priority is inclusive.  Default: true, inclusive.
	 * @return array the array of priorities keys with values of arrays of items that are above a specified priority.
	 *  The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toArrayAbovePriority($priority, bool $inclusive = true): array
	{
		$this->sortPriorities();
		$items = [];
		foreach ($this->_d as $itemspriority => $itemsatpriority) {
			if ((!$inclusive && $itemspriority <= $priority) || $itemspriority < $priority) {
				continue;
			}
			$items[] = $itemsatpriority;
		}
		if(empty($items)) {
			return [];
		}
		if ($this->getPriorityCombineStyle()) {
			return array_merge(...$items);
		} else {
			return array_replace(...$items);
		}
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 */
	protected function _priorityZappableSleepProps(&$exprops)
	{
		if ($this->_o === false) {
			$exprops[] = "\0*\0_o";
		}
		$exprops[] = "\0*\0_fd";
		if ($this->_dp === null) {
			$exprops[] = "\0" . __CLASS__ . "\0_dp";
		}
		if ($this->_p === null) {
			$exprops[] = "\0" . __CLASS__ . "\0_p";
		}
	}
}
