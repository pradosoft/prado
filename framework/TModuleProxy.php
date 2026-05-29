<?php

/**
 * TModuleProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TConfigurationException;
use Prado\Util\TLogger;

/**
 * TModuleProxy class.
 *
 * TModuleProxy is a transparent proxy that delegates every property access,
 * method call, and event operation to another {@see TModule} registered with
 * the application as a module. This lets a single logical "component slot"
 * (e.g. a service or manager identified by a fixed module ID) be hot-swapped
 * at configuration time without changing the consumers that depend on it.
 *
 * TModuleProxy extends {@see TModule} directly and uses {@see TComponentProxyTrait},
 * adding:
 * - A {@see getBackingComponentId BackingComponentId} property that resolves
 *   the backing from the application module registry on first use.
 * - {@see \Prado\IModuleDependency} support so the framework initializes the
 *   backing module before this proxy.
 *
 * ## Configuration
 *
 * Set {@see getBackingComponentId BackingComponentId} to the module ID of the
 * target component. TModuleProxy declares that module as a required dependency
 * so the framework initializes it first, guaranteeing the backing is available
 * on first access.
 *
 * ## Transparency
 *
 * All property reads, property writes, method calls, `isset` checks, and `unset`
 * operations are forwarded to the backing component. `dy`- and `fx`-prefixed
 * names are never forwarded — those belong exclusively to the behavior and
 * global-event system.
 *
 * ## Event sharing
 *
 * {@see attachProxy()} reflects all public `on[A-Z]*` methods on the backing
 * component — including those contributed by its behaviors — and shares their
 * {@see \Prado\Collections\TWeakCallableCollection} handler lists with this
 * proxy.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="myService"
 *         class="Prado\TModuleProxy"
 *         BackingComponentId="myRealService" />
 *
 * <module id="myRealService"
 *         class="MyApp\MyService" />
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TModuleProxy();
 * $proxy->setBackingComponentId('myRealService');
 * $proxy->init(null);
 * // All operations now delegate to the 'myRealService' module.
 * ```
 *
 * To proxy any non-module component use {@see TComponentProxy} instead.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TModuleProxy extends TModule implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string Module ID of the backing component; empty until configured. */
	private string $_backingComponentId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares a required dependency on the backing component module so that
	 * {@see \Prado\TApplication} initializes it before this proxy.
	 *
	 * @param bool $isPreInit `true` when collecting for the dyPreInit pass,
	 *   `false` when collecting for the init() pass (default).
	 *   TModuleProxy requires its backing in all phases, so `$isPreInit` is not used.
	 * @return ?array<int, array{id: ?string, required: bool}> dependency list,
	 *   or null when no {@see getBackingComponentId BackingComponentId} has been set yet
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?array
	{
		$id = $this->getBackingComponentId();
		if ($id === '') {
			return null;
		}
		return [['id' => $id, 'required' => true]];
	}

	/**
	 * Initializes the proxy module. Throws when no
	 * {@see getBackingComponentId BackingComponentId} has been configured.
	 *
	 * @param ?\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingComponentId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingComponentId() === '') {
			throw new TConfigurationException('componentproxy_backing_component_id_required');
		}
		parent::init($config);
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns `true` when a {@see getBackingComponentId BackingComponentId} has
	 * been configured, enabling lazy resolution of the backing from the
	 * application module registry.
	 *
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->_backingComponentId !== '';
	}

	/**
	 * Returns the resolved backing component, lazily resolving it from the
	 * application module registry on first call.
	 *
	 * @throws TConfigurationException when {@see getBackingComponentId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @return ?TComponent the resolved backing component
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
	 * Returns the resolved backing component, lazily resolving it from the
	 * application module registry on first call.
	 *
	 * @throws TConfigurationException when {@see getBackingComponentId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @return TComponent the resolved backing component
	 */
	public function getBackingComponent(): TComponent
	{
		$backing = $this->getBackingComponentDirect();
		if ($backing === null) {
			$id = $this->_backingComponentId;
			if ($id === '') {
				throw new TConfigurationException('componentproxy_backing_component_id_required');
			}
			$backing = $this->getApplication()->getModule($id);
			if ($backing === null) {
				throw new TConfigurationException('componentproxy_component_not_found', $id);
			}
			$this->setBackingComponentDirect($backing);
			$this->attachProxy();
			$backing = $this->getBackingComponentDirect();
		}
		return $backing;
	}

	/**
	 * @return string the module ID of the backing component
	 */
	protected function getBackingComponentIdDirect(): string
	{
		return $this->_backingComponentId;
	}

	/**
	 * @param string $value the module ID to store directly
	 */
	protected function setBackingComponentIdDirect(string $value): void
	{
		$this->_backingComponentId = $value;
	}

	/**
	 * @return string the module ID of the backing component
	 */
	public function getBackingComponentId(): string
	{
		return $this->getBackingComponentIdDirect();
	}

	/**
	 * Sets the module ID of the backing component. When a non-empty ID was
	 * already set and the new value differs, the change is logged at
	 * {@see \Prado\Util\TLogger::WARNING} level and the resolved component
	 * reference is invalidated so the next operation re-resolves the module.
	 *
	 * @param string $value the module ID of the component to proxy
	 */
	public function setBackingComponentId(string $value): void
	{
		$value = TPropertyValue::ensureString($value);
		$current = $this->getBackingComponentIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf(
					"TModuleProxy.BackingComponentId changed from '%s' to '%s'.",
					$current,
					$value
				),
				TLogger::WARNING,
				'prado.component'
			);
		}
		$this->setBackingComponentIdDirect($value);
		$this->setBackingComponentDirect(null);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes transient and default-valued fields from serialization. The
	 * resolved backing component reference is excluded because it is re-resolved
	 * from the application module registry after deserialization. The
	 * `_proxyEventNames` list is always transient.
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		// The backing component is lazily re-resolved from the module registry
		// after deserialization; exclude it so it re-resolves cleanly.
		$this->_addProxyBackingZappable($exprops);
		if ($this->_backingComponentId === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingComponentId";
		}
	}
}
