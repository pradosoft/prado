<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System
 */

/**
 * ICallbackEventHandler interface.
 *
 * If a control wants to respond to callback event, it must implement this
 * interface.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package System
 * @since 3.1
 */
interface ICallbackEventHandler
{
	/**
	 * Raises callback event. The implementation of this function should raise
	 * appropriate event(s) (e.g. OnClick, OnCommand) indicating the component
	 * is responsible for the callback event.
	 * @param TCallbackEventParameter the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($eventArgument);
}