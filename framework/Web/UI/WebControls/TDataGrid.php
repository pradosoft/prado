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
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TBaseList, TPagedDataSource, TDummyDataSource and TTable classes
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
 * To populate data into the datagrid, sets its {@link setDataSource DataSource}
 * to a tabular data source and call {@link dataBind()}.
 * Each row of data will be represented by an item in the {@link getItems Items}
 * collection of the datagrid.
 *
 * An item can be at one of three states: browsing, selected and edit.
 * The state determines how the item will be displayed. For example, if an item
 * is in edit state, it may be displayed as a table row with input text boxes
 * if the columns are of type {@link TBoundColumn}; and if in browsing state,
 * they are displayed as static text.
 *
 * To change the state of an item, set {@link setEditItemIndex EditItemIndex}
 * or {@link setSelectedItemIndex SelectedItemIndex} property.
 *
 * A datagrid is specified with a list of columns. Each column specifies how the corresponding
 * table column will be displayed. For example, the header/footer text of that column,
 * the cells in that column, and so on. The following column types are currently
 * provided by the framework,
 * - {@link TBoundColumn}, associated with a specific field in datasource and displays the corresponding data.
 * - {@link TEditCommandColumn}, displaying edit/update/cancel command buttons
 * - {@link TButtonColumn}, displaying generic command buttons that may be bound to specific field in datasource.
 * - {@link THyperLinkColumn}, displaying a hyperlink that may be bound to specific field in datasource.
 * - {@link TCheckBoxColumn}, displaying a checkbox that may be bound to specific field in datasource.
 * - {@link TTemplateColumn}, displaying content based on templates.
 *
 * There are three ways to specify columns for a datagrid.
 * <ul>
 *  <li>Automatically generated based on data source.
 *  By setting {@link setAutoGenerateColumns AutoGenerateColumns} to true,
 *  a list of columns will be automatically generated based on the schema of the data source.
 *  Each column corresponds to a column of the data.</li>
 *  <li>Specified in template. For example,
 *    <code>
 *     <com:TDataGrid ...>
 *        <com:TBoundColumn .../>
 *        <com:TEditCommandColumn .../>
 *     </com:TDataGrid>
 *    </code>
 *  </li>
 *  <li>Manually created in code. Columns can be manipulated via
 *  the {@link setColumns Columns} property of the datagrid. For example,
 *  <code>
 *    $column=new TBoundColumn;
 *    $datagrid->Columns[]=$column;
 *  </code>
 *  </li>
 * </ul>
 * Note, automatically generated columns cannot be accessed via
 * the {@link getColumns Columns} property.
 *
 * TDataGrid supports sorting. If the {@link setAllowSorting AllowSorting}
 * is set to true, a column with nonempty {@link setSortExpression SortExpression}
 * will have its header text displayed as a clickable link button.
 * Clicking on the link button will raise {@link onSortCommand OnSortCommand}
 * event. You can respond to this event, sort the data source according
 * to the event parameter, and then invoke {@link databind()} on the datagrid
 * to show to end users the sorted data.
 *
 * TDataGrid supports paging. If the {@link setAllowPaging AllowPaging}
 * is set to true, a pager will be displayed on top and/or bottom of the table.
 * How the pager will be displayed is determined by the {@link getPagerStyle PagerStyle}
 * property. Clicking on a pager button will raise an {@link onPageIndexChanged OnPageIndexChanged}
 * event. You can respond to this event, specify the page to be displayed by
 * setting {@link setCurrentPageIndex CurrentPageIndex}</b> property,
 * and then invoke {@link databind()} on the datagrid to show to end users
 * a new page of data.
 *
 * TDataGrid supports two kinds of paging. The first one is based on the number of data items in
 * datasource. The number of pages {@link getPageCount PageCount} is calculated based
 * the item number and the {@link setPageSize PageSize} property.
 * The datagrid will manage which section of the data source to be displayed
 * based on the {@link setCurrentPageIndex CurrentPageIndex} property.
 * The second approach calculates the page number based on the
 * {@link setVirtualItemCount VirtualItemCount} property and
 * the {@link setPageSize PageSize} property. The datagrid will always
 * display from the beginning of the datasource up to the number of
 * {@link setPageSize PageSize} data items. This approach is especially
 * useful when the datasource may contain too many data items to be managed by
 * the datagrid efficiently.
 *
 * When the datagrid contains a button control that raises an {@link onCommand OnCommand}
 * event, the event will be bubbled up to the datagrid control.
 * If the event's command name is recognizable by the datagrid control,
 * a corresponding item event will be raised. The following item events will be
 * raised upon a specific command:
 * - OnEditCommand, if CommandName=edit
 * - OnCancelCommand, if CommandName=cancel
 * - OnSelectCommand, if CommandName=select
 * - OnDeleteCommand, if CommandName=delete
 * - OnUpdateCommand, if CommandName=update
 * - onPageIndexChanged, if CommandName=page
 * - OnSortCommand, if CommandName=sort
 * Note, an {@link onItemCommand OnItemCommand} event is raised in addition to
 * the above specific command events.
 *
 * TDataGrid also raises an {@link onItemCreated OnItemCreated} event for
 * every newly created datagrid item. You can respond to this event to customize
 * the content or style of the newly created item.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGrid extends TBaseDataList implements INamingContainer
{
	const CMD_SELECT='Select';
	const CMD_EDIT='Edit';
	const CMD_UPDATE='Update';
	const CMD_DELETE='Delete';
	const CMD_CANCEL='Cancel';
	const CMD_SORT='Sort';
	const CMD_PAGE='Page';
	const CMD_PAGE_NEXT='Next';
	const CMD_PAGE_PREV='Prev';

	/**
	 * @var TDataGridColumnCollection manually created column collection
	 */
	private $_columns=null;
	/**
	 * @var TDataGridColumnCollection automatically created column collection
	 */
	private $_autoColumns=null;
	/**
	 * @var TDataGridItemCollection datagrid item collection
	 */
	private $_items=null;
	/**
	 * @var TDataGridItem header item
	 */
	private $_header=null;
	/**
	 * @var TDataGridItem footer item
	 */
	private $_footer=null;
	/**
	 * @var TPagedDataSource paged data source object
	 */
	private $_pagedDataSource=null;

	/**
	 * @return string tag name (table) of the datagrid
	 */
	protected function getTagName()
	{
		return 'table';
	}

	/**
	 * Adds objects parsed in template to datagrid.
	 * Only datagrid columns are added into {@link getColumns Columns} collection.
	 * @param mixed object parsed in template
	 */
	public function addParsedObject($object)
	{
		if($object instanceof TDataGridColumn)
			$this->getColumns()->add($object);
	}

	/**
	 * @return TDataGridColumnCollection manually specified datagrid columns
	 */
	public function getColumns()
	{
		if(!$this->_columns)
			$this->_columns=new TDataGridColumnCollection($this);
		return $this->_columns;
	}

	/**
	 * @return TDataGridColumnCollection automatically specified datagrid columns
	 */
	public function getAutoColumns()
	{
		if(!$this->_autoColumns)
			$this->_autoColumns=new TDataGridColumnCollection($this);
		return $this->_autoColumns;
	}

	/**
	 * @return TDataGridItemCollection datagrid item collection
	 */
	public function getItems()
	{
		if(!$this->_items)
			$this->_items=new TDataGridItemCollection;
		return $this->_items;
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableStyle} to be used by datagrid.
	 * @return TTableStyle control style to be used
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
	 * @param string the URL of the background image for the datagrid
	 */
	public function setBackImageUrl($value)
	{
		$this->getStyle()->setBackImageUrl($value);
	}

	/**
	 * @return TTableItemStyle the style for every item
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
	 * @return TDataGridItem the header item
	 */
	public function getHeader()
	{
		return $this->_header;
	}

	/**
	 * @return TDataGridItem the footer item
	 */
	public function getFooter()
	{
		return $this->_footer;
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
	 * @return boolean whether the custom paging is enabled. Defaults to false.
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
	 * @return integer the zero-based index of the current page. Defaults to 0.
	 */
	public function getCurrentPageIndex()
	{
		return $this->getViewState('CurrentPageIndex',0);
	}

	/**
	 * @param integer the zero-based index of the current page
	 * @throws TInvalidDataValueException if the value is less than 0
	 */
	public function setCurrentPageIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('datagrid_currentpageindex_invalid');
		$this->setViewState('CurrentPageIndex',$value,0);
	}

	/**
	 * @return integer the number of rows displayed each page. Defaults to 10.
	 */
	public function getPageSize()
	{
		return $this->getViewState('PageSize',10);
	}

	/**
	 * @param integer the number of rows displayed within a page
	 * @throws TInvalidDataValueException if the value is less than 1
	 */
	public function setPageSize($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<1)
			throw new TInvalidDataValueException('datagrid_pagesize_invalid');
		$this->setViewState('PageSize',TPropertyValue::ensureInteger($value),10);
	}

	/**
	 * @return integer number of pages of items available
	 */
	public function getPageCount()
	{
		if($this->_pagedDataSource)
			return $this->_pagedDataSource->getPageCount();
		else
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
	 * @throws TInvalidDataValueException if the value is less than 0
	 */
	public function setVirtualItemCount($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			throw new TInvalidDataValueException('datagrid_virtualitemcount_invalid');
		$this->setViewState('VirtualItemCount',$value,0);
	}

	/**
	 * @return boolean whether the header should be displayed. Defaults to true.
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
	 * Handles <b>OnBubbleEvent</b>.
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
			if(strcasecmp($command,self::CMD_SELECT)===0)
			{
				$this->setSelectedItemIndex($param->getItem()->getItemIndex());
				$this->onSelectedIndexChanged(null);
				return true;
			}
			else if(strcasecmp($command,self::CMD_EDIT)===0)
			{
				$this->onEditCommand($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_DELETE)===0)
			{
				$this->onDeleteCommand($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_UPDATE)===0)
			{
				$this->onUpdateCommand($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_CANCEL)===0)
			{
				$this->onCancelCommand($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_SORT)===0)
			{
				$this->onSortCommand(new TDataGridSortCommandEventParameter($sender,$param));
				return true;
			}
			else if(strcasecmp($command,self::CMD_PAGE)===0)
			{
				$p=$param->getCommandParameter();
				if(strcasecmp($p,self::CMD_PAGE_NEXT)===0)
					$pageIndex=$this->getCurrentPageIndex()+1;
				else if(strcasecmp($p,self::CMD_PAGE_PREV)===0)
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
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>cancel</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onCancelCommand($param)
	{
		$this->raiseEvent('OnCancelCommand',$this,$param);
	}

	/**
	 * Raises <b>OnDeleteCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>delete</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onDeleteCommand($param)
	{
		$this->raiseEvent('OnDeleteCommand',$this,$param);
	}

	/**
	 * Raises <b>OnEditCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>edit</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onEditCommand($param)
	{
		$this->raiseEvent('OnEditCommand',$this,$param);
	}

	/**
	 * Raises <b>OnItemCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onItemCommand($param)
	{
		$this->raiseEvent('OnItemCommand',$this,$param);
	}

	/**
	 * Raises <b>OnSortCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>sort</b> command name.
	 * @param TDataGridSortCommandEventParameter event parameter
	 */
	public function onSortCommand($param)
	{
		$this->raiseEvent('OnSortCommand',$this,$param);
	}

	/**
	 * Raises <b>OnUpdateCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
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
				$this->_autoColumns=new TDataGridColumnCollection($this);
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

	/**
	 * @return TPagedDataSource creates a paged data source
	 */
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
	 * Clears up all items in the datagrid.
	 */
	public function reset()
	{
		$this->getControls()->clear();
		$this->getItems()->clear();
		$this->_header=null;
		$this->_footer=null;
	}

	/**
	 * Restores datagrid content from viewstate.
	 */
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
			$this->_header=$this->createItemInternal(-1,-1,'Header',false,null,$columns);
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
			$this->_footer=$this->createItemInternal(-1,-1,'Footer',false,null,$columns);
			if($allowPaging)
				$this->createPager(-1,-1,$columnCount,$ds);
		}
		$this->_pagedDataSource=null;
	}

	/**
	 * Performs databinding to populate data list items from data source.
	 * This method is invoked by {@link dataBind()}.
	 * You may override this function to provide your own way of data population.
	 * @param Traversable the bound data
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
			$this->_header=$this->createItemInternal(-1,-1,'Header',true,null,$columns);
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
			$this->_footer=$this->createItemInternal(-1,-1,'Footer',true,null,$columns);
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

	/**
	 * Initializes a datagrid item and cells inside it
	 * @param TDataGrid datagrid item to be initialized
	 * @param TDataGridColumnCollection datagrid columns to be used to initialize the cells in the item
	 */
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

	/**
	 * Initializes the pager.
	 * @param TDataGridItem the pager to be initialized
	 * @param integer columnspan for the pager
	 * @param TPagedDataSource data source bound to the datagrid
	 */
	protected function initializePager($pager,$columnSpan,$pagedDataSource)
	{
		$cell=new TTableCell;
		if($columnSpan>1)
			$cell->setColumnSpan($columnSpan);
		$this->buildPager($cell,$pagedDataSource);
		$pager->getCells()->add($cell);
	}

	/**
	 * Builds the pager content based on pager style.
	 * @param TTableCell table cell for the pager
	 * @param TPagedDataSource data source bound to the datagrid
	 */
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

	/**
	 * Creates a pager button.
	 * @param string button type, LinkButton or PushButton
	 * @param boolean whether the button should be enabled
	 * @return mixed the button instance
	 */
	protected function createPagerButton($buttonType,$enabled)
	{
		if($buttonType==='LinkButton')
		{
			return $enabled?new TLinkButton:new TLabel;
		}
		else
		{
			$button=new TButton;
			if(!$enabled)
				$button->setEnabled(false);
			return $button;
		}
	}

	/**
	 * Builds a next-prev pager
	 * @param TTableCell table cell for the pager
	 * @param TPagedDataSource data source bound to the datagrid
	 */
	protected function buildNextPrevPager($cell,$dataSource)
	{
		$style=$this->getPagerStyle();
		$buttonType=$style->getButtonType();
		$controls=$cell->getControls();
		if($dataSource->getIsFirstPage())
		{
			$label=$this->createPagerButton($buttonType,false);
			$label->setText($style->getPrevPageText());
			$controls->add($label);
		}
		else
		{
			$button=$this->createPagerButton($buttonType,true);
			$button->setText($style->getPrevPageText());
			$button->setCommandName('page');
			$button->setCommandParameter('prev');
			$button->setCausesValidation(false);
			$controls->add($button);
		}
		$controls->add('&nbsp;');
		if($dataSource->getIsLastPage())
		{
			$label=$this->createPagerButton($buttonType,false);
			$label->setText($style->getNextPageText());
			$controls->add($label);
		}
		else
		{
			$button=$this->createPagerButton($buttonType,true);
			$button->setText($style->getNextPageText());
			$button->setCommandName('page');
			$button->setCommandParameter('next');
			$button->setCausesValidation(false);
			$controls->add($button);
		}
	}

	/**
	 * Builds a numeric pager
	 * @param TTableCell table cell for the pager
	 * @param TPagedDataSource data source bound to the datagrid
	 */
	protected function buildNumericPager($cell,$dataSource)
	{
		$style=$this->getPagerStyle();
		$buttonType=$style->getButtonType();
		$controls=$cell->getControls();
		$pageCount=$dataSource->getPageCount();
		$pageIndex=$dataSource->getCurrentPageIndex()+1;
		$maxButtonCount=$style->getPageButtonCount();
		$buttonCount=$maxButtonCount>$pageCount?$pageCount:$maxButtonCount;
		$startPageIndex=1;
		$endPageIndex=$buttonCount;
		if($pageIndex>$endPageIndex)
		{
			$startPageIndex=((int)(($pageIndex-1)/$maxButtonCount))*$maxButtonCount+1;
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
			$button=$this->createPagerButton($buttonType,true);
			$button->setText($style->getPrevPageText());
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
				$label=$this->createPagerButton($buttonType,false);
				$label->setText("$i");
				$controls->add($label);
			}
			else
			{
				$button=$this->createPagerButton($buttonType,true);
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
			$button=$this->createPagerButton($buttonType,true);
			$button->setText($style->getNextPageText());
			$button->setCommandName('page');
			$button->setCommandParameter($endPageIndex+1);
			$button->setCausesValidation(false);
			$controls->add($button);
		}
	}

	/**
	 * Automatically generates datagrid columns based on datasource schema
	 * @param TPagedDataSource data source bound to the datagrid
	 * @return TDataGridColumnCollection
	 */
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
					$autoColumns->add($column);
				}
				else
				{
					$column->setHeaderText('Item');
					$column->setDataField($key);
					$column->setSortExpression('Item');
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

		$alternatingItemStyle=$this->getViewState('AlternatingItemStyle',null);
		if($itemStyle!==null)
		{
			if($alternatingItemStyle===null)
				$alternatingItemStyle=$itemStyle;
			else
				$alternatingItemStyle->mergeWith($itemStyle);
		}

		$selectedItemStyle=$this->getViewState('SelectedItemStyle',null);

		$editItemStyle=$this->getViewState('EditItemStyle',null);
		if($selectedItemStyle!==null)
		{
			if($editItemStyle===null)
				$editItemStyle=$selectedItemStyle;
			else
				$editItemStyle->mergeWith($selectedItemStyle);
		}

		$headerStyle=$this->getViewState('HeaderStyle',null);
		$footerStyle=$this->getViewState('FooterStyle',null);
		$pagerStyle=$this->getViewState('PagerStyle',null);
		$separatorStyle=$this->getViewState('SeparatorStyle',null);

		$invisibleColumns=0;
		if($this->_columns)
		{
			foreach($this->_columns as $column)
				if(!$column->getVisible())
					$invisibleColumns++;
		}

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
					if($selectedItemStyle)
						$item->getStyle()->mergeWith($selectedItemStyle);
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
					break;
				case 'EditItem':
					if($editItemStyle)
						$item->getStyle()->mergeWith($editItemStyle);
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
			else if($itemType==='Pager' && $invisibleColumns>0)
			{
				$cell=$item->getCells()->itemAt(0);
				$cell->setColumnSpan($cell->getColumnSpan()-$invisibleColumns);
			}
		}
	}

	/**
	 * Renders the content in the datagrid.
	 * @param THtmlWriter writer for the rendering purpose
	 */
	public function renderContents($writer)
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
	 * the control that owns this collection.
	 * @var TControl
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param TDataGrid the control that owns this collection.
	 */
	public function __construct(TDataGrid $owner)
	{
		$this->_o=$owner;
	}

	/**
	 * @return TDataGrid the control that owns this collection.
	 */
	protected function getOwner()
	{
		return $this->_o;
	}

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
		{
			$item->setOwner($this->_o);
			parent::insertAt($index,$item);
		}
		else
			throw new TInvalidDataTypeException('datagridcolumncollection_datagridcolumn_required');
	}
}

/**
 * TDataGridPagerStyle class.
 *
 * TDataGridPagerStyle specifies the styles available for a datagrid pager.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataGridPagerStyle extends TTableItemStyle
{
	private $_mode=null;
	private $_nextText=null;
	private $_prevText=null;
	private $_buttonCount=null;
	private $_position=null;
	private $_visible=null;
	private $_buttonType=null;

	/**
	 * @return string pager mode. Defaults to 'NextPrev'.
	 */
	public function getMode()
	{
		return $this->_mode===null?'NextPrev':$this->_mode;
	}

	/**
	 * @param string pager mode. Valid values include 'NextPrev' and 'Numeric'.
	 */
	public function setMode($value)
	{
		$this->_mode=TPropertyValue::ensureEnum($value,'NextPrev','Numeric');
	}

	/**
	 * @return string the type of command button. Defaults to LinkButton.
	 */
	public function getButtonType()
	{
		return $this->_buttonType===null?'LinkButton':$this->_buttonType;
	}

	/**
	 * @param string the type of command button, LinkButton or PushButton
	 */
	public function setButtonType($value)
	{
		$this->_buttonType=TPropertyValue::ensureEnum($value,'LinkButton','PushButton');
	}

	/**
	 * @return string text for the next page button. Defaults to '>'.
	 */
	public function getNextPageText()
	{
		return $this->_nextText===null?'>':$this->_nextText;
	}

	/**
	 * @param string text for the next page button.
	 */
	public function setNextPageText($value)
	{
		$this->_nextText=$value;
	}

	/**
	 * @return string text for the previous page button. Defaults to '<'.
	 */
	public function getPrevPageText()
	{
		return $this->_prevText===null?'<':$this->_prevText;
	}

	/**
	 * @param string text for the next page button.
	 */
	public function setPrevPageText($value)
	{
		$this->_prevText=$value;
	}

	/**
	 * @return integer maximum number of pager buttons to be displayed. Defaults to 10.
	 */
	public function getPageButtonCount()
	{
		return $this->_buttonCount===null?10:$this->_buttonCount;
	}

	/**
	 * @param integer maximum number of pager buttons to be displayed
	 * @throws TInvalidDataValueException if the value is less than 1.
	 */
	public function setPageButtonCount($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<1)
			throw new TInvalidDataValueException('datagridpagerstyle_pagebuttoncount_invalid');
		$this->_buttonCount=$value;
	}

	/**
	 * @return string where the pager is to be displayed. Defaults to 'Bottom'.
	 */
	public function getPosition()
	{
		return $this->_position===null?'Bottom':$this->_position;
	}

	/**
	 * @param string where the pager is to be displayed. Valid values include 'Bottom', 'Top', 'TopAndBottom'
	 */
	public function setPosition($value)
	{
		$this->_position=TPropertyValue::ensureEnum($value,'Bottom','Top','TopAndBottom');
	}

	/**
	 * @return boolean whether the pager is visible. Defaults to true.
	 */
	public function getVisible()
	{
		return $this->_visible===null?true:$this->_visible;
	}

	/**
	 * @param boolean whether the pager is visible.
	 */
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
		$this->_buttonType=null;
	}

	/**
	 * Copies the fields in a new style to this style.
	 * If a style field is set in the new style, the corresponding field
	 * in this style will be overwritten.
	 * @param TStyle the new style
	 */
	public function copyFrom($style)
	{
		parent::copyFrom($style);
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
			if($style->_buttonType!==null)
				$this->_buttonType=$style->_buttonType;
		}
	}

	/**
	 * Merges the style with a new one.
	 * If a style field is not set in this style, it will be overwritten by
	 * the new one.
	 * @param TStyle the new style
	 */
	public function mergeWith($style)
	{
		parent::mergeWith($style);
		if($style instanceof TDataGridPagerStyle)
		{
			if($this->_visible===null)
				$this->_visible=$style->_visible;
			if($this->_position===null)
				$this->_position=$style->_position;
			if($this->_buttonCount===null)
				$this->_buttonCount=$style->_buttonCount;
			if($this->_prevText===null)
				$this->_prevText=$style->_prevText;
			if($this->_nextText===null)
				$this->_nextText=$style->_nextText;
			if($this->_mode===null)
				$this->_mode=$style->_mode;
			if($this->_buttonType===null)
				$this->_buttonType=$style->_buttonType;
		}
	}
}

?>