<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TBulletedListEventParameter
 * Event parameter for {@link TBulletedList::onClick Click} event of the
 * bulleted list. The {@link getIndex Index} gives the zero-based index
 * of the item that is currently being clicked.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TBulletedListEventParameter extends \Prado\TEventParameter
{
	/**
	 * @var int index of the item clicked
	 */
	private $_index;

	/**
	 * Constructor.
	 * @param int $index index of the item clicked
	 */
	public function __construct($index)
	{
		$this->_index = $index;
	}

	/**
	 * @return int zero-based index of the item (rendered as a link button) that is clicked
	 */
	public function getIndex()
	{
		return $this->_index;
	}
}
