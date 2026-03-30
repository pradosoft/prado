<?php

/**
 * DynamicMethodsClassReflectionExtension class
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\PHPStan;

use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;

/**
 * DynamicMethodsClassReflectionExtension class.
 *
 * This is a PHPStan Extension that tells PHPStan that "dynamic methods",
 * with the prefix of "dy" or "fx", is present regardless of parameters.
 *
 * 'dy-" and 'fx-' methods are events, and the entire space of the methods
 * are valid regardless of implementation.  The lack of an implementation
 * is simply a NO-OP.
 *
 * ```php
 *		// For Example, does nothing if not implemented by the subclass
 *		$component->dy(...)
 *		$component->fx(...)
 * ```
 *
 * Within a projects `phpstan.neon.dist`, add the following configuration:
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

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return new DynamicMethodReflection($classReflection, $methodName);
	}
}
