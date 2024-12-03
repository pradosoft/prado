<?php

/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Composer\Autoload\ClassLoader;
use Prado\Caching\TChainedCacheDependency;
use Prado\Caching\TFileCacheDependency;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Xml\TXmlDocument;

/**
 * TApplicationConfiguration class.
 *
 * This class is used internally by TApplication to parse and represent application configuration.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @since 3.0
 */
class TApplicationConfiguration extends \Prado\TComponent
{
	/**
	 * The cache name for installed Prado composer packages
	 */
	public const COMPOSER_INSTALLED_CACHE = 'prado:composer:installedcache';
	/**
	 * Name of the Extra field to look for Prado Extension Class
	 */
	public const COMPOSER_EXTRA_CLASS = 'bootstrap';
	/**
	 * @var array list of application initial property values, indexed by property names
	 */
	private $_properties = [];
	/**
	 * @var array list of namespaces to be used
	 */
	private $_usings = [];
	/**
	 * @var array list of path aliases, indexed by alias names
	 */
	private $_aliases = [];
	/**
	 * @var array list of module configurations
	 */
	private $_modules = [];
	/**
	 * @var array list of service configurations
	 */
	private $_services = [];
	/**
	 * @var array list of parameters
	 */
	private $_parameters = [];
	/**
	 * @var array list of included configurations
	 */
	private $_includes = [];
	/**
	 * @var bool whether this configuration contains actual stuff
	 */
	private $_empty = true;
	/**
	 * @var array<string, string> name/id of the composer extension and class for extension
	 */
	private static $_composerPlugins;

	/**
	 * Parses the application configuration file.
	 * @param string $fname configuration file name
	 * @throws TConfigurationException if there is any parsing error
	 */
	public function loadFromFile($fname)
	{
		if (Prado::getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			$fcontent = include $fname;
			$this->loadFromPhp($fcontent, dirname($fname));
		} else {
			$dom = new TXmlDocument();
			$dom->loadFromFile($fname);
			$this->loadFromXml($dom, dirname($fname));
		}
	}

	/**
	 * @return bool whether this configuration contains actual stuff
	 */
	public function getIsEmpty()
	{
		return $this->_empty;
	}

	/**
	 * Parses the application configuration given in terms of a PHP array.
	 * @param array $config the PHP array
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	public function loadFromPhp($config, $configPath)
	{
		// application properties
		if (isset($config['application'])) {
			foreach ($config['application'] as $name => $value) {
				$this->_properties[$name] = $value;
			}
			$this->_empty = false;
		}

		if (isset($config['paths']) && is_array($config['paths'])) {
			$this->loadPathsPhp($config['paths'], $configPath);
		}

		if (isset($config['modules']) && is_array($config['modules'])) {
			$this->loadModulesPhp($config['modules'], $configPath);
		}

		if (isset($config['services']) && is_array($config['services'])) {
			$this->loadServicesPhp($config['services'], $configPath);
		}

		if (isset($config['parameters']) && is_array($config['parameters'])) {
			$this->loadParametersPhp($config['parameters'], $configPath);
		}

		if (isset($config['includes']) && is_array($config['includes'])) {
			$this->loadExternalXml($config['includes'], $configPath);
		}
	}

	/**
	 * Parses the application configuration given in terms of a TXmlElement.
	 * @param \Prado\Xml\TXmlElement $dom the XML element
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	public function loadFromXml($dom, $configPath)
	{
		// application properties
		foreach ($dom->getAttributes() as $name => $value) {
			$this->_properties[$name] = $value;
			$this->_empty = false;
		}

		foreach ($dom->getElements() as $element) {
			switch ($element->getTagName()) {
				case 'paths':
					$this->loadPathsXml($element, $configPath);
					break;
				case 'modules':
					$this->loadModulesXml($element, $configPath);
					break;
				case 'services':
					$this->loadServicesXml($element, $configPath);
					break;
				case 'parameters':
					$this->loadParametersXml($element, $configPath);
					break;
				case 'include':
					$this->loadExternalXml($element, $configPath);
					break;
				default:
					//throw new TConfigurationException('appconfig_tag_invalid',$element->getTagName());
					break;
			}
		}
	}

	/**
	 * Loads the paths PHP array
	 * @param array $pathsNode the paths PHP array
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadPathsPhp($pathsNode, $configPath)
	{
		if (isset($pathsNode['aliases']) && is_array($pathsNode['aliases'])) {
			foreach ($pathsNode['aliases'] as $id => $path) {
				$path = str_replace('\\', '/', $path);
				if (preg_match('/^\\/|.:\\/|.:\\\\/', $path)) {	// if absolute path
					$p = realpath($path);
				} else {
					$p = realpath($configPath . DIRECTORY_SEPARATOR . $path);
				}
				if ($p === false || !is_dir($p)) {
					throw new TConfigurationException('appconfig_aliaspath_invalid', $id, $path);
				}
				if (isset($this->_aliases[$id])) {
					throw new TConfigurationException('appconfig_alias_redefined', $id);
				}
				$this->_aliases[$id] = $p;
			}
		}

		if (isset($pathsNode['using']) && is_array($pathsNode['using'])) {
			foreach ($pathsNode['using'] as $namespace) {
				$this->_usings[] = $namespace;
			}
		}
	}

	/**
	 * Loads the paths XML node.
	 * @param \Prado\Xml\TXmlElement $pathsNode the paths XML node
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadPathsXml($pathsNode, $configPath)
	{
		foreach ($pathsNode->getElements() as $element) {
			switch ($element->getTagName()) {
				case 'alias':
					{
						if (($id = $element->getAttribute('id')) !== null && ($path = $element->getAttribute('path')) !== null) {
							$path = str_replace('\\', '/', $path);
							if (preg_match('/^\\/|.:\\/|.:\\\\/', $path)) {	// if absolute path
								$p = realpath($path);
							} else {
								$p = realpath($configPath . DIRECTORY_SEPARATOR . $path);
							}
							if ($p === false || !is_dir($p)) {
								throw new TConfigurationException('appconfig_aliaspath_invalid', $id, $path);
							}
							if (isset($this->_aliases[$id])) {
								throw new TConfigurationException('appconfig_alias_redefined', $id);
							}
							$this->_aliases[$id] = $p;
						} else {
							throw new TConfigurationException('appconfig_alias_invalid');
						}
						$this->_empty = false;
						break;
					}
				case 'using':
					{
						if (($namespace = $element->getAttribute('namespace')) !== null) {
							$this->_usings[] = $namespace;
						} else {
							throw new TConfigurationException('appconfig_using_invalid');
						}
						$this->_empty = false;
						break;
					}
				default:
					throw new TConfigurationException('appconfig_paths_invalid', $element->getTagName());
			}
		}
	}

	/**
	 * Reads the Composer static RegisteredLoaders for their Vendor Directory. Reads the Vendor
	 * Directory composer file 'installed.json' (accumulated extensions composer.json) for the project.
	 * The ['extra']['bootstrap'] field is read for each extension, if it's there.
	 * @return array<string, string> the extension name and bootstrap class.
	 * @since 4.2.0
	 */
	protected function getComposerExtensionBootStraps()
	{
		if ($cache = Prado::getApplication()->getCache()) {
			$plugins = $cache->get(self::COMPOSER_INSTALLED_CACHE);
			if ($plugins !== null) {
				return $plugins;
			}
		}
		$dependencies = new TChainedCacheDependency();
		$listDeps = $dependencies->getDependencies();
		$plugins = [];
		foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
			$file = $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
			$manifests = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

			// Loop through the installed packages
			foreach ($manifests['packages'] as $package) {
				$name = $package['name'];
				if (isset($package['extra']) && isset($package['extra'][self::COMPOSER_EXTRA_CLASS])) {
					$plugins[$name] = $package['extra'][self::COMPOSER_EXTRA_CLASS];
					//$packagepath =  realpath($vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . $package['install-path']);
				}
			}
			$listDeps[] = new TFileCacheDependency($file);
		}
		if ($cache) {
			$cache->set(self::COMPOSER_INSTALLED_CACHE, $plugins, null, $dependencies);
		}
		return $plugins;
	}

	/**
	 * Given a module id as a composer package name, returns the extension bootstrap
	 * {@see \Prado\TModule} class.
	 * @param string $name the name of the Composer Extension.
	 * @return null|string the bootstrap class of the Composer Extension.
	 * @since 4.2.0
	 */
	public function getComposerExtensionClass($name)
	{
		if (self::$_composerPlugins === null) {
			self::$_composerPlugins = $this->getComposerExtensionBootStraps();
		}
		return self::$_composerPlugins[$name] ?? null;
	}

	/**
	 * Loads the modules PHP array.
	 * @param array $modulesNode the modules PHP array
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadModulesPhp($modulesNode, $configPath)
	{
		foreach ($modulesNode as $id => $module) {
			if (strpos($id, '/') !== false && ($class = $this->getComposerExtensionClass($id))) {
				if (isset($module['class'])) {
					throw new TConfigurationException('appconfig_moduletype_inapplicable', $id);
				}
				$module['class'] = $class;
			}
			if (!isset($module['class'])) {
				throw new TConfigurationException('appconfig_moduletype_required', $id);
			}
			$type = $module['class'];
			unset($module['class']);
			$properties = [];
			if (isset($module['properties'])) {
				$properties = $module['properties'];
				unset($module['properties']);
			}
			$properties['id'] = $id;
			$this->_modules[$id] = [$type, $properties, $module];
			$this->_empty = false;
		}
	}

	/**
	 * Loads the modules XML node.
	 * @param \Prado\Xml\TXmlElement $modulesNode the modules XML node
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadModulesXml($modulesNode, $configPath)
	{
		foreach ($modulesNode->getElements() as $element) {
			if ($element->getTagName() === 'module') {
				$properties = $element->getAttributes();
				$id = $properties->itemAt('id');
				$type = $properties->remove('class');
				if (strpos($id ?? '', '/') !== false && ($class = $this->getComposerExtensionClass($id))) {
					if ($type) {
						throw new TConfigurationException('appconfig_moduletype_inapplicable', $id);
					}
					$type = $class;
				}
				if ($type === null) {
					throw new TConfigurationException('appconfig_moduletype_required', $id);
				}
				$element->setParent(null);
				if ($id === null) {
					$this->_modules[] = [$type, $properties->toArray(), $element];
				} else {
					$this->_modules[$id] = [$type, $properties->toArray(), $element];
				}
				$this->_empty = false;
			} else {
				throw new TConfigurationException('appconfig_modules_invalid', $element->getTagName());
			}
		}
	}

	/**
	 * Loads the services PHP array.
	 * @param array $servicesNode the services PHP array
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadServicesPhp($servicesNode, $configPath)
	{
		foreach ($servicesNode as $id => $service) {
			if (!isset($service['class'])) {
				throw new TConfigurationException('appconfig_servicetype_required');
			}
			$type = $service['class'];
			$properties = $service['properties'] ?? [];
			unset($service['properties']);
			$properties['id'] = $id;
			$this->_services[$id] = [$type, $properties, $service];
			$this->_empty = false;
		}
	}

	/**
	 * Loads the services XML node.
	 * @param \Prado\Xml\TXmlElement $servicesNode the services XML node
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadServicesXml($servicesNode, $configPath)
	{
		foreach ($servicesNode->getElements() as $element) {
			if ($element->getTagName() === 'service') {
				$properties = $element->getAttributes();
				if (($id = $properties->itemAt('id')) === null) {
					throw new TConfigurationException('appconfig_serviceid_required');
				}
				if (($type = $properties->remove('class')) === null) {
					throw new TConfigurationException('appconfig_servicetype_required', $id);
				}
				$element->setParent(null);
				$this->_services[$id] = [$type, $properties->toArray(), $element];
				$this->_empty = false;
			} else {
				throw new TConfigurationException('appconfig_services_invalid', $element->getTagName());
			}
		}
	}

	/**
	 * Loads the parameters PHP array.
	 * @param array $parametersNode the parameters PHP array
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadParametersPhp($parametersNode, $configPath)
	{
		foreach ($parametersNode as $id => $parameter) {
			if (is_array($parameter)) {
				if (isset($parameter['class'])) {
					$type = $parameter['class'];
					unset($parameter['class']);
					$properties = $parameter['properties'] ?? [];
					$properties['id'] = $id;
					$this->_parameters[$id] = [$type, $properties, $parameter];
				}
			} else {
				$this->_parameters[$id] = $parameter;
			}
		}
	}

	/**
	 * Loads the parameters XML node.
	 * @param \Prado\Xml\TXmlElement $parametersNode the parameters XML node
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadParametersXml($parametersNode, $configPath)
	{
		foreach ($parametersNode->getElements() as $element) {
			if ($element->getTagName() === 'parameter') {
				$properties = $element->getAttributes();
				if (($id = $properties->remove('id')) === null) {
					throw new TConfigurationException('appconfig_parameterid_required');
				}
				if (($type = $properties->remove('class')) === null) {
					if (($value = $properties->remove('value')) === null) {
						$this->_parameters[$id] = $element;
					} else {
						$this->_parameters[$id] = $value;
					}
				} else {
					$this->_parameters[$id] = [$type, $properties->toArray(), $element];
				}
				$this->_empty = false;
			} else {
				throw new TConfigurationException('appconfig_parameters_invalid', $element->getTagName());
			}
		}
	}

	/**
	 * Loads the external PHP array.
	 * @param array $includeNode the application PHP array
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadExternalPhp($includeNode, $configPath)
	{
		foreach ($includeNode as $include) {
			$when = isset($include['when']) ? true : false;
			if (!isset($include['file'])) {
				throw new TConfigurationException('appconfig_includefile_required');
			}
			$filePath = $include['file'];
			if (isset($this->_includes[$filePath])) {
				$this->_includes[$filePath] = '(' . $this->_includes[$filePath] . ') || (' . $when . ')';
			} else {
				$$this->_includes[$filePath] = $when;
			}
			$this->_empty = false;
		}
	}

	/**
	 * Loads the external XML configurations.
	 * @param \Prado\Xml\TXmlElement $includeNode the application DOM element
	 * @param string $configPath the context path (for specifying relative paths)
	 */
	protected function loadExternalXml($includeNode, $configPath)
	{
		if (($when = $includeNode->getAttribute('when')) === null) {
			$when = true;
		}
		if (($filePath = $includeNode->getAttribute('file')) === null) {
			throw new TConfigurationException('appconfig_includefile_required');
		}
		if (isset($this->_includes[$filePath])) {
			$this->_includes[$filePath] = '(' . $this->_includes[$filePath] . ') || (' . $when . ')';
		} else {
			$this->_includes[$filePath] = $when;
		}
		$this->_empty = false;
	}

	/**
	 * Returns list of page initial property values.
	 * Each array element represents a single property with the key
	 * being the property name and the value the initial property value.
	 * @return array list of page initial property values
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * Returns list of path alias definitions.
	 * The definitions are aggregated (top-down) from configuration files along the path
	 * to the specified page. Each array element represents a single alias definition,
	 * with the key being the alias name and the value the absolute path.
	 * @return array list of path alias definitions
	 */
	public function getAliases()
	{
		return $this->_aliases;
	}

	/**
	 * Returns list of namespaces to be used.
	 * The namespaces are aggregated (top-down) from configuration files along the path
	 * to the specified page. Each array element represents a single namespace usage,
	 * with the value being the namespace to be used.
	 * @return array list of namespaces to be used
	 */
	public function getUsings()
	{
		return $this->_usings;
	}

	/**
	 * Returns list of module configurations.
	 * The module configurations are aggregated (top-down) from configuration files
	 * along the path to the specified page. Each array element represents
	 * a single module configuration, with the key being the module ID and
	 * the value the module configuration. Each module configuration is
	 * stored in terms of an array with the following content
	 * ([0]=>module type, [1]=>module properties, [2]=>complete module configuration)
	 * The module properties are an array of property values indexed by property names.
	 * The complete module configuration is a TXmlElement object representing
	 * the raw module configuration which may contain contents enclosed within
	 * module tags.
	 * @return array list of module configurations to be used
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * @return array list of service configurations
	 */
	public function getServices()
	{
		return $this->_services;
	}

	/**
	 * Returns list of parameter definitions.
	 * The parameter definitions are aggregated (top-down) from configuration files
	 * along the path to the specified page. Each array element represents
	 * a single parameter definition, with the key being the parameter ID and
	 * the value the parameter definition. A parameter definition can be either
	 * a string representing a string-typed parameter, or an array.
	 * The latter defines a component-typed parameter whose format is as follows,
	 * ([0]=>component type, [1]=>component properties)
	 * The component properties are an array of property values indexed by property names.
	 * @return array list of parameter definitions to be used
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * @return array list of external configuration files. Each element is like $filePath=>$condition
	 */
	public function getExternalConfigurations()
	{
		return $this->_includes;
	}
}
