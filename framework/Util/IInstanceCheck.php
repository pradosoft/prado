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
 * IInstanceCheck This interface allows objects to determine their own
 * 'instanceof' results when {@link TComponent::isa} is called.  This is
 * important with behaviors because behaviors may want to look like
 * particular objects other than themselves.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @package Prado\Util
 * @since 3.2.3
 */
interface IInstanceCheck
{
	/**
	 * The method checks $this or, if needed, the parameter $instance is of type
	 * class.  In the case of a Class Behavior, the instance to which the behavior
	 * is attached may be important to determine if $this is an instance
	 * of a particular class.
	 * @param class|string $class the component that this behavior is checking if it is an instanceof.
	 * @param null|object $instance the object which the behavior is attached to.  default: null
	 * @return null|bool if the this or the instance is of type class.  When null, no information could be derived and
	 * the default mechanisms take over.
	 */
	public function isinstanceof($class, $instance = null);
}
