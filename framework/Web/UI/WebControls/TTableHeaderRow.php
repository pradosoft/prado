<?php
/**
 * TTableHeaderRow class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidOperationException;

/**
 * TTableHeaderRow class.
 *
 * TTableHeaderRow displays a table header row.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.1
 */
class TTableHeaderRow extends TTableRow
{
	/**
	 * @return string location of a row in a table. Always returns 'Header'.
	 */
	public function getTableSection()
	{
		return 'Header';
	}

	/**
	 * @param string $value location of a row in a table.
	 * @throws TInvalidOperationException if this method is invoked
	 */
	public function setTableSection($value)
	{
		throw new TInvalidOperationException('tableheaderrow_tablesection_readonly');
	}
}
