<?php
/**
 * TTableRow and TTableCellCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTableRowSection enum.
 * TTableRowSection defines the enumerable type for the possible table sections
 * that a {@link TTableRow} can be within.
 *
 * The following enumerable values are defined:
 * - Header: in table header
 * - Body: in table body
 * - Footer: in table footer
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TTableRowSection: string
{
	case Header = 'Header';
	case Body = 'Body';
	case Footer = 'Footer';
}
