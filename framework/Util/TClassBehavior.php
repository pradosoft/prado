<?php
/**
 * TClassBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Collections\TWeakList;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;
use Prado\Util\TCallChain;

/**
 * TClassBehavior is the base class for class-wide behaviors that extend owner components
 * with new stateless information, properties, run time methods, and process modification.
 * Each instance of the TClassBehavior can have multiple owners.  TClassBehavior
 * tracks all its owners.  It manages the attachment status of the behavior event
 * handlers on each owner.
 *
 * TClassBehavior is one of two types of behaviors in PRADO. The other type is the
 * {@see \Prado\Util\TBehavior} where each instance can have only one owner and has per object
 * state.
 *
 * TClassBehavior must attach to owner components with the same behavior name.
 *
 * Behaviors can be attached to instanced objects, with {@see \Prado\TComponent::attachbehavior},
 * or to each class, the parent classes, interfaces, and first level traits used by
 * the class(es) with {@see \Prado\TComponent::attachClassBehavior}. Class-wide behaviors
 * cannot be attached to the root class {@see \Prado\TComponent} but can attach to any subclass.
 * All new components with an attached class behavior will receive the behavior on
 * instancing.  Instances of a class will receive the class behavior if they are
 * {@see \Prado\TComponent::listen}ing.
 *
 * TClassBehavior is a subclass of {@see \Prado\Util\TBaseBehavior} that implements the core
 * behavior functionality.  See {@see \Prado\Util\IClassBehavior} for implementation details.
 *
 * The interface IClassBehavior is used by TComponent to inject the owner as the
 * first method parameter, when the behavior method is called on the owner, so the
 * behavior knows which owner is calling it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.2.3
 */
class TClassBehavior extends TBaseBehavior implements IClassBehavior
{
	/** @var ?TWeakList The WeakReference list of owner components. Default null
	 */
	private ?TWeakList $_owners = null;

	/** @var null|array|\WeakMap This tracks whether an owner has event handlers attached. */
	private $_handlersInstalled;

	/**
	 * Cloning a new instance clears both the owners and handler attachment tracking
	 */
	public function __clone()
	{
		$this->_owners = null;
		$this->_handlersInstalled = null;
		parent::__clone();
	}

	/**
	 * Initializes the WeakMap to manager the attachment status of owners.
	 * Prior to PHP 8, this will use an array rather than a WeakMap.
	 */
	protected function initMap()
	{
		if (class_exists('\WeakMap')) {
			$this->_handlersInstalled = new \WeakMap();
		} else {
			$this->_handlersInstalled = [];
		}
	}

	/**
	 * Attaches the behavior object to a new owner component.  This is normally called
	 * by the new owner when attaching a behavior, by {@see \Prado\TComponent::attachBehavior},
	 * and not directly called.
	 *
	 * The default implementation will add the new owner to the {@see getOwners} and
	 * synchronize the behavior event handlers (cached in {@see eventsLog}) with the
	 * new owner. Make sure you call this parent implementation if you override this
	 * method.
	 * @param TComponent $component The component this behavior is being attached to.
	 * @throws TInvalidOperationException When attaching to a component that is already
	 *   attached.  Otherwise handlers would not be unique.
	 * @throws TInvalidOperationException When the owner component is not a TComponent.
	 */
	public function attach($component)
	{
		if (!($component instanceof TComponent)) {
			throw new TInvalidOperationException('classbehavior_bad_attach_component', $this->getName());
		}
		if (!$this->_owners) {
			$this->_owners = new TWeakList();
			$this->initMap();
		} elseif ($this->_owners->contains($component)) {
			throw new TInvalidOperationException('classbehavior_owner_attach_once', $this->getName());
		}
		$this->_owners->add($component);
		parent::attach($component);
	}

	/**
	 * Detaches the behavior object from an owner component.  This is normally called
	 * by the owner when detaching a behavior, by {@see \Prado\TComponent::detachBehavior},
	 * and not directly called.
	 *
	 * The default implementation will remove the behavior event handlers (cached in
	 * {@see eventsLog}) from the owner and remove the owner from the {@see getOwners}.
	 * Make sure you call this parent implementation if you override this method.
	 * @param TComponent $component The component this behavior is being detached from.
	 * @throws TInvalidOperationException When detaching without an owner or from a
	 *   component that isn't the behaviors owner.
	 */
	public function detach($component)
	{
		if ($this->_owners === null) {
			throw new TInvalidOperationException('classbehavior_detach_without_owner', $this->getName());
		}
		if (!$component || !$this->_owners->contains($component)) {
			throw new TInvalidOperationException('classbehavior_detach_wrong_owner', $this->getName());
		}
		parent::detach($component);
		if($this->_owners->remove($component) === 0 && !$this->_owners->getCount()) {
			$this->_owners = null;
			$this->_name = null;
			$this->_handlersInstalled = null;
		}
	}

	/**
	 * @return array The owner components that this behavior is attached to.
	 * @since 4.2.3
	 */
	public function getOwners(): array
	{
		if ($this->_owners) {
			return $this->_owners->toArray();
		}
		return [];
	}

	/**
	 * @return bool Is the behavior attached to an owner.
	 * @since 4.2.3
	 */
	public function hasOwner(): bool
	{
		return $this->_owners !== null;
	}

	/**
	 * @param object $component
	 * @return bool Is the object an owner of the behavior.
	 * @since 4.2.3
	 */
	public function isOwner(object $component): bool
	{
		return $this->_owners && $this->_owners->contains($component);
	}

	/**
	 * This dynamic event method tracks the behaviors enabled status of an owner. Subclasses
	 * can call this method (as "parent::dyEnableBehaviors()") without the $chain and it
	 * will delegate the $chain continuation to the subclass implementation. At this point,
	 * the behaviors are already enabled in the owner.
	 * @param TComponent $owner The owner enabling their behaviors.
	 * @param ?TCallChain $chain The chain of dynamic events being raised.  Default
	 *   is null for no continuation.
	 * @since 4.2.3
	 */
	public function dyEnableBehaviors($owner, ?TCallChain $chain = null)
	{
		$this->syncEventHandlers($owner);
		if ($chain) {
			$chain->dyEnableBehaviors();
		}
	}

	/**
	 * This dynamic event method tracks the "behaviors disabled" status of an owner. Subclasses
	 * can call this method (as "parent::dyDisableBehaviors()") without the $chain and
	 * it will delegate the $chain continuation to the subclass implementation.
	 * @param TComponent $owner The owner disabling their behaviors.
	 * @param ?TCallChain $chain The chain of dynamic events being raised.  Default
	 *   is null for no continuation.
	 * @since 4.2.3
	 */
	public function dyDisableBehaviors($owner, ?TCallChain $chain = null)
	{
		$this->syncEventHandlers($owner);
		if ($chain) {
			$chain->dyDisableBehaviors();
		}
	}

	/**
	 * This gets the attachment status of the behavior handlers on the given component.
	 * @param ?TComponent $component The component to check the handlers attachment status.
	 *   This parameter must be provided; null is not supported in TClassBehavior.
	 * @return bool Are the behavior handlers attached to the given owner events.
	 * @since 4.2.3
	 */
	protected function getHandlersStatus(?TComponent $component = null): ?bool
	{
		if (!$component) {
			return null;
		}
		$ref = is_array($this->_handlersInstalled) ? spl_object_id($component) : $component;
		return isset($this->_handlersInstalled[$ref]);
	}

	/**
	 * This sets the attachment status of the behavior handlers on the given owner
	 * component.  It only returns true when there is a change in status.
	 * @param TComponent $component The component to set the handlers attachment status.
	 * @param bool $attach "true" to attach the handlers or "false" to detach.
	 * @return bool Is there a change in the attachment status for the given owner
	 *   component.
	 * @since 4.2.3
	 */
	protected function setHandlersStatus(TComponent $component, bool $attach): bool
	{
		$ref = is_array($this->_handlersInstalled) ? spl_object_id($component) : $component;
		if($attach) {
			if ($this->_owners && $this->_owners->contains($component) && !isset($this->_handlersInstalled[$ref])) {
				$this->_handlersInstalled[$ref] = true;
				return true;
			}
		} elseif (isset($this->_handlersInstalled[$ref])) {
			unset($this->_handlersInstalled[$ref]);
			return true;
		}
		return false;
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 * @since 4.2.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_owners";
		$exprops[] = "\0" . __CLASS__ . "\0_handlersInstalled";
	}
}
