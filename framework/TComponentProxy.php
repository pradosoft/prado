<?php

/**
 * TComponentProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TConfigurationException;
use Prado\Util\TLogger;

/**
 * TComponentProxy class.
 *
 * TComponentProxy is a transparent proxy that delegates every property access,
 * method call, and event operation to any {@see TComponent} injected directly
 * via {@see setBackingComponent BackingComponent}. This lets a single logical
 * "component slot" be swapped at runtime without changing the consumers that
 * depend on it.
 *
 * Unlike {@see TModuleProxy}, TComponentProxy does not look up the backing by a
 * module ID and does not extend {@see TModule} — it is a pure {@see TComponent}
 * subclass suitable for lightweight, non-module use.
 *
 * ## Transparency
 *
 * All property reads, property writes, method calls, `isset` checks, and `unset`
 * operations are forwarded to the backing component. The proxy is
 * indistinguishable from the component for all practical purposes. `dy`- and
 * `fx`-prefixed names are never forwarded — those belong exclusively to the
 * behavior and global-event system.
 *
 * ## Event forwarding
 *
 * {@see attachProxy()} reflects all public `on[A-Z]*` methods on the backing
 * component — including those contributed by its behaviors — and registers a
 * forwarder closure on each backing event. When the backing raises the event, the
 * forwarder calls through the proxy's own independent handler list. Handlers
 * registered on the proxy survive a backing swap: {@see detachProxy()} removes
 * the old forwarder, and {@see attachProxy()} on the new backing installs a new
 * one pointing at the same proxy list.
 *
 * ## Usage
 *
 * ```php
 * $proxy = new TComponentProxy();
 * $proxy->setBackingComponent($realComponent);
 * // All operations now delegate to $realComponent.
 * ```
 *
 * To proxy a registered application module use {@see TModuleProxy} instead.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TComponentProxy extends TComponent implements IProxy
{
	use TComponentProxyTrait;

	// --------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing component set via {@see setBackingComponent}.
	 *
	 * @throws TConfigurationException when no backing component has been set
	 * @return ?TComponent the backing component
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getBackingComponent();
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return ?TComponent the backing component reference stored directly, or null
	 */
	protected function getBackingComponentDirect(): ?TComponent
	{
		return $this->getProxyBackingDirect();
	}

	/**
	 * @param ?TComponent $value the backing component to store directly
	 */
	protected function setBackingComponentDirect(?TComponent $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing component. Throws when no backing has been set.
	 *
	 * @throws TConfigurationException when no backing component has been set
	 * @return TComponent the backing component
	 */
	public function getBackingComponent(): TComponent
	{
		$b = $this->getBackingComponentDirect();
		if ($b === null) {
			throw new TConfigurationException('componentproxy_backing_component_required');
		}
		return $b;
	}

	/**
	 * Sets the backing component directly. When the backing is changed (a
	 * non-null backing is replaced), the existing proxy event attachment is
	 * detached and a {@see \Prado\Util\TLogger::WARNING} is logged so that
	 * unexpected runtime swaps are visible in the application log.
	 *
	 * After setting, {@see attachProxy()} must be called if event forwarding with
	 * the new backing is required.
	 *
	 * @param TComponent $value the component to use as the backing
	 */
	public function setBackingComponent(TComponent $value): void
	{
		$current = $this->getBackingComponentDirect();
		if ($current === $value) {
			return;
		}
		if ($current !== null) {
			$this->detachProxy();
			Prado::log(
				sprintf(
					"TComponentProxy.BackingComponent changed from '%s' to '%s'.",
					get_class($current),
					get_class($value)
				),
				TLogger::WARNING,
				'prado.component'
			);
		}
		$this->setBackingComponentDirect($value);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the `_proxyBacking` field from serialization. Delegates to
	 * {@see TComponentProxyTrait::_addProxyBackingZappable()}. Provided for
	 * subclasses whose backing is lazily resolved and must not be carried
	 * across process boundaries.
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	final protected function _zappableExcludeBackingComponent(array &$exprops): void
	{
		$this->_addProxyBackingZappable($exprops);
	}

	/**
	 * Excludes `_proxyEventNames` from serialization (always transient).
	 * The `_proxyBacking` reference is preserved across serialization for
	 * TComponentProxy because there is no module registry to re-resolve it from.
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		// _proxyBacking is intentionally preserved: no module registry to re-resolve from.
	}
}
