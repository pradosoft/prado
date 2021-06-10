<?php

/**
 * IPluginModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */
 
namespace Prado\Util;

/**
 * IPluginModule interface.
 *
 * This interface must be implemented by plugin modules.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado
 * @since 4.2.0
 */
interface IPluginModule
{
	/**
	 * @return string the path of the plugin
	 */
	public function getPluginPath();
}
