<?php
/**
 * TApplicationSignals class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;
use Prado\TPropertyValue;
use Prado\Util\TBehavior;
use Prado\Util\TSignalsDispatcher;

/**
 * TApplicationSignals class.
 *
 * This behavior installs the {@see \Prado\Util\TSignalsDispatcher} (or subclass) for
 * the application when PHP pcntl_* is available.  The signals dispatcher class can
 * be specified with {@see self::setSignalsClass()} and is installed when the TApplicationSignals
 * behavior is attached to the TApplication owner.
 *
 * There is a TSignalsDispatcher getter {@see self::getSignalsDispatcher} added to
 * the owner (TApplication) for retrieving the dispatcher.
 *
 * There are two properties of TApplicationSignals for TSignalsDispatcher. {@see
 * self::setAsyncSignals} changes how signals are handled.  When synchronous,
 * {@see \Prado\Util\TSignalsDispatcher::syncDispatch()} must be called for signals
 * to be processed.  When asynchronous, the signals will be handled by atomic interrupt.
 *
 * ```xml
 *		<behavior name="appSignals" AttachToClass="Prado\TApplication" class="Prado\Util\Behaviors\TApplicationSignals" PriorHandlerPriority="5" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TApplicationSignals extends TBehavior
{
	/** @var string the signals class. */
	protected ?string $_signalsClass = null;

	/**
	 * Attaches the TSignalsDispatcher to handle the process signals.
	 * @param TComponent $component The owner.
	 * @return bool Should the behavior's event handlers be attached.
	 */
	protected function attachEventHandlers(TComponent $component): bool
	{
		if ($return = parent::attachEventHandlers($component)) {
			($this->getSignalsClass())::singleton();
		}
		return $return;
	}

	/**
	 * Detaches the TSignalsDispatcher from handling the process signals.
	 * @param TComponent $component The owner.
	 * @return bool Should the behavior's event handlers be detached.
	 */
	protected function detachEventHandlers(TComponent $component): bool
	{
		if ($return = parent::detachEventHandlers($component)) {
			if ($dispatcher = ($this->getSignalsClass())::singleton(false)) {
				$dispatcher->detach();
				unset($dispatcher);
			}
		}
		return $return;
	}

	/**
	 * @return ?object The Signal Dispatcher.
	 */
	public function getSignalsDispatcher(): ?object
	{
		return ($this->getSignalsClass())::singleton(false);
	}

	/**
	 * @return ?string The class of the Signals Dispatcher.
	 */
	public function getSignalsClass(): ?string
	{
		if ($this->_signalsClass === null) {
			$this->_signalsClass = TSignalsDispatcher::class;
		}

		return $this->_signalsClass;
	}

	/**
	 * @param ?string $value The class of the Signals Dispatcher.
	 * @throws TInvalidOperationException When already attached, this cannot be changed.
	 * @throws TInvalidDataValueException When the class is not a TSignalsDispatcher.
	 * @return static The current object.
	 */
	public function setSignalsClass($value): static
	{
		if ($this->getOwner()) {
			throw new TInvalidOperationException('appsignals_no_change', 'SignalsClass');
		}
		if ($value === '') {
			$value = null;
		}

		if ($value !== null) {
			$value = TPropertyValue::ensureString($value);
			if (!is_a($value, TSignalsDispatcher::class, true)) {
				throw new TInvalidDataValueException('appsignals_not_a_dispatcher', $value);
			}
		}

		$this->_signalsClass = $value;

		return $this;
	}

	/**
	 * @return bool Is the system executing signal handlers asynchronously.
	 */
	public function getAsyncSignals(): bool
	{
		return ($this->getSignalsClass())::getAsyncSignals();
	}

	/**
	 * @param mixed $value Set the system to execute signal handlers asynchronously (or synchronously on false).
	 * @return bool Was the system executing signal handlers asynchronously.
	 */
	public function setAsyncSignals($value): bool
	{
		return ($this->getSignalsClass())::setAsyncSignals(TPropertyValue::ensureBoolean($value));
	}

	/**
	 * When the original signal handlers are placed into the Signals Events this is the
	 * priority of original signal handlers.
	 * @return ?float The priority of the signal handlers that were installed before
	 *   the TSignalsDispatcher attaches.
	 */
	public function getPriorHandlerPriority(): ?float
	{
		return ($this->getSignalsClass())::getPriorHandlerPriority();
	}

	/**
	 * @param null|float|string $value The priority of the signal handlers that were installed before
	 *   the TSignalsDispatcher attaches.
	 * @return bool Is the Prior Handler Priority changed.
	 */
	public function setPriorHandlerPriority($value): bool
	{
		if ($value === '') {
			$value = null;
		}
		if ($value !== null) {
			$value = TPropertyValue::ensureFloat($value);
		}
		return ($this->getSignalsClass())::setPriorHandlerPriority($value);
	}
}
