<?php
/**
 * TForkable class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\TComponent;
use Prado\Util\Helpers\TProcessHelper;

/**
 * TForkable class.
 *
 * This attaches the Owner Component `fxPrepareForFork` and `fxRestoreAfterFork`
 * methods as the event handlers for the PRADO global event `fxPrepareForFork` and
 * `fxRestoreAfterFork`, respectively.
 *
 * This should only be used when the TComponent is not {@see \Prado\TComponent::listen()}ing
 * or the handlers will be double added.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TForkable extends \Prado\Util\TBehavior
{
	private int $_methods = 0;

	/**
	 * Attaches the component event handlers:
	 * {@see \Prado\TComponent::fxAttachClassBehavior()} and {@see \Prado\TComponent::fxDetachClassBehavior()}
	 * @param TComponent $component
	 * @return bool Attaching handlers.
	 */
	protected function attachEventHandlers(TComponent $component): bool
	{
		if ($return = parent::attachEventHandlers($component)) {
			if ($component->hasMethod(TProcessHelper::FX_PREPARE_FOR_FORK)) {
				$this->_methods |= 1;
				$component->attachEventHandler(TProcessHelper::FX_PREPARE_FOR_FORK, [$component, TProcessHelper::FX_PREPARE_FOR_FORK], $this->getPriority());
			}
			if ($component->hasMethod(TProcessHelper::FX_RESTORE_AFTER_FORK)) {
				$this->_methods |= 2;
				$component->attachEventHandler(TProcessHelper::FX_RESTORE_AFTER_FORK, [$component, TProcessHelper::FX_RESTORE_AFTER_FORK], $this->getPriority());
			}
		}
		return $return;
	}

	protected function detachEventHandlers(TComponent $component): bool
	{
		if ($return = parent::detachEventHandlers($component)) {
			if ($this->_methods & 1) {
				$component->detachEventHandler(TProcessHelper::FX_PREPARE_FOR_FORK, [$component, TProcessHelper::FX_PREPARE_FOR_FORK]);
			}

			if ($this->_methods & 2) {
				$component->detachEventHandler(TProcessHelper::FX_RESTORE_AFTER_FORK, [$component, TProcessHelper::FX_RESTORE_AFTER_FORK]);
			}
			$this->_methods = 0;
		}
		return $return;
	}
}
