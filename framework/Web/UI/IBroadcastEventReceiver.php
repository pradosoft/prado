<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

/**
 * IBroadcastEventReceiver interface
 *
 * If a control wants to check broadcast event, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
interface IBroadcastEventReceiver
{
	/**
	 * Handles broadcast event.
	 * This method is invoked automatically when an event is broadcasted.
	 * Within this method, you may check the event name given in
	 * the event parameter to determine  whether you should respond to
	 * this event.
	 * @param TControl sender of the event
	 * @param TBroadCastEventParameter event parameter
	 */
	public function broadcastEventReceived($sender,$param);
}