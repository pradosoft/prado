<?php
/**
 * TStyle class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TTableGridLines class.
 * TTableGridLines defines the enumerable type for the possible grid line types of an HTML table.
 *
 * The following enumerable values are defined:
 * - None: no grid lines
 * - Horizontal: horizontal grid lines only
 * - Vertical: vertical grid lines only
 * - Both: both horizontal and vertical grid lines are shown
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TTableGridLines extends TEnumerable
{
	const None='None';
	const Horizontal='Horizontal';
	const Vertical='Vertical';
	const Both='Both';
}
