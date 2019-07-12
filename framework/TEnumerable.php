<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */

namespace Prado;

/**
 * TEnumerable class.
 * TEnumerable is the base class for all enumerable types.
 * To define an enumerable type, extend TEnumberable and define string constants.
 * Each constant represents an enumerable value.
 * The constant name must be the same as the constant value.
 * For example,
 * <code>
 * class TTextAlign extends \Prado\TEnumerable
 * {
 *     const Left='Left';
 *     const Right='Right';
 * }
 * </code>
 * Then, one can use the enumerable values such as TTextAlign::Left and
 * TTextAlign::Right.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
 * @since 3.0
 */
class TEnumerable implements \Iterator
{
	private $_enums = [];

	public function __construct()
	{
		$reflection = new \ReflectionClass($this);
		$this->_enums = $reflection->getConstants();
	}

	public function current()
	{
		return current($this->_enums);
	}

	public function key()
	{
		return key($this->_enums);
	}

	public function next()
	{
		return next($this->_enums);
	}

	public function rewind()
	{
		reset($this->_enums);
	}

	public function valid()
	{
		return $this->current() !== false;
	}
}
