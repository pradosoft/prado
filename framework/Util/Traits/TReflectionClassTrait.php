<?php

/**
 * TReflectionClassTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

use Prado\TComponentReflection;

/**
 * TReflectionClassTrait trait.
 *
 * TReflectionClassTrait adds a single static method, {@see getReflectionClass()},
 * that returns a cached {@see \ReflectionClass} for the using class.
 *
 * ```php
 * class MyClass
 * {
 *     use \Prado\Util\Traits\TReflectionClassTrait;
 *
 *     public static function describe(): string
 *     {
 *         return static::getReflectionClass()->getName();
 *     }
 * }
 * ```
 *
 * {@see \Prado\Util\Traits\TConstantReflectionTrait} uses this trait internally.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TReflectionClassTrait
{
	/**
	 * Returns the cached {@see \ReflectionClass} for the calling class.
	 *
	 * Uses late static binding so each subclass receives its own cache entry.
	 *
	 * @return \ReflectionClass The cached reflection instance for the calling class.
	 */
	public static function getReflectionClass(): \ReflectionClass
	{
		/** @var \ReflectionClass $ref static::class is always a valid loaded class */
		$ref = TComponentReflection::getReflectionClassForType(static::class);
		return $ref;
	}
}
