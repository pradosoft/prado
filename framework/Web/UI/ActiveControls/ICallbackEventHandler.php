<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * ICallbackEventHandler interface.
 *
 * If a control wants to respond to callback event, it must implement this
 * interface.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
interface ICallbackEventHandler
{
	/**
	 * Raises callback event. The implementation of this function should raise
	 * appropriate event(s) (e.g. OnClick, OnCommand) indicating the component
	 * is responsible for the callback event.
	 * @param TCallbackEventParameter $eventArgument the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($eventArgument);
}
