<?php
/**
 * TTableRow and TTableCellCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTableRowSection class.
 * TTableRowSection defines the enumerable type for the possible table sections
 * that a {@link TTableRow} can be within.
 *
 * The following enumerable values are defined:
 * - Header: in table header
 * - Body: in table body
 * - Footer: in table footer
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TTableRowSection extends \Prado\TEnumerable
{
	const Header = 'Header';
	const Body = 'Body';
	const Footer = 'Footer';
}
