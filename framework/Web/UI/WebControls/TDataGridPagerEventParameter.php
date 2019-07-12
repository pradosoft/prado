<?php
/**
 * TDataGrid related class files.
 * This file contains the definition of the following classes:
 * TDataGrid, TDataGridItem, TDataGridItemCollection, TDataGridColumnCollection,
 * TDataGridPagerStyle, TDataGridItemEventParameter,
 * TDataGridCommandEventParameter, TDataGridSortCommandEventParameter,
 * TDataGridPageChangedEventParameter
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDataGridPagerEventParameter class
 *
 * TDataGridPagerEventParameter encapsulates the parameter data for
 * {@link TDataGrid::onPagerCreated OnPagerCreated} event of {@link TDataGrid} controls.
 * The {@link getPager Pager} property indicates the datagrid pager related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataGridPagerEventParameter extends \Prado\TEventParameter
{
	/**
	 * The TDataGridPager control responsible for the event.
	 * @var TDataGridPager
	 */
	protected $_pager;

	/**
	 * Constructor.
	 * @param TDataGridPager $pager datagrid pager related with the corresponding event
	 */
	public function __construct(TDataGridPager $pager)
	{
		$this->_pager = $pager;
	}

	/**
	 * @return TDataGridPager datagrid pager related with the corresponding event
	 */
	public function getPager()
	{
		return $this->_pager;
	}
}
