<?php

/**
 * TInsertOrIgnoreMappedStatement class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Statements;

/**
 * TInsertOrIgnoreMappedStatement executes insertOrIgnore mapped statements.
 *
 * Corresponds to the <insertOrIgnore> SqlMap XML element. Behaves identically to
 * TInsertMappedStatement but signals to the framework that this is an
 * insert-or-ignore operation. The driver-specific SQL (e.g. INSERT IGNORE INTO
 * or INSERT OR IGNORE INTO) is written directly in the XML mapping.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TInsertOrIgnoreMappedStatement extends TInsertMappedStatement
{
}
