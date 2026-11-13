<?php

/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Log;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Xml\TXmlDocument;

/**
 * TLogRouter class.
 *
 * TLogRouter manages routes that record log messages in different media different ways.
 * For example, a file log route {@see \Prado\Util\Log\TFileLogRoute} records log messages
 * in log files. An email log route {@see \Prado\Util\Log\TEmailLogRoute} sends log messages
 * to email addresses.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="log" class="Prado\Util\Log\TLogRouter">
 *     <route class="Prado\Util\Log\TFileLogRoute" Levels="Warning, Error, Fatal" />
 *     <route class="Prado\Util\Log\TEmailLogRoute" Categories="Application"
 *         Levels="Fatal" Emails="admin@example.com" />
 *   </module>
 * </modules>
 * ```
 * Log routes may also be specified in an external configuration file via
 * {@see setConfigFile ConfigFile}.
 * You can specify multiple routes with different filtering conditions and different
 * targets, even if the routes are of the same type.
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'log' => [
 *             'class' => 'Prado\Util\Log\TLogRouter',
 *             'routes' => [
 *                 [
 *                     'class' => 'Prado\Util\Log\TFileLogRoute',
 *                     'properties' => [
 *                         'Levels' => 'Warning, Error, Fatal',
 *                     ],
 *                 ],
 *                 [
 *                     'class' => 'Prado\Util\Log\TEmailLogRoute',
 *                     'properties' => [
 *                         'Categories' => 'Application',
 *                         'Levels' => 'Fatal',
 *                         'Emails' => 'admin@example.com',
 *                     ],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 * A PHP route entry may also be its class name alone.
 *
 * A router with an ID and a {@see \Prado\Util\Log\TDbLogRoute} registers the cron
 * task `dblogprune`, which calls {@see pruneDbLogs} to delete the rows older than each
 * route's RetainPeriod.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 */
class TLogRouter extends \Prado\TModule
{
	/**
	 * @var array list of routes available
	 */
	private $_routes = [];
	/**
	 * @var string external configuration file
	 */
	private $_configFile;

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if {@see getConfigFile ConfigFile} is invalid.
	 */
	public function init($config)
	{
		if ($this->_configFile !== null) {
			if (is_file($this->_configFile)) {
				if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
					$phpConfig = include $this->_configFile;
					$this->loadConfig($phpConfig);
				} else {
					$dom = new TXmlDocument();
					$dom->loadFromFile($this->_configFile);
					$this->loadConfig($dom);
				}
			} else {
				throw new TConfigurationException('logrouter_configfile_invalid', $this->_configFile);
			}
		}
		$this->loadConfig($config);
		Prado::getLogger()->attachEventHandler('onFlushLogs', [$this, 'collectLogs']);
		parent::init($config);
	}

	/**
	 * Creates and adds a route for each `<route>` element of an XML configuration, or each
	 * entry of the `routes` array of a PHP configuration.  A PHP entry is an array with
	 * `class` and `properties`, or a class name alone.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration node
	 * @throws TConfigurationException if a route has no class, or its class is not a {@see TLogRoute}
	 */
	private function loadConfig($config)
	{
		if (is_array($config)) {
			$config = ['route' => is_array($config['routes'] ?? null) ? array_values($config['routes']) : []];
		}
		foreach ($this->getConfigElements($config, 'route', true) as $element) {
			$route = $this->createElementComponent($element, 'route', TLogRoute::class, 'logrouter_routetype_invalid');
			$this->addRoute($route, $element);
		}
	}

	/**
	 * Adds a TLogRoute instance to the log router.
	 * @param TLogRoute $route the route being added.
	 * @param mixed $config the configuration for the route.
	 * @throws TInvalidDataTypeException if the route object is invalid
	 */
	public function addRoute($route, $config = null)
	{
		if (!($route instanceof TLogRoute)) {
			throw new TInvalidDataTypeException('logrouter_routetype_invalid');
		}
		$this->_routes[] = $route;
		$route->init($config);
	}

	/**
	 * Gets the number of log routes.
	 * @return int The number of routes.
	 * @since 4.3.0
	 */
	public function getRoutesCount(): int
	{
		return count($this->_routes);
	}

	/**
	 * Gets the log routes.
	 * @return TLogRoute[] The routes for the Router
	 * @since 4.3.0
	 */
	public function getRoutes(): array
	{
		return $this->_routes;
	}

	/**
	 * Deletes the old log rows of every {@see TDbLogRoute} of the router.
	 * @param ?float $retainPeriod the seconds of the log to retain; null for each
	 *   route's {@see TDbLogRoute::getRetainPeriod RetainPeriod}.
	 * @return int the number of rows deleted.
	 * @since 4.4.0
	 */
	public function pruneDbLogs(?float $retainPeriod = null): int
	{
		$count = 0;
		foreach ($this->getRoutes() as $route) {
			if ($route instanceof TDbLogRoute) {
				$count += $route->pruneLogs($retainPeriod);
			}
		}
		return $count;
	}

	/**
	 * Registers the cron task `dblogprune`, which calls {@see pruneDbLogs}, when the
	 * router has an ID and a {@see TDbLogRoute}.  Another TLogRouter module registers
	 * the same task name.
	 * @param object $sender the object raising fxGetCronTaskInfos.
	 * @param mixed $param the parameter.
	 * @return ?TCronTaskInfo the task information; null without an ID or a TDbLogRoute.
	 * @since 4.4.0
	 */
	public function fxGetCronTaskInfos($sender, $param): ?TCronTaskInfo
	{
		if (!($id = $this->getID())) {
			return null;
		}
		foreach ($this->getRoutes() as $route) {
			if ($route instanceof TDbLogRoute) {
				return new TCronTaskInfo('dblogprune', $id . '->pruneDbLogs()', $this, Prado::localize('DbLogRoute Prune Old Logs'), Prado::localize('Deletes the log rows older than the RetainPeriod of each TDbLogRoute.'));
			}
		}
		return null;
	}

	/**
	 * Removes a TLogRoute instance to the log router.
	 * @param mixed $route the Route or Route Key to remove
	 * @return ?TLogRoute The routes for the Router
	 * @since 4.3.0
	 */
	public function removeRoute($route): ?TLogRoute
	{
		if (!is_array($route) && !is_object($route) && isset($this->_routes[$route])) {
			$removed = $this->_routes[$route];
			unset($this->_routes[$route]);
			$this->_routes = array_values($this->_routes);
			return $removed;
		}
		if (($key = array_search($route, $this->_routes, true)) !== false) {
			$removed = $this->_routes[$key];
			unset($this->_routes[$key]);
			$this->_routes = array_values($this->_routes);
			return $removed;
		}
		return null;
	}

	/**
	 * @return string external configuration file. Defaults to null.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * @param string $value external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TConfigurationException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		$configFile = Prado::getPathOfNamespace($value, $this->getApplication()->getConfigurationFileExt());
		if ($configFile === null) {
			throw new TConfigurationException('logrouter_configfile_invalid', $value);
		}
		$this->_configFile = $configFile;
	}

	/**
	 * Collects log messages from a logger.
	 * This method is an event handler to application's EndRequest event.
	 * @param TLogger $logger
	 * @param bool $final
	 */
	public function collectLogs($logger, bool $final)
	{
		if (!($logger instanceof TLogger)) {
			$logger = Prado::getLogger();
		}
		foreach ($this->_routes as $route) {
			if ($route->getEnabled()) {
				$route->collectLogs($logger, $final);
			}
		}
	}

	/**
	 * This is a passthrough to the Application TLogger.
	 * @return int The number of logs before triggering {@see self::onFlushLogs()}, default 1000.
	 * @since 4.3.0
	 */
	public function getFlushCount(): int
	{
		return Prado::getLogger()->getFlushCount();
	}

	/**
	 * This is a passthrough to the Application TLogger.
	 * @param int|string $value the number of logs before triggering {@see self::onFlushLogs()}
	 * @return static $this
	 * @since 4.3.0
	 */
	public function setFlushCount($value): static
	{
		Prado::getLogger()->setFlushCount(TPropertyValue::ensureInteger($value));

		return $this;
	}

	/**
	 * This is a passthrough to the Application TLogger.
	 * @return int How much debug trace stack information to include. Default 0.
	 * @since 4.3.0
	 */
	public function getTraceLevel(): int
	{
		return Prado::getLogger()->getTraceLevel();
	}

	/**
	 * This is a passthrough to the Application TLogger.
	 * @param null|int|string $value How much debug trace stack information to include.
	 * @return static $this
	 * @since 4.3.0
	 */
	public function setTraceLevel($value): static
	{
		Prado::getLogger()->setTraceLevel(TPropertyValue::ensureInteger($value));

		return $this;
	}
}
