<?php
/**
 * IPriorityCapture interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * IPriorityCapture interface
 *
 * IPriorityCapture specifies the interface for capturing the priority of
 * added objects in a TPriorityList or TPriorityMap
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.2
 */
interface IPriorityCapture
{
	/**
	 * @param numeric $value priority of the item
	 */
	public function setPriority($value);
}
