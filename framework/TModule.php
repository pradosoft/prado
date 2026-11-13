<?php

/**
 * TModule class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TConfigurationException;
use Prado\Xml\TXmlElement;

/**
 * TModule class
 *
 * TModule implements {@see IModule} and is the base class for all application modules.
 *
 * ## Initialization Phases
 *
 * {@see TApplication} initializes modules in four ordered phases:
 *
 * 1. **Instantiate & configure** — module created; configuration properties applied.
 * 2. **dyPreInit** — dispatched to attached behaviors before `init()`.
 * 3. **init** — {@see init()} called; the primary initialization point.
 * 4. **dyPostInit** — dispatched to attached behaviors after `init()` returns.
 *
 * To dispatch `dyPreInit` or `dyPostInit` to behaviors from a TModule subclass with
 * custom `dyPreInit` or `dyPostInit` methods, calling `$this->callBehaviorsMethod(...)`
 * is required.  If it is not custom dynamic event, the dynamic event is passed
 * through to behaviors automatically.
 * For example:
 * ```php
 * class MyModule extends TModule {
 *      public function dyPreInit($config) {
 *          ...
 *          $this->callBehaviorsMethod('dyPreInit', $return, $config);
 *      }
 *  }
 * ```
 *
 * ## Child Elements
 *
 * A module creates a component it alone uses from a child element of its configuration with
 * {@see createConfigComponent()}, or each of several with {@see getConfigElements()} and
 * {@see createElementComponent()}.  The `class` attribute names the class, the other
 * attributes are its properties, and a component that is a module is initialized with its
 * element.  The PHP form is the tag's key holding `class` and `properties`, or a class name.
 * ```xml
 * <module id="mymodule" class="MyModule">
 *     <mutex class="Prado\Util\Mutex\TDbMutex" ConnectionID="db" />
 * </module>
 * ```
 * ```php
 * $mutex = $this->createConfigComponent($config, 'mutex', \Prado\Util\Mutex\IMutex::class);
 * ```
 *
 * ## Dependencies
 *
 * Implement {@see IModuleDependency} on the module or on an attached behavior to
 * declare dependencies. Both sources are re-evaluated before each sort pass.
 *
 * ```php
 * class TMyModule extends TModule implements IModuleDependency {
 *     public function getModuleDependencies(bool $isPreInit = false): ?array {
 *         return ['db', 'cache'];
 *     }
 * }
 * ```
 *
 * After both sources have been collected, {@see TApplication::collectModuleDependencies()}
 * dispatches `dyFilterDependencies` to all behaviors attached to the module, passing
 * the accumulated dependency map and returning the (possibly modified) map. Behaviors
 * may add, remove, or replace entries before the map is used for topological sorting.
 *
 * When multiple behaviors implement `dyFilterDependencies`, the `TCallChain` dispatch
 * calls each behavior with the **original** map and returns the **last** behavior's
 * return value — unless each behavior accepts the `$callchain` parameter and explicitly
 * forwards its modified map via `$callchain->dyFilterDependencies($deps)`. Use the
 * callchain pattern when multiple behaviors must each see the previous behavior's output:
 *
 * ```php
 * class TMyDependencyBehavior extends TBehavior {
 *     public function dyFilterDependencies(array $deps, \Prado\Util\TCallChain $callchain): array {
 *         unset($deps['moduleId']);   // drop an optional dependency
 *         $deps['otherModule'] = ['id' => 'otherModule', 'required' => true];
 *         return $callchain->dyFilterDependencies($deps);  // forward to next behavior
 *     }
 * }
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method void dyPreInit(mixed $config) Dispatched by {@see TApplication} before {@see init()} runs.
 * @method void dyInit(mixed $config) Dispatched from within {@see init()} to all attached behaviors.
 * @method void dyPostInit(mixed $config) Dispatched by {@see TApplication} after {@see init()} returns.
 * @method array dyFilterDependencies(array $deps) Filters the collected dependency map before topological sorting. Behaviors may add, remove, or replace entries; the (possibly modified) map is returned.
 */
abstract class TModule extends \Prado\TApplicationComponent implements IModule
{
	/**
	 * @var string module id
	 */
	private $_id;

	/**
	 * Initializes the module. Required by {@see IModule}; invoked by the application.
	 * Raises {@see dyInit()} on all attached behaviors.
	 * @param null|array|\Prado\Xml\TXmlElement $config Module configuration element.
	 *  `TXmlElement` for XML configuration, `array` for PHP configuration, and null
	 *   when invoked without configuration.
	 */
	public function init($config)
	{
		$this->dyInit($config);
	}

	/**
	 * Returns the module ID assigned by the application.
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * Sets the module ID. Called by the application during configuration.
	 * @param string $value id of this module
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}

	/**
	 * Returns the child elements of a module configuration with a tag.  An XML configuration
	 * gives its direct child elements.  A PHP configuration gives the value of the tag's key as
	 * an element: an array with `class` and `properties`, or a class name alone; with
	 * `$multiple`, the key also holds a list of elements.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration
	 * @param string $tag the element tag name, and the PHP key
	 * @param bool $multiple whether the configuration may hold several elements with the tag
	 * @throws TConfigurationException when a PHP element is not an array or a class name
	 * @return array the XML elements or PHP element arrays, in order
	 * @since 4.4.0
	 */
	protected function getConfigElements($config, string $tag, bool $multiple = false): array
	{
		if ($config instanceof TXmlElement) {
			if ($multiple) {
				return $config->getElementsByTagName($tag)->toArray();
			}
			return ($element = $config->getElementByTagName($tag)) === null ? [] : [$element];
		}
		if (!is_array($config) || !isset($config[$tag])) {
			return [];
		}
		$elements = $config[$tag];
		if (!$multiple || !is_array($elements) || !array_is_list($elements)) {
			$elements = [$elements];
		}
		foreach ($elements as $i => $element) {
			if (is_string($element)) {
				$elements[$i] = ['class' => $element];
			} elseif (!is_array($element)) {
				throw new TConfigurationException('module_config_class_required', static::class, $tag);
			}
		}
		return $elements;
	}

	/**
	 * Creates the component of the first child element of a module configuration with a tag.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration
	 * @param string $tag the element tag name, and the PHP key
	 * @param string $type the class or interface the component must be
	 * @param ?string $error the message key when the component is not `$type`, with the class
	 *   as its parameter; null for `module_config_component_invalid`
	 * @throws TConfigurationException when the element has no class, or the component is not `$type`
	 * @return ?object the component, or null when the configuration has no such element
	 * @since 4.4.0
	 */
	protected function createConfigComponent($config, string $tag, string $type, ?string $error = null): ?object
	{
		$elements = $this->getConfigElements($config, $tag);
		return $elements ? $this->createElementComponent($elements[0], $tag, $type, $error) : null;
	}

	/**
	 * Creates the component of one configuration element: the `class` attribute names the
	 * class, the other attributes are its properties, and a module is initialized with the
	 * element.  A PHP element is an array with `class` and `properties`.  A component given
	 * properties is a {@see TComponent}; one without may be any class.
	 * @param array|\Prado\Xml\TXmlElement $element the configuration element
	 * @param string $tag the element tag name, and the PHP key
	 * @param string $type the class or interface the component must be
	 * @param ?string $error the message key when the component is not `$type`, with the class
	 *   as its parameter; null for `module_config_component_invalid`
	 * @throws TConfigurationException when the element has no class, its PHP properties are not
	 *   an array, the component is not `$type`, or the component is given properties and is not a
	 *   {@see TComponent}
	 * @return object the component
	 * @since 4.4.0
	 */
	protected function createElementComponent($element, string $tag, string $type, ?string $error = null): object
	{
		if ($element instanceof TXmlElement) {
			$properties = $element->getAttributes()->toArray();
			$class = $properties['class'] ?? null;
			unset($properties['class']);
		} else {
			$class = $element['class'] ?? null;
			$properties = $element['properties'] ?? [];
		}
		if (!is_string($class) || $class === '') {
			throw new TConfigurationException('module_config_class_required', static::class, $tag);
		}
		if (!is_iterable($properties)) {
			throw new TConfigurationException('module_config_properties_invalid', static::class, $tag);
		}
		$component = Prado::createComponent($class);
		if (!($component instanceof $type)) {
			if ($error !== null) {
				throw new TConfigurationException($error, $class);
			}
			throw new TConfigurationException('module_config_component_invalid', static::class, $tag, $class, $type);
		}
		if (!is_array($properties)) {
			$properties = iterator_to_array($properties);
		}
		if ($properties) {
			if (!($component instanceof TComponent)) {
				throw new TConfigurationException('module_config_properties_unsupported', static::class, $tag, $class);
			}
			$component->setSubProperties($properties);
		}
		if ($component instanceof IModule) {
			$component->init($element);
		}
		return $component;
	}
}
