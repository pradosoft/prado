<?php

/**
 * IDbModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * IDbModule interface.
 *
 * This interface should be implemented by database modules.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
interface IDbModule extends \Prado\IModule
{
	/**
	 * @return \Prado\Data\TDbConnection the DB connection instance
	 */
	public function getDbConnection();

	/**
	 * @return string the ID of a {@see TDataSourceConfig} module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID();

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection for this log route.
	 * @param string $value ID of the {@see TDataSourceConfig} module
	 */
	public function setConnectionID($value);
}
