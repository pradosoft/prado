<?php
/**
 * TTableHeaderCell class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TTableHeaderScope class.
 * TTableHeaderScope defines the enumerable type for the possible table scopes that a table header is associated with.
 *
 * The following enumerable values are defined:
 * - NotSet: the scope is not specified
 * - Row: the scope is row-wise
 * - Column: the scope is column-wise
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TTableHeaderScope extends TEnumerable
{
	const NotSet='NotSet';
	const Row='Row';
	const Column='Column';
}