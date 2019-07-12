<?php
/**
 * TItemDataRenderer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 * @since 3.1.2
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TItemDataRenderer class
 *
 * TItemDataRenderer is the convient base class for template-based item data renderers.
 * It implements the {@link IItemDataRenderer} interface, and because
 * TItemDataRenderer extends from {@link TTemplateControl}, derived child
 * classes can have templates to define their presentational layout.
 *
 * The following properties are provided by TItemDataRenderer:
 * - {@link getItemIndex ItemIndex}: zero-based index of this renderer in the item list collection.
 * - {@link getItemType ItemType}: item type of this renderer, such as TListItemType::AlternatingItem
 * - {@link getData Data}: data associated with this renderer
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.2
 */
abstract class TItemDataRenderer extends TDataRenderer implements IItemDataRenderer
{
	/**
	 * index of the data item in the Items collection of repeater
	 */
	private $_itemIndex;
	/**
	 * type of the TRepeaterItem
	 * @var TListItemType
	 */
	private $_itemType;

	/**
	 * @return TListItemType item type
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param TListItemType $value item type.
	 */
	public function setItemType($value)
	{
		$this->_itemType = TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TListItemType');
	}

	/**
	 * Returns a value indicating the zero-based index of the item in the corresponding data control's item collection.
	 * If the item is not in the collection (e.g. it is a header item), it returns -1.
	 * @return int zero-based index of the item.
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * Sets the zero-based index for the item.
	 * If the item is not in the item collection (e.g. it is a header item), -1 should be used.
	 * @param int $value zero-based index of the item.
	 */
	public function setItemIndex($value)
	{
		$this->_itemIndex = TPropertyValue::ensureInteger($value);
	}
}
