<?php

/**
 * TComponentProxyTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Collections\TWeakCallableCollection;

/**
 * TComponentProxyTrait trait
 *
 * TComponentProxyTrait provides the transparent-proxy logic shared by every
 * {@see IProxy} implementation. It supplies event forwarding ({@see attachProxy}
 * and {@see detachProxy}), property and method dispatch ({@see __call()},
 * {@see __get()}, {@see __set()}, {@see __isset()}, {@see __unset()}), type
 * transparency ({@see isa()}), and the serialization helpers.
 *
 * ## Required interface
 *
 * A class using this trait implements one method and may override a second:
 *
 * | Method | Purpose |
 * |--------|---------|
 * | `getProxyBacking(): ?TComponent` | Returns the backing component, resolving it lazily when needed. Returns `null` or throws {@see \Prado\Exceptions\TConfigurationException} when the backing is not configured or not found. |
 * | `canResolveProxyBacking(): bool` | Returns `true` when the backing can be lazily resolved, such as when a module ID is configured. The default returns `false`. |
 *
 * ## Backing storage
 *
 * The trait owns `$_proxyBacking`, the `?TComponent` field every proxy class
 * shares. A typed proxy such as {@see \Prado\Caching\TCacheProxy} wraps
 * {@see getProxyBackingDirect()} and {@see setProxyBackingDirect()} in accessors
 * that narrow the type with `instanceof`.
 *
 * ## Dispatch
 *
 * Every dispatch method reads {@see getProxyBackingDirect()} first, a field read.
 * Only when the field is `null` and {@see canResolveProxyBacking()} returns `true`
 * is {@see getProxyBacking()} called. A resolved proxy adds one field read to a
 * property or method access.
 *
 * Names the proxy defines itself (its own getters, setters, `on` events, and every
 * `fx` global event) resolve on the proxy. Other names go to the backing when it
 * can serve them, including through the backing's behaviors. The proxy's own
 * behaviors are consulted last. `dy` and `fx` names are never forwarded; they
 * belong to the behavior and global-event systems of the object they are
 * called on.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TComponentProxyTrait
{
	/**
	 * @var ?TComponent the resolved backing component, shared by every proxy class
	 *   through this trait.
	 */
	private ?TComponent $_proxyBacking = null;

	/**
	 * @var array<string, array{0: string, 1: \Closure}> map of lowercase event name to
	 *   `[originalName, forwarder]` for each backing event wired by {@see attachProxy()}.
	 *   The forwarder is registered on the backing event and calls the handlers in the
	 *   proxy's own collection `$this->_e[$lname]`.
	 */
	private array $_proxyEventNames = [];

	// ----------------------------------------------------------------- storage

	/**
	 * Returns the backing component reference without lazy resolution.
	 * @return ?TComponent the resolved backing, or null when not yet available
	 */
	protected function getProxyBackingDirect(): ?TComponent
	{
		return $this->_proxyBacking;
	}

	/**
	 * Stores the backing component reference.
	 * @param ?TComponent $value the backing component, or null to clear
	 */
	protected function setProxyBackingDirect(?TComponent $value): void
	{
		$this->_proxyBacking = $value;
	}

	/**
	 * Clears the backing component reference so the next use re-resolves it.
	 */
	protected function clearProxyBacking(): void
	{
		$this->setProxyBackingDirect(null);
	}

	// ----------------------------------------------------------------- abstract interface

	/**
	 * Returns the backing {@see TComponent}, resolving it lazily when needed.
	 * @throws \Prado\Exceptions\TConfigurationException when the backing is
	 *   required but not configured or cannot be found
	 * @return ?TComponent the backing component, or null when unavailable
	 */
	abstract public function getProxyBacking(): ?TComponent;

	/**
	 * Returns whether the backing can be lazily resolved, such as when a backing
	 * module ID is configured but not yet looked up. The default returns `false`.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return false;
	}

	// ----------------------------------------------------------------- private helpers

	/**
	 * Returns the backing component, resolving it lazily when possible.
	 * @return ?TComponent the backing, or null when none is available
	 */
	private function resolveProxyBacking(): ?TComponent
	{
		$backing = $this->getProxyBackingDirect();
		if ($backing === null && $this->canResolveProxyBacking()) {
			$backing = $this->getProxyBacking();
		}
		return $backing;
	}

	/**
	 * Returns whether the proxy itself defines `$name` as a readable property, an
	 * `on` event, or an `fx` global event. Such names resolve on the proxy.
	 * @param string $name the property or event name
	 * @return bool whether the proxy owns the name for reading
	 */
	private function isProxyReadableName(string $name): bool
	{
		return $this->hasProxyPublicMethod('get' . $name)
			|| $this->hasProxyPublicMethod('getjs' . $name)
			|| (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name))
			|| strncasecmp($name, 'fx', 2) === 0;
	}

	/**
	 * Returns whether the proxy itself defines `$name` as a writable or read-only
	 * property, an `on` event, or an `fx` global event. A read-only proxy property
	 * is owned so the proxy's constraint is kept.
	 * @param string $name the property or event name
	 * @return bool whether the proxy owns the name for writing
	 */
	private function isProxyWritableName(string $name): bool
	{
		return $this->hasProxyPublicMethod('set' . $name)
			|| $this->hasProxyPublicMethod('setjs' . $name)
			|| $this->isProxyReadableName($name);
	}

	/**
	 * Returns whether the proxy class declares a public method of the name.
	 * @param string $method the method name
	 * @return bool whether the proxy has the public method
	 */
	private function hasProxyPublicMethod(string $method): bool
	{
		return TComponentReflection::getReflectionMethodByType($this::class, $method)?->isPublic() ?? false;
	}

	/**
	 * Returns the lowercase key of a forwarded backing event, or null when `$name`
	 * is not an event wired by {@see attachProxy()}.
	 * @param string $name the event name
	 * @return ?string the lowercase event key, or null
	 */
	private function getProxyEventKey(string $name): ?string
	{
		if (strncasecmp($name, 'on', 2) !== 0) {
			return null;
		}
		$lname = strtolower($name);
		return isset($this->_proxyEventNames[$lname]) ? $lname : null;
	}

	// ----------------------------------------------------------------- event sharing

	/**
	 * Wires every public `on` event of the backing component to this proxy.
	 *
	 * The events are discovered with {@see TComponentReflection::getEvents()} on
	 * the backing class and on each enabled behavior attached to the backing; a
	 * candidate is kept when the backing's {@see TComponent::hasEvent} confirms it.
	 * For each event the proxy keeps its own {@see TWeakCallableCollection} in
	 * `$this->_e[$lname]`, and a forwarder closure registered on the backing event
	 * calls those handlers, in priority order, with the backing as `$sender`. A
	 * stopped {@see IEventStoppableParameter} ends the forwarded handler loop.
	 *
	 * The proxy collection persists across backing swaps: {@see detachProxy()}
	 * removes the forwarder and keeps the collection, and the next attachment
	 * registers a new forwarder for the same collection. When the proxy class
	 * defines the same event, the one collection serves both a backing raise
	 * (`$sender` is the backing) and a proxy raise (`$sender` is the proxy).
	 *
	 * The backing must already be resolved. The ID-based proxies call this method
	 * on first resolution; {@see TComponentProxy} leaves the call to the developer.
	 */
	public function attachProxy(): void
	{
		$this->detachProxy();
		$backing = $this->getProxyBackingDirect();
		if ($backing === null) {
			return;
		}
		$candidates = $this->getPublicEventNames($backing);
		foreach ($backing->getBehaviors() as $behavior) {
			if ($behavior->getEnabled()) {
				$candidates += $this->getPublicEventNames($behavior);
			}
		}
		foreach (array_keys($candidates) as $name) {
			if (!$backing->hasEvent($name)) {
				continue;
			}
			$lname = strtolower($name);
			if (!isset($this->_e[$lname])) {
				$this->_e[$lname] = new TWeakCallableCollection();
			}
			$forwarder = $this->createProxyEventForwarder($this->_e[$lname]);
			$backing->attachEventHandler($name, $forwarder);
			$this->_proxyEventNames[$lname] = [$name, $forwarder];
		}
	}

	/**
	 * Returns the public `on` events of an object's class, keyed by event name.
	 * @param object $object the backing component or one of its behaviors
	 * @return array<string, true> the public event names as keys
	 */
	private function getPublicEventNames(object $object): array
	{
		$names = [];
		foreach ((new TComponentReflection($object))->getEvents() as $name => $info) {
			if (!$info['protected']) {
				$names[$name] = true;
			}
		}
		return $names;
	}

	/**
	 * Creates the closure that a backing event calls to run the proxy's handlers.
	 * The closure captures the collection instead of the proxy so the backing holds
	 * no reference to the proxy.
	 * @param TWeakCallableCollection $handlers the proxy's handler collection
	 * @return \Closure the forwarder to register on the backing event
	 */
	private function createProxyEventForwarder(TWeakCallableCollection $handlers): \Closure
	{
		return static function ($sender, $param) use ($handlers): void {
			foreach ($handlers as $handler) {
				if ($param instanceof IEventStoppableParameter && $param->getStopped()) {
					break;
				}
				$handler($sender, $param);
			}
		};
	}

	/**
	 * Removes every forwarder that {@see attachProxy()} registered on the backing.
	 * The proxy's own handler collections are kept, so handlers registered on the
	 * proxy survive a backing swap.
	 */
	public function detachProxy(): void
	{
		$backing = $this->getProxyBackingDirect();
		if ($backing !== null) {
			foreach ($this->_proxyEventNames as [$name, $forwarder]) {
				if ($backing->hasEvent($name)) {
					$backing->detachEventHandler($name, $forwarder);
				}
			}
		}
		$this->_proxyEventNames = [];
	}

	/**
	 * Returns whether an event is defined on this proxy, either by the class or by
	 * a backing event wired through {@see attachProxy()}.
	 * @param string $name the event name
	 * @return bool whether the event is defined
	 */
	public function hasEvent($name): bool
	{
		return parent::hasEvent($name) || $this->getProxyEventKey($name) !== null;
	}

	/**
	 * Returns the handler collection of an event. A backing event wired through
	 * {@see attachProxy()} returns the proxy's own collection; every other event
	 * goes to {@see TComponent::getEventHandlers}.
	 * @param mixed $name the event name
	 * @throws \Prado\Exceptions\TInvalidOperationException if the event is undefined
	 * @return TWeakCallableCollection the attached handlers
	 */
	public function getEventHandlers($name)
	{
		if (($lname = $this->getProxyEventKey($name)) !== null) {
			return $this->_e[$lname];
		}
		return parent::getEventHandlers($name);
	}

	// ----------------------------------------------------------------- isa override

	/**
	 * Returns whether the proxy, one of its behaviors, or the backing component is
	 * an instance of `$class`. The backing is resolved lazily when possible.
	 * @param mixed|string $class class name or object to test against
	 * @return bool whether this proxy or its backing is an instance of `$class`
	 */
	public function isa($class)
	{
		if (parent::isa($class)) {
			return true;
		}
		$backing = $this->resolveProxyBacking();
		return $backing !== null && $backing->isa($class);
	}

	// ----------------------------------------------------------------- dispatch

	/**
	 * Forwards a method call to the backing component when the backing, or one of
	 * its behaviors, exposes the method. `dy` and `fx` names are not forwarded.
	 * Other calls fall through to {@see TComponent::__call()}, which handles the
	 * proxy's behaviors and throws {@see \Prado\Exceptions\TUnknownMethodException}
	 * for an undefined method.
	 * @param string $method the method name
	 * @param array $args the method arguments
	 * @return mixed the return value of the call
	 */
	public function __call($method, $args)
	{
		if (strncasecmp($method, 'dy', 2) !== 0 && strncasecmp($method, 'fx', 2) !== 0) {
			$backing = $this->resolveProxyBacking();
			if ($backing !== null && $backing->hasMethod($method)) {
				return $backing->$method(...$args);
			}
		}
		return parent::__call($method, $args);
	}

	/**
	 * Returns a property value or event handler collection. A name the proxy owns
	 * resolves on the proxy; a forwarded backing event returns the proxy's
	 * collection; a property the backing can read is read from the backing; the
	 * proxy's behaviors are consulted last.
	 * @param string $name the property or event name
	 * @throws \Prado\Exceptions\TInvalidOperationException when the name is
	 *   defined by neither the proxy, the backing, nor a behavior
	 * @return mixed the property value or the event handler collection
	 */
	public function __get($name)
	{
		if ($this->isProxyReadableName($name)) {
			return parent::__get($name);
		}
		if (($lname = $this->getProxyEventKey($name)) !== null) {
			return $this->_e[$lname];
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && $backing->canGetProperty($name)) {
			return $backing->$name;
		}
		return parent::__get($name);
	}

	/**
	 * Sets a property value or attaches an event handler. A name the proxy owns
	 * resolves on the proxy, so a read-only proxy property throws; a forwarded
	 * backing event attaches the handler to the proxy's collection; a property the
	 * backing can write is written on the backing; the proxy's behaviors are
	 * consulted last.
	 * @param string $name the property or event name
	 * @param mixed $value the property value or event handler
	 * @throws \Prado\Exceptions\TInvalidOperationException when the property is
	 *   read-only on the proxy, or defined by neither the proxy, the backing,
	 *   nor a behavior
	 */
	public function __set($name, $value)
	{
		if ($this->isProxyWritableName($name)) {
			parent::__set($name, $value);
			return;
		}
		if ($this->getProxyEventKey($name) !== null) {
			$this->attachEventHandler($name, $value);
			return;
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && $backing->canSetProperty($name)) {
			$backing->$name = $value;
			return;
		}
		parent::__set($name, $value);
	}

	/**
	 * Returns whether a property is set or an event has handlers. A forwarded
	 * backing event is set when the proxy's collection has a handler; a property
	 * the backing can read is set when its value is not null.
	 * @param string $name the property or event name
	 * @return bool whether the property is set
	 */
	public function __isset($name)
	{
		if ($this->isProxyReadableName($name)) {
			return parent::__isset($name);
		}
		if (($lname = $this->getProxyEventKey($name)) !== null) {
			return $this->_e[$lname]->getCount() > 0;
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && $backing->canGetProperty($name)) {
			return isset($backing->$name);
		}
		return parent::__isset($name);
	}

	/**
	 * Sets a property to null or clears an event. A forwarded backing event clears
	 * the proxy's collection; a property the backing can write is unset on the
	 * backing. A read-only proxy property is not forwarded.
	 * @param string $name the property or event name
	 * @throws \Prado\Exceptions\TInvalidOperationException when the property is
	 *   read-only on the proxy
	 */
	public function __unset($name)
	{
		if ($this->isProxyWritableName($name)) {
			parent::__unset($name);
			return;
		}
		if (($lname = $this->getProxyEventKey($name)) !== null) {
			$this->_e[$lname]->clear();
			return;
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && $backing->canSetProperty($name)) {
			unset($backing->$name);
			return;
		}
		parent::__unset($name);
	}

	// -------------------------------------------------- cloning / serialization

	/**
	 * Drops the clone's backing reference and forwarder list so the clone
	 * re-resolves and re-attaches on first use, then re-attaches the behaviors
	 * through {@see TComponent::__clone()}. The forwarders stay registered on the
	 * backing for the original proxy.
	 */
	public function __clone()
	{
		$this->_proxyEventNames = [];
		$this->clearProxyBacking();
		parent::__clone();
	}

	/**
	 * Appends the `_proxyEventNames` field to the serialization exclusion list.
	 * Every class using the trait calls this from its `_getZappableSleepProps()`.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _addProxyEventNamesZappable(array &$exprops): void
	{
		$exprops[] = "\0" . __CLASS__ . "\0_proxyEventNames";
	}

	/**
	 * Appends the `_proxyBacking` field to the serialization exclusion list.
	 * A proxy that re-resolves its backing from the application module registry
	 * calls this from its `_getZappableSleepProps()`; {@see TComponentProxy}
	 * keeps its backing because it has no registry to re-resolve from.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _addProxyBackingZappable(array &$exprops): void
	{
		$exprops[] = "\0" . __CLASS__ . "\0_proxyBacking";
	}
}
