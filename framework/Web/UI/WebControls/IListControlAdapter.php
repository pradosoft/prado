<?php
/**
 * TListControl class file
 *
 * @author Robin J. Rogge <rojaro@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * IListControlAdapter interface
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
interface IListControlAdapter
{
	/**
	 * Selects an item based on zero-base index on the client side.
	 * @param int $index the index (zero-based) of the item to be selected
	 */
	public function setSelectedIndex($index);
	/**
	 * Selects a list of item based on zero-base indices on the client side.
	 * @param array $indices list of index of items to be selected
	 */
	public function setSelectedIndices($indices);

	/**
	 * Sets selection by item value on the client side.
	 * @param string $value the value of the item to be selected.
	 */
	public function setSelectedValue($value);

	/**
	 * Sets selection by a list of item values on the client side.
	 * @param array $values list of the selected item values
	 */
	public function setSelectedValues($values);

	/**
	 * Clears all existing selections on the client side.
	 */
	public function clearSelection();
}
