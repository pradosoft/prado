<?php

namespace Prado\PHPStan;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\MixedType;

class DynamicMethodReflection implements MethodReflection
{
	private $_classReflection;
	private $_methodName;

	public function __construct(ClassReflection $classReflection, string $methodName)
	{
		$this->_classReflection = $classReflection;
		$this->_methodName = $methodName;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->_classReflection;
	}

	public function isStatic(): bool
	{
		return false;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getName(): string
	{
		return $this->_methodName;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	/**
	 * @return \PHPStan\Reflection\ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		return [
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				[],
				true,
				new MixedType()
			),
		];
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getThrowType(): ?Type
	{
		return null;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}
}
