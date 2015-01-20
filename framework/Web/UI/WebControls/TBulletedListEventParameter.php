<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TBulletedListEventParameter
 * Event parameter for {@link TBulletedList::onClick Click} event of the
 * bulleted list. The {@link getIndex Index} gives the zero-based index
 * of the item that is currently being clicked.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TBulletedListEventParameter extends TEventParameter
{
	/**
	 * @var integer index of the item clicked
	 */
	private $_index;

	/**
	 * Constructor.
	 * @param integer index of the item clicked
	 */
	public function __construct($index)
	{
		$this->_index=$index;
	}

	/**
	 * @return integer zero-based index of the item (rendered as a link button) that is clicked
	 */
	public function getIndex()
	{
		return $this->_index;
	}
}