<?php
/**
 * TTable and TTableRowCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TTableRowCollection class.
 *
 * TTableRowCollection is used to maintain a list of rows belong to a table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTableRowCollection extends TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added table row.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TTableRow object.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TTableRow)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('tablerowcollection_tablerow_required');
	}
}