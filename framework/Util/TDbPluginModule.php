<?php

/**
 * TDbPluginModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Data\TDbPropertiesTrait;
use Prado\Util\IDbModule;

/**
 * TDbPluginModule class.
 *
 * TDbPluginModule adds database connectivity to the plugin modules. This standardizes
 * the Database Connectivity for Plugins. Also TParameterizeBehavior can be used to set
 * all TDbPluginModule::ConnectionID with one setting.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TDbPluginModule extends TPluginModule implements IDbModule
{
	use TDbPropertiesTrait;
}
