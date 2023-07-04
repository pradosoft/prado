<?php
/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * THotSpotCollection class.
 *
 * THotSpotCollection represents a collection of hotspots in an imagemap.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THotSpotCollection extends \Prado\Collections\TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only {@see \Prado\Web\UI\WebControls\THotSpot}.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a THotSpot.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof THotSpot) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('hotspotcollection_hotspot_required');
		}
	}
}
