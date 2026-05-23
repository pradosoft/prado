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
 * TReflectionClassTrait provides cached {@see \ReflectionClass} access for the
 * using class.  The cache itself lives in {@see \Prado\TComponentReflection},
 * which is the central reflection-cache authority for the framework; this trait
 * delegates to {@see \Prado\TComponentReflection::getReflectionClassForType()} so
 * that all `ReflectionClass` instances are shared regardless of which code path
 * requested them.
 *
 * Any class or trait that needs to inspect its own class structure via reflection
 * can use this trait and call {@see getReflectionClass()} without worrying about
 * the caching overhead.
 *
 * {@see \Prado\Util\Traits\TConstantReflectionTrait} uses this trait internally.
 *
 * ```php
 * class MyClass
 * {
 *     use \Prado\Util\Traits\TReflectionClassTrait;
 *
 *     public static function describe(): string
 *     {
 *         return self::getReflectionClass()->getName();
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TReflectionClassTrait
{
	/**
	 * Returns a cached {@see \ReflectionClass} for the calling class.
	 *
	 * Delegates to {@see \Prado\TComponentReflection::getReflectionClassForType()} using
	 * late static binding (`static::class`) as the key so that each subclass receives
	 * its own cache entry.
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
