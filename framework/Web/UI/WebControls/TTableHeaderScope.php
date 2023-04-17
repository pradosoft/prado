<?php
/**
 * TTableHeaderCell class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTableHeaderScope enum.
 * TTableHeaderScope defines the enumerable type for the possible table scopes that a table header is associated with.
 *
 * The following enumerable values are defined:
 * - NotSet: the scope is not specified
 * - Row: the scope is row-wise
 * - Column: the scope is column-wise
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TTableHeaderScope: string
{
	case NotSet = 'NotSet';
	case Row = 'Row';
	case Column = 'Column';
}
