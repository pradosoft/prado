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
 * SQL/PDO transaction token, paired with {@see TDbConnection}.  Behaviorally
 * identical to its parent {@see TDataTransaction}; retained as a discovery
 * marker for code that type-hints the PDO-backed transaction.  Created by
 * {@see TDbConnection::beginTransaction}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDbTransaction extends TDataTransaction
{
}
