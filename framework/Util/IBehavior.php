<?php

/**
 * IBehavior class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Dynamic events, Class-wide behaviors, expanded behaviors
 * @author Brad Anderson <belisoful@icloud.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * IBehavior is the base interface for behaviors that extend an owner component with
 * new state information, properties, run time methods, and fine process modification.
 * This is a stateful behavior class and retains per object information.  Each instance
 * of IBehavior may only be attached to a single owner.  When attached at the class
 * level, IBehaviors are cloned from the given IBehavior object or instanced individually
 * from its class/property array configuration (or string).
 *
 * IBehavior is one of two types of behavior interfaces.  The other type of behavior
 * interface is the {@see \Prado\Util\IClassBehavior} that handles stateless behaviors and
 * where each behavior is attached to multiple different owners.
 *
 * All public methods and properties in the behavior are inherited by the owners
 * and so IBehavior act like run-time traits.
 *
 * When a method is called on the owner that is implemented by the behavior, there is
 * no change in the parameters.  For example:
 * ```php
 *  $result = $objWithBehavior->MethodOfBehavior(1, 20);
 *  $filteredText = $objWithBehavior->dyFilteringBehavior('filter text', 10);
 * ```
 * will be implemented within an IBehavior like this:
 * ```php
 *  public function MethodOfBehavior($firstParam, $secondParam)
 *  {
 *      // $firstParam === 1, $secondParam === 20
 *      return $firstParam + $secondParam + $this->getOwner()->getNumber();
 *  }
 * ```
 *
 * When an IBehaviors implements a "dy" dynamic event, the {@see \Prado\Util\TCallChain} is
 * appended to the end of the method argument list.  For example, a dynamic event method
 * implementations might look like:
 * ```php
 *  public function dyFilteringBehavior($defaultReturnData, $secondParam, TCallChain $chain)
 *  {
 *      // $defaultReturnData === 'filter text', $secondParam === 10
 *      $defaultReturnData = $this->getOwner()->processText($defaultReturnData, $secondParam);
 *
 *      // TCallChain dynamic method will return $defaultReturnData after other behaviors are run
 *      return $chain->dyFilteringBehavior($defaultReturnData, $secondParam);
 *  }
 * ```
 * In dynamic events, the TCallChain must be called with the dynamic event method
 * to continue the chain but without the last (TCallChain) argument parameter.  The
 * $chain will return the first parameter value so as to act like a filtering mechanism
 * or default return value.
 *
 * The call chain may be optional to make the dynamic event method callable without
 * the $chain but will always be present in owner called behavior dynamic event
 * methods.  For example:
 * ```php
 *  public function dyFilteringBehavior($defaultReturnData, $secondParam, ?TCallChain $chain = null)
 *  {
 *      // $defaultReturnData === 'filter text', $secondParam === 10
 *      $defaultReturnData = $this->getOwner()->processText($defaultReturnData, $secondParam);
 *
 *      // TCallChain dynamic method will return $defaultReturnData after other behaviors are run
 *      if ($chain)
 *          return $chain->dyFilteringBehavior($defaultReturnData, $secondParam);
 *      else
 *          return $defaultReturnData;
 *  }
 * ```
 *
 * All dynamic event logic should be before the $chain dynamic event continuation
 * unless specifically designated, in very rare instances.  Placing your behavior
 * logic after the $chain continuation will reverse the order of the processing
 * from behavior priority.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.2.3
 */
interface IBehavior extends IBaseBehavior
{
	/**
	 * @return ?object The owner component that this behavior is attached to.
	 *   Default null when not attached.
	 */
	public function getOwner(): ?object;
}
