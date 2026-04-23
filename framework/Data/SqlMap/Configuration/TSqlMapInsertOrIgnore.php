<?php

/**
 * TSqlMapInsertOrIgnore class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapInsertOrIgnore corresponds to the <insertOrIgnore> element.
 *
 * Behaves identically to TSqlMapInsert but executes via insertOrIgnore(),
 * silently ignoring duplicate key conflicts.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TSqlMapInsertOrIgnore extends TSqlMapInsert
{
}
