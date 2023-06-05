<?php
/**
 * TPriorityPropertyTrait class
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\TPropertyValue;

/**
 * TPriorityPropertyTrait class
 *
 * This trait implements the common properties and methods of IPriorityItem. You still
 * must "implement IPriorityItem" in your class when you use this trait as they go
 * together.
 *
 * The trait adds methods:
 *	- {@link getPriority} returns the default priority of items without priority.
 *	- {@link setPriority} sets the default priority. (protected)
 *	- {@link _priorityItemZappableSleepProps} to add the excluded trait properties on sleep.
 *
 * The priority is implement with a float.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
trait TPriorityPropertyTrait
{
	/** @var ?float The set Priority of the item. Default null */
	private ?float $_priority = null;

	/**
	 * @return ?float The priority of the item. Default is null.
	 */
	public function getPriority(): ?float
	{
		return $this->_priority;
	}

	/**
	 * @param numeric $value The priority of the item.
	 */
	public function setPriority($value)
	{
		$this->_priority = TPropertyValue::ensureFloat($value);
	}

	/**
	 * Call this to exclude the priority property from sleep properties when there
	 * is no priority.
	 * @param array $exprops by reference
	 */
	protected function _priorityItemZappableSleepProps(&$exprops)
	{
		if ($this->_priority === null) {
			$exprops[] = "\0" . __CLASS__ . "\0_priority";
		}
	}
}
