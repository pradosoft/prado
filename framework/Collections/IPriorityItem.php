<?php

/**
 * IPriorityItem interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * IPriorityItem interface
 *
 * IPriorityItem specifies the interface for adding objects that automatically
 * prioritize themselves in a TPriorityList or TPriorityMap
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
interface IPriorityItem
{
	/**
	 * @return numeric priority of the item
	 */
	public function getPriority();
}
