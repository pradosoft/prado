<?php

/**
 * DynamicMethodsClassReflectionExtension class
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 */

namespace Prado\PHPStan;

use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;

/**
 * DynamicMethodsClassReflectionExtension class.
 *
 * This is a PHPStan Extension which tells PHPStan that "dynamic methods"
 * and global events, with the prefixes "dy" or "fx", are present
 * regardless of parameters.
 *
 * 'dy-" and 'fx-' methods are events, and the entire prefix space of
 * these methods, are valid regardless of implementation.  The lack of an
 * implementation is simply a NO-OP.
 *
 * ```php
 *		// For Example, the below does nothing if unimplemented
 *      //  and PHPStan will not complain
 *		$component->dy(...)
 *		$component->fx(...)
 * ```
 *
 * This class helps PHPStan understand the dynamic method and global event
 * PRADO features and validate PRADO projects.
 * To use this class, add the following PHPStan configuration to a project:
 * ```neon
 * services:
 *		-
 *			class: Prado\PHPStan\DynamicMethodsClassReflectionExtension
 *			tags:
 *				- phpstan.broker.methodsClassReflectionExtension
 * ```
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @since 4.2.2
 */
class DynamicMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		if (!$classReflection->is(\Prado\TComponent::class)) {
			return false;
		}

		return strncasecmp($methodName, 'dy', 2) === 0 || strncasecmp($methodName, 'fx', 2) === 0;
	}

	/**
	 * @param ClassReflection $classReflection The reflection of the class.
	 * @param string $methodName The name of the Method we are looking up for reflection.
	 * @return MethodReflection The Method Reflection of a Dynamic Method or Global Event.
	 */
	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return new DynamicMethodReflection($classReflection, $methodName);
	}
}
