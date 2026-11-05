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
 * Discovery marker for column metadata classes whose bind-parameter tokens
 * are PDO `PARAM_*` integers.  All `IDbColumn` implementations are also
 * {@see IDataColumn}; the driver-agnostic accessors (including the canonical
 * bind-token method {@see IDataColumn::getParamType()}) live there.
 *
 * The legacy {@see getPdoType()} method is retained for backward compatibility
 * and now forwards to {@see IDataColumn::getParamType()}.  New code should
 * call `getParamType()` directly.
 *
 * Implemented by {@see TDbTableColumn}.  Mirrors the
 * {@see \Prado\Data\IDataConnection} / {@see \Prado\Data\IDbConnection}
 * discovery-marker pattern.
 *
 * Optional SQL/PDO discovery marker — see {@see \Prado\Data\IDataConnection} for the full driver-portability roadmap.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbColumn extends IDataColumn
{
	/**
	 * Returns the PDO parameter-type token for this column.  Equivalent to
	 * {@see IDataColumn::getParamType()}.
	 *
	 * @return int the PDO `PARAM_*` integer.
	 * @deprecated 4.3.3 — use {@see IDataColumn::getParamType()}.
	 */
	public function getPdoType();
}
