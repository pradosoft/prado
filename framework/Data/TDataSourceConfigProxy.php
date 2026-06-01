<?php

/**
 * TDataSourceConfigProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\TPropertyValue;
use Prado\Util\TLogger;

/**
 * TDataSourceConfigProxy class.
 *
 * TDataSourceConfigProxy is a transparent proxy that delegates every database
 * connection operation to another {@see TDataSourceConfig} module already
 * registered with the application. This lets a single logical "data source slot"
 * be hot-swapped at configuration time without changing the consumers that depend
 * on it.
 *
 * ## Configuration
 *
 * Set {@see getBackingDataSourceId BackingDataSourceId} to the module ID of the
 * target data source config. TDataSourceConfigProxy declares that module as a
 * required dependency so the framework initializes it first, guaranteeing the
 * backing is available on first access.
 *
 * ## Transparency
 *
 * All property reads, property writes, and method calls are forwarded to the
 * backing component. The critical override is {@see getDbConnection()}, which
 * delegates directly to the backing data source's connection. `dy`- and
 * `fx`-prefixed names are never forwarded — those belong exclusively to the
 * behavior and global-event system.
 *
 * ## Event sharing
 *
 * {@see attachProxy()} reflects all public `on[A-Z]*` methods on the backing
 * component — including those contributed by its behaviors — and shares their
 * {@see \Prado\Collections\TWeakCallableCollection} handler lists with this proxy.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="db"
 *         class="Prado\Data\TDataSourceConfigProxy"
 *         BackingDataSourceId="realDb" />
 *
 * <module id="realDb">
 *     <database ConnectionString="mysqli:host=localhost;dbname=test"
 *         username="dbuser" password="dbpass" />
 * </module>
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TDataSourceConfigProxy();
 * $proxy->setBackingDataSourceId('realDb');
 * $proxy->init(null);
 * // All operations now delegate to the 'realDb' module.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDataSourceConfigProxy extends TDataSourceConfig implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string Module ID of the backing data source config; empty until configured. */
	private string $_backingDataSourceId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares a required dependency on the backing data source module so that
	 * {@see \Prado\TApplication} initializes it before this proxy.
	 *
	 * @param bool $isPreInit `true` when collecting for the dyPreInit pass,
	 *   `false` when collecting for the init() pass (default).
	 *   TDataSourceConfigProxy requires its backing in all phases, so `$isPreInit` is not used.
	 * @return ?array<int, array{id: ?string, required: bool}> dependency list,
	 *   or null when no {@see getBackingDataSourceId BackingDataSourceId} has been set yet
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?array
	{
		$id = $this->getBackingDataSourceId();
		if ($id === '') {
			return null;
		}
		return [['id' => $id, 'required' => true]];
	}

	/**
	 * Initializes the proxy module. Throws when no
	 * {@see getBackingDataSourceId BackingDataSourceId} has been configured.
	 *
	 * @param ?\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingDataSourceId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingDataSourceId() === '') {
			throw new TConfigurationException('datasourceproxy_backing_data_source_id_required');
		}
		parent::init($config);
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the resolved backing data source, using the same lazy-resolution
	 * path as {@see getDataSource()}.
	 *
	 * @throws TConfigurationException when {@see getBackingDataSourceId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TDataSourceConfig}
	 * @return ?TComponent the resolved backing data source config
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getDataSource();
	}

	/**
	 * Returns `true` when a {@see getBackingDataSourceId BackingDataSourceId} has
	 * been configured, enabling lazy resolution of the backing from the application
	 * module registry.
	 *
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingDataSourceId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the module ID of the backing data source config
	 */
	protected function getBackingDataSourceIdDirect(): string
	{
		return $this->_backingDataSourceId;
	}

	/**
	 * @param string $value the module ID to store directly
	 */
	protected function setBackingDataSourceIdDirect(string $value): void
	{
		$this->_backingDataSourceId = $value;
	}

	/**
	 * @return string the module ID of the backing data source config
	 */
	public function getBackingDataSourceId(): string
	{
		return $this->getBackingDataSourceIdDirect();
	}

	/**
	 * Sets the module ID of the backing data source config. When a non-empty ID was
	 * already set and the new value differs, the change is logged at
	 * {@see \Prado\Util\TLogger::WARNING} level and the resolved component reference
	 * is invalidated so the next operation re-resolves the module.
	 *
	 * @param string $value the module ID of the data source config to proxy
	 */
	public function setBackingDataSourceId(string $value): void
	{
		$value = TPropertyValue::ensureString($value);
		$current = $this->getBackingDataSourceIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf(
					"TDataSourceConfigProxy.BackingDataSourceId changed from '%s' to '%s'.",
					$current,
					$value
				),
				TLogger::WARNING,
				'prado.data'
			);
		}
		$this->setBackingDataSourceIdDirect($value);
		$this->setDataSourceDirect(null);
	}

	/**
	 * Returns the lazily resolved backing data source config reference, or null
	 * when not yet resolved. Narrows the trait's `?TComponent` storage to
	 * `?TDataSourceConfig`.
	 *
	 * @return ?TDataSourceConfig the backing data source config, or null when not yet resolved
	 */
	protected function getDataSourceDirect(): ?TDataSourceConfig
	{
		$b = $this->getProxyBackingDirect();
		return $b instanceof TDataSourceConfig ? $b : null;
	}

	/**
	 * Stores the backing data source config reference directly via the trait's
	 * backing field.
	 *
	 * @param ?TDataSourceConfig $value the backing data source config to store directly
	 */
	protected function setDataSourceDirect(?TDataSourceConfig $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the resolved backing {@see TDataSourceConfig} instance, resolving it
	 * lazily on first call via {@see \Prado\TApplication::getModule()}.
	 *
	 * @throws TConfigurationException when {@see getBackingDataSourceId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TDataSourceConfig}
	 * @return TDataSourceConfig the backing data source config module
	 */
	public function getDataSource(): TDataSourceConfig
	{
		$ds = $this->getDataSourceDirect();
		if ($ds === null) {
			$id = $this->getBackingDataSourceId();
			if ($id === '') {
				throw new TConfigurationException('datasourceproxy_backing_data_source_id_required');
			}
			$module = $this->getApplication()->getModule($id);
			if ($module === null) {
				throw new TConfigurationException('datasourceproxy_data_source_not_found', $id);
			}
			if (!($module instanceof TDataSourceConfig)) {
				throw new TConfigurationException('datasourceproxy_invalid_data_source_type', $id);
			}
			$this->setDataSourceDirect($module);
			$this->attachProxy();
			$ds = $this->getDataSourceDirect();
		}
		return $ds;
	}

	// ----------------------------------------------------------------- TDataSourceConfig overrides

	/**
	 * Returns the database connection from the backing data source config.
	 *
	 * Overrides the parent implementation so all consumers that call
	 * `getDbConnection()` through this proxy receive the backing module's
	 * connection rather than one created by the proxy itself.
	 *
	 * @throws TConfigurationException when the backing module cannot be resolved
	 * @return TDbConnection the database connection from the backing data source
	 */
	public function getDbConnection(): TDbConnection
	{
		return $this->getDataSource()->getDbConnection();
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes transient and default-valued fields from serialization. The
	 * resolved backing data source reference is excluded because it is re-resolved
	 * from the application module registry after deserialization.
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingDataSourceIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingDataSourceId";
		}
	}
}
