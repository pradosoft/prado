<?php

/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TBulletedListEventParameter
 * Event parameter for {@see \Prado\Web\UI\WebControls\TBulletedList::onClick Click} event of the
 * bulleted list. The {@see getIndex Index} gives the zero-based index
 * of the item that is currently being clicked.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
		parent::__construct();
	}

	/**
	 * @return int zero-based index of the item (rendered as a link button) that is clicked
	 */
	public function getIndex()
	{
		return $this->_index;
	}
}
