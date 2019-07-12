<?php
/**
 * IRepeatInfoUser, TRepeatInfo class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * IRepeatInfoUser interface.
 * This interface must be implemented by classes who want to use {@link TRepeatInfo}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
interface IRepeatInfoUser
{
	/**
	 * @return bool whether the repeat user contains footer
	 */
	public function getHasFooter();
	/**
	 * @return bool whether the repeat user contains header
	 */
	public function getHasHeader();
	/**
	 * @return bool whether the repeat user contains separators
	 */
	public function getHasSeparators();
	/**
	 * @return int number of items to be rendered (excluding header, footer and separators)
	 */
	public function getItemCount();
	/**
	 * @param string $itemType item type (Header,Footer,Item,AlternatingItem,SelectedItem,EditItem,Separator,Pager)
	 * @param int $index zero-based index of the current rendering item.
	 * @return TStyle CSS style used for rendering items (including header, footer and separators)
	 */
	public function generateItemStyle($itemType, $index);
	/**
	 * Renders an item.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param TRepeatInfo $repeatInfo repeat information
	 * @param string $itemType item type
	 * @param int $index zero-based index of the item being rendered
	 */
	public function renderItem($writer, $repeatInfo, $itemType, $index);
}
