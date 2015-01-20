<?php
/**
 * THead class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */


/**
 * TMetaTagCollection class
 *
 * TMetaTagCollection represents a collection of meta tags
 * contained in a {@link THead} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TMetaTagCollection extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing type
	 * check on the item being added.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a {@link TMetaTag}
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TMetaTag)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('metatagcollection_metatag_invalid');
	}

	/**
	 * Finds the lowest cardinal index of the meta tag whose id is the one being looked for.
	 * @param string the ID of the meta tag to be looked for
	 * @return integer the index of the meta tag found, -1 if not found.
	 */
	public function findIndexByID($id)
	{
		$index=0;
		foreach($this as $item)
		{
			if($item->getID()===$id)
				return $index;
			$index++;
		}
		return -1;
	}

	/**
	 * Finds the item whose value is the one being looked for.
	 * @param string the id of the meta tag to be looked for
	 * @return TMetaTag the meta tag found, null if not found.
	 */
	public function findMetaTagByID($id)
	{
		if(($index=$this->findIndexByID($id))>=0)
			return $this->itemAt($index);
		else
			return null;
	}
}