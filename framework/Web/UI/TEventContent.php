<?php
/**
 * TEventContent class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\TPropertyValue;

/**
 * TEventContent class
 *
 * TEventContent loads child controls by raising the {@link getBroadcastEvent BroadcastEvent}
 * 'fx' event.  The handlers then add their own controls to the child control list in $param.
 *
 * The event {@link getBroadcastEvent} is raised with this control
 * as the $sender and the {@link getControls Control} List as $param.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TEventContent extends TCompositeControl
{
	/**
	 * creates child controls by raising the 'fx' event BroadcastEvent
	 * for handlers to then add their own controls.
	 */
	public function createChildControls()
	{
		if ($event = $this->getBroadcastEvent()) {
			$this->raiseEvent($event, $this, $this->getControls());
		}
	}

	/**
	 * @return string the the event to be raised for createChildControls
	 */
	public function getBroadcastEvent()
	{
		return $this->getControlState('BroadcastEvent', '');
	}

	/**
	 * @param string $value the the event to be raised for createChildControls
	 */
	public function setBroadcastEvent($value)
	{
		$this->setControlState('BroadcastEvent', TPropertyValue::ensureString($value), '');
	}
}
