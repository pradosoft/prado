<?php
/**
 * TPagedList, TPagedListFetchDataEventParameter, TPagedListPageChangedEventParameter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * TPagedListPageChangedEventParameter class.
 * TPagedListPageChangedEventParameter is used as the parameter for
 * {@see \Prado\Collections\TPagedList::onPageChanged OnPageChanged} event.
 * To obtain the page index before it was changed, use {@see getOldPageIndex OldPageIndex}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TPagedListPageChangedEventParameter extends \Prado\TEventParameter
{
	private $_oldPage;

	/**
	 * Constructor.
	 * @param int $oldPage old page index
	 */
	public function __construct($oldPage)
	{
		$this->_oldPage = $oldPage;
		parent::__construct();
	}

	/**
	 * @return int the index of the page before the list changed to the new page
	 */
	public function getOldPageIndex()
	{
		return $this->_oldPage;
	}
}
