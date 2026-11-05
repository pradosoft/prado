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
 * Discovery marker for connection classes that expose the raw PDO escape
 * hatch via {@see getPdoInstance()}.  Use this interface (or test for it
 * with `instanceof`) when code needs the raw `\PDO` handle for an operation
 * the framework does not abstract — for example, driver-specific attribute
 * tuning or LOB streaming.
 *
 * All driver-agnostic accessors live on the parent {@see IDataConnection},
 * which is what most code should depend on.  Implementing IDbConnection
 * advertises "this connection is backed by PDO"; it adds no behavior beyond
 * exposing the underlying handle.
 *
 * Implemented by {@see TDbConnection}.
 *
 * Optional SQL/PDO discovery marker — see {@see \Prado\Data\IDataConnection} for the full driver-portability roadmap.
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
