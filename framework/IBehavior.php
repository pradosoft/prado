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
 * @package Prado
 */

namespace Prado;

/**
 * IBehavior interfaces is implemented by instance behavior classes.
 *
 * A behavior is a way to enhance a component with additional methods and
 * events that are defined in the behavior class and not available in the
 * class.  Objects may signal behaviors through dynamic events.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package Prado
 * @since 3.2.3
 */
interface IBehavior extends IBaseBehavior
{
	/**
	 * @return boolean whether this behavior is enabled
	 */
	public function getEnabled();
	/**
	 * @param boolean whether this behavior is enabled
	 */
	public function setEnabled($value);
}