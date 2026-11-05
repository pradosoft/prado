<?php

/**
 * TDataGridSortDirection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDataGridSortDirection class.
 * TDataGridSortDirection defines the enumerable type for the directions of a
 * {@see TDataGrid} sort.
 *
 * The following enumerable values are defined:
 * - Ascending: the data sorts from the lowest value to the highest
 * - Descending: the data sorts from the highest value to the lowest
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDataGridSortDirection extends \Prado\TEnumerable
{
	public const Ascending = 'Ascending';
	public const Descending = 'Descending';
}
