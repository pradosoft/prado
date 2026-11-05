<?php

/**
 * TDbTransaction class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
 * TDbTransaction class.
 *
 * SQL/PDO transaction token.  Identical in behavior to its parent
 * {@see TDataTransaction}; retained as a discovery-marker subclass for
 * code that wants to type-hint specifically against the PDO-backed
 * transaction (paired with {@see TDbConnection}).  All commit/rollback
 * work is delegated up the chain to {@see TDbConnection}, which holds the
 * underlying PDO handle.
 *
 * Created by {@see TDbConnection::beginTransaction()}.  See
 * {@see TDataTransaction} for usage details.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDbTransaction extends TDataTransaction
{
}
