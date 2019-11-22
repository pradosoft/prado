<?php
/**
 * TParameterModule class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Caching\TFileCacheDependency;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TParameterModule class
 *
 * TParameterModule enables loading application parameters from external
 * storage other than the application configuration.
 * To load parameters from an XML file, configure the module by setting
 * its {@link setParameterFile ParameterFile} property.
 * Note, the property only accepts a file path in namespace format with
 * file extension being '.xml'. The file format is as follows,  which is
 * similar to the parameter portion in an application configuration,
 * <code>
 * <parameters>
 *   <parameter id="param1" value="paramValue1" />
 *   <parameter id="param2" Property1="Value1" Property2="Value2" ... />
 * </parameters>
 * </code>
 *
 * In addition, any content enclosed within the module tag is also treated
 * as parameters, e.g.,
 * <code>
 * <module class="Prado\Util\TParameterModule">
 *   <parameter id="param1" value="paramValue1" />
 *   <parameter id="param2" Property1="Value1" Property2="Value2" ... />
 * </module>
 * </code>
 *
 * If a parameter is defined both in the external file and within the module
 * tag, the former takes precedence.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Util
 * @since 3.0
 */
class TParameterModule extends \Prado\TModule
{
	const PARAM_FILE_EXT = '.xml';
	private $_initialized = false;
	private $_paramFile;

	/**
	 * Initializes the module by loading parameters.
	 * @param mixed $config content enclosed within the module tag
	 */
	public function init($config)
	{
		$this->loadParameters($config);
		if ($this->_paramFile !== null) {
			$configFile = null;
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_XML && ($cache = $this->getApplication()->getCache()) !== null) {
				$cacheKey = 'TParameterModule:' . $this->_paramFile;
				if (($configFile = $cache->get($cacheKey)) === false) {
					$configFile = new TXmlDocument;
					$configFile->loadFromFile($this->_paramFile);
					$cache->set($cacheKey, $configFile, 0, new TFileCacheDependency($this->_paramFile));
				}
			} else {
				if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
					$configFile = include $this->_paramFile;
				} else {
					$configFile = new TXmlDocument;
					$configFile->loadFromFile($this->_paramFile);
				}
			}
			$this->loadParameters($configFile);
		}
		$this->_initialized = true;
	}

	/**
	 * Loads parameters into application.
	 * @param mixed $config XML of PHP representation of the parameters
	 * @throws TConfigurationException if the parameter file format is invalid
	 */
	protected function loadParameters($config)
	{
		$parameters = [];
		if (is_array($config)) {
			foreach ($config as $id => $parameter) {
				if (is_array($parameter) && isset($parameter['class'])) {
					$properties = $parameter['properties'] ?? [];
					$parameters[$id] = [$parameter['class'], $properties];
				} else {
					$parameters[$id] = $parameter;
				}
			}
		} elseif ($config instanceof TXmlElement) {
			foreach ($config->getElementsByTagName('parameter') as $node) {
				$properties = $node->getAttributes();
				if (($id = $properties->remove('id')) === null) {
					throw new TConfigurationException('parametermodule_parameterid_required');
				}
				if (($type = $properties->remove('class')) === null) {
					if (($value = $properties->remove('value')) === null) {
						$parameters[$id] = $node;
					} else {
						$parameters[$id] = $value;
					}
				} else {
					$parameters[$id] = [$type, $properties->toArray()];
				}
			}
		}

		$appParams = $this->getApplication()->getParameters();
		foreach ($parameters as $id => $parameter) {
			if (is_array($parameter)) {
				$component = Prado::createComponent($parameter[0]);
				foreach ($parameter[1] as $name => $value) {
					$component->setSubProperty($name, $value);
				}
				$appParams->add($id, $component);
			} else {
				$appParams->add($id, $parameter);
			}
		}
	}

	/**
	 * @return string the parameter file path
	 */
	public function getParameterFile()
	{
		return $this->_paramFile;
	}

	/**
	 * @param string $value the parameter file path. It must be in namespace format
	 * and the file extension is '.xml'.
	 * @throws TInvalidOperationException if the module is initialized
	 * @throws TConfigurationException if the file is invalid
	 */
	public function setParameterFile($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('parametermodule_parameterfile_unchangeable');
		} elseif (($this->_paramFile = Prado::getPathOfNamespace($value, $this->getApplication()->getConfigurationFileExt())) === null || !is_file($this->_paramFile)) {
			throw new TConfigurationException('parametermodule_parameterfile_invalid', $value);
		}
	}
}
