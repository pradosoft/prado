<?php

/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Util\TComposer;
use Prado\Util\Traits\TModuleConfigurationFileTrait;
use Prado\Xml\TXmlDocument;

/**
 * TApplicationConfiguration class
 *
 * TApplicationConfiguration parses an application configuration file, either XML
 * (`application.xml`) or PHP (an array returned from `application.php`), into a
 * structured, cache-safe form that {@see TApplication::applyConfiguration()}
 * consumes. Both formats describe the same logical sections:
 *
 * - **Application properties** are direct property assignments on
 *   {@see TApplication} (for example `Mode`, `DefaultModule`).
 * - **Paths** are `aliases` (named directory shortcuts resolved via
 *   {@see \Prado\Prado::setPathOfAlias()}) and `using` namespaces forwarded to
 *   {@see \Prado\Prado::using()}.
 * - **Includes** are recursive include entries, optionally guarded by a `when`
 *   expression evaluated against the live application.
 * - **Error messages** are extra exception-message files declared by `<errorMessage>`
 *   tags (XML), an `errormessages` key (PHP), or any installed Composer extension's
 *   `extra.prado.error-messages` (system-wide). Their absolute paths are exposed via
 *   {@see getErrorMessages()}.
 * - **Class map** is the Prado3-namespace class map (class name => PHP FQN) declared
 *   by any installed Composer extension's `extra.prado.class-map` (system-wide, data
 *   only), exposed via {@see getClassMap()}.
 * - **Modules** are declarative module registrations, instantiated in dependency
 *   order during {@see TApplication::applyConfiguration()}. A module id that is a
 *   Composer package name (`vendor/package`) resolves its class from the package's
 *   bootstrap declaration via {@see getComposerExtensionClass()}.
 * - **Services** are service-class registrations stored as three-element tuples
 *   `[class, properties, configElement]`. The body (`configElement`) is held
 *   lazily until {@see TApplication::startService()} resolves a request; at that
 *   point a fresh `TApplicationConfiguration` parses the body and is applied
 *   recursively with `$withinService = true`, letting the service declare its own
 *   modules, parameters, and includes that do not affect the application until the
 *   service runs.
 * - **Parameters** are named values exposed via {@see TApplication::getParameters()},
 *   either bare scalars or component-typed entries.
 *
 * ### XML configuration
 *
 * ```xml
 * <application id="my-app" Mode="Performance">
 *
 *   <include file="Application.Common.shared-config" when="$this->getMode() == 'Performance'"/>
 *
 *   <paths>
 *     <alias id="Common" path="../common"/>
 *     <using namespace="Application.Common.*"/>
 *   </paths>
 *
 *   <errorMessage file="Application.messages.app-messages"/>
 *
 *   <parameters>
 *     <parameter id="SiteName" value="My Site"/>
 *     <parameter id="Mailer" class="MyMailer" Host="smtp.example.com"/>
 *   </parameters>
 *
 *   <modules>
 *     <module id="cache" class="Prado\Caching\TFileCache" Directory="runtime/cache"/>
 *   </modules>
 *
 *   <services>
 *     <service id="page" class="Prado\Web\Services\TPageService" DefaultPage="Home">
 *       <!-- The body below is held lazily until the "page" service starts. -->
 *       <parameters>
 *         <parameter id="PageTitle" value="My Site"/>
 *       </parameters>
 *       <modules>
 *         <module id="pageThemes" class="Prado\Web\UI\TThemeManager"/>
 *       </modules>
 *     </service>
 *   </services>
 *
 * </application>
 * ```
 *
 * ### PHP configuration
 *
 * Equivalent shape returned from `application.php`. Top-level keys mirror the XML
 * element names.
 *
 * ```php
 * return [
 *   'application' => ['Mode' => 'Performance'],
 *
 *   'includes' => [
 *     ['file' => 'Application.Common.shared-config', 'when' => '$this->getMode() == "Performance"'],
 *   ],
 *
 *   'paths' => [
 *     'aliases' => ['Common' => '../common'],
 *     'using'   => ['Application.Common.*'],
 *   ],
 *
 *   'errormessages' => ['Application.messages.app-messages'],
 *
 *   'parameters' => [
 *     'SiteName' => 'My Site',
 *     'Mailer'   => ['class' => 'MyMailer', 'properties' => ['Host' => 'smtp.example.com']],
 *   ],
 *
 *   'modules' => [
 *     'cache' => ['class' => Prado\Caching\TFileCache::class,
 *                 'properties' => ['Directory' => 'runtime/cache']],
 *   ],
 *
 *   'services' => [
 *     'page' => ['class' => Prado\Web\Services\TPageService::class,
 *                'properties' => ['DefaultPage' => 'Home'],
 *                // Held verbatim until the service starts, then parsed
 *                // by a fresh TApplicationConfiguration:
 *                'modules'    => [ ... ],
 *                'parameters' => [ ... ]],
 *   ],
 * ];
 * ```
 *
 * ### Composer extension packages
 *
 * An installed Composer package that declares an `extra.prado` section is a Prado
 * extension. Its fields:
 *
 * | Field            | Value                                      | Effect |
 * |------------------|--------------------------------------------|--------|
 * | `bootstrap`      | bootstrap {@see \Prado\TModule} class name | Opt-in module by id |
 * | `error-messages` | path or array of paths                     | System-wide message files |
 * | `class-map`      | map, JSON file, or array of both           | System-wide class map (data) |
 *
 * `bootstrap` runs as a module only when `<module id="vendor/package"/>` appears in
 * `<modules>`; {@see getComposerExtensionClass()} resolves the class, falling back to the
 * legacy top-level `extra.bootstrap` ({@see getComposerExtensionClassLegacy()}, deprecated).
 * Giving such a module an explicit `class` is an error, since the class comes from the package.
 *
 * `error-messages` and `class-map` load for every installed extension, independent of the
 * bootstrap module, and {@see TApplication::applyConfiguration()} applies them before any
 * module initializes.
 *
 * `error-messages` is a package-relative path (or array of paths) to message files, resolved
 * to absolute via {@see \Prado\Util\TComposer::getPackagePath()} and registered with
 * {@see \Prado\Exceptions\TException::addMessageFile()} (exposed by {@see getErrorMessages()}).
 *
 * `class-map` is data, never executed. A single value is treated as a one-element array; in
 * the array a non-numeric key is a Prado3 class name mapped to its PHP FQN, and a numeric key
 * (a list entry) is a package-relative path to a JSON file holding such a map. The merged
 * map is exposed via {@see getClassMap()} and registered with {@see \Prado\Prado::registerClassMap()};
 * the first declaration of a class name wins.
 *
 * ```json
 * {
 *   "name": "acme/prado-extension",
 *   "extra": {
 *     "bootstrap":     "OldKeyIs\\Deprecated\\TBootstrapModule",
 *     "prado": {
 *       "bootstrap":      "Acme\\PradoExtension\\TBootstrapModule",
 *       "error-messages": "messages/acme-messages.txt",
 *       "class-map":      { "TAcmeWidget": "Acme\\Widget\\TAcmeWidget", "0": "config/more-classes.json" }
 *     }
 *   }
 * }
 * ```
 *
 * ```xml
 * <!-- Using the extension's bootstrap module in the application configuration.
 *      Its error-messages and class-map load system-wide even without this entry. -->
 * <modules>
 *   <module id="acme/prado-extension"/>
 * </modules>
 * ```
 *
 * ### How a parsed configuration is applied
 *
 * {@see TApplication::applyConfiguration()} consumes the parsed object in this
 * order: path aliases and usings → class map ({@see \Prado\Prado::registerClassMap()})
 * and error-message files ({@see \Prado\Exceptions\TException::addMessageFile()}) →
 * application properties (skipped under `$withinService`) → service registrations →
 * parameters → modules (instantiate → sort by {@see \Prado\Util\IModuleDependency}
 * → `dyPreInit` → `init` → `dyPostInit`) → recursive includes. Services do not
 * start here; each service's
 * `configElement` is reparsed and applied via a fresh `TApplicationConfiguration`
 * only when {@see TApplication::startService()} resolves a request.
 *
 * ### File I/O
 *
 * {@see loadFromFile()} delegates the actual read to {@see readConfigurationFile()},
 * which subclasses override to add formats (JSON, YAML, in-memory test payloads).
 * When the configuration type is `null`, the file extension picks PHP vs. XML.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> Composer Bootstrap, error messages,
 *		class maps
 * @since 3.0
 */
class TApplicationConfiguration extends \Prado\TApplicationComponent
{
	use TModuleConfigurationFileTrait;
	/**
	 * Name of the Composer field naming a package's Prado bootstrap module class,
	 * read from `extra.prado.bootstrap` (preferred) or the legacy `extra.bootstrap`.
	 */
	public const COMPOSER_EXTRA_CLASS = 'bootstrap';
	/**
	 * Name of the `extra.prado` field naming a package's exception message
	 * file(s) (a string or array of paths relative to the package), registered
	 * via {@see \Prado\Exceptions\TException::addMessageFile()}.
	 * @since 4.4.0
	 */
	public const COMPOSER_EXTRA_ERRORMESSAGES = 'error-messages';
	/**
	 * Name of the `extra.prado` field carrying a package's Prado3-namespace class map
	 * (data, never executed). The value is a class name => PHP FQN map, a package-relative
	 * path to a JSON file holding such a map, or an array mixing both (string keys are
	 * class names, integer keys are JSON file paths). Merged into the autoloader via
	 * {@see \Prado\Prado::registerClassMap()}.
	 * @since 4.4.0
	 */
	public const COMPOSER_EXTRA_CLASSMAP = 'class-map';
	/**
	 * @var array list of included configurations
	 */
	private $_includes = [];
	/**
	 * @var array list of path aliases, indexed by alias names
	 */
	private $_aliases = [];
	/**
	 * @var array list of namespaces to be used
	 */
	private $_usings = [];
	/**
	 * @var array<string, string> merged Prado3-namespace class map (class name =>
	 *   PHP FQN) collected from installed extensions, applied via
	 *   {@see \Prado\Prado::registerClassMap()} at apply time.
	 * @since 4.4.0
	 */
	private $_classMap = [];
	/**
	 * @var string[] absolute paths of extra exception message files to register
	 *   via {@see \Prado\Exceptions\TException::addMessageFile()} at apply time.
	 * @since 4.4.0
	 */
	private $_errorMessages = [];
	/**
	 * @var array list of application initial property values, indexed by property names
	 */
	private $_properties = [];
	/**
	 * @var array list of service configurations
	 */
	private $_services = [];
	/**
	 * @var array list of parameters
	 */
	private $_parameters = [];
	/**
	 * @var array list of module configurations
	 */
	private $_modules = [];
	/**
	 * @var bool whether this configuration contains actual stuff
	 */
	private $_empty = true;
	/**
	 * @var ?string configuration type used while parsing ({@see TApplication::CONFIG_TYPE_PHP}
	 *   or {@see TApplication::CONFIG_TYPE_XML}); `null` defers to the application
	 *   then to extension-based auto-detect.
	 * @since 4.4.0
	 */
	private $_configurationType;

	/**
	 * @return ?string the configuration type used while parsing, or `null` when
	 *   not set (defers to the application then to extension auto-detect).
	 * @since 4.4.0
	 */
	public function getConfigurationType(): ?string
	{
		return $this->_configurationType;
	}

	/**
	 * Sets the configuration type used while parsing, the first fallback for a
	 * `null` `$type` in {@see loadFromFile()}.
	 * @param ?string $value the configuration type ({@see TApplication::CONFIG_TYPE_PHP}
	 *   or {@see TApplication::CONFIG_TYPE_XML}), or `null` to defer.
	 * @since 4.4.0
	 */
	public function setConfigurationType(?string $value): void
	{
		$this->_configurationType = $value;
	}

	/**
	 * Parses the application configuration file. Delegates the raw read to
	 * {@see readConfigurationFile()} and dispatches the result by shape:
	 * `array` → {@see loadFromPhp()}, {@see TXmlDocument} → {@see loadFromXml()},
	 * `null` → no-op.
	 * @param string $fname configuration file name.
	 * @param ?string $type configuration type ({@see TApplication::CONFIG_TYPE_PHP}
	 *   or {@see TApplication::CONFIG_TYPE_XML}). When `null`, falls back to
	 *   {@see getConfigurationType()}, then the active application's type, then
	 *   extension-based auto-detect.
	 * @throws TConfigurationException if there is any parsing error
	 */
	public function loadFromFile($fname, ?string $type = null)
	{
		$type ??= $this->getConfigurationType();
		$type ??= $this->getApplication()?->getConfigurationType();
		$content = $this->readConfigurationFile($type, $fname);

		if ($content instanceof TXmlDocument) {
			$this->loadFromXml($content, dirname($fname));
		} elseif (is_array($content)) {
			$this->loadFromPhp($content, dirname($fname));
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
			$this->loadExternalPhp($config['includes'], $configPath);
		}

		if (isset($config['errormessages'])) {
			foreach ((array) $config['errormessages'] as $file) {
				if (is_string($file) && $file !== '') {
					$this->addErrorMessageFile($file, $configPath);
					$this->_empty = false;
				}
			}
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
				case 'errorMessage':
					$file = $element->getAttribute('file');
					if ($file !== null && $file !== '') {
						$this->addErrorMessageFile($file, $configPath);
						$this->_empty = false;
					}
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
	 * Returns the bootstrap {@see \Prado\TModule} class a Composer package declares.
	 * Read from `extra.prado.bootstrap` (preferred); falls back to the legacy
	 * `extra.bootstrap` via {@see getComposerExtensionClassLegacy()}. Used to
	 * resolve a module id that is a Composer package name (`vendor/package`).
	 * @param string $name the Composer package name, for example `vendor/package`.
	 * @return null|string the bootstrap class, or null when the package declares none.
	 * @since 4.2.0
	 */
	public function getComposerExtensionClass($name)
	{
		$class = TComposer::getPradoExtra($name, self::COMPOSER_EXTRA_CLASS);
		if (!is_string($class)) {
			$class = $this->getComposerExtensionClassLegacy($name);
		}
		return is_string($class) ? $class : null;
	}

	/**
	 * Returns the bootstrap class from the legacy un-nested `extra.bootstrap`
	 * Composer field.
	 * @param string $name the Composer package name, for example `vendor/package`.
	 * @return null|string the bootstrap class, or null when the field is absent.
	 * @deprecated 4.4.0 declare the bootstrap under `extra.prado.bootstrap` instead.
	 * @since 4.4.0
	 */
	protected function getComposerExtensionClassLegacy($name)
	{
		$class = TComposer::getExtra($name, self::COMPOSER_EXTRA_CLASS);
		return is_string($class) ? $class : null;
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
			$when = $include['when'] ?? true;
			if (!isset($include['file'])) {
				throw new TConfigurationException('appconfig_includefile_required');
			}
			$filePath = $include['file'];
			if (isset($this->_includes[$filePath])) {
				$this->_includes[$filePath] = '(' . $this->_includes[$filePath] . ') || (' . $when . ')';
			} else {
				$this->_includes[$filePath] = $when;
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

	/**
	 * Returns the absolute paths of extra exception message files declared by the
	 * configuration (`<errorMessage>` tags) and by every installed Composer extension
	 * (`extra.prado.error-messages`, captured by {@see captureComposerExtensions()}).
	 * {@see TApplication} registers each via
	 * {@see \Prado\Exceptions\TException::addMessageFile()}.
	 * @return string[] absolute message-file paths in declaration order.
	 * @since 4.4.0
	 */
	public function getErrorMessages()
	{
		return $this->_errorMessages;
	}

	/**
	 * Returns the merged Prado3-namespace class map declared by every installed
	 * Composer extension (`extra.prado.class-map`, captured by
	 * {@see captureComposerExtensions()}). {@see TApplication} merges it into the
	 * autoloader via {@see \Prado\Prado::registerClassMap()}.
	 * @return array<string, string> class name => PHP FQN, first declaration winning.
	 * @since 4.4.0
	 */
	public function getClassMap()
	{
		return $this->_classMap;
	}

	/**
	 * Adds an extra exception message file by namespace or path, resolved
	 * relative to `$configPath` when not a Prado namespace. Deduplicated.
	 * @param string $file the message file namespace or path.
	 * @param string $configPath the configuration directory for relative paths.
	 * @since 4.4.0
	 */
	protected function addErrorMessageFile(string $file, string $configPath): void
	{
		$path = Prado::getPathOfNamespace($file);
		if ($path === null || !is_file($path)) {
			$path = (preg_match('#^(?:[a-zA-Z]:)?[/\\\\]#', $file) ? $file : $configPath . DIRECTORY_SEPARATOR . $file);
		}
		if (!in_array($path, $this->_errorMessages, true)) {
			$this->_errorMessages[] = $path;
		}
	}

	/**
	 * Captures the error-message files and class map of every installed Prado
	 * Composer extension, independent of the application configuration. A package
	 * is a Prado extension when it declares an `extra.prado` section. Each such
	 * package's `extra.prado.error-messages` and `extra.prado.class-map` are recorded
	 * for {@see TApplication::applyConfiguration()} to register before any module
	 * initializes. The presence of an extension's bootstrap module in the
	 * configuration is a separate opt-in for running that module; it does not
	 * affect these system-wide entries.
	 *
	 * Intended to run once for the top-level application configuration, so the
	 * captured data is serialized with the configuration cache.
	 * {@see TApplication::initApplication()} invalidates that cache when any Composer
	 * `installed.json` changes (via {@see \Prado\Util\TComposer::getInstalledManifestsTime()}),
	 * so installing, updating, or removing an extension is picked up on the next request.
	 * @since 4.4.0
	 */
	public function captureComposerExtensions(): void
	{
		foreach (array_keys(TComposer::getInstalledPackages()) as $name) {
			if (TComposer::getPradoExtra($name) !== null) {
				$this->captureComposerExtensionExtras($name);
			}
		}
	}

	/**
	 * Captures the `extra.prado` error-message files and class map a single Prado
	 * Composer extension declares, resolving package-relative paths against the
	 * package directory ({@see \Prado\Util\TComposer::getPackagePath()}). The
	 * `error-messages` field is a path or array of paths, deduplicated to absolute
	 * paths. The `class-map` field is a class name => PHP FQN map, a JSON file path,
	 * or an array mixing both ({@see mergeClassMap()}, {@see readClassMapFile()}). A
	 * package that is not installed (no resolvable path) is skipped.
	 * @param string $name the Composer package name (`vendor/package`).
	 * @since 4.4.0
	 */
	protected function captureComposerExtensionExtras(string $name): void
	{
		$base = TComposer::getPackagePath($name);
		if ($base === null) {
			return;
		}
		foreach ((array) (TComposer::getPradoExtra($name, self::COMPOSER_EXTRA_ERRORMESSAGES) ?? []) as $file) {
			if (is_string($file) && $file !== '') {
				$path = $base . DIRECTORY_SEPARATOR . $file;
				if (!in_array($path, $this->_errorMessages, true)) {
					$this->_errorMessages[] = $path;
					$this->_empty = false;
				}
			}
		}
		foreach ((array) (TComposer::getPradoExtra($name, self::COMPOSER_EXTRA_CLASSMAP) ?? []) as $key => $value) {
			if (is_numeric($key)) {
				if (is_string($value) && $value !== '') {
					$this->mergeClassMap($this->readClassMapFile($base . DIRECTORY_SEPARATOR . $value));
				}
			} elseif (is_string($value)) {
				$this->mergeClassMap([$key => $value]);
			}
		}
	}

	/**
	 * Merges class name => PHP FQN entries into the collected class map. Only
	 * non-empty string keys and values are kept, and the first declaration of a
	 * class name wins, so neither an extension file nor a later extension can shadow
	 * an earlier mapping.
	 * @param array $map the class name => PHP FQN entries to merge.
	 * @since 4.4.0
	 */
	protected function mergeClassMap(array $map): void
	{
		foreach ($map as $class => $fqn) {
			if (is_string($class) && $class !== '' && is_string($fqn) && $fqn !== '' && !isset($this->_classMap[$class])) {
				$this->_classMap[$class] = $fqn;
				$this->_empty = false;
			}
		}
	}

	/**
	 * Reads a JSON class-map file into a class name => PHP FQN array. The file is
	 * parsed as data with no code execution; a missing file or content that is not a
	 * JSON object yields an empty map. Invalid entries are filtered by
	 * {@see mergeClassMap()}.
	 * @param string $path the absolute path to the JSON class-map file.
	 * @return array the decoded class map, or an empty array.
	 * @since 4.4.0
	 */
	protected function readClassMapFile(string $path): array
	{
		$contents = $this->getFileContents($path);
		if (!is_string($contents)) {
			return [];
		}
		$data = json_decode($contents, true);
		return is_array($data) ? $data : [];
	}

	/**
	 * Reads a file's contents, the seam isolating filesystem access from
	 * {@see readClassMapFile()} so tests can supply class-map JSON without a file on
	 * disk. A missing or unreadable file yields `false`.
	 * @param string $path the absolute file path.
	 * @return false|string the file contents, or `false` when the file is absent or unreadable.
	 * @since 4.4.0
	 */
	protected function getFileContents(string $path): string|false
	{
		return is_file($path) ? file_get_contents($path) : false;
	}
}
