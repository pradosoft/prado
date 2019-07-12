<?php
/**
 * TDataBoundControl class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * IItemDataRenderer interface.
 *
 * IItemDataRenderer defines the interface that an item renderer
 * needs to implement. Besides the {@link getData Data} property, a list item
 * renderer also needs to provide {@link getItemIndex ItemIndex} and
 * {@link getItemType ItemType} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.0
 */
interface IItemDataRenderer extends \Prado\IDataRenderer
{
	/**
	 * Returns a value indicating the zero-based index of the item in the corresponding data control's item collection.
	 * If the item is not in the collection (e.g. it is a header item), it returns -1.
	 * @return int zero-based index of the item.
	 */
	public function getItemIndex();

	/**
	 * Sets the zero-based index for the item.
	 * If the item is not in the item collection (e.g. it is a header item), -1 should be used.
	 * @param int $value zero-based index of the item.
	 */
	public function setItemIndex($value);

	/**
	 * @return TListItemType the item type.
	 */
	public function getItemType();

	/**
	 * @param TListItemType $value the item type.
	 */
	public function setItemType($value);
}
