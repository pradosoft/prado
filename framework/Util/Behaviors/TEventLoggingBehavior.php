<?php

/**
 * TEventLoggingBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\TPropertyValue;
use Prado\Util\IBaseBehavior;
use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;
use Prado\Util\TCallChain;
use Prado\Util\TLogger;

/**
 * TEventLoggingBehavior class.
 *
 * TEventLoggingBehavior is a universal logging behavior that attaches to any
 * {@see \Prado\TComponent} and forwards every `on*` event the owner raises to
 * {@see \Prado\Prado::log()} with a configurable log level and category.
 *
 * **Dynamic event discovery** — unlike a behavior with a hand-maintained event
 * list, TEventLoggingBehavior inspects the owner's class hierarchy at attach
 * time via reflection, collecting every public method whose name matches the
 * `on[A-Z]*` convention. It also walks every other active behavior already
 * attached to the owner so that behavior-contributed events are captured too.
 * The result is stored internally for efficient detach.
 *
 * **Live behavior tracking** — when another behavior is attached to the same
 * owner after this logger is already active, {@see dyAttachBehavior} wires any
 * new `on*` events it contributes. When a behavior is detached,
 * {@see dyDetachBehavior} unwires the events it contributed, provided no other
 * source (owner class or sibling behavior) still declares them.
 *
 * **Filtering** — {@see getEventFilter} accepts an allowlist of event names
 * (case-insensitive). When the filter is empty every event is logged.
 *
 * **Dynamic events** — when {@see getLogDynamicEvents} is true, the behavior
 * implements {@see \Prado\Util\IDynamicMethods} and captures every `dy*` call
 * via {@see __dycall}, logging it at the configured level.
 *
 * Example — attach programmatically:
 * ```php
 * $component->attachBehavior('logger', new TEventLoggingBehavior());
 * ```
 *
 * Example — attach via application.xml:
 * ```xml
 *   <behavior name="logger"
 *             class="Prado\Util\Behaviors\TEventLoggingBehavior"
 *             Level="2"
 *             Category="myapp.events"
 *             LogDynamicEvents="true" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEventLoggingBehavior extends TBehavior implements IDynamicMethods
{
	/** @var int TLogger log level for event messages. Default: {@see TLogger::INFO}. */
	private int $_level = TLogger::INFO;

	/** @var string Log category. Default: 'prado'. */
	private string $_category = 'prado';

	/** @var bool Whether to log `on*` events. Default: true. */
	private bool $_logEvents = true;

	/** @var bool Whether to log `dy*` dynamic-event calls. Default: false. */
	private bool $_logDynamicEvents = false;

	/** @var array<int, string> Allowlist of `on*` event names; empty = all. */
	private array $_eventFilter = [];

	/** @var array<int, string> Allowlist of `dy*` method names; empty = all. */
	private array $_dynamicEventFilter = [];

	/** @var array<int, string> `on*` event names wired via attach or {@see dyAttachBehavior}. */
	private array $_loggedEvents = [];

	// ------------------------------------------------------------------ events

	/**
	 * Returns all `on*` events declared on the current owner component and
	 * its active behaviors, each mapped to {@see logEvent}. Returns the
	 * parent's (empty) map when called without an attached owner.
	 *
	 * Because the event set is derived from the owner at runtime, the result
	 * is never cached — {@see eventsLog} always calls this method fresh.
	 *
	 * @return array<string, string> event name → handler method name
	 */
	public function events(): array
	{
		$owner = $this->getOwner();
		if ($owner === null) {
			return parent::events();
		}
		$map = array_fill_keys($this->discoverOwnerEvents($owner), 'logEvent');
		return self::mergeHandlers(parent::events(), $map);
	}

	/**
	 * Returns the event-handler map without caching. Because {@see events}
	 * reflects the owner's current event set dynamically, the result must
	 * be recomputed on every call rather than cached.
	 *
	 * @return array<string, array<int, mixed>> event name → array of handlers
	 */
	public function eventsLog(): array
	{
		return self::mergeHandlers($this->events());
	}

	/**
	 * Returns false so that PRADO does not refuse to attach when the owner
	 * lacks some of the discovered events (owner-side discovery is the
	 * authoritative source, not a fixed list).
	 */
	public function getStrictEvents(): bool
	{
		return false;
	}

	/**
	 * Discovers every `on*` event on the owner at attach time and wires a
	 * single {@see logEvent} handler to each one.
	 *
	 * @param TComponent $component the owner component
	 * @return bool true when handlers transitioned from detached to attached
	 */
	protected function attachEventHandlers(TComponent $component): bool
	{
		if (!$this->setHandlersStatus($component, true)) {
			return false;
		}
		$priority = $this->getPriority();
		$events = $this->discoverOwnerEvents($component);
		$this->_loggedEvents = $events;
		foreach ($events as $event) {
			$component->attachEventHandler($event, [$this, 'logEvent'], $priority);
		}
		return true;
	}

	/**
	 * Detaches the {@see logEvent} handler from every event wired during the
	 * last {@see attachEventHandlers} call.
	 *
	 * @param TComponent $component the owner component
	 * @return bool true when handlers transitioned from attached to detached
	 */
	protected function detachEventHandlers(TComponent $component): bool
	{
		if (!$this->setHandlersStatus($component, false)) {
			return false;
		}
		foreach ($this->_loggedEvents as $event) {
			$component->detachEventHandler($event, [$this, 'logEvent']);
		}
		$this->_loggedEvents = [];
		return true;
	}

	// ---------------------------------------------------------------- accessors

	/**
	 * @return int the PRADO {@see \Prado\Util\TLogger} log level
	 */
	public function getLevel(): int
	{
		return $this->_level;
	}

	/**
	 * @param int|string $value a {@see \Prado\Util\TLogger} level constant
	 */
	public function setLevel(int|string $value): void
	{
		$this->_level = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string the log category
	 */
	public function getCategory(): string
	{
		return $this->_category;
	}

	/**
	 * @param string $value the log category
	 */
	public function setCategory(string $value): void
	{
		$this->_category = TPropertyValue::ensureString($value);
	}

	/**
	 * @return bool whether `on*` events are logged
	 */
	public function getLogEvents(): bool
	{
		return $this->_logEvents;
	}

	/**
	 * @param bool|string $value whether `on*` events are logged
	 */
	public function setLogEvents(bool|string $value): void
	{
		$this->_logEvents = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether `dy*` dynamic-event calls are logged
	 */
	public function getLogDynamicEvents(): bool
	{
		return $this->_logDynamicEvents;
	}

	/**
	 * @param bool|string $value whether `dy*` dynamic-event calls are logged
	 */
	public function setLogDynamicEvents(bool|string $value): void
	{
		$this->_logDynamicEvents = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return array<int, string> the `on*` event-name allowlist; empty = log all
	 */
	public function getEventFilter(): array
	{
		return $this->_eventFilter;
	}

	/**
	 * @param array<int, string>|string $value an allowlist of `on*` event names
	 *   (array or comma-separated string); empty = log every event
	 */
	public function setEventFilter(array|string $value): void
	{
		$this->_eventFilter = $this->normalizeFilter($value);
	}

	/**
	 * @return array<int, string> the `dy*` method-name allowlist; empty = log all
	 */
	public function getDynamicEventFilter(): array
	{
		return $this->_dynamicEventFilter;
	}

	/**
	 * @param array<int, string>|string $value an allowlist of `dy*` method names
	 *   (array or comma-separated string); empty = log every `dy*` call
	 */
	public function setDynamicEventFilter(array|string $value): void
	{
		$this->_dynamicEventFilter = $this->normalizeFilter($value);
	}

	// ----------------------------------------------------------------- handler

	/**
	 * Generic `on*` event handler wired to every discovered event on the owner.
	 * Logs the event name and sender class when the event passes the filter.
	 *
	 * @param object $sender the component raising the event
	 * @param TEventParameter $param the event parameter
	 */
	public function logEvent(object $sender, TEventParameter $param): void
	{
		if (!$this->shouldLogEvent($param)) {
			return;
		}
		Prado::log(
			sprintf('%s on %s', $param->getEventName() ?: '(unnamed)', get_class($sender)),
			$this->getLevel(),
			$this->getCategory()
		);
	}

	// -------------------------------------------------------- dynamic events

	/**
	 * Catches every undefined `dy*` method call on this behavior. When
	 * {@see getLogDynamicEvents} is enabled and the call passes
	 * {@see getDynamicEventFilter}, the invocation is logged at the
	 * configured level. The first argument is returned unchanged to
	 * preserve the `dy*` filter chain.
	 *
	 * @param string $method the `dy*` method name
	 * @param array<int, mixed> $args the arguments passed
	 * @return mixed the first argument (per PRADO `dy*` filter convention)
	 */
	public function __dycall($method, $args)
	{
		if ($this->getLogDynamicEvents() && $this->shouldLogDynamic($method)) {
			Prado::log(
				sprintf('%s(%d args) on %s', $method, count($args), $this->describeOwner()),
				$this->getLevel(),
				$this->getCategory()
			);
		}
		return $args[0] ?? null;
	}

	/**
	 * Wires any new `on[A-Z]*` events contributed by `$behavior` to
	 * {@see logEvent} when a behavior is added to the owner after this logger
	 * is already attached. Events already tracked in `$_loggedEvents` are
	 * skipped to avoid duplicate handlers.
	 *
	 * Called automatically by PRADO's behavior system after `$behavior` is
	 * fully attached to the owner.
	 *
	 * @param string $name the behavior name used during attachment
	 * @param IBaseBehavior $behavior the newly attached behavior
	 * @param ?TCallChain $chain the dynamic-event call chain; null when called directly
	 */
	public function dyAttachBehavior(string $name, IBaseBehavior $behavior, ?TCallChain $chain = null): void
	{
		if ($this->getOwner() !== null && $behavior !== $this && $behavior->getEnabled()) {
			$rc = new \ReflectionClass($behavior);
			$priority = $this->getPriority();
			$component = $this->getOwner();
			foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
				$evtName = $method->getName();
				if (!$method->isStatic() && preg_match('/^on[A-Z]/', $evtName) && !in_array($evtName, $this->_loggedEvents, true)) {
					$component->attachEventHandler($evtName, [$this, 'logEvent'], $priority);
					$this->_loggedEvents[] = $evtName;
				}
			}
		}
		if ($chain) {
			$chain->dyAttachBehavior($name, $behavior);
		}
	}

	/**
	 * Unwires `on[A-Z]*` events that were contributed solely by `$behavior`
	 * when it is detached from the owner. An event is kept when the owner's
	 * own class or another enabled sibling behavior still declares it.
	 *
	 * Called automatically by PRADO's behavior system before `$behavior` is
	 * removed from the owner.
	 *
	 * @param string $name the behavior name used during attachment
	 * @param IBaseBehavior $behavior the behavior being detached
	 * @param ?TCallChain $chain the dynamic-event call chain; null when called directly
	 */
	public function dyDetachBehavior(string $name, IBaseBehavior $behavior, ?TCallChain $chain = null): void
	{
		if ($this->getOwner() !== null && $behavior !== $this) {
			$rc = new \ReflectionClass($behavior);
			$component = $this->getOwner();
			$compRc = new \ReflectionClass($component);
			foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
				$evtName = $method->getName();
				if (!preg_match('/^on[A-Z]/', $evtName)) {
					continue;
				}
				// Determine whether the event survives once $behavior is gone.
				$survives = false;
				$survivorBehavior = null;
				if ($compRc->hasMethod($evtName)) {
					$cm = $compRc->getMethod($evtName);
					$survives = $cm->isPublic() && !$cm->isStatic();
				}
				if (!$survives && $component->getBehaviorsEnabled()) {
					foreach ($component->getBehaviors() as $other) {
						if ($other === $behavior || $other === $this || !$other->getEnabled()) {
							continue;
						}
						$bRc = new \ReflectionClass($other);
						if ($bRc->hasMethod($evtName)) {
							$bm = $bRc->getMethod($evtName);
							if ($bm->isPublic() && !$bm->isStatic()) {
								$survives = true;
								$survivorBehavior = $other;
								break;
							}
						}
					}
				}
				if (!$survives && in_array($evtName, $this->_loggedEvents, true)) {
					$component->detachEventHandler($evtName, [$this, 'logEvent']);
					$this->_loggedEvents = array_values(array_diff($this->_loggedEvents, [$evtName]));
				} elseif ($survivorBehavior instanceof TComponent
					&& in_array($evtName, $this->_loggedEvents, true)
					&& $behavior instanceof TComponent
					&& $behavior->hasEventHandler($evtName)
				) {
					// The event survives via another behavior. The logEvent handler
					// is stored on the behavior being detached (whose event list will
					// vanish). Move it to the surviving behavior so it persists.
					$behavior->detachEventHandler($evtName, [$this, 'logEvent']);
					$survivorBehavior->getEventHandlers($evtName)->add([$this, 'logEvent'], $this->getPriority());
				}
			}
		}
		if ($chain) {
			$chain->dyDetachBehavior($name, $behavior);
		}
	}

	// --------------------------------------------------------------- internals

	/**
	 * Discovers all public `on[A-Z]*` methods on the component's class hierarchy
	 * and on every currently enabled behavior attached to it (excluding self).
	 * The returned list is deduplicated and preserves the reflection order.
	 *
	 * @param TComponent $component the component to inspect
	 * @return array<int, string> deduplicated list of event method names
	 */
	private function discoverOwnerEvents(TComponent $component): array
	{
		$events = [];

		// Events declared directly on the component class hierarchy.
		$rc = new \ReflectionClass($component);
		foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			$name = $method->getName();
			if (!$method->isStatic() && preg_match('/^on[A-Z]/', $name)) {
				$events[] = $name;
			}
		}

		// Events contributed by other active behaviors already attached.
		if ($component->getBehaviorsEnabled()) {
			foreach ($component->getBehaviors() as $behavior) {
				if ($behavior === $this || !$behavior->getEnabled()) {
					continue;
				}
				$bRc = new \ReflectionClass($behavior);
				foreach ($bRc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
					$name = $method->getName();
					if (!$method->isStatic() && preg_match('/^on[A-Z]/', $name)) {
						$events[] = $name;
					}
				}
			}
		}

		return array_values(array_unique($events));
	}

	/**
	 * Returns whether a given `on*` event parameter passes the log filter.
	 *
	 * @param TEventParameter $param the event parameter
	 * @return bool true when the event should be logged
	 */
	private function shouldLogEvent(TEventParameter $param): bool
	{
		if (!$this->getLogEvents()) {
			return false;
		}
		$filter = $this->getEventFilter();
		if ($filter === []) {
			return true;
		}
		return in_array(strtolower($param->getEventName()), array_map('strtolower', $filter), true);
	}

	/**
	 * Returns whether a given `dy*` method name passes the dynamic event filter.
	 *
	 * @param string $method the `dy*` method name
	 * @return bool true when the call should be logged
	 */
	private function shouldLogDynamic(string $method): bool
	{
		$filter = $this->getDynamicEventFilter();
		if ($filter === []) {
			return true;
		}
		return in_array($method, $filter, true);
	}

	/**
	 * Returns a short description of the current owner for log messages.
	 *
	 * @return string the owner class name, or `'(detached)'` when no owner
	 */
	private function describeOwner(): string
	{
		$owner = $this->getOwner();
		return $owner !== null ? get_class($owner) : '(detached)';
	}

	/**
	 * Normalizes a filter value to a trimmed, non-empty string array.
	 * Arrays of strings pass through unchanged; comma-separated strings are
	 * split and trimmed; whitespace-only entries are dropped.
	 *
	 * @param array<int, string>|string $value raw filter value
	 * @return array<int, string> normalized filter list
	 */
	private function normalizeFilter(array|string $value): array
	{
		if (is_string($value)) {
			$value = $value === '' ? [] : explode(',', $value);
		}
		$out = [];
		foreach ($value as $entry) {
			$entry = trim((string) $entry);
			if ($entry !== '') {
				$out[] = $entry;
			}
		}
		return $out;
	}

	/**
	 * Excludes transient and default-valued fields from serialization.
	 * `$_loggedEvents` is always excluded because it is rebuilt on every
	 * {@see attachEventHandlers()} call. `$_level`, `$_category`,
	 * `$_logEvents`, `$_logDynamicEvents`, `$_eventFilter`, and
	 * `$_dynamicEventFilter` are excluded only when they hold their
	 * default values, keeping the serialized payload lean.
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_loggedEvents";
		if ($this->getLevel() === TLogger::INFO) {
			$exprops[] = "\0" . __CLASS__ . "\0_level";
		}
		if ($this->getCategory() === 'prado') {
			$exprops[] = "\0" . __CLASS__ . "\0_category";
		}
		if ($this->getLogEvents() === true) {
			$exprops[] = "\0" . __CLASS__ . "\0_logEvents";
		}
		if ($this->getLogDynamicEvents() === false) {
			$exprops[] = "\0" . __CLASS__ . "\0_logDynamicEvents";
		}
		if ($this->getEventFilter() === []) {
			$exprops[] = "\0" . __CLASS__ . "\0_eventFilter";
		}
		if ($this->getDynamicEventFilter() === []) {
			$exprops[] = "\0" . __CLASS__ . "\0_dynamicEventFilter";
		}
	}
}
