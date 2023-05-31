<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\Xml\TXmlDocument;

/**
 * TLogRouter class.
 *
 * TLogRouter manages routes that record log messages in different media different ways.
 * For example, a file log route {@see TFileLogRoute} records log messages
 * in log files. An email log route {@see TEmailLogRoute} sends log messages
 * to email addresses.
 *
 * Log routes may be configured in application or page folder configuration files
 * or an external configuration file specified by {@see setConfigFile ConfigFile}.
 * The format is as follows,
 * ```xml
 *   <route class="TFileLogRoute" Categories="Prado\Web\UI" Levels="Warning" />
 *   <route class="TEmailLogRoute" Categories="Application" Levels="Fatal" Emails="admin@prado.local" />
 * ```
 * You can specify multiple routes with different filtering conditions and different
 * targets, even if the routes are of the same type.
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
	 * @param mixed $config configuration for this module, can be null
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
	 * Loads configuration from an XML element or PHP array
	 * @param mixed $config configuration node
	 * @throws TConfigurationException if log route class or type is not specified
	 */
	private function loadConfig($config)
	{
		if (is_array($config)) {
			if (isset($config['routes']) && is_array($config['routes'])) {
				foreach ($config['routes'] as $route) {
					$properties = $route['properties'] ?? [];
					if (!isset($route['class'])) {
						throw new TConfigurationException('logrouter_routeclass_required');
					}
					$route = Prado::createComponent($route['class']);
					if (!($route instanceof TLogRoute)) {
						throw new TConfigurationException('logrouter_routetype_invalid');
					}
					foreach ($properties as $name => $value) {
						$route->setSubproperty($name, $value);
					}
					$this->addRoute($route, $route);
				}
			}
		} else {
			foreach ($config->getElementsByTagName('route') as $routeConfig) {
				$properties = $routeConfig->getAttributes();
				if (($class = $properties->remove('class')) === null) {
					throw new TConfigurationException('logrouter_routeclass_required');
				}
				$route = Prado::createComponent($class);
				if (!($route instanceof TLogRoute)) {
					throw new TConfigurationException('logrouter_routetype_invalid');
				}
				foreach ($properties as $name => $value) {
					$route->setSubproperty($name, $value);
				}
				$this->addRoute($route, $routeConfig);
			}
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
	 * @since 4.2.3
	 */
	public function getRoutesCount(): int
	{
		return count($this->_routes);
	}

	/**
	 * Gets the log routes.
	 * @return TLogRoute[] The routes for the Router
	 * @since 4.2.3
	 */
	public function getRoutes(): array
	{
		return $this->_routes;
	}

	/**
	 * Removes a TLogRoute instance to the log router.
	 * @param mixed $route the Route or Route Key to remove
	 * @return ?TLogRoute The routes for the Router
	 * @since 4.2.3
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
		if (($this->_configFile = Prado::getPathOfNamespace($value, $this->getApplication()->getConfigurationFileExt())) === null) {
			throw new TConfigurationException('logrouter_configfile_invalid', $value);
		}
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
	 * @since 4.2.3
	 */
	public function getFlushCount(): int
	{
		return Prado::getLogger()->getFlushCount();
	}

	/**
	 * This is a passthrough to the Application TLogger.
	 * @param int|string $value the number of logs before triggering {@see self::onFlushLogs()}
	 * @return static $this
	 * @since 4.2.3
	 */
	public function setFlushCount($value): static
	{
		Prado::getLogger()->setFlushCount(TPropertyValue::ensureInteger($value));

		return $this;
	}

	/**
	 * This is a passthrough to the Application TLogger.
	 * @return int How much debug trace stack information to include. Default 0.
	 * @since 4.2.3
	 */
	public function getTraceLevel(): int
	{
		return Prado::getLogger()->getTraceLevel();
	}

	/**
	 * This is a passthrough to the Application TLogger.
	 * @param null|int|string $value How much debug trace stack information to include.
	 * @return static $this
	 * @since 4.2.3
	 */
	public function setTraceLevel($value): static
	{
		Prado::getLogger()->setTraceLevel(TPropertyValue::ensureInteger($value));

		return $this;
	}
}
