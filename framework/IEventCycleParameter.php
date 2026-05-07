<?php

/**
 * IEventCycleParameter interface.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IEventCycleParameter interface.
 *
 * Extends {@see IEventParameter} to provide event lifecycle hooks that are invoked
 * by {@see TComponent::raiseEvent} before and after event handlers are executed.
 *
 * This interface allows event parameters to participate in the event raising process
 * by providing hooks for pre-processing before handlers are called and post-processing
 * after all handlers have completed. This is useful for:
 * - Logging event execution.
 * - Modifying event parameters before they reach handlers.
 * - Processing or aggregating handler responses.
 * - Implementing event caching or optimization strategies.
 *
 * When an event parameter implements this interface, {@see TComponent::raiseEvent}
 * automatically invokes the lifecycle methods at appropriate points:
 * 1. Before event handlers: {@see preRaiseEvent} is called.
 * 2. Event handlers are executed in standard fashion.
 * 3. After event handlers: {@see postRaiseEvent} is called with all responses.
 *
 * The lifecycle methods receive the same parameters used in the event raising process,
 * allowing the parameter to access the event name, sender, response type, and post-function.
 *
 * Usage example:
 * ```php
 * class TLoggingEventParameter extends TEventParameter implements IEventCycleParameter
 * {
 *     public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction)
 *     {
 *         // Log that event is about to be raised
 *         Prado::log("Event {$name} raised by " . $sender::class, 'info');
 *     }
 *
 *     public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)
 *     {
 *         // Log results after event processing
 *         $responseCount = is_array($responses) ? count($responses) : 0;
 *         Prado::log("Event {$name} completed with {$responseCount} responses", 'info');
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 * @see TComponent::raiseEvent
 * @see TEventParameter
 */
interface IEventCycleParameter extends IEventParameter
{
	/**
	 * Invoked before event handlers are executed during {@see TComponent::raiseEvent}.
	 *
	 * This method is called immediately after the event name is set on the parameter
	 * and before any event handlers are invoked. It allows the event parameter to
	 * perform pre-processing, validation, or initialization based on the event context.
	 *
	 * The method receives all parameters involved in the event raising process:
	 * - $name: The event name (lowercase)
	 * - $sender: The object raising the event
	 * - $param: The event parameter instance itself
	 * - $responsetype: How to handle handler responses (EVENT_RESULT_FILTER, etc.)
	 * - $postfunction: Optional callable for per-handler response processing
	 *
	 * This is useful for:
	 * - Logging/debugging event execution
	 * - Validating or modifying event context before handlers run
	 * - Setting up state for tracking during the event lifecycle
	 *
	 * @param string $name The event name (lowercase)
	 * @param mixed $sender The object raising the event
	 * @param TEventParameter $param The event parameter instance
	 * @param null|int $responsetype How results should be tabulated (see TEventResults constants)
	 * @param null|callable $postfunction Optional callable for post-processing each handler response
	 * @see TComponent::raiseEvent
	 * @see TEventResults
	 */
	public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction);

	/**
	 * Invoked after all event handlers have been executed during {@see TComponent::raiseEvent}.
	 *
	 * This method is called after all event handlers complete execution and after any
	 * response filtering or post-processing has occurred. It receives the aggregated
	 * responses from all handlers, allowing the event parameter to perform final
	 * processing, aggregation, or cleanup.
	 *
	 * The method receives:
	 * - $responses: Array of handler responses (or filtered responses based on responsetype)
	 * - $name: The event name (lowercase)
	 * - $sender: The object raising the event
	 * - $param: The event parameter instance
	 * - $responsetype: How responses were tabulated
	 * - $postfunction: The post-processing function that was used
	 *
	 * This is useful for:
	 * - Aggregating or analyzing handler responses
	 * - Logging event completion and results
	 * - Implementing caching or memoization of results
	 * - Cleanup of state set up in preRaiseEvent
	 *
	 * @param array $responses Aggregated responses from all event handlers
	 * @param string $name The event name (lowercase)
	 * @param mixed $sender The object raising the event
	 * @param TEventParameter $param The event parameter instance
	 * @param null|int $responsetype How responses were tabulated
	 * @param null|callable $postfunction The post-processing function that was used
	 * @see TComponent::raiseEvent
	 * @see TEventResults
	 */
	public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction);
}
