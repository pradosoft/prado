<?php

/**
 * TUpsertMappedStatement class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Statements;

/**
 * TUpsertMappedStatement executes upsert mapped statements.
 *
 * Corresponds to the <upsert> SqlMap XML element. Behaves identically to
 * TInsertMappedStatement but signals to the framework that this is an
 * insert-or-update (upsert) operation. The driver-specific SQL (e.g.
 * INSERT ... ON DUPLICATE KEY UPDATE or INSERT ... ON CONFLICT ... DO UPDATE SET)
 * is written directly in the XML mapping. Optional updateColumns and conflictColumns
 * attributes in the <upsert> element are stored on the TSqlMapUpsert configuration object.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TUpsertMappedStatement extends TInsertMappedStatement
{
}
