<?php
/**
 * TEventContent class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * TEventContent class
 *
 * TEventContent loads child controls by raising the {@link getBroadcastEvent BroadcastEvent}
 * 'fx' event.  The handlers then add their own controls to the 
 * child control list in $param.
 *
 * The event {@link getBroadcastEvent} is raised with this control
 * as the $sender and the Control List as $param.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Web\UI
 * @since 4.2.0
 */

class TEventContent extends TCompositeControl
{
	/**
	 * creates child controls from a raised 'fx' event BroadcastEvent 
	 * for handlers to then add their own controls.
	 */
	public function createChildControls()
	{
		if ($event = $this->getBroadcastEvent()) {
			$this->raiseEvent($event, $this, $this->getControls());
		}
	}

	/**
	 * @return string the the event to be raised for creatiChildControls
	 */
	public function getBroadcastEvent()
	{
		return $this->getControlState('BroadcastEvent', '');
	}

	/**
	 * @param mixed $value the the event to be raised for creatiChildControls
	 */
	public function setBroadcastEvent($value)
	{
		$this->setControlState('BroadcastEvent', $value, '');
	}
}
