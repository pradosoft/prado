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
 */

namespace Prado;

use ArrayAccess;

/**
 * TEventParameter class.
 *
 * TEventParameter is the base class for all event parameter classes.
 * When raising an event, this class captures the event name being raised by being
 * an {@see \Prado\IEventParameter}. TEventParameter encapsulates the parameter data for
 * events. The event parameter is set via {@see setParameter Parameter} property
 * or by constructor parameter.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 */
class TEventParameter extends \Prado\TComponent implements IEventParameter, ArrayAccess
{
	private string $_eventName = '';
	private mixed $_param;
	private bool $_initialParameterNull;
	/**
	 * Constructor.
	 * @param null|mixed $parameter parameter of the event
	 * @since 4.3.0
	 */
	public function __construct(mixed $parameter = null)
	{
		$this->_initialParameterNull = $parameter === null;
		$this->setParameter($parameter);
		parent::__construct();
	}

	/**
	 * @return string name of the event
	 * @since 4.3.0
	 */
	public function getEventName(): string
	{
		return $this->_eventName;
	}

	/**
	 * @param string $value name of the event
	 * @since 4.3.0
	 */
	public function setEventName(string $value)
	{
		$this->_eventName = $value;
	}

	/**
	 * @return bool if the initial parameter was null
	 * @since 4.3.3
	 */
	public function getInitialParameterNull(): bool
	{
		return $this->_initialParameterNull;
	}

	/**
	 * @return mixed parameter of the event
	 * @since 4.3.0
	 */
	public function getParameter(): mixed
	{
		return $this->_param;
	}

	/**
	 * @param mixed $value parameter of the event
	 * @since 4.3.0
	 */
	public function setParameter(mixed $value)
	{
		$this->_param = $value;
	}

	/**
	 * @return bool if the initial parameter was null
	 * @since 4.3.3
	 */
	public function getIsParameterArray(): bool
	{
		return is_array($this->_param) || $this->_param instanceof ArrayAccess;
	}

	//	----- ArrayAccess -----

	/**
	 * This method is required by the interface \ArrayAccess.
	 * @param mixed $offset the offset to check on
	 * @return bool if not an array, then false, otherwise check _param.
	 * @since 4.3.3
	 */
	public function offsetExists($offset): bool
	{
		if (!$this->getIsParameterArray()) {
			return false;
		}
		return isset($this->_param[$offset]);
	}

	/**
	 * This method is required by the interface \ArrayAccess. When the Parameter
	 * is an array (or ArrayAccess) this will get the item at $offset.  If Parameter
	 * is not an array, this returns null.
	 * @param int $offset the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found.
	 * @since 4.3.3
	 */
	public function offsetGet($offset): mixed
	{
		if (!$this->getIsParameterArray()) {
			return null;
		}
		return $this->_param[$offset] ?? null;
	}

	/**
	 * This method is required by the interface \ArrayAccess. When the Parameter
	 * is an array (or ArrayAccess) this will set the item at $offset.  Otherwise
	 * this has no effect.
	 * @param int $offset the offset to set element
	 * @param mixed $item the element value
	 * @since 4.3.3
	 */
	public function offsetSet($offset, $item): void
	{
		$this->_param ??= [];
		if (!$this->getIsParameterArray()) {
			return;
		}
		$this->_param[$offset] = $item;
	}

	/**
	 * This method is required by the interface \ArrayAccess. When the Parameter
	 * is an array (or ArrayAccess) this will set the item at $offset.  Otherwise
	 * this has no effect.
	 * @param mixed $offset the offset to unset element
	 * @since 4.3.3
	 */
	public function offsetUnset($offset): void
	{
		if (!$this->getIsParameterArray()) {
			return;
		}
		unset($this->_param[$offset]);
	}
}
