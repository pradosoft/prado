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
 * TDataGridPager class.
 *
 * TDataGridPager represents a datagrid pager.
 *
 * The pager renders a `<nav>` landmark named "Pagination". An `aria-label` or
 * `aria-labelledby` attribute set on the pager in an
 * {@see TDataGrid::onPagerCreated OnPagerCreated} handler replaces the name.
 * {@see TDataGridPagerStyle::setListLayout PagerStyle.ListLayout} renders the
 * buttons as a `<ul>` list; see {@see TPagerAccessibilityTrait}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDataGridPager extends TPanel implements \Prado\Web\UI\INamingContainer
{
	use TPagerAccessibilityTrait;

	private $_dataGrid;

	/**
	 * Constructor.
	 * @param TDataGrid $dataGrid datagrid object
	 */
	public function __construct($dataGrid)
	{
		$this->_dataGrid = $dataGrid;
		parent::__construct();
	}

	/**
	 * @return string the tag name of the pager, `nav`.
	 * @since 4.4.0
	 */
	protected function getTagName()
	{
		return 'nav';
	}

	/**
	 * Adds the default accessible name of the pager.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 * @since 4.4.0
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$this->addPagerLabelToRender($writer);
	}

	/**
	 * Renders the pager buttons, as a `<ul>` list when the datagrid
	 * {@see TDataGridPagerStyle::getListLayout PagerStyle.ListLayout} is true.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 * @since 4.4.0
	 */
	public function renderContents($writer)
	{
		if ($this->_dataGrid->getPagerStyle()->getListLayout()) {
			$this->renderPagerList($writer);
		} else {
			parent::renderContents($writer);
		}
	}

	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param \Prado\Web\UI\TControl $sender the sender of the event
	 * @param \Prado\TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof \Prado\Web\UI\TCommandEventParameter) {
			$this->raiseBubbleEvent($this, new TDataGridCommandEventParameter($this, $sender, $param));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return TDataGrid the datagrid owning this pager
	 */
	public function getDataGrid()
	{
		return $this->_dataGrid;
	}

	/**
	 * @return string item type.
	 */
	public function getItemType()
	{
		return TListItemType::Pager;
	}
}
