<?php
/**
 * TMultiView and TView class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TViewCollection class.
 * TViewCollection represents a collection that only takes {@link TView} instances
 * as collection elements.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TViewCollection extends TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by ensuring only {@link TView}
	 * controls be added into the collection.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is neither a string nor a TControl.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TView)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('viewcollection_view_required');
	}
}