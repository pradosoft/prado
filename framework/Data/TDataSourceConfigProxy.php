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
use Prado\Util\Log\TLogger;

/**
 * TDataSourceConfigProxy class.
 *
 * TDataSourceConfigProxy is a transparent data source module that delegates its
 * database connection to another {@see TDataSourceConfig} module registered with
 * the application. One logical data source slot can be swapped at configuration
 * time without changing the consumers that depend on it.
 *
 * ## Configuration
 *
 * {@see getBackingDataSourceId BackingDataSourceId} names the backing data source
 * module. TDataSourceConfigProxy declares that module as a required
 * {@see IModuleDependency}, so the application initializes it first.
 *
 * ## Transparency
 *
 * {@see getDbConnection()} returns the backing module's connection, so every
 * consumer of the proxy shares the backing connection. Other property reads and
 * writes, method calls, and events reach the backing through
 * {@see TComponentProxyTrait}, which wires the backing's public `on` events on
 * first resolution. `dy` and `fx` names are never forwarded.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="db" class="Prado\Data\TDataSourceConfigProxy" BackingDataSourceId="realDb" />
 * <module id="realDb" class="Prado\Data\TDataSourceConfig">
 *     <database ConnectionString="mysql:host=localhost;dbname=test" username="dbuser" password="dbpass" />
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

	/** @var string module ID of the backing data source config; empty until configured */
	private string $_backingDataSourceId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing data source module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing data source module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingDataSourceId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingDataSourceId BackingDataSourceId} is empty
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
	 * Returns the backing data source config through {@see getDataSource()}.
	 * @throws TConfigurationException when {@see getBackingDataSourceId BackingDataSourceId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TDataSourceConfig}
	 * @return ?TComponent the backing data source config
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getDataSource();
	}

	/**
	 * Returns whether a {@see getBackingDataSourceId BackingDataSourceId} is
	 * configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingDataSourceId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing data source config
	 */
	protected function getBackingDataSourceIdDirect(): string
	{
		return $this->_backingDataSourceId;
	}

	/**
	 * @param string $value the module ID to store
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
	 * Sets the module ID of the backing data source config. Replacing a non-empty
	 * ID detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops
	 * the resolved backing so the next operation resolves the new module.
	 * @param string $value the module ID of the data source config to proxy
	 */
	public function setBackingDataSourceId(string $value): void
	{
		$current = $this->getBackingDataSourceIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TDataSourceConfigProxy.BackingDataSourceId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.data'
			);
		}
		$this->setBackingDataSourceIdDirect($value);
		$this->setDataSourceDirect(null);
	}

	/**
	 * Returns the stored backing data source config, narrowing the trait's
	 * `?TComponent` to `?TDataSourceConfig`.
	 * @return ?TDataSourceConfig the backing data source config, or null when not yet resolved
	 */
	protected function getDataSourceDirect(): ?TDataSourceConfig
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TDataSourceConfig ? $backing : null;
	}

	/**
	 * @param ?TDataSourceConfig $value the backing data source config to store
	 */
	protected function setDataSourceDirect(?TDataSourceConfig $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing {@see TDataSourceConfig}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingDataSourceId BackingDataSourceId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TDataSourceConfig}
	 * @return TDataSourceConfig the backing data source config module
	 */
	public function getDataSource(): TDataSourceConfig
	{
		$dataSource = $this->getDataSourceDirect();
		if ($dataSource === null) {
			$id = $this->getBackingDataSourceId();
			if ($id === '') {
				throw new TConfigurationException('datasourceproxy_backing_data_source_id_required');
			}
			$dataSource = $this->getApplication()->getModule($id);
			if ($dataSource === null) {
				throw new TConfigurationException('datasourceproxy_data_source_not_found', $id);
			}
			if (!($dataSource instanceof TDataSourceConfig)) {
				throw new TConfigurationException('datasourceproxy_invalid_data_source_type', $id);
			}
			$this->setDataSourceDirect($dataSource);
			$this->attachProxy();
		}
		return $dataSource;
	}

	// ----------------------------------------------------------------- TDataSourceConfig overrides

	/**
	 * Returns the database connection of the backing data source config, so the
	 * proxy never creates a connection of its own.
	 * @throws TConfigurationException when the backing module cannot be resolved
	 * @return TDbConnection the backing data source's database connection
	 */
	public function getDbConnection()
	{
		return $this->getDataSource()->getDbConnection();
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
		if ($this->getBackingDataSourceIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingDataSourceId";
		}
	}
}
