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
 * IDynamicMethods interface.
 * IDynamicMethods marks an object to receive undefined global or dynamic events.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
interface IDynamicMethods
{
	public function __dycall($method,$args);
}