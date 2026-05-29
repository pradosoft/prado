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
 * Discovery marker for connections that expose the raw PDO escape hatch via
 * {@see getPdoInstance}.  Use this interface (or `instanceof`) when code needs
 * the raw `\PDO` handle for an operation the framework does not abstract
 * (e.g. driver-specific attribute tuning, LOB streaming).  Implemented by
 * {@see TDbConnection}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbConnection extends IDataConnection
{
	/**
	 * @return null|\PDO the underlying PDO instance, or null if not yet connected.
	 */
	public function getPdoInstance();
}
