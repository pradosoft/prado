<?php

/**
 * TFiberContext class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

/**
 * TFiberContext class.
 *
 * TFiberContext is per-fiber state: a map of keys to values that belongs to the current
 * {@see \Fiber}, held in a `WeakMap` so it is released with the fiber.  The main fiber has its
 * own map.  {@see TAsync::run()} copies the parent's context into the new fiber, so a value set
 * before the fiber starts is visible inside it and a change inside stays inside.
 *
 * Framework uses: the user a cron task runs as (`user`), the log category of a task.
 *
 * ```php
 * TFiberContext::set('user', $taskUser);
 * TAsync::run(fn () => TFiberContext::get('user'));   // $taskUser
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
final class TFiberContext
{
	/** @var ?\WeakMap<\Fiber, array<string, mixed>> The contexts of worker fibers. */
	private static ?\WeakMap $_contexts = null;

	/** @var array<string, mixed> The context of the main fiber. */
	private static array $_main = [];

	/**
	 * Returns a value of the current fiber's context.
	 * @param string $key The key.
	 * @param mixed $default The value when the key is absent.
	 * @return mixed The value.
	 */
	public static function get(string $key, mixed $default = null): mixed
	{
		return self::all()[$key] ?? $default;
	}

	/**
	 * Indicates whether the current fiber's context holds a key.
	 * @param string $key The key.
	 * @return bool Whether the key is present.
	 */
	public static function has(string $key): bool
	{
		return array_key_exists($key, self::all());
	}

	/**
	 * Sets a value in the current fiber's context.
	 * @param string $key The key.
	 * @param mixed $value The value.
	 */
	public static function set(string $key, mixed $value): void
	{
		$values = self::all();
		$values[$key] = $value;
		self::replace($values);
	}

	/**
	 * Removes a key from the current fiber's context.
	 * @param string $key The key.
	 */
	public static function remove(string $key): void
	{
		$values = self::all();
		unset($values[$key]);
		self::replace($values);
	}

	/**
	 * Returns the current fiber's whole context.
	 * @return array<string, mixed> The context.
	 */
	public static function all(): array
	{
		$fiber = \Fiber::getCurrent();
		if ($fiber === null) {
			return self::$_main;
		}
		return self::$_contexts[$fiber] ?? [];
	}

	/**
	 * Replaces the current fiber's whole context.
	 * @param array<string, mixed> $values The context.
	 */
	public static function replace(array $values): void
	{
		$fiber = \Fiber::getCurrent();
		if ($fiber === null) {
			self::$_main = $values;
			return;
		}
		self::$_contexts ??= new \WeakMap();
		self::$_contexts[$fiber] = $values;
	}

	/**
	 * Empties the current fiber's context.
	 */
	public static function clear(): void
	{
		self::replace([]);
	}
}
