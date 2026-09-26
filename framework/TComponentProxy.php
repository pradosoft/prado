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
use Prado\Util\Log\TLogger;

/**
 * TComponentProxy class.
 *
 * TComponentProxy is a transparent proxy that delegates property access, method
 * calls, and event operations to a {@see TComponent} injected through
 * {@see setBackingComponent BackingComponent}. One logical component slot can
 * be swapped at runtime without changing the consumers that depend on it.
 *
 * TComponentProxy is a plain {@see TComponent}; it has no module ID and no
 * registry to resolve a backing from. {@see TModuleProxy} proxies a registered
 * application module by ID.
 *
 * ## Transparency
 *
 * Property reads and writes, method calls, `isset`, and `unset` go to the
 * backing when the proxy does not define the name itself. `dy` and `fx` names
 * are never forwarded. {@see TComponentProxyTrait} documents the dispatch order.
 *
 * ## Event forwarding
 *
 * {@see attachProxy()} wires every public `on` event of the backing, including
 * events contributed by its behaviors, to a handler collection the proxy owns.
 * Handlers registered on the proxy survive a backing swap: {@see detachProxy()}
 * removes the old forwarders, and {@see attachProxy()} on the new backing
 * registers new forwarders for the same collections. The proxy does not attach
 * on its own; call {@see attachProxy()} after setting the backing when event
 * forwarding is required.
 *
 * ## Usage
 *
 * ```php
 * $proxy = new TComponentProxy();
 * $proxy->setBackingComponent($realComponent);
 * $proxy->attachProxy();
 * // All operations now delegate to $realComponent.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TComponentProxy extends TComponent implements IProxy
{
	use TComponentProxyTrait;

	// --------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing component set through {@see setBackingComponent}.
	 * @throws TConfigurationException when no backing component has been set
	 * @return ?TComponent the backing component
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getBackingComponent();
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return ?TComponent the stored backing component, or null when none is set
	 */
	protected function getBackingComponentDirect(): ?TComponent
	{
		return $this->getProxyBackingDirect();
	}

	/**
	 * @param ?TComponent $value the backing component to store
	 */
	protected function setBackingComponentDirect(?TComponent $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing component.
	 * @throws TConfigurationException when no backing component has been set
	 * @return TComponent the backing component
	 */
	public function getBackingComponent(): TComponent
	{
		$backing = $this->getBackingComponentDirect();
		if ($backing === null) {
			throw new TConfigurationException('componentproxy_backing_component_required');
		}
		return $backing;
	}

	/**
	 * Sets the backing component. Replacing a backing detaches the event
	 * forwarders from the old backing and logs a {@see TLogger::WARNING}, so a
	 * runtime swap is visible in the application log. Call {@see attachProxy()}
	 * afterwards when event forwarding with the new backing is required.
	 * @param TComponent $value the component to proxy
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
				sprintf("TComponentProxy.BackingComponent changed from '%s' to '%s'.", $current::class, $value::class),
				TLogger::WARNING,
				'prado.component'
			);
		}
		$this->setBackingComponentDirect($value);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the backing component from serialization. A subclass whose backing
	 * is resolved lazily calls this from its `_getZappableSleepProps()`.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	final protected function _zappableExcludeBackingComponent(array &$exprops): void
	{
		$this->_addProxyBackingZappable($exprops);
	}

	/**
	 * Excludes the transient forwarder list from serialization. The backing
	 * component is serialized with the proxy because there is no registry to
	 * re-resolve it from.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
	}
}
