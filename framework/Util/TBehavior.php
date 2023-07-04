<?php
/**
 * TBehavior class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;
use Prado\Util\TCallChain;

use WeakReference;

/**
 * TBehavior is the base class for behaviors that extend owner components with new
 * state information, properties, run time methods, and process modification.  Each
 * instance of the TBehavior can only have one owner.  This tracks behavior event
 * handlers attachment status on its owner.
 *
 * TBehavior is one of two types of behaviors in PRADO. The other type is the
 * {@see \Prado\Util\TClassBehavior} where each instance can have multiple owners and is
 * is designed to not retain per object information (acting stateless).
 *
 * Behaviors can be attached to instanced objects, with {@see \Prado\TComponent::attachbehavior},
 * or to each class, the parent classes, interfaces, and first level traits used by
 * the class(es) with {@see \Prado\TComponent::attachClassBehavior}. Class-wide behaviors
 * cannot be attached to the root class {@see \Prado\TComponent} but can attach to any subclass.
 * All new components with an attached class behavior will receive the behavior on
 * instancing.  Instances of a class will receive the class behavior if they are
 * {@see \Prado\TComponent::listen}ing.
 *
 * TComponent clones instanced IBehavior when applied to whole classes.
 *
 * TBehavior is a subclass of {@see \Prado\Util\TBaseBehavior} that implements the core
 * behavior functionality.  See {@see \Prado\Util\IBehavior} for implementation details.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.2.3
 */
class TBehavior extends TBaseBehavior implements IBehavior
{
	/** @var ?WeakReference The owner component of the behavior. Default: null */
	private ?WeakReference $_owner = null;

	/** @var bool Are the handlers installed on the owner. Default: false */
	private bool $_handlersInstalled = false;

	/**
	 * This resets the owner and _handlersInstalled flag on cloning.
	 * @since 4.2.3
	 */
	public function __clone()
	{
		$this->_owner = null;
		$this->_handlersInstalled = false;
		parent::__clone();
	}

	/**
	 * Attaches the behavior object to the new owner component.  This is normally called
	 * by the new owner when attaching a behavior, by {@see \Prado\TComponent::attachBehavior},
	 * and not directly called.
	 *
	 * The default implementation will set the {@see getOwner Owner} property and
	 * synchronized the behavior event handlers (cached in {@see eventsLog}) with
	 * the owner.  Make sure you call the parent implementation if you override this method.
	 * @param TComponent $owner The owner component that this behavior is being attached.
	 * @throws TInvalidOperationException When attaching a behavior that is already
	 *   attached to an owner or the owner isn't a TComponent.
	 */
	public function attach($owner)
	{
		if ($this->_owner !== null) {
			throw new TInvalidOperationException('behavior_has_owner', $this->getName());
		}
		if (!($owner instanceof TComponent)) {
			throw new TInvalidOperationException('behavior_bad_attach_component', $this->getName());
		}
		$this->_owner = WeakReference::create($owner);
		parent::attach($owner);
	}

	/**
	 * Detaches the behavior object from the owner component.  This is normally called
	 * by the owner when detaching a behavior, by {@see \Prado\TComponent::detachBehavior},
	 * and not directly called.
	 *
	 * The default implementation detaches the behavior event handlers (cached in {@see
	 * eventsLog}) and resets the owner. Make sure you call this parent implementation
	 * if you override this method.
	 * @param TComponent $owner The component this behavior is being detached from.
	 * @throws TInvalidOperationException When detaching without an owner or from a
	 *   component that isn't the behaviors owner.
	 */
	public function detach($owner)
	{
		if ($this->_owner === null) {
			throw new TInvalidOperationException('behavior_detach_without_owner', $this->getName());
		}
		if (!$owner || $this->getOwner() !== $owner) {
			throw new TInvalidOperationException('behavior_detach_wrong_owner', $this->getName());
		}
		parent::detach($owner);
		$this->_owner = null;
		$this->_name = null;
	}

	/**
	 * This class stores the owner as a WeakReference.  This method retrieves and re-references
	 * the owner.
	 * @return ?object The owner component that this behavior is attached to.
	 *   Default null before the behavior is attached to an owner.
	 */
	public function getOwner(): ?object
	{
		if ($this->_owner) {
			return $this->_owner->get();
		}
		return null;
	}

	/**
	 * This returns an array of the one owner that the TBehavior is attached to.
	 * TClassBehavior could return multiple owners, but this type of behavior only
	 *  has one.
	 * @return array An array of the owner component.
	 * @since 4.2.3
	 */
	public function getOwners(): array
	{
		if ($owner = $this->getOwner()) {
			return [$owner];
		}
		return [];
	}

	/**
	 * @return bool Is the behavior attached to an owner.
	 * @since 4.2.3
	 */
	public function hasOwner(): bool
	{
		return $this->_owner !== null;
	}

	/**
	 * @param object $component The object to check if its the owner of the behavior.
	 * @return bool Is the object an owner of the behavior.
	 * @since 4.2.3
	 */
	public function isOwner(object $component): bool
	{
		return $this->_owner && $this->getOwner() === $component;
	}

	/**
	 * This dynamic event method tracks the "behaviors enabled" status of an owner. Subclasses
	 * can call this method (as "parent::dyEnableBehaviors()") without the $chain and it will
	 * delegate the $chain continuation to the subclass implementation. At this point,
	 * the behaviors are already enabled in the owner.
	 * @param ?TCallChain $chain The chain of dynamic events being raised.  Default
	 *   is null for no chain continuation.
	 * @since 4.2.3
	 */
	public function dyEnableBehaviors(?TCallChain $chain = null)
	{
		$this->syncEventHandlers();
		if ($chain) {
			$chain->dyEnableBehaviors();
		}
	}

	/**
	 * This dynamic event method tracks the "behaviors disabled" status of an owner. Subclasses
	 * can call this parent method (as "parent::dyDisableBehaviors()") without the $chain
	 * and it will delegate the $chain continuation to the subclass implementation.
	 * @param ?TCallChain $chain The chain of dynamic events being raised.  Default
	 *   is null for no chain continuation.
	 * @since 4.2.3
	 */
	public function dyDisableBehaviors(?TCallChain $chain = null)
	{
		$this->syncEventHandlers();
		if ($chain) {
			$chain->dyDisableBehaviors();
		}
	}

	/**
	 * This gets the attachment status of the behavior event handlers on the owner
	 * component.
	 * @param ?TComponent $component The component to check the attachment status.
	 *   Default null for the IBehavior owner status.  This must match the owner if/when
	 *   provided.
	 * @return bool Are the behavior handlers attached to the owner events.
	 * @since 4.2.3
	 */
	protected function getHandlersStatus(?TComponent $component = null): ?bool
	{
		if ($component && $this->getOwner() !== $component) {
			return null;
		}
		return $this->_handlersInstalled;
	}

	/**
	 * This sets the attachment status of the behavior handlers on the owner. It only
	 * returns true when there is a change in status.
	 * @param TComponent $component The owner of the behavior.
	 * @param bool $attach "true" to attach the handlers or "false" to detach.
	 * @return bool Is there a change in the attachment status on the owner.
	 * @since 4.2.3
	 */
	protected function setHandlersStatus(TComponent $component, bool $attach): bool
	{
		if ($this->getOwner() !== $component) {
			return false;
		}
		if($attach ^ $this->_handlersInstalled) {
			$this->_handlersInstalled = $attach;
			return true;
		}
		return false;
	}

	/**
	 * Returns an array with the names of all variables of this object that should
	 * NOT be serialized because their value is the default one or useless to be cached
	 * for the next page loads.  Reimplement in derived classes to add new variables,
	 * but remember to also to call the parent implementation first.
	 * @param array $exprops by reference
	 * @since 4.2.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_owner";
		$exprops[] = "\0" . __CLASS__ . "\0_handlersInstalled";
	}
}
