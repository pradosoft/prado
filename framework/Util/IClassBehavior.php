<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * IClassBehavior interface is implements behaviors across all instances of
 * a particular class
 *
 * Any calls to functions not present in the original object but to behaviors
 * derived from this class, will have inserted as the first argument parameter
 * the object containing the behavior.
 *
 * For example:
 * <code>
 * $objWithClassBehavior->MethodOfClassBehavior(1, 20);
 * </code>
 * will be acted within the class behavior like this:
 * <code>
 * public function MethodOfClassBehavior($object, $firstParam, $secondParam){
 *      // $object === $objWithClassBehavior, $firstParam === 1, $secondParam === 20
 * }
 * </code>
 *
 * This also holds for 'dy' events as well.  For dynamic events, method arguments would be:
 * <code>
 * public function dyMethodOfClassBehavior($object, $firstParam, $secondParam, $callchain){
 *      // $object === $objWithClassBehavior, $firstParam === 1, $secondParam === 20, $callchain instanceof {@link TCallChain}
 * }
 * </code>
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @package Prado\Util
 * @since 3.2.3
 */
interface IClassBehavior extends IBaseBehavior
{
}
