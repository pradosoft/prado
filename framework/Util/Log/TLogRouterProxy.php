<?php

/**
 * TLogRouterProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Log;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\TModule;
use Prado\Xml\TXmlElement;

/**
 * TLogRouterProxy class.
 *
 * TLogRouterProxy is a transparent log router module that delegates every
 * {@see TLogRouter} operation to another TLogRouter module registered with the
 * application. One logical log router slot, such as the module with ID `log`,
 * can be swapped at configuration time without changing the consumers that
 * depend on it.
 *
 * ## Configuration
 *
 * {@see getBackingLogRouterId BackingLogRouterId} names the backing log router
 * module. TLogRouterProxy declares that module as a required
 * {@see IModuleDependency}, so the application initializes it first.
 *
 * The proxy owns no routes. The backing module holds the routes and attaches its
 * own {@see TLogRouter::collectLogs()} handler to the logger, so {@see init()}
 * does not attach a second handler. A `<route>` child, a `routes` array key, or
 * a `ConfigFile` on the proxy throws `logrouterproxy_routes_not_allowed` at
 * {@see init()}; routes are configured on the backing module.
 *
 * ## Transparency
 *
 * {@see addRoute()}, {@see getRoutes()}, {@see getRoutesCount()},
 * {@see removeRoute()}, {@see collectLogs()}, and the
 * {@see getConfigFile ConfigFile}, {@see getFlushCount FlushCount}, and
 * {@see getTraceLevel TraceLevel} properties call the backing router's public
 * methods. Other property reads and writes, method calls, and events reach the
 * backing through {@see TComponentProxyTrait}, which wires the backing's public
 * `on` events on first resolution. `dy` and `fx` names are never forwarded.
 *
 * Replacing a {@see setBackingLogRouterId BackingLogRouterId} logs a
 * {@see TLogger::WARNING} so a runtime swap is visible in the application log.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="log" class="Prado\Util\Log\TLogRouterProxy" BackingLogRouterId="fileLog" />
 * <module id="fileLog" class="Prado\Util\Log\TLogRouter">
 *     <route class="Prado\Util\Log\TFileLogRoute" Levels="Warning, Error, Fatal" />
 * </module>
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TLogRouterProxy();
 * $proxy->setBackingLogRouterId('fileLog');
 * $proxy->init(null);
 * // All operations now delegate to the 'fileLog' module.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLogRouterProxy extends TLogRouter implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing log router; empty until configured */
	private string $_backingLogRouterId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing log router module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing log router module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingLogRouterId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module. The routes and the logger handler belong to
	 * the backing module, so {@see TLogRouter::init()} is not called: no route is
	 * loaded and no second {@see collectLogs()} handler is attached. The behaviors'
	 * `dyInit` runs as in {@see \Prado\TModule::init()}.
	 * @param null|array|TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingLogRouterId BackingLogRouterId} is empty
	 * @throws TConfigurationException when the configuration carries routes or a ConfigFile
	 */
	public function init($config)
	{
		if ($this->getBackingLogRouterId() === '') {
			throw new TConfigurationException('logrouterproxy_backing_log_router_id_required');
		}
		if ($this->hasRoutesConfig($config)) {
			throw new TConfigurationException('logrouterproxy_routes_not_allowed', $this->getID());
		}
		TModule::init($config);
	}

	/**
	 * Returns whether a module configuration carries routes: a `<route>` child or
	 * `ConfigFile` attribute in XML, or a `routes` or `ConfigFile` key in a PHP array.
	 * @param null|array|TXmlElement $config module configuration
	 * @return bool whether the configuration declares routes on the proxy
	 */
	protected function hasRoutesConfig($config): bool
	{
		if (is_array($config)) {
			return isset($config['routes']) || isset($config['ConfigFile']) || isset($config['properties']['ConfigFile']);
		}
		if (!($config instanceof TXmlElement)) {
			return false;
		}
		if ($config->getElementByTagName('route') !== null) {
			return true;
		}
		foreach ($config->getAttributes()->getKeys() as $name) {
			if (strcasecmp((string) $name, 'ConfigFile') === 0) {
				return true;
			}
		}
		return false;
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing log router through {@see getLogRouter()}.
	 * @throws TConfigurationException when {@see getBackingLogRouterId BackingLogRouterId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TLogRouter}
	 * @return ?TComponent the backing log router
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getLogRouter();
	}

	/**
	 * Returns whether a {@see getBackingLogRouterId BackingLogRouterId} is
	 * configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingLogRouterId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing log router
	 */
	protected function getBackingLogRouterIdDirect(): string
	{
		return $this->_backingLogRouterId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingLogRouterIdDirect(string $value): void
	{
		$this->_backingLogRouterId = $value;
	}

	/**
	 * @return string the module ID of the backing log router
	 */
	public function getBackingLogRouterId(): string
	{
		return $this->getBackingLogRouterIdDirect();
	}

	/**
	 * Sets the module ID of the backing log router. Replacing a non-empty ID
	 * detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops the
	 * resolved router so the next operation resolves the new module.
	 * @param string $value the module ID of the log router to proxy
	 */
	public function setBackingLogRouterId(string $value): void
	{
		$current = $this->getBackingLogRouterIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TLogRouterProxy.BackingLogRouterId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.util.log'
			);
		}
		$this->setBackingLogRouterIdDirect($value);
		$this->setLogRouterDirect(null);
	}

	/**
	 * Returns the stored backing log router, narrowing the trait's `?TComponent`
	 * to `?TLogRouter`.
	 * @return ?TLogRouter the backing log router, or null when not yet resolved
	 */
	protected function getLogRouterDirect(): ?TLogRouter
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TLogRouter ? $backing : null;
	}

	/**
	 * @param ?TLogRouter $value the backing log router to store
	 */
	protected function setLogRouterDirect(?TLogRouter $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing {@see TLogRouter}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingLogRouterId BackingLogRouterId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TLogRouter}
	 * @return TLogRouter the backing log router module
	 */
	public function getLogRouter(): TLogRouter
	{
		$router = $this->getLogRouterDirect();
		if ($router === null) {
			$id = $this->getBackingLogRouterId();
			if ($id === '') {
				throw new TConfigurationException('logrouterproxy_backing_log_router_id_required');
			}
			$router = $this->getApplication()->getModule($id);
			if ($router === null) {
				throw new TConfigurationException('logrouterproxy_log_router_not_found', $id);
			}
			if (!($router instanceof TLogRouter)) {
				throw new TConfigurationException('logrouterproxy_invalid_log_router_type', $id);
			}
			$this->setLogRouterDirect($router);
			$this->attachProxy();
		}
		return $router;
	}

	// ----------------------------------------------------------------- TLogRouter delegation

	/**
	 * Adds a route to the backing log router.
	 * @param TLogRoute $route the route being added
	 * @param mixed $config the configuration for the route
	 * @throws \Prado\Exceptions\TInvalidDataTypeException if the route object is invalid
	 */
	public function addRoute($route, $config = null)
	{
		$this->getLogRouter()->addRoute($route, $config);
	}

	/**
	 * @return int the number of routes of the backing log router
	 */
	public function getRoutesCount(): int
	{
		return $this->getLogRouter()->getRoutesCount();
	}

	/**
	 * @return TLogRoute[] the routes of the backing log router
	 */
	public function getRoutes(): array
	{
		return $this->getLogRouter()->getRoutes();
	}

	/**
	 * Removes a route from the backing log router.
	 * @param mixed $route the route or route key to remove
	 * @return ?TLogRoute the removed route, or null when not found
	 */
	public function removeRoute($route): ?TLogRoute
	{
		return $this->getLogRouter()->removeRoute($route);
	}

	/**
	 * @return ?string the external configuration file of the backing log router
	 */
	public function getConfigFile()
	{
		return $this->getLogRouter()->getConfigFile();
	}

	/**
	 * Sets the external configuration file of the backing log router. A
	 * `ConfigFile` declared on the proxy in the application configuration is
	 * rejected by {@see init()}.
	 * @param string $value external configuration file in namespace format
	 * @throws TConfigurationException if the file is invalid
	 */
	public function setConfigFile($value)
	{
		$this->getLogRouter()->setConfigFile($value);
	}

	/**
	 * Collects log messages through the backing log router's routes.
	 * @param TLogger $logger the logger holding the messages
	 * @param bool $final whether this is the final collection of the request
	 */
	public function collectLogs($logger, bool $final)
	{
		$this->getLogRouter()->collectLogs($logger, $final);
	}

	/**
	 * @return int the number of logs before the backing router's logger flushes
	 */
	public function getFlushCount(): int
	{
		return $this->getLogRouter()->getFlushCount();
	}

	/**
	 * @param int|string $value the number of logs before the logger flushes
	 * @return static $this
	 */
	public function setFlushCount($value): static
	{
		$this->getLogRouter()->setFlushCount($value);

		return $this;
	}

	/**
	 * @return int how much debug trace stack information the backing router's logger includes
	 */
	public function getTraceLevel(): int
	{
		return $this->getLogRouter()->getTraceLevel();
	}

	/**
	 * @param null|int|string $value how much debug trace stack information to include
	 * @return static $this
	 */
	public function setTraceLevel($value): static
	{
		$this->getLogRouter()->setTraceLevel($value);

		return $this;
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing log router
	 * (re-resolved from the module registry after unserialization), and an empty
	 * backing ID from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingLogRouterIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingLogRouterId";
		}
	}
}
