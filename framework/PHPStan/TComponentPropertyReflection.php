<?php

/**
 * TComponentPropertyReflection class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 */

declare(strict_types=1);

namespace Prado\PHPStan;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/**
 * TComponentPropertyReflection class.
 *
 * This class implements PHPStan's {@see PropertyReflection} interface for PRADO
 * virtual properties — properties that are backed by `get{Name}()` / `set{Name}()`
 * method pairs rather than a real PHP property declaration.
 *
 * A virtual property is readable when a getter method exists and writable when a
 * setter method exists.  The readable type is derived from the getter's declared
 * return type; the writable type is derived from the setter's first parameter
 * type.  When no type information is available, {@see MixedType} is used.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TComponentPropertyReflection implements PropertyReflection
{
	public function __construct(
		private ClassReflection $declaringClass,
		private ?MethodReflection $getter,
		private ?MethodReflection $setter
	) {
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
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
		if ($this->getter !== null) {
			return $this->getter->getDocComment();
		}
		if ($this->setter !== null) {
			return $this->setter->getDocComment();
		}
		return null;
	}

	/**
	 * Returns the readable type of the property.
	 * Derived from the getter's return type when available.
	 */
	public function getReadableType(): Type
	{
		if ($this->getter !== null) {
			$variants = $this->getter->getVariants();
			if ($variants !== []) {
				return $variants[0]->getReturnType();
			}
		}
		return new MixedType();
	}

	/**
	 * Returns the writable type of the property.
	 * Derived from the setter's first parameter type when available.
	 */
	public function getWritableType(): Type
	{
		if ($this->setter !== null) {
			$variants = $this->setter->getVariants();
			if ($variants !== []) {
				$params = $variants[0]->getParameters();
				if ($params !== []) {
					return $params[0]->getType();
				}
			}
		}
		return new MixedType();
	}

	/**
	 * Virtual properties backed by get/set method pairs use different logic
	 * for reading and writing (method hooks), so type narrowing via assignment
	 * is not safe.
	 */
	public function canChangeTypeAfterAssignment(): bool
	{
		return false;
	}

	public function isReadable(): bool
	{
		return $this->getter !== null;
	}

	public function isWritable(): bool
	{
		return $this->setter !== null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}
}
