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
 * IDynamicMethods interface.
 * IDynamicMethods marks an object to receive undefined global or dynamic events.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @since 3.2.3
 */
interface IDynamicMethods
{
	public function __dycall($method, $args);
}
