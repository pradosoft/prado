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
 * TEventContent loads child controls by raising an 'fx' event.
 *
 * The event {@link getBroadcastEvent} is raised with this control
 * as the $sender to all handlers.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Web\UI
 * @since 4.2.0
 */

class TEventContent extends TCompositeControl
{
	/**
	 * creates child controls from calling an 'fx' event for
	 * handlers to then fill add their own controls.
	 */
	public function createChildControls()
	{
		if ($event = $this->getBroadcastEvent()) {
			$newCtls = $this->raiseEvent($event, $this, null);
			if ($newCtls) {
				$ctls = $this->getControls();
				foreach ($newCtls as $ctl) {
					$ctls[] = $ctl;
				}
			}
		}
	}

	/**
	 * @return string the text value of the label
	 */
	public function getBroadcastEvent()
	{
		return $this->getControlState('BroadcastEvent', '');
	}

	/**
	 * @param string the text value of the label
	 * @param mixed $value
	 */
	public function setBroadcastEvent($value)
	{
		$this->setControlState('BroadcastEvent', $value, '');
	}
}
