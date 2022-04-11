<?php

/**
 * IPluginModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * IPluginModule interface.
 *
 * This interface should be implemented by plugin modules.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
interface IPluginModule extends \Prado\IModule
{
	/**
	 * @return string the path of the plugin
	 */
	public function getPluginPath();
}
