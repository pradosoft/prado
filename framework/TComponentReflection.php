<?php

/**
 * TComponentReflection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TComponentReflection class.
 *
 * TComponentReflection provides functionalities to inspect the public/protected
 * properties, events and methods defined in a class.
 *
 * It also serves as the central cache for {@see \ReflectionClass},
 * {@see \ReflectionMethod}, and {@see \ReflectionProperty} instances across
 * the framework.  {@see \Prado\Util\Traits\TReflectionClassTrait} delegates to
 * {@see getReflectionClassByType()}.
 *
 * When reflecting methods on a {@see \Prado\TComponent} instance, attached
 * behaviors are queried so that behavior-contributed methods are discoverable.
 *
 * The following code displays the properties and events defined in {@see \Prado\Web\UI\WebControls\TDataGrid},
 * ```php
 *   $reflection=new TComponentReflection('TDataGrid');
 *   Prado::varDump($reflection->getProperties());
 *   Prado::varDump($reflection->getEvents());
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TComponentReflection extends \Prado\TComponent
{
	/** @var array<string,\ReflectionClass> Shared cache of ReflectionClass instances, keyed by lowercase FQN.  @since 4.4.0 */
	private static array $_reflection_cache = [];

	/** @var array<string,?\ReflectionMethod> Cached ReflectionMethod instances. Class-level entries use
	 *   "{class}::{method}" lowercased (shared across all instances). Behavior-contributed methods use
	 *   "{spl_object_id of behavior}::{method}" (shared across any component the behavior is attached to).
	 *   @since 4.4.0 */
	private static array $_reflection_method_cache = [];

	/** @var array<string,?\ReflectionProperty> Cached ReflectionProperty instances, keyed by
	 *   "{class}::{property}" lowercased (shared across all instances).
	 *   @since 4.4.0 */
	private static array $_reflection_property_cache = [];

	private $_className;
	private $_properties = [];
	private $_events = [];
	private $_methods = [];

	/**
	 * Returns a cached {@see \ReflectionClass} for the given class or object, or
	 * `null` if the class does not exist or cannot be reflected.
	 *
	 * Accepts either a fully-qualified class name string or an object instance
	 * (the instance's class is reflected).
	 *
	 * This method is the central reflection cache for the framework.
	 * {@see \Prado\Util\Traits\TReflectionClassTrait} delegates here rather than
	 * maintaining its own cache, so all `ReflectionClass` instances are shared
	 * in one place regardless of which code path requested them.
	 *
	 * Failed lookups are not cached — a class that does not exist now may be
	 * autoloaded later, so each call retries.
	 *
	 * @param object|string $class Fully-qualified class name or object instance to reflect.
	 * @return ?\ReflectionClass The cached instance, or `null` on failure.
	 * @since 4.4.0
	 */
	public static function getReflectionClassByType(string|object $class): ?\ReflectionClass
	{
		$className = is_object($class) ? $class::class : $class;
		$key = strtolower($className);
		if (!array_key_exists($key, self::$_reflection_cache)) {
			try {
				self::$_reflection_cache[$key] = new \ReflectionClass($className);
			} catch (\ReflectionException $e) {
				return null;
			}
		}
		return self::$_reflection_cache[$key];
	}

	/**
	 * Returns a cached {@see \ReflectionMethod} for `$class::$method`, or `null`
	 * when the method does not exist on the class or any attached behavior.
	 *
	 * Class methods are resolved via `ReflectionClass::getMethod()` and cached
	 * by class name ({className}::{method}), shared across all instances.
	 * When the class does not have the method and the parameter is a
	 * {@see \Prado\TComponent} instance with enabled behaviors, each behavior is
	 * queried via {@see \Prado\Prado::method_visible()}. Behavior-contributed
	 * methods are cached by the behavior's own `spl_object_id`, so the same
	 * behavior instance attached to different components shares its cache.
	 *
	 * @param object|string $class  Class name or instance.
	 * @param string        $method Method name.
	 * @return ?\ReflectionMethod Cached instance, or `null` when the method is
	 *   not found on the class or its behaviors.
	 * @since 4.4.0
	 */
	public static function getReflectionMethodByType(string|object $class, string $method): ?\ReflectionMethod
	{
		$className = is_object($class) ? $class::class : $class;

		// Resolve and cache class methods by class name (shared across all instances)
		$classKey = strtolower($className . '::' . $method);
		if (!array_key_exists($classKey, self::$_reflection_method_cache)) {
			$rc = self::getReflectionClassByType($className);
			if ($rc === null) {
				// Class does not exist and may be autoloaded later — do not cache
				return null;
			}
			try {
				self::$_reflection_method_cache[$classKey] = $rc->getMethod($method);
			} catch (\ReflectionException) {
				self::$_reflection_method_cache[$classKey] = null;
			}
		}

		$classMethod = self::$_reflection_method_cache[$classKey];
		if ($classMethod !== null) {
			return $classMethod;
		}

		// Class does not have the method — check behaviors on TComponent instances
		if (is_object($class) && $class instanceof TComponent && $class->getBehaviorsEnabled()) {
			foreach ($class->getBehaviors() as $behavior) {
				if (!$behavior->getEnabled()) {
					continue;
				}
				$behaviorKey = strtolower(spl_object_id($behavior) . '::' . $method);
				if (!array_key_exists($behaviorKey, self::$_reflection_method_cache)) {
					if (Prado::method_visible($behavior, $method)) {
						self::$_reflection_method_cache[$behaviorKey] = new \ReflectionMethod($behavior, $method);
					} else {
						self::$_reflection_method_cache[$behaviorKey] = null;
					}
				}
				$behaviorMethod = self::$_reflection_method_cache[$behaviorKey];
				if ($behaviorMethod !== null) {
					return $behaviorMethod;
				}
			}
		}

		return null;
	}

	/**
	 * Returns a cached {@see \ReflectionProperty} for `$class::$property`, or
	 * `null` when no property by that name is declared at any level of the
	 * class hierarchy.
	 *
	 * Walks the full class hierarchy so that private properties declared in
	 * ancestor classes are reachable — the standard `ReflectionClass::getProperty()`
	 * cannot see ancestor `private` members from a subclass reflection.
	 *
	 * Accepts either a fully-qualified class name string or an object instance.
	 * Results are cached by class name, shared across all instances.
	 *
	 * When the parameter is a {@see \Prado\TComponent} instance with enabled
	 * behaviors, a property not found in the class hierarchy is not cached as
	 * null — attached behaviors may contribute the property via a getter/setter
	 * method, which is discoverable through {@see getReflectionMethodByType()}.
	 *
	 * @param object|string $class    Class name or instance.
	 * @param string        $property Property name (without `$` prefix).
	 * @return ?\ReflectionProperty Cached instance, or `null` when the property
	 *   is not declared at any level.
	 * @since 4.4.0
	 */
	public static function getReflectionPropertyByType(string|object $class, string $property): ?\ReflectionProperty
	{
		$className = is_object($class) ? $class::class : $class;
		$key = strtolower($className . '::' . $property);

		if (array_key_exists($key, self::$_reflection_property_cache)) {
			return self::$_reflection_property_cache[$key];
		}

		$rc = self::getReflectionClassByType($className);
		if ($rc === null) {
			return null;
		}
		$found = null;
		while ($rc !== null) {
			$level = $rc->getName();
			foreach ($rc->getProperties() as $rp) {
				if ($rp->getName() === $property
					&& $rp->getDeclaringClass()->getName() === $level) {
					$found = $rp;
					break 2;
				}
			}
			$rc = $rc->getParentClass() ?: null;
		}

		if ($found !== null) {
			return self::$_reflection_property_cache[$key] = $found;
		}

		// Not found as a PHP property in the class hierarchy.
		// TComponent instances with enabled behaviors may contribute the
		// property via a getter/setter method — do not cache null so that
		// behavior changes are respected on subsequent calls.
		// (The getter/setter itself is discoverable via getReflectionMethodByType.)
		if (is_object($class) && $class instanceof TComponent && $class->getBehaviorsEnabled()) {
			return null;
		}

		return self::$_reflection_property_cache[$key] = null;
	}

	/**
	 * Constructor.
	 * @param object|string $component the component instance or the class name
	 * @throws TInvalidDataTypeException if the object is not a component
	 */
	public function __construct($component)
	{
		if (is_string($component) && class_exists($component, false)) {
			$this->_className = $component;
		} elseif (is_object($component)) {
			$this->_className = $component::class;
		} else {
			throw new TInvalidDataTypeException('componentreflection_class_invalid');
		}
		parent::__construct();
		$this->reflect();
	}

	private function isPropertyMethod($method)
	{
		$methodName = $method->getName();
		return $method->getNumberOfRequiredParameters() === 0
				&& strncasecmp($methodName, 'get', 3) === 0
				&& isset($methodName[3]);
	}

	private function isEventMethod($method)
	{
		$methodName = $method->getName();
		return strncasecmp($methodName, 'on', 2) === 0
				&& isset($methodName[2]);
	}

	private function reflect()
	{
		$class = self::getReflectionClassByType($this->getClassName());
		$properties = [];
		$events = [];
		$methods = [];
		$isComponent = is_a($this->getClassName(), TComponent::class, true);
		foreach ($class->getMethods() as $method) {
			if ($method->isPublic() || $method->isProtected()) {
				$methodName = $method->getName();
				if (!$method->isStatic() && $isComponent) {
					if ($this->isPropertyMethod($method)) {
						$properties[substr($methodName, 3)] = $method;
					} elseif ($this->isEventMethod($method)) {
						$methodName[0] = 'O';
						$events[$methodName] = $method;
					}
				}
				if (strncmp($methodName, '__', 2) !== 0) {
					$methods[$methodName] = $method;
				}
			}
		}
		$reserved = [];
		ksort($properties);
		foreach ($properties as $name => $method) {
			$this->_properties[$name] = [
				'type' => $this->determinePropertyType($method),
				'readonly' => !$class->hasMethod('set' . $name),
				'protected' => $method->isProtected(),
				'class' => $method->getDeclaringClass()->getName(),
				'comments' => $method->getDocComment(),
			];
			$reserved['get' . strtolower($name)] = 1;
			$reserved['set' . strtolower($name)] = 1;
		}
		ksort($events);
		foreach ($events as $name => $method) {
			$this->_events[$name] = [
				'class' => $method->getDeclaringClass()->getName(),
				'protected' => $method->isProtected(),
				'comments' => $method->getDocComment(),
			];
			$reserved[strtolower($name)] = 1;
		}
		ksort($methods);
		foreach ($methods as $name => $method) {
			if (!isset($reserved[strtolower($name)])) {
				$this->_methods[$name] = [
					'class' => $method->getDeclaringClass()->getName(),
					'protected' => $method->isProtected(),
					'static' => $method->isStatic(),
					'comments' => $method->getDocComment(),
				];
			}
		}
	}

	/**
	 * Determines the property type.
	 * This method uses the doc comment to determine the property type.
	 * @param \ReflectionMethod $method * @return string the property type, '{unknown}' if type cannot be determined from comment
	 */
	protected function determinePropertyType($method)
	{
		$comment = $method->getDocComment();
		if (preg_match('/@return\\s+(.*?)\\s+/', $comment, $matches)) {
			return $matches[1];
		} else {
			return '{unknown}';
		}
	}

	/**
	 * @return string class name of the component
	 */
	public function getClassName()
	{
		return $this->_className;
	}

	/**
	 * @return array list of component properties. Array keys are property names.
	 * Each array element is of the following structure:
	 * [type]=>property type,
	 * [readonly]=>whether the property is read-only,
	 * [protected]=>whether the method is protected or not
	 * [class]=>the class where the property is inherited from,
	 * [comments]=>comments	associated with the property.
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * @return array list of component events. Array keys are event names.
	 * Each array element is of the following structure:
	 * [protected]=>whether the event is protected or not
	 * [class]=>the class where the event is inherited from.
	 * [comments]=>comments associated with the event.
	 */
	public function getEvents()
	{
		return $this->_events;
	}

	/**
	 * @return array list of public/protected methods. Array keys are method names.
	 * Each array element is of the following structure:
	 * [protected]=>whether the method is protected or not
	 * [static]=>whether the method is static or not
	 * [class]=>the class where the property is inherited from,
	 * [comments]=>comments associated with the event.
	 */
	public function getMethods()
	{
		return $this->_methods;
	}
}
