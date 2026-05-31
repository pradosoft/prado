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
 * Discovery marker for column metadata backed by PDO `PARAM_*` tokens.  All
 * driver-agnostic accessors — including the canonical bind-token method
 * {@see IDataColumn::getParamType} — live on the parent {@see IDataColumn}.
 * Implemented by {@see TDbTableColumn}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbColumn extends IDataColumn
{
	/**
	 * @return int the PDO `PARAM_*` integer.  Equivalent to {@see IDataColumn::getParamType}.
	 * @deprecated 4.3.3 — use {@see IDataColumn::getParamType}.
	 */
	public function getPdoType();
}
