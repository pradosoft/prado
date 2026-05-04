<?php

/**
 * PradoMethodVisibleStaticMethodTypeSpecifyingExtension class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/teamdigitale/licenses/blob/master/CC0-1.0
 */

declare(strict_types=1);

namespace Prado\PHPStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use Prado\Prado;

/**
 * PradoMethodVisibleStaticMethodTypeSpecifyingExtension class.
 *
 * This is a PHPStan Extension which tells PHPStan that:
 * ```php
 *		if (Prado::method_visible($this, 'someMethod')) {
 *			$this->someMethod();
 *		}
 * ```
 * has the same effect as:
 * ```php
 *		if (method_exists($this, 'someMethod')) {
 *			$this->someMethod();
 *		}
 * ```
 *
 * When the condition is true (inside the if block), PHPStan will know that the
 * method exists and is publicly callable on the object and will not throw an
 * error for calling it.
 *
 * {@see Prado::method_visible()} extends {@see method_exists()} with PHP visibility
 * checks — a method must both exist and be accessible from the calling context.
 * From PHPStan's perspective this narrows the subject type exactly as
 * method_exists() would: the method is present on the object.
 *
 * This class helps PHPStan understand the "method_visible" PRADO feature and
 * validate PRADO projects.
 * To use this class, add the following PHPStan configuration to a project:
 * ```neon
 * services:
 *		-
 *			class: Prado\PHPStan\PradoMethodVisibleStaticMethodTypeSpecifyingExtension
 *			tags:
 *				- phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
final class PradoMethodVisibleStaticMethodTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function getClass(): string
	{
		return Prado::class;
	}

	public function isStaticMethodSupported(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		TypeSpecifierContext $context
	): bool {
		return $staticMethodReflection->getName() === 'method_visible'
			&& isset($node->args[0], $node->args[1])
			&& $context->true();
	}

	public function specifyTypes(
		MethodReflection $staticMethodReflection,
		StaticCall $node,
		Scope $scope,
		TypeSpecifierContext $context
	): SpecifiedTypes {
		$objectArg = $node->args[0]->value;
		$objectType = $scope->getType($objectArg);

		if (!$objectType->isObject()->yes()) {
			return new SpecifiedTypes();
		}

		$methodArgType = $scope->getType($node->args[1]->value);
		$constantStrings = $methodArgType->getConstantStrings();

		if (count($constantStrings) !== 1) {
			return new SpecifiedTypes();
		}

		$methodName = $constantStrings[0]->getValue();

		// Narrow the object type to include HasMethodType so PHPStan knows the
		// method is visible and callable when the condition is true.
		return $this->typeSpecifier->create(
			$objectArg,
			new IntersectionType([$objectType, new HasMethodType($methodName)]),
			$context,
			$scope
		);
	}
}
