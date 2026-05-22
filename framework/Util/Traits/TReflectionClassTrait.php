<?php

/**
 * TReflectionClassTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

/**
 * TReflectionClassTrait trait.
 *
 * TReflectionClassTrait provides a single static cache of {@see \ReflectionClass}
 * instances, one per concrete class, to avoid repeated `new \ReflectionClass()`
 * allocations across a request.
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
	/** @var array<string,\ReflectionClass> Cache of ReflectionClass instances, keyed by class name. */
	private static array $_reflection_cache = [];

	/**
	 * Returns a cached {@see \ReflectionClass} for the calling class.
	 *
	 * Uses late static binding (`static::class`) as the cache key so that each
	 * subclass receives its own entry rather than sharing the parent's.
	 *
	 * @throws \ReflectionException if the calling class cannot be reflected.
	 * @return \ReflectionClass The cached reflection instance for the calling class.
	 */
	public static function getReflectionClass(): \ReflectionClass
	{
		$class = static::class;
		if (!array_key_exists($class, self::$_reflection_cache)) {
			self::$_reflection_cache[$class] = new \ReflectionClass($class);
		}
		return self::$_reflection_cache[$class];
	}

	/**
	 * Returns a cached {@see \ReflectionClass} for `$class`, or the calling class
	 * when `$class` is `null`.  Returns `null` if `$class` does not exist.
	 *
	 * @param ?string $class Fully-qualified class name, or `null` for `static::class`.
	 * @return ?\ReflectionClass
	 */
	protected static function getReflectionForClass(?string $class = null): ?\ReflectionClass
	{
		$class ??= static::class;
		if (!array_key_exists($class, self::$_reflection_cache)) {
			try {
				self::$_reflection_cache[$class] = new \ReflectionClass($class);
			} catch (\ReflectionException $e) {
				return null;
			}
		}
		return self::$_reflection_cache[$class];
	}
}
