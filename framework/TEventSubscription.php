<?php
/**
 * TEventSubscription classes
 *
 * @author Brad Anderson <belisoful@icloud.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TInvalidOperationException;

use ArrayAccess;
use WeakReference;

/**
 * TEventSubscription class.
 *
 * This class subscribes an event handler to an event for a limited period of time.
 *
 * ```php
 *	{
 * 		$exitLoop = false;
 *		$subscription = new TEventSubscription($dispatcher, 'fxSignalInterrupt',
 *			function ($sender, $param) use (&$exitLoop){$exitLoop = true;});
 *      ...
 *	} // dereference unsubscribes
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TEventSubscription extends \Prado\Collections\TCollectionSubscription
{
	/** @var ?WeakReference The component with the event being subscribed. */
	private ?WeakReference $_component = null;

	/** @var ?string The event being subscribed. */
	private ?string $_event = null;

	/**
	 * Constructor.
	 * @param TComponent $component The component with the event being subscribed.
	 * @param mixed $event The event of the component being subscribed.
	 * @param mixed $handler The handler being added to the event.
	 * @param null|float|int $priority The priority of the handler in the event.
	 * @param ?bool $autoSubscribe
	 * @param mixed $index
	 */
	public function __construct(?TComponent $component = null, mixed $event = null, mixed $handler = null, null|int|float $priority = null, ?bool $autoSubscribe = null, mixed $index = null)
	{
		if ($component) {
			$this->setComponent($component);
		}
		if ($event) {
			$this->setEvent($event);
		}
		parent::__construct(null, $index, $handler, $priority, false, $autoSubscribe);
	}

	/**
	 * Gets the component event handler.
	 * @param bool $weak
	 * @return null|array|ArrayAccess|WeakReference The Event Handler from the component.
	 */
	public function &getArray(bool $weak = false): array|ArrayAccess|WeakReference|null
	{
		if ($handlers = parent::getArray($weak)) {
			return $handlers;
		}

		$component = $this->getComponent();
		$event = $this->getEvent();

		if ($event !== null && strncasecmp($event, 'fx', 2) === 0) {
			$component ??= Prado::getApplication();
		}

		if (!$component || !$event) {
			$return = null;
			return $return;
		}

		if (!$component->hasEvent($event)) {
			$return = null;
			return $return;
		}

		$handlers = $component->getEventHandlers($event);

		parent::setArray($handlers);

		return $handlers;
	}

	/**
	 * The Array for a TEventSubscription cannot be set
	 * @param null|array|ArrayAccess $value
	 * @throws TInvalidOperationException This property cannot be set directly.
	 */
	public function setArray(null|array|ArrayAccess &$value): static
	{
		if ($value !== null) {
			throw new TInvalidOperationException('eventsubscription_no_setarray');
		}

		return $this;
	}

	/**
	 * @param bool $weak Return the WeakReference of the component, default false.
	 * @return mixed The component with the event being subscribed.
	 */
	public function getComponent(bool $weak = false): mixed
	{
		if (!$weak && $this->_component) {
			return $this->_component->get();
		}
		return $this->_component;
	}

	/**
	 * @param ?TComponent $value The component with the event being subscribed.
	 * @throws TInvalidOperationException If the item is already subscribed.
	 * @return static The current object.
	 */
	public function setComponent(?TComponent $value): static
	{
		if ($this->getIsSubscribed()) {
			throw new TInvalidOperationException('eventsubscription_no_change', 'Component');
		}

		if ($value !== null) {
			$value = WeakReference::create($value);
		}

		$events = null;
		parent::setArray($events);

		$this->_component = $value;

		return $this;
	}

	/**
	 * @return mixed The event being subscribed.
	 */
	public function getEvent(): mixed
	{
		return $this->_event;
	}

	/**
	 * @param mixed $value The event being subscribed.
	 * @throws TInvalidOperationException If the item is already subscribed.
	 * @return static The current object.
	 */
	public function setEvent(mixed $value): static
	{
		if ($this->getIsSubscribed()) {
			throw new TInvalidOperationException('eventsubscription_no_change', 'Event');
		}

		$events = null;
		parent::setArray($events);

		if (is_string($value)) {
			$value = strtolower($value);
		}

		$this->_event = $value;

		return $this;
	}

}
