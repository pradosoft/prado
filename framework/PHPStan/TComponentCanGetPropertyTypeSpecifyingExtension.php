<?php

/**
 * TComponentCanGetPropertyTypeSpecifyingExtension class
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
 * TComponentCanGetPropertyTypeSpecifyingExtension class.
 *
 * This is a PHPStan Extension which tells PHPStan that:
 * ```php
 *		if ($this->canGetProperty('Foo')) {
 *			$value = $this->getFoo(); // method call form
 *			$value = $this->foo;      // virtual property form
 *		}
 * ```
 * narrows the type of `$this` inside the if-block to indicate that both the
 * getter method `getFoo()` exists and the virtual property `foo` is readable.
 *
 * In PRADO, {@see TComponent::canGetProperty()} returns true when a `get{Name}()`
 * or `getjs{Name}()` method is publicly visible on the object or one of its
 * enabled behaviors.  This extension teaches PHPStan about both the `get{Name}()`
 * method and the `lcfirst($name)` virtual property so that neither the direct
 * getter call nor the `$obj->prop` access produces a false PHPStan error inside
 * the guarded branch.
 *
 * This class helps PHPStan understand the "canGetProperty" PRADO feature and
 * validate PRADO projects.
 * To use this class, add the following PHPStan configuration to a project:
 * ```neon
 * services:
 *		-
 *			class: Prado\PHPStan\TComponentCanGetPropertyTypeSpecifyingExtension
 *			tags:
 *				- phpstan.typeSpecifier.methodTypeSpecifyingExtension
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
final class TComponentCanGetPropertyTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
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
		return $methodReflection->getName() === 'canGetProperty'
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
		//   1. The getter method get{Name}() exists (covers $obj->getName() calls).
		//   2. The virtual property is readable (covers $obj->Name access).
		// PHPStan asks for the property with the spelling written in the source, so
		// both the given spelling and its lowercase-first form are registered:
		// PRADO templates and code use `Title`, while `title` also reaches the same
		// accessor because PHP method names are case-insensitive.
		$types = [
			$calledOnType,
			new HasMethodType('get' . $propertyName),
		];
		foreach (array_unique([$propertyName, lcfirst($propertyName)]) as $spelling) {
			$types[] = new HasPropertyType($spelling);
		}

		return $this->typeSpecifier->create(
			$node->var,
			new IntersectionType($types),
			$context,
			$scope
		);
	}
}
