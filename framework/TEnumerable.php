<?php

/**
 * TEnumerable class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Util\Traits\TConstantReflectionTrait;

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
 * @see \Prado\Util\Traits\TConstantReflectionTrait
 * @since 3.0
 */
class TEnumerable implements \Iterator
{
	use TConstantReflectionTrait;

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
}
