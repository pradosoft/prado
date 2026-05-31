<?php

/**
 * IDbCommand interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
 * IDbCommand interface
 *
 * Discovery marker for command classes that expose SQL/PDO statement-text and
 * statement-lifecycle operations.  All driver-agnostic accessors live on the
 * parent {@see IDataCommand}; this sub-interface adds the SQL-specific surface
 * that non-SQL drivers (document stores, etc.) have no analog for.
 *
 * Implemented by {@see TDbCommand}.  Mirrors the
 * {@see IDataConnection} / {@see IDbConnection} discovery-marker pattern.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbCommand extends IDataCommand
{
	/**
	 * Returns the SQL statement text for this command.
	 *
	 * @return string the SQL statement.
	 */
	public function getText();

	/**
	 * Sets the SQL statement text.  Invalidates any prepared statement so the
	 * next execution re-prepares.
	 *
	 * @param string $value the SQL statement.
	 */
	public function setText($value);

	/**
	 * Compiles the SQL statement.  Optional; parameter binding triggers it
	 * implicitly.  Calling this explicitly is useful when the same statement
	 * will be executed multiple times.
	 */
	public function prepare();

	/**
	 * Discards the prepared statement, releasing its resources.  The next call
	 * to {@see execute()} or {@see query()} will re-prepare.
	 */
	public function cancel();
}
