<?php

/**
 * IClassBehavior class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * IClassBehavior is the base interface for class behaviors that extend owner objects
 * with new information, properties, run time methods, and fine process modification.
 * This is a stateless behavior class and typically does not retains per object
 * information.  Each instance of IClassBehavior may be attached to multiple owners.
 *
 * IClassBehavior is one of two types of behavior interfaces.  The other type of
 * behavior interface is the {@see \Prado\Util\IBehavior} that handles stateful behaviors and
 * where each behavior is attached to only one owner.
 *
 * All public methods and properties in the behavior are inherited by the owners
 * and so IClassBehavior act like run-time traits.  All methods called on the
 * owner but implemented by the IClassBehavior, will have the owner (calling object)
 * injected as the first argument parameter of the method to identify it.
 *
 * For example:
 * ```php
 *  $result = $ownerObject->MethodOfClassBehavior(1, 20);
 *  $filteredText = $ownerObject->dyFilteringBehavior('filtered text', 10);
 * ```
 * will be acted within the IClassBehavior implementation as:
 * ```php
 *  public function MethodOfClassBehavior($owner, $firstParam, $secondParam)
 *  {
 *      // $owner === $ownerObject, $firstParam === 1, $secondParam === 20
 *      return $firstParam + $secondParam + $owner->getNumber();
 *  }
 * ```
 *
 * When an IClassBehaviors implements a "dy" dynamic event, the {@see \Prado\Util\TCallChain}
 * is appended to the end of the method argument list as well.  For example, a dynamic
 * event method implementation might look like:
 * ```php
 *  public function dyFilteringBehavior($owner, $defaultReturnData, $secondParam, TCallChain $chain)
 *  {
 *      // $owner === $ownerObject, $defaultReturnData === 'filter text', $secondParam === 10
 *      $defaultReturnData = $owner->processText($defaultReturnData, $secondParam);
 *
 *      // TCallChain dynamic method will return $defaultReturnData after other behaviors are run
 *      return $chain->dyFilteringBehavior($defaultReturnData, $secondParam);
 *  }
 * ```
 * In dynamic events, the TCallChain must be called with the dynamic event method
 * to continue the chain but without the first (owner object) and last (TCallChain)
 * argument parameter.  The $chain will return the first parameter value so as to
 * act like a filtering mechanism or default return value.
 *
 * The call chain may be optional to make the dynamic event method callable without
 * the $chain but will always be present in owner called behavior dynamic event
 * methods.  For example:
 * ```php
 *  public function dyFilteringBehavior($owner, $defaultReturnData, $secondParam, ?TCallChain $chain = null)
 *  {
 *      // $owner === $ownerObject, $defaultReturnData === 'filter text', $secondParam === 10
 *      $defaultReturnData = $owner->processText($defaultReturnData, $secondParam);
 *
 *      // TCallChain dynamic method will return $defaultReturnData after other behaviors are run
 *      if ($chain)
 *          return $chain->dyFilteringBehavior($defaultReturnData, $secondParam);
 *      else
 *           return $defaultReturnData;
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
interface IClassBehavior extends IBaseBehavior
{
}
