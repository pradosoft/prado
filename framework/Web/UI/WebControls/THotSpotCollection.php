<?php
/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * THotSpotCollection class.
 *
 * THotSpotCollection represents a collection of hotspots in an imagemap.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class THotSpotCollection extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only {@link THotSpot}.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a THotSpot.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof THotSpot)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('hotspotcollection_hotspot_required');
	}
}