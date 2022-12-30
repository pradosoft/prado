<?php
/**
 * IPriorityProperty interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * IPriorityProperty interface
 *
 * IPriorityProperty specifies the interface for objects to have
 * setter methods for the priority they receive in a TPriorityList
 * or TPriorityMap.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.2
 */
interface IPriorityProperty extends IPriorityItem
{
	/**
	 * @param numeric $value priority of the item
	 */
	public function setPriority($value);
}
