<?php

/**
 * TComponentProxyTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TComponentProxyTrait trait
 *
 * TComponentProxyTrait provides the shared transparent-proxy logic used by every
 * {@see IProxy} implementation in the framework. It supplies event-sharing
 * ({@see attachProxy}/{@see detachProxy}), property and method dispatch
 * ({@see __call()}, {@see __get()}, {@see __set()}, {@see __isset()},
 * {@see __unset()}), type-transparency ({@see isa()}), and serialization helpers.
 *
 * ## Required abstract interface
 *
 * Each class using this trait must implement exactly one method:
 *
 * | Method | Purpose |
 * |--------|---------|
 * | `getProxyBacking(): ?TComponent` | Returns the backing component, performing lazy resolution when needed. May return `null` or throw {@see \Prado\Exceptions\TConfigurationException} when the backing is not configured or not found. |
 *
 * Optionally override:
 *
 * | Method | Purpose |
 * |--------|---------|
 * | `canResolveProxyBacking(): bool` | Returns `true` when the backing can be lazily resolved (e.g., a module ID is configured). The default returns `false`. Override in classes that support lazy resolution from the application module registry. |
 *
 * ## Backing storage
 *
 * The trait owns `$_proxyBacking` — a `?TComponent` field shared by all proxy
 * classes. Domain-specific proxies (e.g. {@see \Prado\Caching\TCacheProxy}) wrap
 * {@see getProxyBackingDirect()} and {@see setProxyBackingDirect()} in typed
 * accessors that narrow the return type via `instanceof`.
 *
 * ## Architecture note
 *
 * All dispatch methods first call {@see getProxyBackingDirect()} — a zero-cost
 * field read. Only when the result is `null` AND {@see canResolveProxyBacking()}
 * returns `true` is the potentially expensive {@see getProxyBacking()} called.
 * This ensures that hot-path property/method access on a fully resolved proxy
 * incurs only one field read of overhead.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TComponentProxyTrait
{
	/**
	 * @var ?TComponent The lazily resolved backing component. Owned by this trait
	 *   so that all proxy classes share a single field rather than each declaring
	 *   their own private backing reference.
	 */
	private ?TComponent $_proxyBacking = null;

	/**
	 * @var array<string, array{0: string, 1: \Closure}> Map of lowercase event name to
	 *   a two-element tuple `[originalName, forwarderClosure]` for each event shared
	 *   with the backing component. Only events that the proxy class does not itself own
	 *   are stored here. The proxy owns an independent
	 *   {@see \Prado\Collections\TWeakCallableCollection} for each such event (held in
	 *   `$this->_e[$lname]`); the forwarder closure is registered on the backing's
	 *   event and, when the backing raises the event, iterates the proxy's own list.
	 *   This lets handler registrations on the proxy survive a backing swap: on swap,
	 *   the old forwarder is detached from the old backing and a new forwarder pointing
	 *   to the same proxy collection is attached to the new backing.
	 */
	private array $_proxyEventNames = [];

	// ----------------------------------------------------------------- storage

	/**
	 * Returns the cached backing component reference without triggering lazy
	 * resolution. Returns `null` when the backing has not yet been resolved or
	 * was cleared (e.g. after {@see __clone()}).
	 *
	 * @return ?TComponent the resolved backing, or null when not yet available
	 */
	protected function getProxyBackingDirect(): ?TComponent
	{
		return $this->_proxyBacking;
	}

	/**
	 * Stores the resolved backing component reference directly.
	 *
	 * @param ?TComponent $value the backing component to store, or null to clear
	 */
	protected function setProxyBackingDirect(?TComponent $value): void
	{
		$this->_proxyBacking = $value;
	}

	/**
	 * Clears the cached backing component reference. Called by {@see __clone()}
	 * so that the clone re-resolves its backing on first use.
	 */
	protected function clearProxyBacking(): void
	{
		$this->_proxyBacking = null;
	}

	// ----------------------------------------------------------------- abstract interface

	/**
	 * Returns the resolved backing {@see TComponent}, performing lazy resolution
	 * when the backing has not yet been resolved. Implementations may return
	 * `null` when no backing is available, or throw
	 * {@see \Prado\Exceptions\TConfigurationException} when the backing is
	 * required but missing or misconfigured.
	 *
	 * @throws \Prado\Exceptions\TConfigurationException when the backing is
	 *   required but not configured or cannot be found
	 * @return ?TComponent the resolved backing component, or null when unavailable
	 */
	abstract public function getProxyBacking(): ?TComponent;

	/**
	 * Returns `true` when the backing can be lazily resolved — for example, when
	 * a backing module ID has been configured but the module has not yet been
	 * looked up. The default implementation returns `false`; override in classes
	 * that support lazy resolution from the application module registry.
	 *
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return false;
	}

	// ----------------------------------------------------------------- private helpers

	/**
	 * Returns the backing component, performing lazy resolution when needed.
	 * Returns `null` when no backing is available and lazy resolution is either
	 * not supported or not yet possible. Does not throw.
	 *
	 * @return ?TComponent the resolved backing, or null
	 */
	private function resolveProxyBacking(): ?TComponent
	{
		$backing = $this->getProxyBackingDirect();
		if ($backing === null && $this->canResolveProxyBacking()) {
			$backing = $this->getProxyBacking();
		}
		return $backing;
	}

	// ----------------------------------------------------------------- event sharing

	/**
	 * Reflects all public `on[A-Z]*` methods on the backing component — including
	 * those contributed by behaviors attached to it — and wires a forwarder
	 * closure onto each backing event so that when the backing raises the event,
	 * the forwarder iterates the proxy's **own** independent handler list. Any
	 * previous attachment is detached first.
	 *
	 * ## Event isolation
	 *
	 * The proxy maintains its own {@see \Prado\Collections\TWeakCallableCollection}
	 * for each proxied event (stored in `$this->_e[$lname]`). A static forwarder
	 * closure — registered on the backing via
	 * {@see \Prado\TComponent::attachEventHandler} — holds a reference to that
	 * collection and calls through it when the backing fires. This means:
	 *
	 * - Handlers registered on the proxy are in the **proxy's** list, completely
	 *   independent of the backing's list.
	 * - If the backing is swapped, {@see detachProxy()} removes the old forwarder,
	 *   the proxy's list is preserved, and re-attaching injects a new forwarder
	 *   onto the new backing — existing handler registrations survive seamlessly.
	 * - Two distinct proxy instances sharing the same backing class each have
	 *   entirely separate handler lists.
	 *
	 * When the proxy class itself also owns an event that the backing exposes, the
	 * forwarder is still registered. The `$sender` argument differentiates the two
	 * origins: when the backing raises the event the forwarder passes the backing as
	 * `$sender`; when the proxy raises the event directly it passes itself as
	 * `$sender`. The same handler list in `$this->_e[$lname]` serves both paths.
	 *
	 * Discovery is a two-pass scan via {@see \Prado\TComponentReflection::getEvents()}:
	 * first the backing class itself, then every enabled behavior attached to it.
	 * Each candidate is accepted only when {@see \Prado\TComponent::hasEvent}
	 * confirms the backing exposes it. Event-name comparison is case-insensitive
	 * throughout.
	 *
	 * The backing must already be resolved (i.e. {@see getProxyBackingDirect()}
	 * must return non-null) before invoking this method. It is called
	 * automatically by {@see getProxyBacking()} on first lazy resolution.
	 */
	public function attachProxy(): void
	{
		$this->detachProxy();
		$backing = $this->getProxyBackingDirect();
		if ($backing === null) {
			return;
		}
		$candidates = [];
		foreach (array_keys((new TComponentReflection($backing))->getEvents()) as $name) {
			$candidates[$name] = true;
		}
		foreach ($backing->getBehaviors() as $behavior) {
			if (!$behavior->getEnabled()) {
				continue;
			}
			foreach (array_keys((new TComponentReflection($behavior))->getEvents()) as $name) {
				$candidates[$name] = true;
			}
		}
		foreach ($candidates as $name => $_) {
			if (!$backing->hasEvent($name)) {
				continue;
			}
			$lname = strtolower($name);
			// Ensure the proxy has a persistent handler collection for this event.
			// Reuse an existing collection across backing swaps so that any handlers
			// registered while no backing was attached survive the re-attach.
			if (!isset($this->_e[$lname])) {
				$this->_e[$lname] = new \Prado\Collections\TWeakCallableCollection();
			}
			// Capture the proxy's collection object (not $this) to avoid a
			// circular reference between the closure and _proxyEventNames.
			$proxyList = $this->_e[$lname];
			// Forwarder: registered on the backing; when the backing raises this
			// event, calls through the proxy's own independent handler list.
			$forwarder = static function ($sender, $param) use ($proxyList): void {
				foreach ($proxyList as $handler) {
					call_user_func($handler, $sender, $param);
				}
			};
			$backing->attachEventHandler($name, $forwarder);
			$this->_proxyEventNames[$lname] = [$name, $forwarder];
		}
	}

	/**
	 * Detaches all forwarder closures that {@see attachProxy()} registered on the
	 * backing component, severing the event forwarding path. The proxy's own
	 * handler collections (`$this->_e[$lname]`) are intentionally preserved so
	 * that existing handler registrations survive a backing swap.
	 */
	public function detachProxy(): void
	{
		$backing = $this->getProxyBackingDirect();
		foreach ($this->_proxyEventNames as $lname => [$name, $forwarder]) {
			if ($backing !== null && $backing->hasEvent($name)) {
				$backing->detachEventHandler($name, $forwarder);
			}
		}
		$this->_proxyEventNames = [];
	}

	/**
	 * Determines whether an event is defined on this proxy.
	 *
	 * Extends the parent check to also return `true` for event names that were
	 * injected by {@see attachProxy()} from backing-component behaviors — these
	 * events are stored in `$this->_proxyEventNames` but have no corresponding
	 * method on the proxy class itself, so the parent `method_exists` check alone
	 * would miss them.
	 *
	 * @param string $name the event name
	 * @return bool whether the event is defined
	 */
	public function hasEvent($name): bool
	{
		if (parent::hasEvent($name)) {
			return true;
		}
		return isset($this->_proxyEventNames[strtolower($name)]);
	}

	/**
	 * Returns the handler list for a proxy-injected event, or delegates to the
	 * parent implementation for events defined directly on this class.
	 *
	 * {@see \Prado\TComponent::getEventHandlers} gates `on*` events through
	 * `method_exists`, which misses events injected by {@see attachProxy()} from
	 * backing-component behaviors. This override intercepts those names and
	 * returns the already-shared handler list directly.
	 *
	 * @param mixed $name the event name
	 * @throws \Prado\Exceptions\TInvalidOperationException if the event is undefined
	 * @return \Prado\Collections\TWeakCallableCollection list of attached handlers
	 */
	public function getEventHandlers($name)
	{
		$lname = strtolower($name);
		if (isset($this->_proxyEventNames[$lname])) {
			return $this->_e[$lname];
		}
		return parent::getEventHandlers($name);
	}

	// ----------------------------------------------------------------- isa override

	/**
	 * Extends the parent {@see \Prado\TComponent::isa()} check to also return
	 * `true` when the resolved backing component is an instance of `$class`.
	 *
	 * When the backing has not yet been resolved and lazy resolution is possible
	 * ({@see canResolveProxyBacking()} returns `true`), this method resolves it
	 * via {@see getProxyBacking()} — consistent with how the dispatch methods
	 * resolve the backing on demand.
	 *
	 * @param mixed|string $class class name or object to test against
	 * @return bool `true` when this proxy or its backing component is an instance of `$class`
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
	 * Forwards unrecognized method calls to the backing component.
	 *
	 * `dy`-prefixed names are never forwarded — those belong exclusively to
	 * the behavior-dispatch system. For all other names the backing component
	 * is consulted first; if it does not expose the method, the call falls
	 * through to {@see \Prado\TComponent::__call()}, which handles JS-property
	 * variants, behaviors, and raises
	 * {@see \Prado\Exceptions\TUnknownMethodException} for truly undefined
	 * methods.
	 *
	 * @param string $method the method name
	 * @param array $args the method arguments
	 * @return mixed the return value of the forwarded call
	 */
	public function __call($method, $args)
	{
		$prefix = substr($method, 0, 2);
		if ($prefix !== 'dy') {
			$backing = $this->resolveProxyBacking();
			if ($backing !== null && Prado::method_visible($backing, $method)) {
				return $backing->$method(...$args);
			}
		}
		return parent::__call($method, $args);
	}

	/**
	 * Forwards property-read access to the backing component when the property
	 * is not defined on the proxy itself.
	 *
	 * Properties owned by the proxy (including inherited ones), `on`-events, and
	 * `fx`-events are handled by {@see \Prado\TComponent::__get()} without
	 * consulting the backing component. Behaviors are checked last, after the
	 * backing component, via the parent fallback.
	 *
	 * @param string $name the property name
	 * @throws \Prado\Exceptions\TInvalidOperationException when neither the
	 *   proxy, the backing component, nor any attached behavior defines the
	 *   property
	 * @return mixed the property value
	 */
	public function __get($name)
	{
		if (Prado::method_visible($this, 'get' . $name)
			|| Prado::method_visible($this, 'getjs' . $name)
			|| (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name))
			|| strncasecmp($name, 'fx', 2) === 0
		) {
			return parent::__get($name);
		}
		// Proxy-forwarded events: return the proxy's own handler collection.
		if (strncasecmp($name, 'on', 2) === 0 && isset($this->_proxyEventNames[strtolower($name)])) {
			return $this->_e[strtolower($name)];
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && Prado::method_visible($backing, 'get' . $name)) {
			return $backing->{'get' . $name}();
		}
		return parent::__get($name);
	}

	/**
	 * Forwards property-write access to the backing component when the property
	 * is not defined on the proxy itself.
	 *
	 * Read-only properties defined on the proxy (a getter exists but no setter)
	 * are never forwarded — the proxy's constraint is preserved and a
	 * {@see \Prado\Exceptions\TInvalidOperationException} is raised. Behaviors
	 * are checked last, after the backing component, via the parent fallback.
	 *
	 * @param string $name the property name
	 * @param mixed $value the property value
	 * @throws \Prado\Exceptions\TInvalidOperationException when the property is
	 *   read-only on the proxy, or undefined on both proxy and backing component
	 */
	public function __set($name, $value)
	{
		if (Prado::method_visible($this, 'set' . $name)
			|| Prado::method_visible($this, 'setjs' . $name)
			|| Prado::method_visible($this, 'get' . $name)
			|| Prado::method_visible($this, 'getjs' . $name)
			|| (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name))
			|| strncasecmp($name, 'fx', 2) === 0
		) {
			return parent::__set($name, $value);
		}
		// Handler lists shared via attachProxy() — attachEventHandler uses our getEventHandlers override.
		if (strncasecmp($name, 'on', 2) === 0 && isset($this->_proxyEventNames[strtolower($name)])) {
			return $this->attachEventHandler($name, $value);
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && Prado::method_visible($backing, 'set' . $name)) {
			return $backing->{'set' . $name}($value);
		}
		return parent::__set($name, $value);
	}

	/**
	 * Forwards `isset()` checks to the backing component when the property is
	 * not defined on the proxy itself. Returns `true` when the backing getter
	 * returns a non-null value.
	 *
	 * @param string $name the property name
	 * @return bool whether the property is considered set
	 */
	public function __isset($name)
	{
		if (Prado::method_visible($this, 'get' . $name)
			|| Prado::method_visible($this, 'getjs' . $name)
			|| (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name))
			|| strncasecmp($name, 'fx', 2) === 0
		) {
			return parent::__isset($name);
		}
		// Proxy-forwarded events: isset means at least one handler in the proxy's list.
		if (strncasecmp($name, 'on', 2) === 0) {
			$lname = strtolower($name);
			if (isset($this->_proxyEventNames[$lname])) {
				return $this->_e[$lname]->getCount() > 0;
			}
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && Prado::method_visible($backing, 'get' . $name)) {
			return $backing->{'get' . $name}() !== null;
		}
		return parent::__isset($name);
	}

	/**
	 * Forwards `unset()` to the backing component (by calling the setter with
	 * `null`) when the property is not defined on the proxy itself.
	 *
	 * Read-only properties defined on the proxy are not forwarded.
	 *
	 * @param string $name the property name
	 * @throws \Prado\Exceptions\TInvalidOperationException when the property is
	 *   read-only on the proxy
	 */
	public function __unset($name)
	{
		if (Prado::method_visible($this, 'set' . $name)
			|| Prado::method_visible($this, 'setjs' . $name)
			|| Prado::method_visible($this, 'get' . $name)
			|| Prado::method_visible($this, 'getjs' . $name)
			|| (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name))
			|| strncasecmp($name, 'fx', 2) === 0
		) {
			parent::__unset($name);
			return;
		}
		// Proxy-forwarded events: clear the proxy's own handler collection.
		if (strncasecmp($name, 'on', 2) === 0) {
			$lname = strtolower($name);
			if (isset($this->_proxyEventNames[$lname])) {
				$this->_e[$lname]->clear();
				return;
			}
		}
		$backing = $this->resolveProxyBacking();
		if ($backing !== null && Prado::method_visible($backing, 'set' . $name)) {
			$backing->{'set' . $name}(null);
			return;
		}
		parent::__unset($name);
	}

	// -------------------------------------------------- cloning / serialization

	/**
	 * Clears the proxy attachment and the lazily resolved backing reference so
	 * that the clone re-resolves its backing on first use, then delegates to
	 * {@see \Prado\TComponent::__clone()} to re-attach behaviors.
	 */
	public function __clone()
	{
		$this->detachProxy();
		$this->clearProxyBacking();
		parent::__clone();
	}

	/**
	 * Appends the `_proxyEventNames` field to the serialization exclusion list.
	 * Call this from {@see _getZappableSleepProps()} in each class that uses the
	 * trait:
	 *
	 * ```php
	 * protected function _getZappableSleepProps(&$exprops)
	 * {
	 *     parent::_getZappableSleepProps($exprops);
	 *     $this->_addProxyEventNamesZappable($exprops);
	 *     // ... class-specific exclusions ...
	 * }
	 * ```
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _addProxyEventNamesZappable(array &$exprops): void
	{
		$exprops[] = "\0" . __CLASS__ . "\0_proxyEventNames";
	}

	/**
	 * Appends the `_proxyBacking` field to the serialization exclusion list.
	 * Call this from {@see _getZappableSleepProps()} in proxy classes whose
	 * backing is lazily re-resolved from the application module registry after
	 * deserialization (i.e. all ID-based proxies: {@see \Prado\TModuleProxy},
	 * {@see \Prado\Caching\TCacheProxy},
	 * {@see \Prado\Data\TDataSourceConfigProxy}).
	 *
	 * Do **not** call this in {@see \Prado\TComponentProxy}: its backing is
	 * injected directly and has no module-registry source to re-resolve from.
	 *
	 * ```php
	 * protected function _getZappableSleepProps(&$exprops)
	 * {
	 *     parent::_getZappableSleepProps($exprops);
	 *     $this->_addProxyEventNamesZappable($exprops);
	 *     $this->_addProxyBackingZappable($exprops);
	 *     // ... class-specific exclusions ...
	 * }
	 * ```
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _addProxyBackingZappable(array &$exprops): void
	{
		$exprops[] = "\0" . __CLASS__ . "\0_proxyBacking";
	}
}
