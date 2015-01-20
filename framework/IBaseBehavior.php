<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System
 */

/**
 * IBaseBehavior interface is the base behavior class from which all other
 * behaviors types are derived
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
interface IBaseBehavior {
	/**
	 * Attaches the behavior object to the component.
	 * @param CComponent the component that this behavior is to be attached to.
	 */
	public function attach($component);
	/**
	 * Detaches the behavior object from the component.
	 * @param CComponent the component that this behavior is to be detached from.
	 */
	public function detach($component);
}