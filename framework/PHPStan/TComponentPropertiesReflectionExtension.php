<?php

/**
 * TComponentPropertiesReflectionExtension class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 */

declare(strict_types=1);

namespace Prado\PHPStan;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use Prado\TComponent;

/**
 * TComponentPropertiesReflectionExtension class.
 *
 * This is a PHPStan Extension that teaches PHPStan about PRADO's virtual property
 * system.  In PRADO, any pair of `get{Name}()` / `set{Name}()` methods on a
 * {@see TComponent} subclass defines a virtual property that can be read or written
 * via PHP's magic `__get` / `__set`:
 *
 * ```php
 *		$component->Text       // calls $component->getText()
 *		$component->Text = 'x' // calls $component->setText('x')
 * ```
 *
 * Without this extension PHPStan reports "Access to an undefined property" for
 * every such access.  With it, PHPStan resolves the getter return type and setter
 * parameter type automatically, giving full type-checked property access.
 *
 * The `getjs` / `setjs` variants used for JavaScript-aware properties are also
 * recognised.
 *
 * This class helps PHPStan understand PRADO's virtual property convention and
 * validate PRADO projects.
 * To use this class, add the following PHPStan configuration to a project:
 * ```neon
 * services:
 *		-
 *			class: Prado\PHPStan\TComponentPropertiesReflectionExtension
 *			tags:
 *				- phpstan.broker.propertiesClassReflectionExtension
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TComponentPropertiesReflectionExtension implements PropertiesClassReflectionExtension
{
	/**
	 * Returns true when the given class is a TComponent subclass that exposes a
	 * virtual property named $propertyName via a getter or setter method.
	 *
	 * Both the standard `get{Name}` / `set{Name}` convention and the JavaScript
	 * `getjs{Name}` / `setjs{Name}` variant are checked.  Method lookup is
	 * case-insensitive (matching PHP's own method resolution).
	 * @param ClassReflection $classReflection
	 * @param string $propertyName
	 */
	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		if (!$classReflection->is(TComponent::class)) {
			return false;
		}
		return $classReflection->hasMethod('get' . $propertyName)
			|| $classReflection->hasMethod('set' . $propertyName)
			|| $classReflection->hasMethod('getjs' . $propertyName)
			|| $classReflection->hasMethod('setjs' . $propertyName);
	}

	/**
	 * Returns a {@see TComponentPropertyReflection} that describes the virtual
	 * property.  The getter is preferred over its JS variant for the readable
	 * type; the setter is preferred over its JS variant for the writable type.
	 * @param ClassReflection $classReflection
	 * @param string $propertyName
	 */
	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		$getter = null;
		$setter = null;

		if ($classReflection->hasMethod('get' . $propertyName)) {
			$getter = $classReflection->getMethod('get' . $propertyName, new OutOfClassScope());
		} elseif ($classReflection->hasMethod('getjs' . $propertyName)) {
			$getter = $classReflection->getMethod('getjs' . $propertyName, new OutOfClassScope());
		}

		if ($classReflection->hasMethod('set' . $propertyName)) {
			$setter = $classReflection->getMethod('set' . $propertyName, new OutOfClassScope());
		} elseif ($classReflection->hasMethod('setjs' . $propertyName)) {
			$setter = $classReflection->getMethod('setjs' . $propertyName, new OutOfClassScope());
		}

		return new TComponentPropertyReflection($classReflection, $getter, $setter);
	}
}
