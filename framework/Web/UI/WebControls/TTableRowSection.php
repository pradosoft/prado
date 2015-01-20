<?php
/**
 * TTableRow and TTableCellCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


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
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TTableRowSection extends TEnumerable
{
	const Header='Header';
	const Body='Body';
	const Footer='Footer';
}