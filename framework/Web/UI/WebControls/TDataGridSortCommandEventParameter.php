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
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDataGridSortCommandEventParameter class
 *
 * TDataGridSortCommandEventParameter encapsulates the parameter data for
 * {@see \Prado\Web\UI\WebControls\TDataGrid::onSortCommand SortCommand} event of {@see \Prado\Web\UI\WebControls\TDataGrid} controls.
 *
 * The {@see getCommandSource CommandSource} property refers to the control
 * that originally raises the OnCommand event, while {@see getSortExpression SortExpression}
 * gives the sort expression carried with the sort command.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDataGridSortCommandEventParameter extends \Prado\TEventParameter
{
	/**
	 * @var string sort expression
	 */
	private $_sortExpression = '';
	/**
	 * @var \Prado\Web\UI\TControl original event sender
	 */
	private $_source;

	/**
	 * Constructor.
	 * @param \Prado\Web\UI\TControl $source the control originally raises the <b>OnCommand</b> event.
	 * @param TDataGridCommandEventParameter $param command event parameter
	 */
	public function __construct($source, TDataGridCommandEventParameter $param)
	{
		$this->_source = $source;
		$this->_sortExpression = $param->getCommandParameter();
		parent::__construct();
	}

	/**
	 * @return \Prado\Web\UI\TControl the control originally raises the <b>OnCommand</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}

	/**
	 * @return string sort expression
	 */
	public function getSortExpression()
	{
		return $this->_sortExpression;
	}
}
