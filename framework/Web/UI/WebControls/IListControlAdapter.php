<?php
/**
 * TListControl class file
 *
 * @author Robin J. Rogge <rojaro@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * IListControlAdapter interface
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Sun Jun 25 04:53:43 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
interface IListControlAdapter
{
	/**
	 * Selects an item based on zero-base index on the client side.
	 * @param integer the index (zero-based) of the item to be selected
	 */
	public function setSelectedIndex($index);
	/**
	 * Selects a list of item based on zero-base indices on the client side.
	 * @param array list of index of items to be selected
	 */
	public function setSelectedIndices($indices);

	/**
	 * Sets selection by item value on the client side.
	 * @param string the value of the item to be selected.
	 */
	public function setSelectedValue($value);

	/**
	 * Sets selection by a list of item values on the client side.
	 * @param array list of the selected item values
	 */
	public function setSelectedValues($values);

    /**
     * Clears all existing selections on the client side.
     */
    public function clearSelection();
}