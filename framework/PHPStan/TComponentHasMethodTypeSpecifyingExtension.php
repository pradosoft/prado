<?php

/**
 * TComponentHasMethodTypeSpecifyingExtension class
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
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use Prado\TComponent;

/**
 * TComponentHasMethodTypeSpecifyingExtension class.
 *
 * This is a PHPStan Extension which tells PHPStan that:
 * ```php
 *		if ($this->hasMethod('someMethod')) {
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
 * method exists on the object and will not throw an error for calling it.
 *
 * This class helps PHPStan understand the "hasMethod" PRADO feature and validate
 * PRADO projects.
 * To use this class, add the following PHPStan configuration to a project:
 * ```neon
 * services:
 *		-
 *			class: Prado\PHPStan\TComponentHasMethodTypeSpecifyingExtension
 *			tags:
 *				- phpstan.typeSpecifier.methodTypeSpecifyingExtension
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
final class TComponentHasMethodTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
	private TypeSpecifier $typeSpecifier;

	public function __construct(
	) {
	}

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
		return $methodReflection->getName() === 'hasMethod'
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

		$methodName = $constantStrings[0]->getValue();

		// Intersect the object type with HasMethodType so PHPStan understands
		// that the method exists on the object inside the if-block, matching
		// the same narrowing behaviour as method_exists().
		return $this->typeSpecifier->create(
			$node->var,
			new IntersectionType([$calledOnType, new HasMethodType($methodName)]),
			$context,
			$scope
		);
	}
}
