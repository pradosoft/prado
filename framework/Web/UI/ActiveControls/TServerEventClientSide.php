<?php

/**
 * TServerEventClientSide class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TServerEventClientSide class.
 *
 * TServerEventClientSide holds the client-side options of a {@see TServerEventTriggeredCallback}.
 * It adds {@see setOnServerEvent OnServerEvent} to the callback options of {@see TCallbackClientSide}.
 *
 * ```xml
 * <com:TServerEventTriggeredCallback Channel="orders" CallbackOnEvent="false"
 *     ClientSide.OnServerEvent="document.getElementById('count').textContent = parameter.Data.count;" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventClientSide extends TCallbackClientSide
{
	/**
	 * @return string The javascript code of the client-side OnServerEvent event.
	 */
	public function getOnServerEvent()
	{
		return $this->getOption('OnServerEvent');
	}

	/**
	 * Sets the javascript code run for each server event that matches the control's EventName,
	 * before its callback. The code receives `sender`, the client control, and `parameter`, the
	 * event `{Channel, ID, Event, Data}`. A `return false;` skips the callback of that event.
	 * @param string $javascript The javascript code of the client-side OnServerEvent event.
	 */
	public function setOnServerEvent($javascript)
	{
		$this->setFunction('OnServerEvent', $javascript);
	}
}
