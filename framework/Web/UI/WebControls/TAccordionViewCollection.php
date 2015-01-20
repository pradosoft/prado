<?php
/**
 * TAccordion class file.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 * @since 3.2
 */

/**
 * Class TAccordionViewCollection.
 *
 * TAccordionViewCollection is a collection of {@link TAccordionView} to be used inside a {@link TAccordion}.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @package System.Web.UI.WebControls
 * @since 3.2
 */
class TAccordionViewCollection extends TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing sanity check on the type of new item.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a {@link TAccordionView} object.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TAccordionView)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('tabviewcollection_tabview_required');
	}

	/**
	 * Finds the index of the accordion view whose ID is the same as the one being looked for.
	 * @param string the explicit ID of the accordion view to be looked for
	 * @return integer the index of the accordion view found, -1 if not found.
	 */
	public function findIndexByID($id)
	{
		foreach($this as $index=>$view)
		{
			if($view->getID(false)===$id)
				return $index;
		}
		return -1;
	}
}
