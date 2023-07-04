<?php
/**
 * IPriorityCollection interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * IPriorityCollection interface
 *
 * IPriorityCollection is implemented by {@see \Prado\Collections\TPriorityList} and {@see \Prado\Collections\TPriorityMap}
 * that provide priority collections.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
interface IPriorityCollection
{
	/**
	 * @return numeric gets the default priority of inserted items without a specified priority.
	 */
	public function getDefaultPriority();

	/**
	 * @return int The precision of numeric priorities, defaults to 8.
	 */
	public function getPrecision(): int;

	/**
	 * Returns the priority of an item at a particular key/index.  This searches the map for the item.
	 * @param mixed $key key or index of the item within the map.
	 * @return false|numeric priority of the item in the map. False if not found.
	 */
	public function priorityAt($key);
}
