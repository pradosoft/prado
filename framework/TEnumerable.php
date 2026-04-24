<?php

/**
 * TEnumerable class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TEnumerable class.
 *
 * TEnumerable is the base class for all enumerable types.
 * To define an enumerable type, extend TEnumerable and define string constants.
 * Each constant represents an enumerable value.
 * The constant name should usually be the same as the constant value.
 * For example,
 * ```php
 * class TTextAlign extends \Prado\TEnumerable
 * {
 *     const Left = 'Left';
 *     const Right = 'Right';
 * }
 * ```
 * Then, one can use the enumerable values such as TTextAlign::Left and
 * TTextAlign::Right.
 *
 * As of 4.3.3, to access a constant value by variable, check its presence,
 * or get the constant name by value, use {@see valueOf()}, {@see hasConstant()},
 * and {@see constantOf()}.  For example:
 * ```php
 * $alignConstant = 'Left';
 * $value = TTextAlign::valueOf($alignConstant);  // returns 'Left'
 * $constant = TTextAlign::constantOf($value);    // returns 'Left'
 * $constant === $alignConstant;                  // true
 * $hasLeft = TTextAlign::hasConstant($alignConstant);  // true
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 */
class TEnumerable implements \Iterator
{
	private static $_types = [];
	private $_enums = [];

	/**
	 * Constructor.
	 *
	 * Initializes the enumerable constants from the class definition.
	 */
	public function __construct()
	{
		$reflection = self::getReflectionClass();
		$this->_enums = $reflection->getConstants();
	}

	/**
	 * Returns the current enumerable value.
	 *
	 * @return mixed The current value.
	 */
	#[\ReturnTypeWillChange]
	public function current()
	{
		return current($this->_enums);
	}

	/**
	 * Returns the key of the current enumerable value.
	 *
	 * @return mixed The current key.
	 */
	#[\ReturnTypeWillChange]
	public function key()
	{
		return key($this->_enums);
	}

	/**
	 * Advances the internal pointer to the next element.
	 */
	public function next(): void
	{
		next($this->_enums);
	}

	/**
	 * Rewinds the internal pointer to the first element.
	 */
	public function rewind(): void
	{
		reset($this->_enums);
	}

	/**
	 * Checks if the current position is valid.
	 *
	 * @return bool True if the current position is valid, false otherwise.
	 */
	public function valid(): bool
	{
		return $this->current() !== false;
	}

	/**
	 * Check if a constant exists in this enumerable type. The second parameter
	 * is case sensitivity, defaulting to true. This is similar behavior to
	 * {@see \ReflectionClass}. For example:
	 * ```php
	 * $has_Left = TTextAlign::valueOf('Left');  // true
	 * $hasNo_left = TTextAlign::hasConstant('left');  // false
	 * $hasNo_left = TTextAlign::hasConstant('left', true);  // false
	 * $has_left = TTextAlign::valueOf('left', false);  // true
	 * ```
	 * Case Insensitivity iterates through all constants.
	 * @param string $constant The constant name to check.
	 * @param bool $caseSensitive Whether to perform case-sensitive check. Default is true.
	 * @return bool True if the constant exists, false otherwise.
	 */
	public static function hasConstant($constant, bool $caseSensitive = true): bool
	{
		$ref = self::getReflectionClass();
		if ($ref->hasConstant($constant)) {
			return true;
		}
		if (!$caseSensitive) {
			$consts = $ref->getConstants();
			foreach ($consts as $name => $value) {
				if (strcasecmp($name, $constant) === 0) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Gets the constant value by name. The second parameter is case sensitivity,
	 * defaulting to true. This is similar behavior to {@see \ReflectionClass}.
	 * For example:
	 * ```php
	 * $value_Left = TTextAlign::valueOf('Left');  // returns 'Left'
	 * $null = TTextAlign::valueOf('left');  // returns null
	 * $null = TTextAlign::valueOf('left', true);  // returns null
	 * $value_left = TTextAlign::valueOf('left', false);  // returns 'Left'
	 * ```
	 * Case Insensitivity iterates through all constants.
	 * @param string $constant The constant name to get.
	 * @param bool $caseSensitive Whether to perform case-sensitive check. Default is true.
	 * @return ?string The constant value or null if not found.
	 */
	public static function valueOf($constant, bool $caseSensitive = true): ?string
	{
		$ref = self::getReflectionClass();
		if (($value = $ref->getConstant($constant)) !== false) {
			return $value;
		}
		if (!$caseSensitive) {
			$consts = $ref->getConstants();
			foreach ($consts as $name => $value) {
				if (strcasecmp($name, $constant) === 0) {
					return $value;
				}
			}
		}
		return null;
	}

	/**
	 * Gets the constant name by value. The second parameter is case sensitivity,
	 * defaulting to true. This is similar behavior to {@see \ReflectionClass}.
	 * For example:
	 * ```php
	 * $constant_Left = TTextAlign::constantOf('Left');  // returns 'Left'
	 * $null = TTextAlign::constantOf('left');  // returns null
	 * $null = TTextAlign::constantOf('left', true);  // returns null
	 * $constant_left = TTextAlign::constantOf('left', false);  // returns 'Left'
	 * ```
	 * Case Insensitivity iterates through all constants.
	 * @param string $value The constant value to find.
	 * @param bool $caseSensitive Whether to perform case-sensitive check. Default is true.
	 * @return ?string The constant name or null if not found.
	 */
	public static function constantOf($value, bool $caseSensitive = true): ?string
	{
		$cmp = $caseSensitive ? '\strcmp' : '\strcasecmp';
		$ref = self::getReflectionClass();
		foreach ($ref->getConstants() as $constant => $constValue) {
			if ($cmp($value, $constValue) === 0) {
				return $constant;
			}
		}
		return null;
	}

	// ----- Private Helpers

	/**
	 * Gets or creates the ReflectionClass for the current enumerable class.
	 * Uses a static cache to store ReflectionClass instances per class.
	 * @return \ReflectionClass The reflection class instance.
	 */
	private static function getReflectionClass(): \ReflectionClass
	{
		$class = static::class;
		if (!isset(self::$_types[$class])) {
			self::$_types[$class] = new \ReflectionClass($class);
		}
		return self::$_types[$class];
	}
}
