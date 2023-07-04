<?php
/**
 * TBaseBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Collections\TPriorityPropertyTrait;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplicationComponent;
use Prado\TComponent;
use Prado\TPropertyValue;

/**
 * TBaseBehavior is the base implementing class for both PRADO behaviors types
 * {@see \Prado\Util\TClassBehavior} and {@see @TBehavior}.
 *
 * This provides an {@see init} stub, {@see events} for attaching the behaviors'
 * handlers (value) to events (keys), an {@see getEnabled Enabled} flag, the {@see
 * getName Name} of the behavior, a {@see getRetainDisabledHandlers RetainDisabledHandlers}
 * flag to retain event handlers on the behavior being disabled, and {@see attach}ing
 * and {@see detach}ing from an owner.  Attaching and detaching call methods {@see
 * syncEventHandlers} and {@see detachEventHandlers}, respectively, to manage the
 * behaviors' handlers in the owner[s] events.
 *
 * Behaviors use the {@see TPriorityItemTrait} to receive priority information
 * from insertion into the owner's {@see \Prado\Collections\TPriorityMap} of behaviors.  When attaching
 * events to an owner, the event handlers receive the same priority as the behavior
 * in the owner.
 *
 * Changing the {@see setEnabled Enabled} flag can automatically attach or detach
 * events from the owner. If the behavior events should be preserved in the owner
 * when disabled, set {@see setRetainDisabledHandlers RetainDisabledHandlers} to
 * true.  To force detach event handlers, give this property the value null.  When
 * false, the default attachment logic applies where the behavior event handlers
 * are attached where the owner has behaviors enabled and the behavior is enabled.
 *
 * Event Handlers from {@see events} are cached and available by the method {@see
 * eventsLog}.  The eventsLog retains Closures across event handler management
 * and multiple TClassBehavior owners.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
abstract class TBaseBehavior extends TApplicationComponent implements IBaseBehavior
{
	use TPriorityPropertyTrait;

	/** @var ?string The name of the behavior in the owner[s] */
	protected ?string $_name = null;

	/** @var bool Is the behavior enabled or not. Default true */
	private bool $_enabled = true;

	/** @var null|bool Indicates how to maintain the event handlers. Default: false
	 *   for default installation logic. True for always attached and null for always
	 *   detached.
	 */
	private $_retainDisabledHandlers = false;

	/** @var null|array|false The cached events of the behavior, for Closures. */
	private $_eventsLog = false;

	/**
	 * Cloning a new instance clears the behavior name.
	 */
	public function __clone()
	{
		$this->_name = null;
		parent::__clone();
	}

	/**
	 * Behaviors do not automatically listen to global events.
	 *
	 * @return bool returns whether or not to listen.
	 */
	public function getAutoGlobalListen()
	{
		return false;
	}

	/**
	 * This processes behavior configuration elements.  This is usually called before
	 * attach. This is only needed for complex behavior configurations.
	 * @param array|\Prado\Xml\TXmlElement $config The innards to the behavior
	 *   configuration.
	 */
	public function init($config)
	{
	}

	/**
	 * Declares events and the corresponding event handler methods.
	 * The events are defined by the {@see owner} component, while the handler
	 * methods defined in the behavior class.  These events will be attached to the
	 * owner depending on the enable status and {@see getRetainDisabledHandlers}.
	 * The format of events is as follows:
	 * e.g. return ["onEvent" => function($sender, $param) {...}, "onInit" => "myInitHandler",
	 * 'onAnEvent' => [$this, 'methodHandler'], 'onOtherEvent' => [[$this, 'handlerMethod'],
	 * "behaviorMethod", function($sender, $param) {...}];
	 *
	 * Subclasses should use {@see mergeHandlers} to combine event handlers with the
	 * parent's event handlers.  For example:
	 * ```php
	 *	return self::mergeHandlers(parent::events(), ['onEvent' => 'myHandler', ...]]);
	 * ```
	 *
	 * Acceptable array values are string name of behavior method, callable, or
	 * an array of string behavior method names or callables.
	 * @return array events (array keys) and the corresponding event handler methods
	 *   (array values).
	 */
	public function events()
	{
		return [];
	}

	/**
	 * Returns the cached events of the behavior where Closures are retained.  TClassBehavior
	 * owners will all have the same Closure instances.
	 * @return array events (keys) and the array of corresponding event handler methods (values).
	 *   e.g. return results look like: ['onMyEvent' => ['objectMethod', $callable, $closure],
	 *   'onEvent' => ['behaviorMethod', $callable2, $closure2]]
	 */
	public function eventsLog()
	{
		if ($this->_eventsLog === false) {
			$this->_eventsLog = self::mergeHandlers($this->events());
		}
		return $this->_eventsLog;
	}

	/**
	 * By default, all events with handlers in {@see eventsLog} are attached or there
	 * will be an error.  When this is false, attaching behavior event handlers will
	 * not fail if the owner is missing events and handlers are not attached.  Put
	 * another way, when false, behavior event handlers are optional rather than made
	 * mandatory.
	 * @return bool Strictly enforce attaching behavior event handlers. Default true.
	 */
	public function getStrictEvents(): bool
	{
		return true;
	}

	/**
	 * Merges the handlers of an event into an array of keys (as event names) and values
	 * as an array of behavior method names (strings) and callable.
	 * @param array $args The array of events (keys) and values of either behavior method
	 *   names (strings), callable, or an array of the former.  Multiple handlers can be
	 *   set on a single event to allow subclasses to have their own handlers.
	 * @return array The array of combined events (keys) and an array (values) of handlers
	 *   for each.
	 */
	public static function mergeHandlers(...$args): array
	{
		if(empty($args)) {
			return [];
		}
		$combined = [];
		foreach ($args as $events) {
			foreach ($events as $name => $handler) {
				if (!isset($combined[$name])) {
					$combined[$name] = [];
				}
				if (is_string($handler) || is_callable($handler)) {
					$combined[$name][] = $handler;
				} elseif (is_array($handler)) {
					$combined[$name] += $handler;
				}
			}
		}
		return $combined;
	}

	/**
	 * Attaches the behavior object to a new owner component.
	 * The default implementation will synchronize attachment of event handlers declared
	 * in {@see eventsLog}.
	 * Make sure you call the parent implementation if you override this method.
	 * @param TComponent $component the component that this behavior is being attached to.
	 */
	public function attach($component)
	{
		$this->syncEventHandlers($component);
	}

	/**
	 * Detaches the behavior object from an owner component.
	 * The default implementation will detach event handlers declared in {@see eventsLog}.
	 * Make sure you call this parent implementation if you override this method.
	 * @param TComponent $component the component that this behavior is being detached from.
	 */
	public function detach($component)
	{
		$this->detachEventHandlers($component);
	}

	/**
	 * @return ?string The name of the behavior in the owner[s].
	 */
	public function getName(): ?string
	{
		return $this->_name;
	}

	/**
	 * @param ?string $value The name of the behavior in the owner[s].
	 * @throws TInvalidOperationException When there is an owner and the new name is
	 *   not the same as the given name.
	 */
	public function setName($value)
	{
		if (!$this->hasOwner()) {
			$this->_name = $value;
		} elseif (!is_numeric($value) && strtolower($value) !== $this->_name) {
			throw new TInvalidOperationException('basebehavior_cannot_setname_with_owner', $this->_name, $value);
		}
	}

	/**
	 * @return bool Whether this behavior is enabled, Default true.
	 */
	public function getEnabled(): bool
	{
		return $this->_enabled;
	}

	/**
	 * This method sets the enabled flag and synchronizes the behavior's handlers with
	 * its owner[s].
	 * @param bool|string $value Whether this behavior is enabled.
	 */
	public function setEnabled($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		if ($this->_enabled !== $value) {
			$this->_enabled = $value;
			$this->syncEventHandlers();
		}
	}

	/**
	 * RetainDisabledHandlers has three states:
	 *   1) "true" is to always install the event handlers regardless of behavior enabled
	 *      or owner behaviors enabled status.
	 *   2) "false" is the default attachment logic to remove the event handlers when
	 *      the behavior or owner behaviors are disabled. (default)
	 *   3) "null" is to always remove the event handlers.
	 * @return null|bool Does the behavior retain the handlers when disabled (true),
	 *   remove the handlers when the behavior/owner is disabled (false), or always
	 *   remove the handlers (null).
	 */
	public function getRetainDisabledHandlers()
	{
		return $this->_retainDisabledHandlers;
	}

	/**
	 * This changes the retaining of disabled behavior handlers and then synchronizes
	 * the behavior's handlers with its owner[s].  There are three acceptable values:
	 *   1) "true" is to always install the event handlers regardless of behavior enabled
	 *      or owner behaviors enabled status.
	 *   2) "false" is the default attachment logic to remove the event handlers when
	 *      the behavior or owner behaviors are disabled. (default)
	 *   3) "null" is to always remove the event handlers.
	 * @param null|bool $value Does the behavior retain the handlers when disabled (true),
	 *   remove the handlers when the behavior/owner is disabled (false), or always
	 *   remove handlers (null).
	 */
	public function setRetainDisabledHandlers($value)
	{
		if ($value !== 0 && $value !== null && (!is_string($value) || strtolower($value) !== 'null' && $value !== '0')) {
			$value = TPropertyValue::ensureBoolean($value);
		} else {
			$value = null;
		}
		if ($this->_retainDisabledHandlers !== $value) {
			$this->_retainDisabledHandlers = $value;
			$this->syncEventHandlers();
		}
	}

	/**
	 * This synchronizes an owner's events of the behavior event handlers by attaching
	 * or detaching where needed.  A behaviors handlers are attached depending on whether
	 * {@see getRetainDisabledHandlers} is true (or null) or both the owner and behavior are
	 * [Behavior] enabled.  The $attachOverride will set RetainDisabledHandlers when not
	 * its default value 0 and thus can act like {@see setRetainDisabledHandlers}.
	 * @param ?object $component The component to manage the behaviors handlers on. Default
	 *   is null for synchronizing all owners.
	 * @param null|bool|int $attachOverride Overrides the default attachment logic or whether
	 *   to install and forcibly attach or detach the handlers when true or null.  false resets
	 *   RetainDisabledHandlers to the default attachment logic (of when enabled). Default is 0
	 *   for normal action and no override (true/null) or reset (false).
	 * @throws TInvalidOperationException When synchronizing a component without owners
	 *   or the component that isn't an owner.
	 * @since 4.2.3
	 */
	public function syncEventHandlers(?object $component = null, $attachOverride = 0)
	{
		$hasOwner = $this->hasOwner();
		if ($component && !$hasOwner) {
			throw new TInvalidOperationException('basebehavior_sync_no_owner', $this->getName());
		}
		if (!$hasOwner) {
			return;
		}
		if ($component && !$this->isOwner($component)) {
			throw new TInvalidOperationException('basebehavior_sync_not_owner', $this->getName());
		}
		foreach ($component ? [$component] : $this->getOwners() as $component) {
			if (is_bool($attachOverride) || $attachOverride === null) {
				$this->_retainDisabledHandlers = $attachOverride;
			}
			if ($this->_retainDisabledHandlers !== false) {
				$install = (bool) $this->_retainDisabledHandlers;
			} else {
				$install = $this->getEnabled() && $component->getBehaviorsEnabled();
			}
			if ($this->getHandlersStatus($component) ^ $install) {
				if ($install) {
					$this->attachEventHandlers($component);
				} else {
					$this->detachEventHandlers($component);
				}
			}
		}
	}

	/*
	 * Attaches the behavior event handlers to an owner component. This tracks of the
	 * attachment status of handlers on the owner components.
	 * @param TComponent $component The component to attach the behavior event handlers to.
	 * @return bool Successfully attached the event handlers.
	 * @since 4.2.3
	 */
	protected function attachEventHandlers(TComponent $component): bool
	{
		if ($this->setHandlersStatus($component, true)) {
			$priority = $this->getPriority();
			$strict = $this->getStrictEvents();
			foreach ($this->eventsLog() as $event => $handlers) {
				if ($strict || $this->hasEvent($event)) {
					foreach($handlers as $handler) {
						$component->attachEventHandler($event, is_string($handler) ? [$this, $handler] : $handler, $priority);
					}
				}
			}
			return true;
		}
		return false;
	}

	/*
	 * Detaches the behavior event handlers from an owner component. This tracks of the
	 * attachment status of handlers on the owner components.
	 * @param TComponent $component The component to detach the behavior event handlers from.
	 * @return bool Successfully detached the event handlers.
	 * @since 4.2.3
	 */
	protected function detachEventHandlers(TComponent $component): bool
	{
		if ($this->setHandlersStatus($component, false)) {
			$strict = $this->getStrictEvents();
			foreach ($this->eventsLog() as $event => $handlers) {
				if ($strict || $this->hasEvent($event)) {
					foreach($handlers as $handler) {
						$component->detachEventHandler($event, is_string($handler) ? [$this, $handler] : $handler);
					}
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * This gets the attachment status of the behavior event handlers on the given
	 * component.
	 * @param ?TComponent $component The component to check the status of the handlers.
	 *   Null only works for IBehavior and returns the status of the single owner.
	 * @return bool Are the behavior handlers attached to the given owner events.
	 * @since 4.2.3
	 */
	abstract protected function getHandlersStatus(?TComponent $component = null): ?bool;

	/**
	 * This sets the attachment status of the behavior event handlers on the given
	 * component.
	 * @param TComponent $component The component to set the status of.
	 * @param bool $attach "true" to attach the handlers or "false" detach them.
	 * @return bool Is there a change in the attachment status for the given owner
	 *   component.
	 * @since 4.2.3
	 */
	abstract protected function setHandlersStatus(TComponent $component, bool $attach): bool;

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array &$exprops Properties of the object to exclude.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0*\0_name";
		if ($this->_enabled === true) {
			$exprops[] = "\0" . __CLASS__ . "\0_enabled";
		}
		if ($this->_retainDisabledHandlers === null) {
			$exprops[] = "\0" . __CLASS__ . "\0_retainDisabledHandlers";
		}
		$exprops[] = "\0" . __CLASS__ . "\0_eventsLog";
		$this->_priorityItemZappableSleepProps($exprops);
	}
}
