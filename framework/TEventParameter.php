<?php

/**
 * TEventParameter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <belisoful@icloud.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use ArrayAccess;
use Prado\Exceptions\TInvalidOperationException;

/**
 * TEventParameter class.
 *
 * TEventParameter is the base class for all event parameter classes.
 * When raising an event, this class captures the event name being raised by being
 * an {@see \Prado\IEventParameter}. TEventParameter encapsulates the parameter data for
 * events. The event parameter is set via {@see setParameter Parameter} property
 * or by constructor parameter.
 *
 * TEventParameter also implements \ArrayAccess to allow direct access to the
 * parameter data when it is an array or implements \ArrayAccess. If Parameter is
 * null (the default constructor value), the Parameter becomes an array upon offsetSet.
 *
 * TEventParameter tracks whether the parameter has been changed via the ParameterChanged
 * property. It is a one-way flag that, once set to true, remains true until
 * {@see resetParameterChanged} is called. {@see setParameterChanged} is useful for
 * handlers that modify the parameter object (e.g., TMap) but does not change the
 * TEventParameter's reference to that object.
 *
 * TEventParameter supports a {@see setReadOnly ReadOnly} mode. When ReadOnly is true,
 * any attempt to mutate the parameter — via {@see setParameter}, {@see offsetSet}, or
 * {@see offsetUnset} — will throw a {@see \Prado\Exceptions\TInvalidOperationException}.
 * Reads are always permitted regardless of ReadOnly state.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> ArrayAccess, ParameterChanged, ReadOnly
 * @since 3.0
 */
class TEventParameter extends \Prado\TComponent implements IEventParameter, ArrayAccess
{
	private string $_eventName = '';
	private mixed $_param = null;
	private bool $_parameterChanged = false;
	private bool $_readOnly = false;

	/**
	 * Constructor.
	 * @param null|mixed $parameter parameter of the event
	 * @since 4.3.0
	 */
	public function __construct(mixed $parameter = null)
	{
		$this->setParameter($parameter);
		$this->resetParameterChanged();
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
	 * Setting the Event Name also resets the ParameterChanged back to false.
	 * This method is called by {@see TComponent::raiseEvent} to set the EventName
	 * before the event handlers are processed.
	 * @param string $value name of the event
	 * @since 4.3.0
	 */
	public function setEventName(string $value)
	{
		$this->_eventName = $value;
		$this->resetParameterChanged();
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
	 * @throws TInvalidOperationException if the TEventParameter is read-only
	 * @since 4.3.0
	 */
	public function setParameter(mixed $value)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('eventparam_readonly', $this::class);
		}
		$this->setParameterChanged($value !== $this->_param);
		$this->_param = $value;
	}

	/**
	 * @return bool Returns if the Parameter has changed
	 * @since 4.3.3
	 */
	public function getParameterChanged(): bool
	{
		return $this->_parameterChanged;
	}

	/**
	 * This provides handlers to be able to set the "parameter changed" if the internal
	 * Event Parameter was an object, like TMap.  The handler can changed the TMap
	 * (event parameter object) without changing the TEventParameter::Parameter. This
	 * method provides a way to "manually" set the ParameterChanged of the event.
	 * This is a one-way function where once ParameterChanged is true, it remains true
	 * until {@see resetParameterChanged} is called. If false is passed, this method
	 * does nothing.
	 * @param bool $value Sets if the Parameter has changed.
	 * @since 4.3.3
	 */
	public function setParameterChanged(bool $value)
	{
		$this->_parameterChanged = $this->_parameterChanged || $value;
	}

	/**
	 * This resets the ParameterChanged indicator back to false.
	 * @since 4.3.3
	 */
	public function resetParameterChanged()
	{
		$this->_parameterChanged = false;
	}

	/**
	 * @return bool whether the TEventParameter is read-only
	 * @since 4.3.3
	 */
	public function getReadOnly(): bool
	{
		return $this->_readOnly;
	}

	/**
	 * When ReadOnly is true, any call to {@see setParameter}, {@see offsetSet}, or
	 * {@see offsetUnset} will throw a {@see \Prado\Exceptions\TInvalidOperationException}.
	 * @param bool $value whether the TEventParameter should be read-only
	 * @since 4.3.3
	 */
	public function setReadOnly(bool $value)
	{
		$this->_readOnly = $value;
	}

	/**
	 * @return bool if the Parameter is an array or is an instanceof ArrayAccess
	 * @since 4.3.3
	 */
	public function getParameterIsArray(): bool
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
		if (!$this->getParameterIsArray()) {
			return false;
		}

		return isset($this->_param[$offset]);
	}

	/**
	 * This method is required by the interface \ArrayAccess. When the Parameter
	 * is an array (or ArrayAccess) this will get the item at $offset.  If Parameter
	 * is not an array, this returns null.
	 * @param mixed $offset the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found.
	 * @since 4.3.3
	 */
	public function offsetGet($offset): mixed
	{
		if (!$this->getParameterIsArray()) {
			return null;
		}

		return $this->_param[$offset] ?? null;
	}

	/**
	 * This method is required by the interface \ArrayAccess. When the Parameter
	 * is an array (or ArrayAccess) this will set the item at $offset.  Otherwise
	 * this has no effect.
	 * @param mixed $offset the offset to set element
	 * @param mixed $item the element value
	 * @throws TInvalidOperationException if the TEventParameter is read-only
	 * @since 4.3.3
	 */
	public function offsetSet($offset, $item): void
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('eventparam_readonly', $this::class);
		}
		if ($this->_param === null) {
			$this->setParameter([]);
		}
		if (!$this->getParameterIsArray()) {
			return;
		}

		$changed = true;
		if (isset($this->_param[$offset])) {
			$changed = ($item !== $this->_param[$offset]);
		}
		if (!$changed) {
			return;
		}

		$this->_param[$offset] = $item;
		$this->setParameterChanged(true);
	}

	/**
	 * This method is required by the interface \ArrayAccess. When the Parameter
	 * is an array (or ArrayAccess) this will unset the item at $offset.  Otherwise
	 * this has no effect.
	 * @param mixed $offset the offset to unset element
	 * @throws TInvalidOperationException if the TEventParameter is read-only
	 * @since 4.3.3
	 */
	public function offsetUnset($offset): void
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('eventparam_readonly', $this::class);
		}
		if (!$this->getParameterIsArray() || !isset($this->_param[$offset])) {
			return;
		}

		unset($this->_param[$offset]);
		$this->setParameterChanged(true);
	}
}
