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
use Prado\Util\Log\TLogger;

/**
 * TModuleProxy class.
 *
 * TModuleProxy is a transparent proxy module that delegates property access,
 * method calls, and event operations to another module registered with the
 * application. One logical module slot, such as a service or manager with a
 * fixed module ID, can be swapped at configuration time without changing the
 * consumers that depend on it.
 *
 * TModuleProxy extends {@see TModule}, uses {@see TComponentProxyTrait}, and adds:
 * - {@see getBackingComponentId BackingComponentId}, the module ID of the backing,
 *   resolved from the application module registry on first use.
 * - {@see IModuleDependency}, so the application initializes the backing module
 *   before this proxy.
 *
 * ## Transparency
 *
 * Property reads and writes, method calls, `isset`, and `unset` go to the
 * backing when the proxy does not define the name itself; the proxy's own
 * {@see TModule::getID ID} stays its own. `dy` and `fx` names are never
 * forwarded. {@see TComponentProxyTrait} documents the dispatch order.
 *
 * ## Event forwarding
 *
 * The first resolution of the backing calls {@see attachProxy()}, which wires
 * every public `on` event of the backing, including events contributed by its
 * behaviors, to a handler collection the proxy owns.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="myService" class="Prado\TModuleProxy" BackingComponentId="myRealService" />
 * <module id="myRealService" class="MyApp\MyService" />
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
 * {@see TComponentProxy} proxies a component that is not a module.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TModuleProxy extends TModule implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing component; empty until configured */
	private string $_backingComponentId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing module as a required dependency so that
	 * {@see TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingComponentId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingComponentId BackingComponentId} is empty
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
	 * Returns whether a {@see getBackingComponentId BackingComponentId} is
	 * configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingComponentId() !== '';
	}

	/**
	 * Returns the backing component, resolving it from the module registry on
	 * first call.
	 * @throws TConfigurationException when {@see getBackingComponentId BackingComponentId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @return ?TComponent the backing component
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getBackingComponent();
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return ?TComponent the stored backing component, or null when not yet resolved
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
	 * Returns the backing component, resolving it from the application module
	 * registry on first call. The first resolution calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingComponentId BackingComponentId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @return TComponent the backing component
	 */
	public function getBackingComponent(): TComponent
	{
		$backing = $this->getBackingComponentDirect();
		if ($backing === null) {
			$id = $this->getBackingComponentId();
			if ($id === '') {
				throw new TConfigurationException('componentproxy_backing_component_id_required');
			}
			$backing = $this->getApplication()->getModule($id);
			if ($backing === null) {
				throw new TConfigurationException('componentproxy_component_not_found', $id);
			}
			$this->setBackingComponentDirect($backing);
			$this->attachProxy();
		}
		return $backing;
	}

	/**
	 * @return string the stored module ID of the backing component
	 */
	protected function getBackingComponentIdDirect(): string
	{
		return $this->_backingComponentId;
	}

	/**
	 * @param string $value the module ID to store
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
	 * Sets the module ID of the backing component. Replacing a non-empty ID
	 * detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops the
	 * resolved backing so the next operation resolves the new module.
	 * @param string $value the module ID of the component to proxy
	 */
	public function setBackingComponentId(string $value): void
	{
		$current = $this->getBackingComponentIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TModuleProxy.BackingComponentId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.component'
			);
		}
		$this->setBackingComponentIdDirect($value);
		$this->setBackingComponentDirect(null);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing (re-resolved
	 * from the module registry after unserialization), and an empty backing ID
	 * from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingComponentIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingComponentId";
		}
	}
}
