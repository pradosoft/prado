<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TDataListItemCollection class.
 *
 * TDataListItemCollection represents a collection of data list items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItemCollection extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only TControl descendants.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TControl descendant.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TControl)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('datalistitemcollection_datalistitem_required');
	}
}