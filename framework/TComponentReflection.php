<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */

namespace Prado;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TComponentReflection class.
 *
 * TComponentReflection provides functionalities to inspect the public/protected
 * properties, events and methods defined in a class.
 *
 * The following code displays the properties and events defined in {@link TDataGrid},
 * <code>
 *   $reflection=new TComponentReflection('TDataGrid');
 *   Prado::varDump($reflection->getProperties());
 *   Prado::varDump($reflection->getEvents());
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
 * @since 3.0
 */
class TComponentReflection extends \Prado\TComponent
{
	private $_className;
	private $_properties = [];
	private $_events = [];
	private $_methods = [];

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
			$this->_className = get_class($component);
		} else {
			throw new TInvalidDataTypeException('componentreflection_class_invalid');
		}
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
		$class = new \ReflectionClass($this->_className);
		$properties = [];
		$events = [];
		$methods = [];
		$isComponent = is_subclass_of($this->_className, 'TComponent') || strcasecmp($this->_className, 'TComponent') === 0;
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
				'comments' => $method->getDocComment()
			];
			$reserved['get' . strtolower($name)] = 1;
			$reserved['set' . strtolower($name)] = 1;
		}
		ksort($events);
		foreach ($events as $name => $method) {
			$this->_events[$name] = [
				'class' => $method->getDeclaringClass()->getName(),
				'protected' => $method->isProtected(),
				'comments' => $method->getDocComment()
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
					'comments' => $method->getDocComment()
				];
			}
		}
	}

	/**
	 * Determines the property type.
	 * This method uses the doc comment to determine the property type.
	 * @param ReflectionMethod $method * @return string the property type, '{unknown}' if type cannot be determined from comment
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
