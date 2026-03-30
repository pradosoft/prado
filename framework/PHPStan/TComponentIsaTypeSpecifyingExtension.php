<?php

/**
 * TComponentIsaTypeSpecifyingExtension class
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
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use Prado\TComponent;

/**
 * TComponentIsaTypeSpecifyingExtension class.
 *
 * This is a PHPStan Extension which tells PHPStan that:
 * ```php
 *		$component->isa(MyClass::class)
 * ```
 * has the same operation and effect as:
 * ```php
 *		$component instanceof MyClass::class
 * ```
 *
 * This class helps PHPStan understand the "isa" PRADO feature and validate
 * PRADO projects.
 * To use this class, add the following PHPStan configuration to a project:
 * ```neon
 * services:
 *		-
 *			class: Prado\PHPStan\TComponentIsaTypeSpecifyingExtension
 *			tags:
 *				- phpstan.typeSpecifier.methodTypeSpecifyingExtension
 * ```
 *
 * @author Brad Anderson <beisoful@icloud.com>
 * @since 4.3.3
 */
class TComponentIsaTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
	private TypeSpecifier $typeSpecifier;

	public function __construct(
		private ReflectionProvider $reflectionProvider  // ← inject
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
		return $methodReflection->getName() === 'isa'
			&& isset($node->args[0]);
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

		if (count($constantStrings) === 0) {
			return new SpecifiedTypes();
		}

		$types = [];

		foreach ($constantStrings as $constString) {
			$className = $constString->getValue();

			// ← use ReflectionProvider instead of is_a()
			if (!$this->reflectionProvider->hasClass($className)) {
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass($className);

			if (!$classReflection->isSubclassOf(TComponent::class) && $className !== TComponent::class) {
				continue;
			}

			$types[] = new ObjectType($className);
		}

		if ($types === []) {
			return new SpecifiedTypes();
		}

		$type = count($types) === 1
			? $types[0]
			: new UnionType($types);

		// ← 4th argument `true` means overwrite existing type
		return $this->typeSpecifier->create(
			$node->var,
			$type,
			$context,
			true                // ← was missing
		);
	}
}
