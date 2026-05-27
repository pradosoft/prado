<?php

/**
 * TTestApplicationConfiguration class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\TApplicationConfiguration;

/**
 * TTestApplicationConfiguration
 *
 * A testable subclass of {@see TApplicationConfiguration} that allows integration
 * tests to inject pre-built module and service configurations without parsing XML
 * or PHP files.
 *
 * Each module and service configuration entry uses the same three-element tuple
 * format used internally by `TApplicationConfiguration`:
 * ```
 * [0] => class name (string)
 * [1] => property array (string name => mixed value)
 * [2] => raw config element (TXmlElement, array, or null)
 * ```
 *
 * This class is auto-loaded by {@see PradoUnitRequires} — any test that includes
 * `PradoUnitRequires.php` has access to it without an explicit `require_once`.
 *
 * Typical usage in a test:
 * ```php
 * $config = new TTestApplicationConfiguration();
 * $config->addModuleConfig('mymod', TMyModule::class, [
 *     'BoolProp' => 'true',
 *     'IntProp'  => '42',
 * ]);
 *
 * foreach ($config->getModules() as $id => [$type, $props]) {
 *     $module = Prado::createComponent($type);
 *     foreach ($props as $name => $value) {
 *         $module->setSubProperty($name, $value);
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestApplicationConfiguration extends TApplicationConfiguration
{
	/**
	 * Has registered a module configuration entry under the given ID.
	 *
	 * The tuple format matches the internal representation consumed by
	 * {@see TApplicationConfiguration::getModules()} and by
	 * `TApplication::internalLoadModule()`.
	 *
	 * @param string $id the module ID used as the configuration key.
	 * @param string $type the fully-qualified class name of the module.
	 * @param array $properties property name → value pairs to apply via
	 *   `setSubProperty()` during initialization.  Use string values when
	 *   simulating XML configuration; PHP-typed values are accepted when
	 *   simulating PHP array configuration.
	 * @param mixed $element the raw configuration element passed to `init()`;
	 *   use `null` when the module has no sub-configuration.
	 */
	public function addModuleConfig(string $id, string $type, array $properties = [], mixed $element = null): void
	{
		$this->_modules[$id] = [$type, $properties, $element];
	}

	/**
	 * Has registered a service configuration entry under the given ID.
	 *
	 * The tuple format matches the internal representation consumed by
	 * {@see TApplicationConfiguration::getServices()} and by
	 * `TApplication::startService()`.
	 *
	 * @param string $id the service ID.
	 * @param string $type the fully-qualified class name of the service.
	 * @param array $properties property name → value pairs applied via
	 *   `setSubProperty()` during service startup.
	 * @param mixed $element the raw configuration element passed to `init()`;
	 *   use `null` when the service has no sub-configuration.
	 */
	public function addServiceConfig(string $id, string $type, array $properties = [], mixed $element = null): void
	{
		$this->_services[$id] = [$type, $properties, $element];
	}

	/**
	 * Has replaced the entire module configuration map.
	 *
	 * Each entry must follow the three-element tuple format:
	 * `[$className, $propertyArray, $element]`.
	 *
	 * @param array $modules keyed by module ID.
	 */
	public function setModuleConfigs(array $modules): void
	{
		$this->_modules = $modules;
	}

	/**
	 * Has replaced the entire service configuration map.
	 *
	 * Each entry must follow the three-element tuple format:
	 * `[$className, $propertyArray, $element]`.
	 *
	 * @param array $services keyed by service ID.
	 */
	public function setServiceConfigs(array $services): void
	{
		$this->_services = $services;
	}
}
