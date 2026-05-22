<?php

/**
 * IDbConnection interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
 * IDbConnection interface
 *
 * IDbConnection extends {@see IDataConnection} with PDO-specific access,
 * providing direct access to the underlying {@see \PDO} instance.
 *
 * This interface is implemented by {@see TDbConnection} and should be used
 * as the type hint wherever code needs to call PDO-specific methods directly
 * (e.g. `getPdoInstance()->lastInsertId()`, `getPdoInstance()->prepare()`).
 *
 * Code that does not require PDO access should use {@see IDataConnection}
 * so that non-PDO driver implementations remain compatible.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbConnection extends IDataConnection
{
	/**
	 * Returns the underlying PDO instance for this connection.
	 *
	 * Returns null if the connection has not been opened yet.
	 *
	 * @return null|\PDO the PDO instance, or null if not yet connected.
	 */
	public function getPdoInstance();
}
