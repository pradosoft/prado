<?php

/**
 * TNoUnserializeClassBehaviorTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Util\TCallChain;

/**
 * TNoUnserializeClassBehaviorTrait class.
 *
 * When this trait is used by an IClassBehavior, upon the owner being unserialized
 * (via magic method __wakeup and dyWakeUp) this trait removes itself from its owner.
 *
 * This trait is used to deprecate serialized objects' IClassBehavior. By re-serializing
 * the object can be saved without the deprecated behavior.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
trait TNoUnserializeClassBehaviorTrait
{
	/**
	 * This is raised when an owner is completed its unserialize() method call to
	 * __wakeup.  This method removes its behavior from the owner.
	 * @param object $owner The owner of he behavior raising the dynamic event.
	 * @param TCallChain $chain The chain of dynamic event method handlers.
	 */
	public function dyWakeUp(object $owner, TCallChain $chain)
	{
		if ($index = array_search($this, $owner->getBehaviors())) {
			$owner->detachBehavior($index);
		}
		return $chain->dyWakeUp();
	}
}
