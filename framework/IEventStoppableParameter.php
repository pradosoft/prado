<?php

/**
 * IEventStoppableParameter interface.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IEventStoppableParameter interface.
 *
 * Extends {@see IEventParameter} to let an event handler halt the remaining
 * handlers of a {@see TComponent::raiseEvent} call. After a handler stops the
 * parameter with {@see stopImmediatePropagation}, {@see TComponent::raiseEvent} finishes
 * the current handler and then skips every handler still queued. Handlers that
 * already ran, and the post-processing of their responses, are unaffected.
 *
 * The behavior matches the DOM `stopImmediatePropagation()`: because a Prado
 * event dispatches to a flat list of handlers rather than a bubbling tree,
 * stopping propagation stops the remaining handlers in that list. This is
 * distinct from the control-tree bubbling of {@see TControl::raiseBubbleEvent};
 * it does not affect that chain.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see TComponent::raiseEvent
 * @see TEventParameter
 */
interface IEventStoppableParameter extends IEventParameter
{
	/**
	 * @return bool whether propagation to the remaining event handlers is stopped.
	 */
	public function getStopped(): bool;

	/**
	 * Stops propagation to the remaining event handlers. The current handler
	 * still completes; handlers still queued for this event are skipped.
	 */
	public function stopImmediatePropagation(): void;
}
