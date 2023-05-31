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
 * TDataGridCommandEventParameter class
 *
 * TDataGridCommandEventParameter encapsulates the parameter data for
 * {@see TDataGrid::onItemCommand ItemCommand} event of {@see TDataGrid} controls.
 *
 * The {@see getItem Item} property indicates the datagrid item related with the event.
 * The {@see getCommandSource CommandSource} refers to the control that originally
 * raises the Command event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDataGridCommandEventParameter extends \Prado\Web\UI\TCommandEventParameter
{
	/**
	 * @var TDataGridItem the TDataGridItem control responsible for the event.
	 */
	private $_item;
	/**
	 * @var \Prado\Web\UI\TControl the control originally raises the <b>Command</b> event.
	 */
	private $_source;

	/**
	 * Constructor.
	 * @param TDataGridItem $item datagrid item responsible for the event
	 * @param \Prado\Web\UI\TControl $source original event sender
	 * @param \Prado\Web\UI\TCommandEventParameter $param original event parameter
	 */
	public function __construct($item, $source, \Prado\Web\UI\TCommandEventParameter $param)
	{
		$this->_item = $item;
		$this->_source = $source;
		parent::__construct($param->getCommandName(), $param->getCommandParameter());
	}

	/**
	 * @return TDataGridItem the TDataGridItem control responsible for the event.
	 */
	public function getItem()
	{
		return $this->_item;
	}

	/**
	 * @return \Prado\Web\UI\TControl the control originally raises the <b>Command</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}
}
