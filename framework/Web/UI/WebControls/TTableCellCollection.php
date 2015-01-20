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
 * TTableCellCollection class.
 *
 * TTableCellCollection is used to maintain a list of cells belong to a table row.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableCellCollection extends TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added table cell.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TTableCell object.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TTableCell)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('tablecellcollection_tablecell_required');
	}
}