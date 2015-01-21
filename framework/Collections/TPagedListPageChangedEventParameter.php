<?php
/**
 * TPagedList, TPagedListFetchDataEventParameter, TPagedListPageChangedEventParameter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Collections
 */

namespace Prado\Collections;

/**
 * TPagedListPageChangedEventParameter class.
 * TPagedListPageChangedEventParameter is used as the parameter for
 * {@link TPagedList::onPageChanged OnPageChanged} event.
 * To obtain the page index before it was changed, use {@link getOldPageIndex OldPageIndex}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TPagedListPageChangedEventParameter extends TEventParameter
{
	private $_oldPage;

	/**
	 * Constructor.
	 * @param integer old page index
	 */
	public function __construct($oldPage)
	{
		$this->_oldPage=$oldPage;
	}

	/**
	 * @return integer the index of the page before the list changed to the new page
	 */
	public function getOldPageIndex()
	{
		return $this->_oldPage;
	}
}