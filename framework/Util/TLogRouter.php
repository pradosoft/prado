<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;

/**
 * TLogRouter class.
 *
 * TLogRouter manages routes that record log messages in different media different ways.
 * For example, a file log route {@link TFileLogRoute} records log messages
 * in log files. An email log route {@link TEmailLogRoute} sends log messages
 * to email addresses.
 *
 * Log routes may be configured in application or page folder configuration files
 * or an external configuration file specified by {@link setConfigFile ConfigFile}.
 * The format is as follows,
 * <code>
 *   <route class="TFileLogRoute" Categories="Prado\Web\UI" Levels="Warning" />
 *   <route class="TEmailLogRoute" Categories="Application" Levels="Fatal" Emails="admin@prado.local" />
 * </code>
 * PHP configuration style:
 * <code>
 *
 * </code>
 * You can specify multiple routes with different filtering conditions and different
 * targets, even if the routes are of the same type.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Util
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
	 * @throws TConfigurationException if {@link getConfigFile ConfigFile} is invalid.
	 */
	public function init($config)
	{
		if ($this->_configFile !== null) {
			if (is_file($this->_configFile)) {
				if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
					$phpConfig = include $this->_configFile;
					$this->loadConfig($phpConfig);
				} else {
					$dom = new TXmlDocument;
					$dom->loadFromFile($this->_configFile);
					$this->loadConfig($dom);
				}
			} else {
				throw new TConfigurationException('logrouter_configfile_invalid', $this->_configFile);
			}
		}
		$this->loadConfig($config);
		$this->getApplication()->attachEventHandler('OnEndRequest', [$this, 'collectLogs']);
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
					$this->_routes[] = $route;
					$route->init($route);
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
				$this->_routes[] = $route;
				$route->init($routeConfig);
			}
		}
	}

	/**
	 * Adds a TLogRoute instance to the log router.
	 *
	 * @param TLogRoute $route $route
	 * @throws TInvalidDataTypeException if the route object is invalid
	 */
	public function addRoute($route)
	{
		if (!($route instanceof TLogRoute)) {
			throw new TInvalidDataTypeException('logrouter_routetype_invalid');
		}
		$this->_routes[] = $route;
		$route->init(null);
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
	 * @param mixed $param event parameter
	 */
	public function collectLogs($param)
	{
		$logger = Prado::getLogger();
		foreach ($this->_routes as $route) {
			$route->collectLogs($logger);
		}
	}
}
