<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * IBehavior interfaces is implemented by instance behavior classes.
 *
 * A behavior is a way to enhance a component with additional methods and
 * events that are defined in the behavior class and not available in the
 * class.  Objects may signal behaviors through dynamic events.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @package Prado\Util
 * @since 3.2.3
 */
interface IBehavior extends IBaseBehavior
{
	/**
	 * @return bool whether this behavior is enabled
	 */
	public function getEnabled();
	/**
	 * @param bool $value whether this behavior is enabled
	 */
	public function setEnabled($value);
}
