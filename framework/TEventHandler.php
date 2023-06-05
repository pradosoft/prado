<?php
/**
 * TEventHandler class
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Collections\IPriorityProperty;
use Prado\Collections\IWeakRetainable;
use Prado\Collections\TPriorityPropertyTrait;
use Prado\Exceptions\TApplicationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;

use Closure;
use WeakReference;

/**
 * TEventHandler class
 *
 * This class is a helper class for passing specific data along with an event handler.
 * Normal event handlers only have a sender and parameter, but under a TEventHandler
 * there is a third parameter in the event handler method for data.  This class is
 * invokable and will pass invoked method arguments forward to the managed handler
 * with the specified data.
 *
 * A TEventHandler handler method would be implemented as such:
 * <code>
 *    $handler = new TEventHandler([$object, 'myHandler'], ['key' => 'data']);
 *    $handler($sender, $param); // <- invokable
 *    $component->attachEventHandler('onMyEvent', $handler, $priority);
 *
 *  // In the $object class:
 *    public function myHandler(object $sender, mixed $param, mixed $data = null): mixed
 *    {
 *	 	// $data === ['key' => 'data']
 *       ....
 *    }
 * </code>
 * In this instance, $data is default to null so it can be called on a raised
 * attached event without TEventHandler.  If you will only have a your handler use
 * TEventHandler, then $data can be required (without the null default).
 *
 * There are several ways to access the event handler (callable).  {@link getHandler}
 * will return the event handler and can be requested to return the callable as
 * WeakReference.
 *
 * The event handler can be accessed by ArrayAccess as well.  For example:
 * <code>
 *		$handler = new TEventHandler('TMyClass::myStaticHandler', ['data' => 2, ...]);
 *		$handler[null] === 'TMyClass::myStaticHandler' === $handler->getHandler();
 *		$handler[0] === 'TMyClass::myStaticHandler';
 *		$handler[1] === null
 *		$handler[2] === ['data' => 2, ...] === $handler->getData();
 *
 *		$handler = new TEventHandler([$behavior, 'myHandler'], ['data' => 3, ...]);
 *		$handler[null] === [$behavior, 'myHandler'] === $handler->getHandler();
 *		$handler[0] === $behavior;
 *		$handler[1] === 'myHandler';
 *		$handler[2] === ['data' => 3, ...] === $handler->getData();
 *
 *		// Add the handler to the event at priority 12 (the default is 10)
 *		$component->attachEventHandler('onMyEvent', $handler, 12);
 * </code>
 *
 * PRADO event handler objects are stored as WeakReference to improve PHP garbage
 * collection.  To enable this functionality, TEventHandler holds its callable object
 * as WeakReference and re-references the callable handler object when used.
 *
 * The only exceptions to conversion into WeakReference are Closure and {@link IWeakRetainable}.
 * Closure and IWeakRetainable are retained without WeakReference conversion because
 * they might be the only instance in the application.  Holding these instances
 * directly will properly increment their PHP use counter to be retained.
 *
 * {@link hasWeakObject} returns if there is an object being held as WeakReference.
 *
 * When the TEventHandler {@link getData data} is an array, and the 3rd parameter
 * $data of {@link __invoke invoke} is also an array, an `array_replace` (with the
 * function parameter array taking precedence) will combine the data.
 *
 * In nesting TEventHandlers, a base TEventHandler, pointing to a callable, can be
 * instanced with core data.  Children TEventHandler[s] can point to the parent
 * TEventHandler and override specific data items in the {@link getData Data} array
 * with its own (array) data items.  This only works if the Data is an array otherwise
 * the children will override the parent TEventHandler data.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TEventHandler implements IPriorityProperty, IWeakRetainable, \ArrayAccess, \Countable
{
	use TPriorityPropertyTrait;

	/** @var mixed The callable event handler being managed. */
	private mixed $_handler;

	/** @var mixed The data to feed the handler when invoked. */
	private mixed $_data;

	/** @var bool Is the callable object converted into WeakReference? */
	private bool $_weakObject = false;

	/**
	 * Constructs a new TEventHandler and initializes the Event Handler and Data.
	 * @param mixed $handler The event handler being managed.
	 * @param mixed $data The data to feed the event handler.
	 */
	public function __construct(mixed $handler, mixed $data = null)
	{
		if(!is_callable($handler)) {
			throw new TInvalidDataTypeException('eventhandler_not_callable');
		}

		if (is_array($handler) && is_object($handler[0]) && !($handler[0] instanceof IWeakRetainable)) {
			$handler[0] = WeakReference::create($handler[0]);
			$this->_weakObject = true;
		} elseif (is_object($handler) && !($handler instanceof Closure) && !($handler instanceof IWeakRetainable)) {
			$handler = WeakReference::create($handler);
			$this->_weakObject = true;
		}

		$this->_handler = $handler;
		$this->_data = $data;
	}

	/**
	 * This calls the handler with the specified data.  If no $data is specified then
	 * TEventHandler injects its own data for the event handler.  When $data is an array
	 * and the TEventHandler data is an array, the data is combined by `array_replace`,
	 * with the function input array data  taking precedence over TEventHandler data.
	 * This allows TEventHandlers to be nested, with children taking precedence.
	 * @param mixed $sender The sender that raised the event.
	 * @param mixed $param The parameter that goes with the raised event.
	 * @param null|mixed $data The data for the managed event handler. default null
	 *   for data from the TEventHandler.
	 * @param array $argv Any additional function arguments.
	 * @throws TApplicationException
	 * @return mixed The result of the event handler.
	 */
	public function __invoke(mixed $sender = null, mixed $param = null, mixed $data = null, ...$argv): mixed
	{
		$handler = $this->getHandler();
		if (!$handler) {
			throw new TApplicationException('eventhandler_lost_weak_ref');
		}
		if (is_array($data) && is_array($this->_data)) {
			$data = array_replace($this->_data, $data);
		} elseif ($data === null) {
			$data = $this->_data;
		}
		return $handler($sender, $param, $data, ...$argv);
	}

	/**
	 * Gets the handler being managed. The WeakReference version of the handler can
	 * be retrieved by passing true to the $weak parameter.  When there are nested
	 * TEventHandlers, this will return the next TEventHandler as an invokable and will
	 * not link to the last and actual callable handler.
	 * @param bool $weak Return the handler with WeakReference instead of objects. Default false.
	 * @return null|array|object|string The callable event handler.
	 */
	public function getHandler(bool $weak = false): null|string|object|array
	{
		$handler = $this->_handler;
		if (!$weak && $this->_weakObject) {
			if (is_array($handler) && is_object($handler[0]) && ($handler[0] instanceof WeakReference)) {
				if ($obj = $handler[0]->get()) {
					$handler[0] = $obj;
				} else {
					$handler = null;
				}
			} elseif (is_object($handler) && ($handler instanceof WeakReference)) {
				$handler = $handler->get();
			}
		}
		return $handler;
	}

	/**
	 * This methods checks if the item is the same as the handler.  When TEventHandler
	 * are nested, we check the nested TEventHandler for isSameHandler.
	 * @param mixed $item
	 * @param bool $weak
	 * @return bool Is the $item the same as the managed callable.
	 */
	public function isSameHandler(mixed $item, bool $weak = false): bool
	{
		$handler = $this->getHandler($weak);
		if ($item === $handler) {
			return true;
		}
		if ($handler instanceof TEventHandler) {
			return $handler->isSameHandler($item, $weak);
		}
		return false;
	}

	/**
	 * If there is an object referenced in the callable, this method returns the object.
	 * By default, the WeakReference is re-referenced.  The WeakReference can be returned
	 * by passing true for $weak.  This will return Closure and IWeakRetainable objects
	 * without any WeakReference because they are exempt from conversion into WeakReference.
	 * When TEventHandlers are nested, this returns the last and actual callable object.
	 * @param bool $weak Return the callable with WeakReference instead of objects.
	 * @return ?object The object of the event handler if there is one.
	 */
	public function getHandlerObject(bool $weak = false): ?object
	{
		$handler = null;
		if (is_array($this->_handler) && is_object($this->_handler[0])) {
			$handler = $this->_handler[0];
		} elseif($this->_handler instanceof TEventHandler) {
			return $this->_handler->getHandlerObject($weak);
		} elseif(is_object($this->_handler)) {
			$handler = $this->_handler;

		}
		if(!$weak && $this->_weakObject) {
			$handler = $handler->get();
		}
		return $handler;
	}

	/**
	 * This checks if the managed handler is still valid.  When TEventHandler are nested,
	 * this returns if the last and actual handler is still valid (from WeakReference).
	 * @return bool Does the managed event Handler exist and is callable.
	 */
	public function hasHandler(): bool
	{
		if($this->_handler instanceof TEventHandler) {
			return $this->_handler->hasHandler();
		}
		return $this->getHandler() !== null;
	}

	/**
	 * Returns the data associated with the managed event handler. By passing true to
	 * $withHandlerData, this will combine the data from nested TEventHandlers when the
	 * data is in array format.  The children data take precedence in the combining
	 * of data.
	 * @param bool $withHandlerData
	 * @return mixed The data associated with the event handler.
	 */
	public function getData(bool $withHandlerData = false): mixed
	{
		if ($withHandlerData && ($this->_handler instanceof TEventHandler) && is_array($this->_data) && is_array($data = $this->_handler->getData(true))) {
			return array_replace($data, $this->_data);
		}
		return $this->_data;
	}

	/**
	 * @param mixed $data The data associated with the event handler.
	 */
	public function setData(mixed $data): void
	{
		$this->_data = $data;
	}

	/**
	 * Does the object contain any WeakReference objects?  When there are nested TEventHandler
	 * this will return the last and actual data from the actual callable.
	 * @return bool If there are objects as WeakReferences.
	 */
	public function hasWeakObject(): bool
	{
		if($this->_handler instanceof TEventHandler) {
			return $this->_handler->hasWeakObject();
		}
		return $this->_weakObject;
	}

	/**
	 * Returns the number of items in the managed event handler (with data).
	 * This method is required by \Countable interface.
	 * @return int The number of items in the managed event handler.
	 */
	public function count(): int
	{
		return $this->getCount();
	}

	/**
	 * There are 3 items when the handler is an array, and 2 items when the handler
	 * is an invokable object or string.
	 * @return int The number of items in the managed event handler (with data).
	 */
	public function getCount(): int
	{
		return is_array($this->_handler) ? 3 : 2;
	}

	/**
	 * These values exist: null (the handler), 0, 2, and conditionally 1 on the handler
	 * being an array.
	 * @param mixed $offset The offset to check existence.
	 * @return bool Does the property exist for the managed event handler (with data).
	 */
	public function offsetExists(mixed $offset): bool
	{
		return $offset === null || $offset === 0 || $offset === 2 || (is_array($this->_handler) ? $offset === 1 : 0);
	}

	/**
	 * This is a convenience method for getting the data of the TEventHandler.
	 * - Index null will return the {@link getHandler Handler},
	 * - Index '0' will return the handler if its a string or object, or the first element
	 *   of the callable array;
	 * - Index '1' will return the second element of the callable array or null if not
	 *   an array; and
	 * - Index '2' will return the data associated with the managed event handler.
	 * If the WeakReference object of the managed event handler is invalid, this will return null
	 * for all handler offsets (null, 0 and 1).  Data will still return properly even
	 * when the handler is invalid.
	 * @param mixed $offset Which property of the managed event handler to retrieve.
	 * @throws TInvalidDataValueException When $offset is not a property of the managed event handler.
	 * @return mixed The value of the handler, handler elements, or data.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		if ($offset === null) {
			return $this->getHandler();
		}
		if (is_numeric($offset)) {
			$offset = (int) $offset;
			if ($offset === 2) {
				return $this->_data;
			} elseif(is_array($this->_handler)) {
				if ($offset === 0) {
					if ($this->_weakObject) {
						return $this->_handler[$offset]->get();
					}
					return $this->_handler[$offset];
				} elseif ($offset === 1) {
					if ($this->_weakObject && !$this->_handler[0]->get()) {
						return null;
					}
					return $this->_handler[$offset];
				}
			} elseif ($offset === 0) {
				if ($this->_weakObject) {
					return $this->_handler->get();
				}
				return $this->_handler;
			} elseif ($offset === 1) {
				return null;
			}
		}
		throw new TInvalidDataValueException('eventhandler_bad_offset', $offset);
	}

	/**
	 * This is a convenience method for setting the data of the managed event handler.
	 * This cannot set the event handler but can set the data.
	 * - Index '2' will set the data associated with the managed event handler.
	 * @param mixed $offset Only accepts '2' to set the data associated with the event handler.
	 * @param mixed $value The data being set.
	 * @throws TInvalidOperationException When trying to set the handler elements.
	 * @throws TInvalidDataValueException When $offset is not a property of the managed event handler.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (is_numeric($offset)) {
			$offset = (int) $offset;
			if ($offset === 2) {
				$this->setData($value);
				return;
			}
		}
		if ($offset === null || is_numeric($offset) && ($offset == 0 || $offset == 1)) {
			throw new TInvalidOperationException('eventhandler_no_set_handler', $offset);
		}
		throw new TInvalidDataValueException('eventhandler_bad_offset', $offset);
	}

	/**
	 * This is a convenience method for resetting the data to null.  The Handler cannot
	 * be unset.  However, the data can be unset.  The only valid index for unsetting
	 * the handler data is '2'.
	 * @param mixed $offset Only accepts '2' for the data element.
	 * @throws TInvalidOperationException When trying to set the handler elements.
	 * @throws TInvalidDataValueException When $offset is not a property of the managed event handler.
	 */
	public function offsetUnset(mixed $offset): void
	{
		if (is_numeric($offset)) {
			$offset = (int) $offset;
			if ($offset === 2) {
				$this->setData(null);
				return;
			}
		}
		if ($offset === null || is_numeric($offset) && ($offset == 0 || $offset == 1)) {
			throw new TInvalidOperationException('eventhandler_no_set_handler', $offset);
		}
		throw new TInvalidDataValueException('eventhandler_bad_offset', $offset);
	}
}
