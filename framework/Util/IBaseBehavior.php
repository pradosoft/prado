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
 */

namespace Prado\Util;

/**
 * IBaseBehavior interface is the base behavior class from which all other
 * behaviors types are derived
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @since 3.2.3
 */
interface IBaseBehavior
{
	/**
	 * The array key for the $config data in instancing behaviors with init($config),
	 */
	public const CONFIG_KEY = "=config";
	/**
	 * Handles behavior configurations from TBehaviorsModule
	 * @param mixed $config
	 * @since 4.2.2
	 */
	public function init($config);
	/**
	 * Attaches the behavior object to the component.
	 * @param \Prado\TComponent $component the component that this behavior is to be attached to.
	 */
	public function attach($component);
	/**
	 * Detaches the behavior object from the component.
	 * @param \Prado\TComponent $component the component that this behavior is to be detached from.
	 */
	public function detach($component);
}
