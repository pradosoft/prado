<?php
/**
 * TDataGrid class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

Prado::using('System.Web.UI.WebControls.TBaseDataList');
Prado::using('System.Collections.TPagedDataSource');
Prado::using('System.Collections.TDummyDataSource');
Prado::using('System.Web.UI.WebControls.TTable');

/**
 * TDataGrid class
 *
 * TDataGrid represents a data bound and updatable grid control.
 *
 * To use TDataGrid, sets its <b>DataSource</b> property and invokes dataBind()
 * afterwards. The data will be populated into the TDataGrid and saved in the <b>Items</b> property.
 *
 * Each item is associated with a row of data and will be displayed as a row in table.
 * A data item can be at one of three states: normal, selected and edit.
 * The state determines how the item will be displayed. For example, if an item
 * is in edit state, it may be displayed as a table row with input text boxes instead
 * static text as in normal state.
 * To change the state of a data item, set either the <b>EditItemIndex</b> property
 * or the <b>SelectedItemIndex</b> property.
 *
 * A datagrid is specified with a list of columns. Each column specifies how the corresponding
 * table column will be displayed. For example, the header/footer text of that column,
 * the cells in that column, and so on. The following column types are provided by the framework,
 * - TBoundColumn, associated with a specific field in datasource and displays the corresponding data.
 * - TEditCommandColumn, displaying edit/update/cancel command buttons
 * - TButtonColumn, displaying generic command buttons that may be bound to specific field in datasource.
 * - THyperLinkColumn, displaying a hyperlink that may be boudn to specific field in datasource.
 * - TTemplateColumn, displaying content based on templates.
 *
 * There are three ways to specify columns for a datagrid.
 * <ul>
 *  <li>Automatically generated based on data source. By setting <b>AutoGenerateColumns</b>
 *  to true, a list of columns will be automatically generated based on the schema of the data source.
 *  Each column corresponds to a column of the data.</li>
 *  <li>Specified in template. For example,
 *    <code>
 *     <com:TDataGrid ...>
 *        <com:TBoundColumn .../>
 *        <com:TEditCommandColumn .../>
 *     </com:TDataGrid>
 *    </code>
 *  </li>
 *  <li>Manually created in code. Columns can be manipulated via the <b>Columns</b> property of
 *  the datagrid. For example,
 *    <code>
 *    $column=$datagrid->createComponent('TBoundColumn');
 *    $datagrid->Columns->add($column);
 *    </code>
 *  </li>
 * </ul>
 * Note, automatically generated columns cannot be accessed via <b>Columns</b> property.
 *
 * TDataGrid supports sorting. If the <b>AllowSorting</b> is set to true, a column
 * whose <b>SortExpression</b> is not empty will have its header text displayed as a link button.
 * Clicking on the link button will raise <b>OnSortCommand</b> event. You can respond to this event,
 * sort the data source according to the event parameter, and then invoke databind on the datagrid.
 *
 * TDataGrid supports paging. If the <b>AllowPaging</b> is set to true, a pager will be displayed
 * on top and/or bottom of the table. How the pager will be displayed is determined by <b>PagerDisplay</b>
 * and <b>PagerButtonCount</b> properties. The former decides the position of the pager and latter
 * specifies how many buttons are to be used for paging purpose. Clicking on a pager button will raise
 * an <b>onPageIndexChanged</b> event. You can respond to this event, specify the page to be displayed by
 * setting <b>CurrentPageIndex</b> property, and then invoke databind on the datagrid.
 *
 * TDataGrid supports two kinds of paging. The first one is based on the number of data items in
 * datasource. The number of pages <b>PageCount</b> is calculated based the item number and the
 * <b>PageSize</b> property. The datagrid will manage which section of the data source to be displayed
 * based on the <b>CurrentPageIndex</b> property.
 * The second approach calculates the page number based on the <b>VirtualItemCount</b> property and
 * the <b>PageSize</b> property. The datagrid will always display from the beginning of the datasource
 * upto the number of <b>PageSize> data items. This approach is especially useful when the datasource may
 * contain too many data items to be managed by the datagrid efficiently.
 *
 * When the datagrid contains a button control that raises an <b>OnCommand</b>
 * event, the event will be bubbled up to the datagrid control.
 * If the event's command name is recognizable by the datagrid control,
 * a corresponding item event will be raised. The following item events will be
 * raised upon a specific command:
 * - OnEditCommand, edit
 * - OnCancelCommand, cancel
 * - OnSelectCommand, select
 * - OnDeleteCommand, delete
 * - OnUpdateCommand, update
 * - onPageIndexChanged, page
 * - OnSortCommand, sort
 * The data list will always raise an <b>OnItemCommand</b>
 * upon its receiving a bubbled <b>OnCommand</b> event.
 *
 * An <b>OnItemCreated</b> event will be raised right after each item is created in the datagrid.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGrid extends TBaseDataList implements INamingContainer
{
	private $_columns=null;
	private $_autoColumns=null;
	private $_items=null;
	private $_header=null;
	private $_footer=null;
	private $_pager=null;
	private $_pagedDataSource=null;

	/**
	 * @return string tag name of the datagrid
	 */
	protected function getTagName()
	{
		return 'table';
	}

	public function addParsedObject($object)
	{
		if($object instanceof TDataGridColumn)
			$this->getColumns()->add($object);
	}

	public function getColumns()
	{
		if(!$this->_columns)
			$this->_columns=new TDataGridColumnCollection;
		return $this->_columns;
	}

	public function getAutoColumns()
	{
		if(!$this->_autoColumns)
			$this->_autoColumns=new TDataGridColumnCollection;
		return $this->_autoColumns;
	}

	public function getItems()
	{
		if(!$this->_items)
			$this->_items=new TDataGridItemCollection;
		return $this->_items;
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableStyle} to be used by datagrid.
	 * @return TStyle control style to be used
	 */
	protected function createStyle()
	{
		$style=new TTableStyle;
		$style->setGridLines('Both');
		$style->setCellSpacing(0);
		return $style;
	}

	/**
	 * @return string the URL of the background image for the datagrid
	 */
	public function getBackImageUrl()
	{
		return $this->getStyle()->getBackImageUrl();
	}

	/**
	 * Sets the URL of the background image for the datagrid
	 * @param string the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->getStyle()->setBackImageUrl($value);
	}

	/**
	 * @return TTableItemStyle the style for each alternating item
	 */
	public function getAlternatingItemStyle()
	{
		if(($style=$this->getViewState('AlternatingItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('AlternatingItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for item
	 */
	public function getItemStyle()
	{
		if(($style=$this->getViewState('ItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('ItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for selected item
	 */
	public function getSelectedItemStyle()
	{
		if(($style=$this->getViewState('SelectedItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('SelectedItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for edit item
	 */
	public function getEditItemStyle()
	{
		if(($style=$this->getViewState('EditItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('EditItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for header
	 */
	public function getHeaderStyle()
	{
		if(($style=$this->getViewState('HeaderStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('HeaderStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TDataGridItem the header item
	 */
	public function getHeader()
	{
		return $this->_header;
	}

	/**
	 * @return TTableItemStyle the style for footer
	 */
	public function getFooterStyle()
	{
		if(($style=$this->getViewState('FooterStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('FooterStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TDataGridItem the footer item
	 */
	public function getFooter()
	{
		return $this->_footer;
	}

	/**
	 * @return TDataGridPagerStyle the style for pager
	 */
	public function getPagerStyle()
	{
		if(($style=$this->getViewState('PagerStyle',null))===null)
		{
			$style=new TDataGridPagerStyle;
			$this->setViewState('PagerStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TDataGridItem the pager
	 */
	public function getPager()
	{
		return $this->_pager;
	}

	/**
	 * @return TDataGridItem the selected item, null if no item is selected.
	 */
	public function getSelectedItem()
	{
		$index=$this->getSelectedItemIndex();
		$items=$this->getItems();
		if($index>=0 && $index<$items->getCount())
			return $items->itemAt($index);
		else
			return null;
	}

	/**
	 * @return integer the zero-based index of the selected item in {@link getItems Items}.
	 * A value -1 means no item selected.
	 */
	public function getSelectedItemIndex()
	{
		return $this->getViewState('SelectedItemIndex',-1);
	}

	/**
	 * Selects an item by its index in {@link getItems Items}.
	 * Previously selected item will be un-selected.
	 * If the item to be selected is already in edit mode, it will remain in edit mode.
	 * If the index is less than 0, any existing selection will be cleared up.
	 * @param integer the selected item index
	 */
	public function setSelectedItemIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=-1;
		if(($current=$this->getSelectedItemIndex())!==$value)
		{
			$this->setViewState('SelectedItemIndex',$value,-1);
			$items=$this->getItems();
			$itemCount=$items->getCount();
			if($current>=0 && $current<$itemCount)
			{
				$item=$items->itemAt($current);
				if($item->getItemType()!=='EditItem')
					$item->setItemType($current%2?'AlternatingItem':'Item');
			}
			if($value>=0 && $value<$itemCount)
			{
				$item=$items->itemAt($value);
				if($item->getItemType()!=='EditItem')
					$item->setItemType('SelectedItem');
			}
		}
	}

	/**
	 * @return TDataGridItem the edit item
	 */
	public function getEditItem()
	{
		$index=$this->getEditItemIndex();
		$items=$this->getItems();
		if($index>=0 && $index<$items->getCount())
			return $items->itemAt($index);
		else
			return null;
	}

	/**
	 * @return integer the zero-based index of the edit item in {@link getItems Items}.
	 * A value -1 means no item is in edit mode.
	 */
	public function getEditItemIndex()
	{
		return $this->getViewState('EditItemIndex',-1);
	}

	/**
	 * Edits an item by its index in {@link getItems Items}.
	 * Previously editting item will change to normal item state.
	 * If the index is less than 0, any existing edit item will be cleared up.
	 * @param integer the edit item index
	 */
	public function setEditItemIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=-1;
		if(($current=$this->getEditItemIndex())!==$value)
		{
			$this->setViewState('EditItemIndex',$value,-1);
			$items=$this->getItems();
			$itemCount=$items->getCount();
			if($current>=0 && $current<$itemCount)
				$items->itemAt($current)->setItemType($current%2?'AlternatingItem':'Item');
			if($value>=0 && $value<$itemCount)
				$items->itemAt($value)->setItemType('EditItem');
		}
	}

	/**
	 * @return boolean whether the custom paging is enabled Defaults to false.
	 */
	public function getAllowCustomPaging()
	{
		return $this->getViewState('AllowCustomPaging',false);
	}

	/**
	 * @param boolean whether the custom paging is enabled
	 */
	public function setAllowCustomPaging($value)
	{
		$this->setViewState('AllowCustomPaging',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether paging is enabled. Defaults to false.
	 */
	public function getAllowPaging()
	{
		return $this->getViewState('AllowPaging',false);
	}

	/**
	 * @param boolean whether paging is enabled
	 */
	public function setAllowPaging($value)
	{
		$this->setViewState('AllowPaging',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether sorting is enabled. Defaults to false.
	 */
	public function getAllowSorting()
	{
		return $this->getViewState('AllowSorting',false);
	}

	/**
	 * @param boolean whether sorting is enabled
	 */
	public function setAllowSorting($value)
	{
		$this->setViewState('AllowSorting',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether datagrid columns should be automatically generated. Defaults to true.
	 */
	public function getAutoGenerateColumns()
	{
		return $this->getViewState('AutoGenerateColumns',true);
	}

	/**
	 * @param boolean whether datagrid columns should be automatically generated
	 */
	public function setAutoGenerateColumns($value)
	{
		$this->setViewState('AutoGenerateColumns',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return integer the index of the current page. Defaults to 0.
	 */
	public function getCurrentPageIndex()
	{
		return $this->getViewState('CurrentPageIndex',0);
	}

	/**
	 * @param integer the index of the current page
	 */
	public function setCurrentPageIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('datagrid_currentpageindex_invalid');
		$this->setViewState('CurrentPageIndex',$value,0);
	}

	/**
	 * @return integer the number of rows displayed within a page. Defaults to 10.
	 */
	public function getPageSize()
	{
		return $this->getViewState('PageSize',10);
	}

	/**
	 * @param integer the number of rows displayed within a page
	 */
	public function setPageSize($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<1)
			throw new TInvalidDataValueException('datagrid_pagesize_invalid');
		$this->setViewState('PageSize',TPropertyValue::ensureInteger($value),10);
	}


	public function getPageCount()
	{
		if($this->_pagedDataSource)
			return $this->_pagedDataSource->getPageCount();
		return $this->getViewState('PageCount',0);
	}

	/**
	 * @return integer virtual number of items in the grid. Defaults to 0, meaning not set.
	 */
	public function getVirtualItemCount()
	{
		return $this->getViewState('VirtualItemCount',0);
	}

	/**
	 * @param integer virtual number of items in the grid
	 */
	public function setVirtualItemCount($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('datagrid_virtualitemcount_invalid');
		$this->setViewState('VirtualItemCount',$value,0);
	}

	/**
	 * @return boolean whether the header should be displayed Defaults to true.
	 */
	public function getShowHeader()
	{
		return $this->getViewState('ShowHeader',true);
	}

	/**
	 * @param boolean whether the header should be displayed
	 */
	public function setShowHeader($value)
	{
		$this->setViewState('ShowHeader',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return boolean whether the footer should be displayed. Defaults to false.
	 */
	public function getShowFooter()
	{
		return $this->getViewState('ShowFooter',false);
	}

	/**
	 * @param boolean whether the footer should be displayed
	 */
	public function setShowFooter($value)
	{
		$this->setViewState('ShowFooter',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * Handles <b>BubbleEvent</b>.
	 * This method overrides parent's implementation to handle
	 * {@link onItemCommand OnItemCommand} event which is bubbled from
	 * {@link TDataGridItem} child controls.
	 * If the event parameter is {@link TDataGridCommandEventParameter} and
	 * the command name is a recognized one, which includes 'select', 'edit',
	 * 'delete', 'update', and 'cancel' (case-insensitive), then a
	 * corresponding command event is also raised (such as {@link onEditCommand OnEditCommand}).
	 * This method should only be used by control developers.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	public function onBubbleEvent($sender,$param)
	{
		if($param instanceof TDataGridCommandEventParameter)
		{
			$this->onItemCommand($param);
			$command=$param->getCommandName();
			if(strcasecmp($command,'select')===0)
			{
				$this->setSelectedIndex($param->getItem()->getItemIndex());
				$this->onSelectedIndexChanged(null);
				return true;
			}
			else if(strcasecmp($command,'edit')===0)
			{
				$this->onEditCommand($param);
				return true;
			}
			else if(strcasecmp($command,'delete')===0)
			{
				$this->onDeleteCommand($param);
				return true;
			}
			else if(strcasecmp($command,'update')===0)
			{
				$this->onUpdateCommand($param);
				return true;
			}
			else if(strcasecmp($command,'cancel')===0)
			{
				$this->onCancelCommand($param);
				return true;
			}
			else if(strcasecmp($command,'sort')===0)
			{
				$this->onSortCommand(new TDataGridSortCommandEventParameter($sender,$param));
				return true;
			}
			else if(strcasecmp($command,'page')===0)
			{
				$p=$param->getCommandParameter();
				if(strcasecmp($p,'next')===0)
					$pageIndex=$this->getCurrentPageIndex()+1;
				else if(strcasecmp($p,'prev')===0)
					$pageIndex=$this->getCurrentPageIndex()-1;
				else
					$pageIndex=TPropertyValue::ensureInteger($p)-1;
				$this->onPageIndexChanged(new TDataGridPageChangedEventParameter($sender,$pageIndex));
				return true;
			}
		}
		return false;
	}

	/**
	 * Raises <b>OnCancelCommand</b> event.
	 * This method is invoked when a button control raises<b>Command</b> event
	 * with<b>cancel</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onCancelCommand($param)
	{
		$this->raiseEvent('OnCancelCommand',$this,$param);
	}

	/**
	 * Raises <b>OnDeleteCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>delete</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onDeleteCommand($param)
	{
		$this->raiseEvent('OnDeleteCommand',$this,$param);
	}

	/**
	 * Raises <b>OnEditCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>edit</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onEditCommand($param)
	{
		$this->raiseEvent('OnEditCommand',$this,$param);
	}

	/**
	 * Raises <b>OnItemCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event.
	 * @param TDataGridItemCommandEventParameter event parameter
	 */
	public function onItemCommand($param)
	{
		$this->raiseEvent('OnItemCommand',$this,$param);
	}

	/**
	 * Raises <b>OnSortCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>sort</b> command name.
	 * @param TDataGridSortCommandEventParameter event parameter
	 */
	public function onSortCommand($param)
	{
		$this->raiseEvent('OnSortCommand',$this,$param);
	}

	/**
	 * Raises <b>OnUpdateCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>update</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onUpdateCommand($param)
	{
		$this->raiseEvent('OnUpdateCommand',$this,$param);
	}

	/**
	 * Raises <b>OnItemCreated</b> event.
	 * This method is invoked right after a datagrid item is created and before
	 * added to page hierarchy.
	 * @param TDataGridItemEventParameter event parameter
	 */
	public function onItemCreated($param)
	{
		$this->raiseEvent('OnItemCreated',$this,$param);
	}

	/**
	 * Raises <b>OnItemDataBound</b> event.
	 * This method is invoked for each datagrid item after it performs
	 * databinding.
	 * @param TDataGridItemEventParameter event parameter
	 */
	public function onItemDataBound($param)
	{
		$this->raiseEvent('OnItemDataBound',$this,$param);
	}

	/**
	 * Raises <b>OnPageIndexChanged</b> event.
	 * This method is invoked when current page is changed.
	 * @param TDataGridPageChangedEventParameter event parameter
	 */
	public function onPageIndexChanged($param)
	{
		$this->raiseEvent('OnPageIndexChanged',$this,$param);
	}

	/**
	 * Saves item count in viewstate.
	 * This method is invoked right before control state is to be saved.
	 */
	public function saveState()
	{
		parent::saveState();
		if(!$this->getEnableViewState(true))
			return;
		if($this->_items)
			$this->setViewState('ItemCount',$this->_items->getCount(),0);
		else
			$this->clearViewState('ItemCount');
		if($this->_autoColumns)
		{
			$state=array();
			foreach($this->_autoColumns as $column)
				$state[]=$column->saveState();
			$this->setViewState('AutoColumns',$state,array());
		}
		else
			$this->clearViewState('AutoColumns');
		if($this->_columns)
		{
			$state=array();
			foreach($this->_columns as $column)
				$state[]=$column->saveState();
			$this->setViewState('Columns',$state,array());
		}
		else
			$this->clearViewState('Columns');
	}

	/**
	 * Loads item count information from viewstate.
	 * This method is invoked right after control state is loaded.
	 */
	public function loadState()
	{
		parent::loadState();
		if(!$this->getEnableViewState(true))
			return;
		if(!$this->getIsDataBound())
		{
			$state=$this->getViewState('AutoColumns',array());
			if(!empty($state))
			{
				$this->_autoColumns=new TDataGridColumnCollection;
				foreach($state as $st)
				{
					$column=new TBoundColumn;
					$column->loadState($st);
					$this->_autoColumns->add($column);
				}
			}
			else
				$this->_autoColumns=null;
			$state=$this->getViewState('Columns',array());
			if($this->_columns && $this->_columns->getCount()===count($state))
			{
				$i=0;
				foreach($this->_columns as $column)
				{
					$column->loadState($state[$i]);
					$i++;
				}
			}
			$this->restoreGridFromViewState();
		}
		$this->clearViewState('ItemCount');
	}

	private function createPagedDataSource()
	{
		$ds=new TPagedDataSource;
		$ds->setCurrentPageIndex($this->getCurrentPageIndex());
		$ds->setPageSize($this->getPageSize());
		$ds->setAllowPaging($this->getAllowPaging());
		$ds->setAllowCustomPaging($this->getAllowCustomPaging());
		$ds->setVirtualItemCount($this->getVirtualItemCount());
		return $ds;
	}

	/**
	 * Clears up all items in the data list.
	 */
	public function reset()
	{
		$this->getControls()->clear();
		$this->getItems()->clear();
		$this->_header=null;
		$this->_footer=null;
	}

	protected function restoreGridFromViewState()
	{
		$this->reset();
		$itemCount=$this->getViewState('ItemCount',0);
		$this->_pagedDataSource=$ds=$this->createPagedDataSource();
		$allowPaging=$ds->getAllowPaging();
		if($allowPaging && $ds->getAllowCustomPaging())
			$ds->setDataSource(new TDummyDataSource($itemCount));
		else
			$ds->setDataSource(new TDummyDataSource($this->getViewState('DataSourceCount',0)));
		$columns=new TList($this->getColumns());
		$columns->mergeWith($this->_autoColumns);

		$items=$this->getItems();
		$items->clear();

		if(($columnCount=$columns->getCount())>0)
		{
			foreach($columns as $column)
				$column->initialize();
			if($allowPaging)
				$this->createPager(-1,-1,$columnCount,$ds);
			$this->createItemInternal(-1,-1,'Header',false,null,$columns);
			$selectedIndex=$this->getSelectedItemIndex();
			$editIndex=$this->getEditItemIndex();
			$index=0;
			$dsIndex=$ds->getAllowPaging()?$ds->getFirstIndexInPage():0;
			foreach($ds as $data)
			{
				if($index===$editIndex)
					$itemType='EditItem';
				else if($index===$selectedIndex)
					$itemType='SelectedItem';
				else if($index % 2)
					$itemType='AlternatingItem';
				else
					$itemType='Item';
				$items->add($this->createItemInternal($index,$dsIndex,$itemType,false,null,$columns));
				$index++;
				$dsIndex++;
			}
			$this->createItemInternal(-1,-1,'Footer',false,null,$columns);
			if($allowPaging)
				$this->createPager(-1,-1,$columnCount,$ds);
		}
		$this->_pagedDataSource=null;
	}

	/**
	 * Performs databinding to populate data list items from data source.
	 * This method is invoked by dataBind().
	 * You may override this function to provide your own way of data population.
	 * @param Traversable the data
	 */
	protected function performDataBinding($data)
	{
		$this->reset();
		$keys=$this->getDataKeys();
		$keys->clear();
		$keyField=$this->getDataKeyField();
		$this->_pagedDataSource=$ds=$this->createPagedDataSource();
		$ds->setDataSource($data);
		$allowPaging=$ds->getAllowPaging();
		if($allowPaging && $ds->getCurrentPageIndex()>=$ds->getPageCount())
			throw new TInvalidDataValueException('datagrid_currentpageindex_invalid');
		// get all columns
		if($this->getAutoGenerateColumns())
		{
			$columns=new TList($this->getColumns());
			$autoColumns=$this->createAutoColumns($ds);
			$columns->mergeWith($autoColumns);
		}
		else
			$columns=$this->getColumns();

		$items=$this->getItems();

		if(($columnCount=$columns->getCount())>0)
		{
			foreach($columns as $column)
				$column->initialize();
			$allowPaging=$ds->getAllowPaging();
			if($allowPaging)
				$this->createPager(-1,-1,$columnCount,$ds);
			$this->createItemInternal(-1,-1,'Header',true,null,$columns);
			$selectedIndex=$this->getSelectedItemIndex();
			$editIndex=$this->getEditItemIndex();
			$index=0;
			$dsIndex=$ds->getAllowPaging()?$ds->getFirstIndexInPage():0;
			foreach($ds as $data)
			{
				if($keyField!=='')
					$keys->add($this->getDataFieldValue($data,$keyField));
				if($index===$editIndex)
					$itemType='EditItem';
				else if($index===$selectedIndex)
					$itemType='SelectedItem';
				else if($index % 2)
					$itemType='AlternatingItem';
				else
					$itemType='Item';
				$items->add($this->createItemInternal($index,$dsIndex,$itemType,true,$data,$columns));
				$index++;
				$dsIndex++;
			}
			$this->createItemInternal(-1,-1,'Footer',true,null,$columns);
			if($allowPaging)
				$this->createPager(-1,-1,$columnCount,$ds);
			$this->setViewState('ItemCount',$index,0);
			$this->setViewState('PageCount',$ds->getPageCount(),0);
			$this->setViewState('DataSourceCount',$ds->getDataSourceCount(),0);
		}
		else
		{
			$this->clearViewState('ItemCount');
			$this->clearViewState('PageCount');
			$this->clearViewState('DataSourceCount');
		}
		$this->_pagedDataSource=null;
	}

	/**
	 * Creates a datagrid item instance based on the item type and index.
	 * @param integer zero-based item index
	 * @param string item type, may be 'Header', 'Footer', 'Item', 'Separator', 'AlternatingItem', 'SelectedItem', 'EditItem'.
	 * @return TDataGridItem created data list item
	 */
	protected function createItem($itemIndex,$dataSourceIndex,$itemType)
	{
		return new TDataGridItem($itemIndex,$dataSourceIndex,$itemType);
	}

	private function createItemInternal($itemIndex,$dataSourceIndex,$itemType,$dataBind,$dataItem,$columns)
	{
		$item=$this->createItem($itemIndex,$dataSourceIndex,$itemType);
		$this->initializeItem($item,$columns);
		$param=new TDataGridItemEventParameter($item);
		if($dataBind)
		{
			$item->setDataItem($dataItem);
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			$item->dataBind();
			$this->onItemDataBound($param);
			$item->setDataItem(null);
		}
		else
		{
			$this->onItemCreated($param);
			$this->getControls()->add($item);
		}
		return $item;
	}

	protected function initializeItem($item,$columns)
	{
		$cells=$item->getCells();
		$itemType=$item->getItemType();
		$index=0;
		foreach($columns as $column)
		{
			if($itemType==='Header')
				$cell=new TTableHeaderCell;
			else
				$cell=new TTableCell;
			$column->initializeCell($cell,$index,$itemType);
			$cells->add($cell);
			$index++;
		}
	}

	private function createPager($itemIndex,$dataSourceIndex,$columnSpan,$pagedDataSource)
	{
		$item=$this->createItem($itemIndex,$dataSourceIndex,'Pager');
		$this->initializePager($item,$columnSpan,$pagedDataSource);
		$this->onItemCreated(new TDataGridItemEventParameter($item));
		$this->getControls()->add($item);
		return $item;
	}

	protected function initializePager($pager,$columnSpan,$pagedDataSource)
	{
		$cell=new TTableCell;
		if($columnSpan>1)
			$cell->setColumnSpan($columnSpan);
		$this->buildPager($cell,$pagedDataSource);
		$pager->getCells()->add($cell);
	}

	protected function buildPager($cell,$dataSource)
	{
		switch($this->getPagerStyle()->getMode())
		{
			case 'NextPrev':
				$this->buildNextPrevPager($cell,$dataSource);
				break;
			case 'Numeric':
				$this->buildNumericPager($cell,$dataSource);
				break;
		}
	}

	protected function buildNextPrevPager($cell,$dataSource)
	{
		$style=$this->getPagerStyle();
		$controls=$cell->getControls();
		if($dataSource->getIsFirstPage())
		{
			$label=new TLabel;
			$label->setText($style->getPrevPageText());
			$controls->add($label);
		}
		else
		{
			$button=new TLinkButton;
			$button->setText($style->getPrevPageText());
			$button->setCommandName('page');
			$button->setCommandParameter('prev');
			$button->setCausesValidation(false);
			$controls->add($button);
		}
		$controls->add('&nbsp;');
		if($dataSource->getIsLastPage())
		{
			$label=new TLabel;
			$label->setText($style->getNextPageText());
			$controls->add($label);
		}
		else
		{
			$button=new TLinkButton;
			$button->setText($style->getNextPageText());
			$button->setCommandName('page');
			$button->setCommandParameter('next');
			$button->setCausesValidation(false);
			$controls->add($button);
		}
	}

	protected function buildNumericPager($cell,$dataSource)
	{
		$style=$this->getPagerStyle();
		$controls=$cell->getControls();
		$pageCount=$dataSource->getPageCount();
		$pageIndex=$dataSource->getCurrentPageIndex()+1;
		$maxButtonCount=$style->getPageButtonCount();
		$buttonCount=$maxButtonCount>$pageCount?$pageCount:$maxButtonCount;
		$startPageIndex=1;
		$endPageIndex=$buttonCount;
		if($pageIndex>$endPageIndex)
		{
			$startPageIndex=((int)($pageIndex/$maxButtonCount))*$maxButtonCount+1;
			if(($endPageIndex=$startPageIndex+$maxButtonCount-1)>$pageCount)
				$endPageIndex=$pageCount;
			if($endPageIndex-$startPageIndex+1<$maxButtonCount)
			{
				if(($startPageIndex=$endPageIndex-$maxButtonCount+1)<1)
					$startPageIndex=1;
			}
		}

		if($startPageIndex>1)
		{
			$button=new TLinkButton;
			$button->setText('...');
			$button->setCommandName('page');
			$button->setCommandParameter($startPageIndex-1);
			$button->setCausesValidation(false);
			$controls->add($button);
			$controls->add('&nbsp;');
		}

		for($i=$startPageIndex;$i<=$endPageIndex;++$i)
		{
			if($i===$pageIndex)
			{
				$label=new TLabel;
				$label->setText("$i");
				$controls->add($label);
			}
			else
			{
				$button=new TLinkButton;
				$button->setText("$i");
				$button->setCommandName('page');
				$button->setCommandParameter($i);
				$button->setCausesValidation(false);
				$controls->add($button);
			}
			if($i<$endPageIndex)
				$controls->add('&nbsp;');
		}

		if($pageCount>$endPageIndex)
		{
			$controls->add('&nbsp;');
			$button=new TLinkButton;
			$button->setText('...');
			$button->setCommandName('page');
			$button->setCommandParameter($endPageIndex+1);
			$button->setCausesValidation(false);
			$controls->add($button);
		}
	}

	protected function createAutoColumns($dataSource)
	{
		if(!$dataSource)
			return null;
		$autoColumns=$this->getAutoColumns();
		$autoColumns->clear();
		foreach($dataSource as $row)
		{
			foreach($row as $key=>$value)
			{
				$column=new TBoundColumn;
				if(is_string($key))
				{
					$column->setHeaderText($key);
					$column->setDataField($key);
					$column->setSortExpression($key);
					$column->setOwner($this);
					$autoColumns->add($column);
				}
				else
				{
					$column->setHeaderText('Item');
					$column->setDataField($key);
					$column->setSortExpression('Item');
					$column->setOwner($this);
					$autoColumns->add($column);
				}
			}
			break;
		}
		return $autoColumns;
	}

	/**
	 * Applies styles to items, header, footer and separators.
	 * Item styles are applied in a hierarchical way. Style in higher hierarchy
	 * will inherit from styles in lower hierarchy.
	 * Starting from the lowest hierarchy, the item styles include
	 * item's own style, {@link getItemStyle ItemStyle}, {@link getAlternatingItemStyle AlternatingItemStyle},
	 * {@link getSelectedItemStyle SelectedItemStyle}, and {@link getEditItemStyle EditItemStyle}.
	 * Therefore, if background color is set as red in {@link getItemStyle ItemStyle},
	 * {@link getEditItemStyle EditItemStyle} will also have red background color
	 * unless it is set to a different value explicitly.
	 */
	protected function applyItemStyles()
	{
		$itemStyle=$this->getViewState('ItemStyle',null);

		$alternatingItemStyle=new TTableItemStyle($itemStyle);
		if(($style=$this->getViewState('AlternatingItemStyle',null))!==null)
			$alternatingItemStyle->mergeWith($style);

		$selectedItemStyle=$this->getViewState('SelectedItemStyle',null);

		$editItemStyle=new TTableItemStyle($selectedItemStyle);
		if(($style=$this->getViewState('EditItemStyle',null))!==null)
			$editItemStyle->copyFrom($style);

		$headerStyle=$this->getViewState('HeaderStyle',null);
		$footerStyle=$this->getViewState('FooterStyle',null);
		$pagerStyle=$this->getViewState('PagerStyle',null);
		$separatorStyle=$this->getViewState('SeparatorStyle',null);

		foreach($this->getControls() as $index=>$item)
		{
			$itemType=$item->getItemType();
			switch($itemType)
			{
				case 'Header':
					if($headerStyle)
						$item->getStyle()->mergeWith($headerStyle);
					if(!$this->getShowHeader())
						$item->setVisible(false);
					break;
				case 'Footer':
					if($footerStyle)
						$item->getStyle()->mergeWith($footerStyle);
					if(!$this->getShowFooter())
						$item->setVisible(false);
					break;
				case 'Separator':
					if($separatorStyle)
						$item->getStyle()->mergeWith($separatorStyle);
					break;
				case 'Item':
					if($itemStyle)
						$item->getStyle()->mergeWith($itemStyle);
					break;
				case 'AlternatingItem':
					if($alternatingItemStyle)
						$item->getStyle()->mergeWith($alternatingItemStyle);
					break;
				case 'SelectedItem':
					if($index % 2==1)
					{
						if($itemStyle)
							$item->getStyle()->mergeWith($itemStyle);
					}
					else
					{
						if($alternatingItemStyle)
							$item->getStyle()->mergeWith($alternatingItemStyle);
					}
					if($selectedItemStyle)
						$item->getStyle()->mergeWith($selectedItemStyle);
					break;
				case 'EditItem':
					if($index % 2==1)
					{
						if($itemStyle)
							$item->getStyle()->mergeWith($itemStyle);
					}
					else
					{
						if($alternatingItemStyle)
							$item->getStyle()->mergeWith($alternatingItemStyle);
					}
					if($editItemStyle)
						$item->getStyle()->mergeWith($editItemStyle);
					break;
				case 'Pager':
					if($pagerStyle)
					{
						$item->getStyle()->mergeWith($pagerStyle);
						if($index===0)
						{
							if($pagerStyle->getPosition()==='Bottom' || !$pagerStyle->getVisible())
								$item->setVisible(false);
						}
						else
						{
							if($pagerStyle->getPosition()==='Top' || !$pagerStyle->getVisible())
								$item->setVisible(false);
						}
					}
					break;
				default:
					break;
			}
			if($this->_columns && $itemType!=='Pager')
			{
				$n=$this->_columns->getCount();
				$cells=$item->getCells();
				for($i=0;$i<$n;++$i)
				{
					$cell=$cells->itemAt($i);
					$column=$this->_columns->itemAt($i);
					if(!$column->getVisible())
						$cell->setVisible(false);
					else
					{
						if($itemType==='Header')
							$style=$column->getHeaderStyle(false);
						else if($itemType==='Footer')
							$style=$column->getFooterStyle(false);
						else
							$style=$column->getItemStyle(false);
						if($style!==null)
							$cell->getStyle()->mergeWith($style);
					}
				}
			}
		}
	}

	protected function renderContents($writer)
	{
		if($this->getHasControls())
		{
			$this->applyItemStyles();
			parent::renderContents($writer);
		}
	}
}

/**
 * TDataGridItemEventParameter class
 *
 * TDataGridItemEventParameter encapsulates the parameter data for
 * {@link TDataGrid::onItemCreated ItemCreated} event of {@link TDataGrid} controls.
 * The {@link getItem Item} property indicates the datagrid item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridItemEventParameter extends TEventParameter
{
	/**
	 * The TDataGridItem control responsible for the event.
	 * @var TDataGridItem
	 */
	private $_item=null;

	/**
	 * Constructor.
	 * @param TDataGridItem datagrid item related with the corresponding event
	 */
	public function __construct(TDataGridItem $item)
	{
		$this->_item=$item;
	}

	/**
	 * @return TDataGridItem datagrid item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}

/**
 * TDataGridCommandEventParameter class
 *
 * TDataGridCommandEventParameter encapsulates the parameter data for
 * {@link TDataGrid::onItemCommand ItemCommand} event of {@link TDataGrid} controls.
 *
 * The {@link getItem Item} property indicates the datagrid item related with the event.
 * The {@link getCommandSource CommandSource} refers to the control that originally
 * raises the Command event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridCommandEventParameter extends TCommandEventParameter
{
	/**
	 * @var TDataGridItem the TDataGridItem control responsible for the event.
	 */
	private $_item=null;
	/**
	 * @var TControl the control originally raises the <b>Command</b> event.
	 */
	private $_source=null;

	/**
	 * Constructor.
	 * @param TDataGridItem datagrid item responsible for the event
	 * @param TControl original event sender
	 * @param TCommandEventParameter original event parameter
	 */
	public function __construct($item,$source,TCommandEventParameter $param)
	{
		$this->_item=$item;
		$this->_source=$source;
		parent::__construct($param->getCommandName(),$param->getCommandParameter());
	}

	/**
	 * @return TDataGridItem the TDataGridItem control responsible for the event.
	 */
	public function getItem()
	{
		return $this->_item;
	}

	/**
	 * @return TControl the control originally raises the <b>Command</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}
}

/**
 * TDataGridSortCommandEventParameter class
 *
 * TDataGridSortCommandEventParameter encapsulates the parameter data for
 * {@link TDataGrid::onSortCommand SortCommand} event of {@link TDataGrid} controls.
 *
 * The {@link getCommandSource CommandSource} property refers to the control
 * that originally raises the Command event, while {@link getSortExpression SortExpression}
 * gives the sort expression carried with the sort command.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridSortCommandEventParameter extends TEventParameter
{
	/**
	 * @var string sort expression
	 */
	private $_sortExpression='';
	/**
	 * @var TControl original event sender
	 */
	private $_source=null;

	/**
	 * Constructor.
	 * @param TControl the control originally raises the <b>Command</b> event.
	 * @param TDataGridCommandEventParameter command event parameter
	 */
	public function __construct($source,TDataGridCommandEventParameter $param)
	{
		$this->_source=$source;
		$this->_sortExpression=$param->getCommandParameter();
	}

	/**
	 * @return TControl the control originally raises the <b>Command</b> event.
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

/**
 * TDataGridPageChangedEventParameter class
 *
 * TDataGridPageChangedEventParameter encapsulates the parameter data for
 * {@link TDataGrid::onPageIndexChanged PageIndexChanged} event of {@link TDataGrid} controls.
 *
 * The {@link getCommandSource CommandSource} property refers to the control
 * that originally raises the Command event, while {@link getNewPageIndex NewPageIndex}
 * returns the new page index carried with the page command.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridPageChangedEventParameter extends TEventParameter
{
	/**
	 * @var integer new page index
	 */
	private $_newIndex;
	/**
	 * @var TControl original event sender
	 */
	private $_source=null;

	/**
	 * Constructor.
	 * @param TControl the control originally raises the <b>Command</b> event.
	 * @param integer new page index
	 */
	public function __construct($source,$newPageIndex)
	{
		$this->_source=$source;
		$this->_newIndex=$newPageIndex;
	}

	/**
	 * @return TControl the control originally raises the <b>Command</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}

	/**
	 * @return integer new page index
	 */
	public function getNewPageIndex()
	{
		return $this->_newIndex;
	}
}

/**
 * TDataGridItem class
 *
 * A TDataGridItem control represents an item in the {@link TDataGrid} control,
 * such as heading section, footer section, or a data item.
 * The index and data value of the item can be accessed via {@link getItemIndex ItemIndex}>
 * and {@link getDataItem DataItem} properties, respectively. The type of the item
 * is given by {@link getItemType ItemType} property. Property {@link getDataSourceIndex DataSourceIndex}
 * gives the index of the item from the bound data source.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridItem extends TTableRow implements INamingContainer
{
	/**
	 * @var integer index of the data item in the Items collection of datagrid
	 */
	private $_itemIndex='';
	/**
	 * @var integer index of the item from the bound data source
	 */
	private $_dataSourceIndex=0;
	/**
	 * type of the TDataGridItem
	 * @var string
	 */
	private $_itemType='';
	/**
	 * value of the data item
	 * @var mixed
	 */
	private $_dataItem=null;

	/**
	 * Constructor.
	 * @param integer zero-based index of the item in the item collection of datagrid
	 * @param string item type, can be 'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager'.
	 */
	public function __construct($itemIndex,$dataSourceIndex,$itemType)
	{
		$this->_itemIndex=$itemIndex;
		$this->_dataSourceIndex=$dataSourceIndex;
		$this->setItemType($itemType);
	}

	/**
	 * @return string item type, can be 'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager'
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setItemType($value)
	{
		$this->_itemType=TPropertyValue::ensureEnum($value,'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager');
	}

	/**
	 * @return integer zero-based index of the item in the item collection of datagrid
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * @return integer the index of the datagrid item from the bound data source
	 */
	public function getDataSourceIndex()
	{
		return $this->_dataSourceIndex;
	}

	/**
	 * @return mixed data associated with the item
	 */
	public function getDataItem()
	{
		return $this->_dataItem;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setDataItem($value)
	{
		$this->_dataItem=$value;
	}

	/**
	 * Handles <b>BubbleEvent</b>.
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>Command</b> event with item information.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	public function onBubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$this->raiseBubbleEvent($this,new TDataGridCommandEventParameter($this,$sender,$param));
			return true;
		}
		else
			return false;
	}
}


/**
 * TDataGridItemCollection class.
 *
 * TDataGridItemCollection represents a collection of data grid items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridItemCollection extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only TDataGridItem.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TDataGridItem.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TDataGridItem)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('datagriditemcollection_datagriditem_required');
	}
}

/**
 * TDataGridColumnCollection class.
 *
 * TDataGridColumnCollection represents a collection of data grid columns.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridColumnCollection extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only TDataGridColumn.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TDataGridColumn.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TDataGridColumn)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('datagridcolumncollection_datagridcolumn_required');
	}
}


class TDataGridPagerStyle extends TTableItemStyle
{
	private $_mode=null;
	private $_nextText=null;
	private $_prevText=null;
	private $_buttonCount=null;
	private $_position=null;
	private $_visible=null;

	public function getMode()
	{
		return $this->_mode===null?'NextPrev':$this->_mode;
	}

	public function setMode($value)
	{
		$this->_mode=TPropertyValue::ensureEnum($value,'NextPrev','Numeric');
	}

	public function getNextPageText()
	{
		return $this->_nextText===null?'>':$this->_nextText;
	}

	public function setNextPageText($value)
	{
		$this->_nextText=$value;
	}

	public function getPrevPageText()
	{
		return $this->_prevText===null?'<':$this->_prevText;
	}

	public function setPrevPageText($value)
	{
		$this->_prevText=$value;
	}

	public function getPageButtonCount()
	{
		return $this->_buttonCount===null?10:$this->_buttonCount;
	}

	public function setPageButtonCount($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<1)
			throw new TInvalidDataValueException('datagridpagerstyle_pagebuttoncount_invalid');
		$this->_buttonCount=$value;
	}

	public function getPosition()
	{
		return $this->_position===null?'Bottom':$this->_position;
	}

	public function setPosition($value)
	{
		$this->_position=TPropertyValue::ensureEnum($value,'Bottom','Top','TopAndBottom');
	}

	public function getVisible()
	{
		return $this->_visible===null?true:$this->_visible;
	}

	public function setVisible($value)
	{
		$this->_visible=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Resets the style to the original empty state.
	 */
	public function reset()
	{
		parent::reset();
		$this->_visible=null;
		$this->_position=null;
		$this->_buttonCount=null;
		$this->_prevText=null;
		$this->_nextText=null;
		$this->_mode=null;
	}

	/**
	 * Copies the style content from an existing style
	 * This method overrides the parent implementation by
	 * adding additional TDataGridPagerStyle specific attributes.
	 * @param TStyle source style
	 */
	public function copyFrom($style)
	{
		parent::copyFrom($style);
		if($style instanceof TDataGridPagerStyle)
		{
			$this->_visible=$style->_visible;
			$this->_position=$style->_position;
			$this->_buttonCount=$style->_buttonCount;
			$this->_prevText=$style->_prevText;
			$this->_nextText=$style->_nextText;
			$this->_mode=$style->_mode;
		}
	}

	/**
	 * Merges with a style.
	 * If a style field is set in the new style, the current style field
	 * will be overwritten.
	 * This method overrides the parent implementation by
	 * merging with additional TDataGridPagerStyle specific attributes.
	 * @param TStyle the new style
	 */
	public function mergeWith($style)
	{
		parent::mergeWith($style);
		if($style instanceof TDataGridPagerStyle)
		{
			if($style->_visible!==null)
				$this->_visible=$style->_visible;
			if($style->_position!==null)
				$this->_position=$style->_position;
			if($style->_buttonCount!==null)
				$this->_buttonCount=$style->_buttonCount;
			if($style->_prevText!==null)
				$this->_prevText=$style->_prevText;
			if($style->_nextText!==null)
				$this->_nextText=$style->_nextText;
			if($style->_mode!==null)
				$this->_mode=$style->_mode;
		}
	}
}

?>