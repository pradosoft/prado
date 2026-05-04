<?php

/**
 * TComponentCanSetPropertyTypeSpecifyingExtension class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/teamdigitale/licenses/blob/master/CC0-1.0
 */

declare(strict_types=1);

namespace Prado\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use Prado\TComponent;

/**
 * TComponentCanSetPropertyTypeSpecifyingExtension class.
 *
 * This is a PHPStan Extension which tells PHPStan that:
 * ```php
 *		if ($this->canSetProperty('Foo')) {
 *			$this->setFoo($value); // method call form
 *			$this->foo = $value;   // virtual property form
 *		}
 * ```
 * narrows the type of `$this` inside the if-block to indicate that both the
 * setter method `setFoo()` exists and the virtual property `foo` is writable.
 *
 * In PRADO, {@see TComponent::canSetProperty()} returns true when a `set{Name}()`
 * or `setjs{Name}()` method is publicly visible on the object or one of its
 * enabled behaviors.  This extension teaches PHPStan about both the `set{Name}()`
 * method and the `lcfirst($name)` virtual property so that neither the direct
 * setter call nor the `$obj->prop = $value` assignment produces a false PHPStan
 * error inside the guarded branch.
 *
 * This class helps PHPStan understand the "canSetProperty" PRADO feature and
 * validate PRADO projects.
 * To use this class, add the following PHPStan configuration to a project:
 * ```neon
 * services:
 *		-
 *			class: Prado\PHPStan\TComponentCanSetPropertyTypeSpecifyingExtension
 *			tags:
 *				- phpstan.typeSpecifier.methodTypeSpecifyingExtension
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
final class TComponentCanSetPropertyTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return TComponent::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
		MethodCall $node,
		TypeSpecifierContext $context
	): bool {
		return $methodReflection->getName() === 'canSetProperty'
			&& isset($node->args[0])
			&& $context->true();
	}

	public function specifyTypes(
		MethodReflection $methodReflection,
		MethodCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes {
		$calledOnType = $scope->getType($node->var);

		if (!$calledOnType->isObject()->yes()) {
			return new SpecifiedTypes();
		}

		$argType = $scope->getType($node->args[0]->value);
		$constantStrings = $argType->getConstantStrings();

		if (count($constantStrings) !== 1) {
			return new SpecifiedTypes();
		}

		$propertyName = $constantStrings[0]->getValue();

		// Narrow the object type so PHPStan knows:
		//   1. The setter method set{Name}() exists (covers $obj->setName() calls).
		//   2. The virtual property lcfirst($name) is writable (covers $obj->name = $v).
		// HasPropertyType uses the lowercase-first form because PHPStan passes the
		// property name exactly as written in source, and PRADO properties are
		// conventionally accessed with a lowercase-first name (e.g. $obj->title = 'x').
		return $this->typeSpecifier->create(
			$node->var,
			new IntersectionType([
				$calledOnType,
				new HasMethodType('set' . $propertyName),
				new HasPropertyType(lcfirst($propertyName)),
			]),
			$context,
			$scope
		);
	}
}
