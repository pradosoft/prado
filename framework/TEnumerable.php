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
use Prado\Util\Traits\TArrayIteratorTrait;

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
 * As of 4.3.3, to access a constant value by name, check its presence by name
 * or value, or get the constant name by value, use {@see valueOfConstant()},
 * {@see hasConstant()}, {@see hasConstantValue()}, and {@see constantOfValue()}.
 * For example:
 * ```php
 * $alignConstant = 'Left';
 * $value = TTextAlign::valueOfConstant($alignConstant);  // returns 'Left'
 * $constant = TTextAlign::constantOfValue($value);       // returns 'Left'
 * $constant === $alignConstant;                          // true
 * $hasLeft = TTextAlign::hasConstant($alignConstant);    // true
 * ```
 *
 * Classes that cannot extend TEnumerable can implement {@see IEnumerable} and
 * {@see \Iterator} by using {@see \Prado\Util\Traits\TConstantReflectionTrait}
 * and {@see \Prado\Util\Traits\TArrayIteratorTrait}; no further implementation is
 * required because {@see TConstantReflectionTrait} supplies {@see getIteratorArrayCopy()}
 * to the {@see TArrayIteratorTrait}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @see \Prado\Util\Traits\TArrayIteratorTrait
 * @see \Prado\Util\Traits\TConstantReflectionTrait
 * @since 3.0
 */
class TEnumerable implements IEnumerable, \Iterator
{
	use TArrayIteratorTrait;
	use TConstantReflectionTrait;
}
