<?php

/**
 * TGlobalClassAware class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\TComponent;

/**
 * TGlobalClassAware class.
 *
 * This behavior registers the handlers `fxAttachClassBehavior` and `fxDetachClassBehavior`
 * of the owner to listen for dynamic changes to its class behaviors (after it is
 * instanced).
 *
 * This should only be used when the TComponent is not {@see \Prado\TComponent::listen()}ing
 * or the handlers will be double added.  Listening is turned on automatically with
 * {@see \Prado\TComponent::getAutoGlobalListen()} (returning true) for select classes.
 *
 * Without this behavior (or listen), an instanced TComponent will not update its
 * class behaviors when there is a change in the global class behaviors.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TGlobalClassAware extends \Prado\Util\TBehavior
{
	/**
	 * Attaches the component event handlers:
	 * {@see \Prado\TComponent::fxAttachClassBehavior()} and {@see \Prado\TComponent::fxDetachClassBehavior()}
	 * @param TComponent $component
	 * @return bool Attaching handlers.
	 */
	protected function attachEventHandlers(TComponent $component): bool
	{
		if ($return = parent::attachEventHandlers($component)) {
			$component->attachEventHandler('fxAttachClassBehavior', [$component, 'fxAttachClassBehavior'], $this->getPriority());
			$component->attachEventHandler('fxDetachClassBehavior', [$component, 'fxDetachClassBehavior'], $this->getPriority());
		}
		return $return;
	}

	/**
	 * Detaches the component event handlers:
	 * {@see \Prado\TComponent::fxAttachClassBehavior()} and {@see \Prado\TComponent::fxDetachClassBehavior()}
	 * @param TComponent $component
	 * @return bool Detaching handlers.
	 */
	protected function detachEventHandlers(TComponent $component): bool
	{
		if ($return = parent::detachEventHandlers($component)) {
			$component->detachEventHandler('fxAttachClassBehavior', [$component, 'fxAttachClassBehavior']);
			$component->detachEventHandler('fxDetachClassBehavior', [$component, 'fxDetachClassBehavior']);
		}
		return $return;
	}
}
