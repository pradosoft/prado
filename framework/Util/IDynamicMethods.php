<?php

/**
 * IDynamicMethods interface file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <belisoful@icloud.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * IDynamicMethods interface.
 * IDynamicMethods marks an object to receive undefined global or dynamic events.
 *
 * {@see \Prado\TComponent::callBehaviorsMethod} calls {@see __dycall} for each `dy` and `fx`
 * event that no behavior handles, on the component and on each behavior in the event's call
 * chain.  The dynamic events that filter their first argument, `dyPreRaiseEvent` on the event
 * name among them, keep the argument on a null result, so an implementation that returns null
 * for the events it ignores needs no case for them.  A handled filter event returns the filtered
 * argument.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.2.3
 */
interface IDynamicMethods
{
	/**
	 * Handles an undefined dynamic or global event.
	 * @param string $method the `dy` or `fx` event name
	 * @param array $args the event arguments
	 * @return mixed the event result; null leaves a filter event's argument in place
	 */
	public function __dycall($method, $args);
}
