<?php

/**
 * IDbColumn interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

/**
 * IDbColumn interface
 *
 * IDbColumn extends {@see IDataColumn} with PDO-specific binding support,
 * providing access to the PDO parameter-type token for this column.
 *
 * This interface is implemented by {@see TDbTableColumn} and should be used
 * as the type hint wherever code needs to call PDO-specific methods directly
 * (e.g. {@see TDbCommandBuilder::bindColumnValues()}).
 *
 * Code that does not require PDO parameter binding should use {@see IDataColumn}
 * so that non-PDO driver implementations remain compatible.
 *
 * This follows the same layering pattern as
 * {@see \Prado\Data\IDataConnection} / {@see \Prado\Data\IDbConnection}:
 * the driver-agnostic interface carries the portable contract; the Db-prefixed
 * sub-interface adds the PDO-specific extension.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbColumn extends IDataColumn
{
	/**
	 * Returns a PDO parameter-type token that best represents this column's
	 * declared database type, for use when binding parameter values.
	 *
	 * The returned integer is one of the stable PDO type constants:
	 * `PDO::PARAM_BOOL` (5), `PDO::PARAM_INT` (1), `PDO::PARAM_STR` (2).
	 * Used by {@see \Prado\Data\Common\TDbCommandBuilder::bindColumnValues()}
	 * when constructing INSERT and UPDATE commands.
	 *
	 * Prefer {@see getPHPType()} for new code that must remain driver-agnostic.
	 *
	 * @return int the PDO parameter-type token.
	 */
	public function getPdoType();
}
