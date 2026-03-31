<?php

/**
 * TNull class file.
 *
 * @author  Brad Anderson <belisoful@icloud.com>
 * @link    https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidOperationException;
use Prado\ISingleton;

/**
 * TNull class
 *
 * TNull implements the Null Object pattern, providing a reusable singleton
 * that represents "nothing." This acts as a type-safe, well-behaved
 * stand-in for {@see null} and any container that does not permit PHP's
 * native {@see null}.
 *
 * While {@see \Prado\Collections\TList}, {@see \Prado\Collections\TMap},
 * etc can handle `null` value items, TNull provides an existing item object
 * but that is like null.
 *
 * Because TNull is a singleton, only one instance ever exists per process.
 * Constructing and cloning is disallowed.  The shared instance is obtained
 * via the {@see null()} factory method or {@see singleton()}:
 *
 * ```php
 * 	$n = TNull::null();      // factory method — mirrors NSNull +null
 * 	$n = TNull::singleton(); // ISingleton-compliant alternative
 * ```
 *
 * Two static predicates are provided, named to mirror their PHP counterparts:
 * ```php
 * 	TNull::is_null($v);  // true when $v is PHP null or a TNull instance
 * 	TNull::empty($v);    // true when $v is TNull, null, false, 0, '', or []
 * ```
 *
 * {@see wrap()} and {@see unwrap()} convert between the two representations:
 *
 * ```php
 * 	$obj = TNull::wrap(null);            // PHP null → TNull singleton
 * 	$raw = TNull::unwrap(TNull::null()); // TNull    → PHP null
 * ```
 *
 * {@see __toString()} returns an empty string, consistent with PHP's own
 * cast behaviour: {@see (string)null} likewise yields {@see ''}.
 *
 * <b>JSON encoding</b>
 *
 * TNull implements {@see \JsonSerializable} and encodes as JSON {@see null},
 * the natural representation of an absent value in a JSON payload:
 *
 * ```php
 * echo json_encode(['value' => TNull::null()]);  // {"value":null}
 * ```
 *
 * @author  Brad Anderson <belisoful@icloud.com>
 * @since   4.3.3
 */
class TNull implements ISingleton, \JsonSerializable, \Stringable
{
	/**
	 * The single shared instance.
	 *
	 * Kept private to prevent external assignment.  The canonical way to
	 * obtain this value is via {@see null()} or {@see singleton()}.
	 * @note After 8.3, readonly static properties are allowed. static::$null?.
	 * @note a readonly $null future is the reason for init().
	 * @var null|static
	 */
	private static ?self $null = null;

	/**
	 * Private constructor — prevents external instantiation.
	 *
	 * Use {@see null()} or {@see singleton()} to obtain the shared instance.
	 */
	private function __construct()
	{
	}

	/**
	 * Returns the singleton TNull instance.
	 *
	 * This method satisfies the {@see ISingleton} contract.  The {@see $create}
	 * parameter mirrors the convention used elsewhere in the framework.
	 *
	 * ```php
	 * 	$n = TNull::singleton();       // create-or-return
	 * 	$n = TNull::singleton(false);  // always the shared TNull object
	 * ```
	 *
	 * @param bool $create whether to create the instance if it does not yet
	 *                     exist; defaults to {@see true}
	 * @return static	   the singleton instance, always valid
	 */
	public static function singleton(bool $create = true): static
	{
		if (self::$null === null) {
			self::$null = new self();
		}
		return self::$null;
	}

	/**
	 * Returns the singleton TNull instance.
	 *
	 * This is the preferred way to obtain the shared instance programmatically.
	 * It is a thin alias for {@see singleton()}.
	 *
	 * ```php
	 * $placeholder = TNull::null();
	 * $map->add('key', $placeholder);
	 * ```
	 *
	 * @return static the singleton TNull instance
	 */
	public static function null(): static
	{
		return static::singleton();
	}

	/**
	 * Initializes the singleton TNull instance during application bootstrap.
	 *
	 * This static method is the canonical bootstrap entry point for TNull,
	 * intended to be called during application startup to ensure the singleton
	 * is created.
	 *
	 * ```php
	 * // During application bootstrap:
	 * TNull::init();
	 *
	 * // Subsequent access uses null() or singleton():
	 * $n = TNull::null();
	 * ```
	 *
	 * Calling {@see init()} more than once is safe; the singleton is created
	 * only on the first call and subsequent calls are no-ops.
	 */
	public static function init(): void
	{
		static::singleton(true);
	}

	// -----------------------------------------------------------------------
	// Singleton integrity
	// -----------------------------------------------------------------------

	/**
	 * Prevents cloning of the singleton.
	 *
	 * Cloning would silently produce a second instance, violating the
	 * singleton contract.
	 *
	 * @throws TInvalidOperationException always thrown
	 */
	public function __clone()
	{
		throw new TInvalidOperationException('tnull_clone_disallowed', static::class);
	}

	// -----------------------------------------------------------------------
	// String representation
	// -----------------------------------------------------------------------

	/**
	 * Returns the string representation of TNull.
	 *
	 * Always returns an empty string.  This mirrors PHP's own cast semantics —
	 * {@see (string)null} evaluates to {@see ''} — making TNull a transparent
	 * substitute for native {@see null} in string contexts.
	 *
	 * @return string blank string ''
	 */
	public function __toString(): string
	{
		return '';
	}

	// -----------------------------------------------------------------------
	// Serialization
	// -----------------------------------------------------------------------

	/**
	 * Specifies the properties to include when serializing.
	 *
	 * TNull carries no state, so the returned array is always empty.
	 * Implementing {@see __sleep()} explicitly prevents PHP from attempting
	 * to serialize any inherited properties should the class hierarchy change
	 * in the future.
	 *
	 * @return array<string> always {@see []}
	 */
	public function __sleep(): array
	{
		return [];
	}

	// -----------------------------------------------------------------------
	// JsonSerializable
	// -----------------------------------------------------------------------

	/**
	 * Returns the value that {@see json_encode()} should use for this instance.
	 *
	 * Encodes as JSON {@see null}, the canonical JSON representation of an
	 * absent value.  Collections containing TNull instances therefore produce
	 * well-formed JSON without any special handling by the caller:
	 *
	 * ```php
	 * echo json_encode(['a' => TNull::null(), 'b' => 1]);
	 * // {"a":null,"b":1}
	 * ```
	 *
	 * @return mixed Always null
	 */
	public function jsonSerialize(): mixed
	{
		return null;
	}

	// -----------------------------------------------------------------------
	// Predicates
	// -----------------------------------------------------------------------

	/**
	 * Returns whether a value is an instance of TNull.
	 *
	 * Returns {@see true} when {@see $value} is an instance of TNull, allowing
	 * call sites to check for just TNull.
	 *
	 * ```php
	 * TNull::is_null_object(TNull::null());  // true
	 * TNull::is_null_object(null);           // false
	 * TNull::is_null_object('');             // false
	 * TNull::is_null_object(0);              // false
	 * ```
	 *
	 * @param mixed $value the value to test
	 * @return bool {@see true} when {@see $value} is a TNull instance;
	 *				{@see false} otherwise
	 */
	public static function is_null_object(mixed $value): bool
	{
		return $value instanceof static;
	}

	/**
	 * Returns whether a value represents an absent value.
	 *
	 * Returns {@see true} when {@see $value} is PHP's native {@see null} or
	 * an instance of TNull, allowing call sites to treat both representations
	 * of "nothing" uniformly without an explicit type check.
	 *
	 * The method is named to mirror PHP's built-in {@see \is_null()} function
	 * so that switching between a plain {@see \is_null()} call and a
	 * TNull-aware {@see TNull::is_null()} call requires only the addition of
	 * the class qualifier:
	 *
	 * ```php
	 * TNull::is_null(TNull::null());  // true
	 * TNull::is_null(null);           // true
	 * TNull::is_null('');             // false
	 * TNull::is_null(0);              // false
	 * ```
	 *
	 * @param mixed $value the value to test
	 * @return bool {@see true} when {@see $value} is {@see null} or a TNull
	 *              instance; {@see false} otherwise
	 * @see empty() to also match falsy scalars and empty arrays
	 */
	public static function is_null(mixed $value): bool
	{
		return $value === null || $value instanceof static;
	}

	/**
	 * Returns whether a value is empty or represents an absent value.
	 *
	 * Returns {@see true} for any value that PHP's {@see \empty()} language
	 * construct considers empty ({@see null}, {@see false}, {@see 0},
	 * {@see ''}, {@see '0'}, {@see []}) as well as any TNull instance.
	 *
	 * The method is named to mirror the {@see \empty()} language construct so
	 * that switching to a TNull-aware check requires only the addition of the
	 * class qualifier.
	 *
	 * ```php
	 * TNull::empty(TNull::null());  // true
	 * TNull::empty(null);           // true
	 * TNull::empty('');             // true
	 * TNull::empty(0);              // true
	 * TNull::empty('hello');        // false
	 * TNull::empty(42);             // false
	 *
	 * // As a callable — array form required:
	 * $isEmpty = [TNull::class, 'empty'];
	 * array_filter($items, $isEmpty);
	 * ```
	 *
	 * @param mixed $value the value to test
	 * @return bool {@see true} when {@see $value} is empty or a TNull instance
	 * @see is_null() to test only for {@see null} and TNull
	 */
	public static function empty(mixed $value): bool
	{
		return empty($value) || $value instanceof static;
	}

	// -----------------------------------------------------------------------
	// Bridging utilities
	// -----------------------------------------------------------------------

	/**
	 * Promotes a PHP null to the TNull singleton; passes all other values through.
	 *
	 * Use {@see wrap()} when storing values into a collection that requires
	 * objects in place of native {@see null}:
	 *
	 * ```php
	 * $list->add(TNull::wrap($maybeNull));
	 * ```
	 *
	 * @param mixed $value the value to wrap
	 * @return mixed|static the TNull singleton when {@see $value} is
	 *                      {@see null}; otherwise {@see $value} unchanged
	 * @see unwrap() for the inverse operation
	 */
	public static function wrap(mixed $value): mixed
	{
		return $value === null ? static::null() : $value;
	}

	/**
	 * Demotes a TNull instance to PHP null; passes all other values through.
	 *
	 * Use {@see unwrap()} when reading values back from a collection that
	 * uses TNull as a null placeholder, and downstream code expects native
	 * {@see null}:
	 *
	 * ```php
	 * $raw = TNull::unwrap($list->itemAt(0));
	 * ```
	 *
	 * @param mixed $value the value to unwrap
	 * @return null|mixed {@see null} when {@see $value} is a TNull instance;
	 *                    otherwise {@see $value} unchanged
	 * @see wrap() for the inverse operation
	 */
	public static function unwrap(mixed $value): mixed
	{
		return $value instanceof static ? null : $value;
	}

	// -----------------------------------------------------------------------
	// Callable
	// -----------------------------------------------------------------------

	/**
	 * Makes TNull instances directly callable, returning PHP null.
	 *
	 * Implementing {@see __invoke()} allows a TNull instance to be used
	 * anywhere a {@see callable} returning {@see null} is expected, without
	 * requiring an explicit wrapper closure:
	 *
	 * ```php
	 * $fn     = TNull::null();
	 * $result = $fn();                            // null
	 * $filled = array_map($fn, $list->toArray()); // [null, null, ...]
	 * ```
	 *
	 * @return mixed always {@see null}
	 */
	public function __invoke(): mixed
	{
		return null;
	}
}
