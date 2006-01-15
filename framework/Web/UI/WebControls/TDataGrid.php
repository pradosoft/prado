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
 * an <b>OnPageCommand</b> event. You can respond to this event, specify the page to be displayed by
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
 * - OnPageCommand, page
 * - OnSortCommand, sort
 * The data list will always raise an <b>OnItemCommand</b>
 * upon its receiving a bubbled <b>OnCommand</b> event.
 *
 * An <b>OnItemCreated</b> event will be raised right after each item is created in the datagrid.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>Items</b>, TDataGridItemCollection, read-only
 *   <br>Gets the list of TDataGridItem controls that correspond to each data item.
 * - <b>Columns</b>, TCollection, read-only
 *   <br>Gets the list of TDataGridColumn controls that are manually specified or created.
 * - <b>AutoGenerateColumns</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets the value indicating whether columns should be generated automatically based on the data in datasource.
 * - <b>AllowSorting</b>, boolean, default=false, kept in viewstate
 *   <br>Gets or sets the value indicating whether sorting should be enabled.
 * - <b>AllowPaging</b>, boolean, default=false, kept in viewstate
 *   <br>Gets or sets the value indicating whether paging should be enabled.
 * - <b>AllowCustomPaging</b>, boolean, default=false, kept in viewstate
 *   <br>Gets or sets the value indicating whether custom paging should be enabled.
 * - <b>CurrentPageIndex</b>, integer, default=0, stored in viewstate
 *   <br>Gets or sets the index for the page to be displayed
 * - <b>PageSize</b>, integer, default=10, stored in viewstate
 *   <br>Gets or sets the number of data items to be displayed in each page.
 * - <b>PageCount</b>, integer, read-only
 *   <br>Gets the number of pages to be displayed.
 * - <b>VirtualItemCount</b>, integer, default=0, stored in viewstate
 *   <br>Gets or sets the number of data items available for paging purpose when custom paging is enabled.
 * - <b>PagerButtonCount</b>, integer, default=10, stored in viewstate
 *   <br>Gets or sets the number of buttons to be displayed in pager for navigating among pages.
 * - <b>PagerDisplay</b>, string (None,Top,Bottom,TopAndBottom), default=Bottom, stored in viewstate
 *   <br>Gets or sets where the pager should be displayed.
 * - <b>EditItemIndex</b>, integer, default=-1, stored in viewstate
 *   <br>Gets or sets the index for edit item.
 * - <b>EditItem</b>, TDataGridItem, read-only
 *   <br>Gets the edit item, null if none
 * - <b>EditItemStyle</b>, string, stored in viewstate
 *   <br>Gets or sets the css style for the edit item
 * - <b>EditItemCssClass</b>, string, stored in viewstate
 *   <br>Gets or sets the css classs for the edit item
 * - <b>SelectedItemIndex</b>, integer, default=-1, stored in viewstate
 *   <br>Gets or sets the index for selected item.
 * - <b>SelectedItem</b>, TDataGridItem, read-only
 *   <br>Gets the selected item, null if none
 * - <b>SelectedItemStyle</b>, string, stored in viewstate
 *   <br>Gets or sets the css style for the selected item
 * - <b>SelectedItemCssClass</b>, string, stored in viewstate
 *   <br>Gets or sets the css class for the selected item
 * - <b>ItemStyle</b>, string, stored in viewstate
 *   <br>Gets or sets the css style for each item
 * - <b>ItemCssClass</b>, string, stored in viewstate
 *   <br>Gets or sets the css class for each item
 * - <b>AlternatingItemStyle</b>, string, stored in viewstate
 *   <br>Gets or sets the css style for each alternating item
 * - <b>AlternatingItemCssClass</b>, string, stored in viewstate
 *   <br>Gets or sets the css class for each alternating item
 * - <b>HeaderStyle</b>, string, stored in viewstate
 *   <br>Gets or sets the css style for the header
 * - <b>HeaderCssClass</b>, string, stored in viewstate
 *   <br>Gets or sets the css class for the header
 * - <b>FooterStyle</b>, string, stored in viewstate
 *   <br>Gets or sets the css style for the footer
 * - <b>FooterCssClass</b>, string, stored in viewstate
 *   <br>Gets or sets the css class for the footer
 * - <b>PagerStyle</b>, string, stored in viewstate
 *   <br>Gets or sets the css style for the pager
 * - <b>PagerCssClass</b>, string, stored in viewstate
 *   <br>Gets or sets the css class for the pager
 * - <b>ShowHeader</b>, boolean, default=true, stored in viewstate
 *   <br>Gets or sets the value whether to show header
 * - <b>ShowFooter</b>, boolean, default=true, stored in viewstate
 *   <br>Gets or sets the value whether to show footer
 * - <b>Header</b>, TDataGridItem
 *   <br>Gets the header of the data grid.
 * - <b>Footer</b>, TDataGridItem
 *   <br>Gets the footer of the data grid.
 * - <b>Pager</b>, TDataGridItem
 *   <br>Gets the pager of the data grid.
 * - <b>BackImageUrl</b>, string, kept in viewstate
 *   <br>Gets or sets the URL of the background image to display behind the datagrid.
 *
 * Events
 * - <b>OnEditCommand</b>, raised when a button control raises an <b>OnCommand</b> event with 'edit' command.
 * - <b>OnSelectCommand</b>, raised when a button control raises an <b>OnCommand</b> event with 'select' command.
 * - <b>OnUpdateCommand</b>, raised when a button control raises an <b>OnCommand</b> event with 'update' command.
 * - <b>OnCancelCommand</b>, raised when a button control raises an <b>OnCommand</b> event with 'cancel' command.
 * - <b>OnDeleteCommand</b>, raised when a button control raises an <b>OnCommand</b> event with 'delete' command.
 * - <b>OnPageCommand</b>, raised when a button control raises an <b>OnCommand</b> event with 'page' command.
 * - <b>OnSortCommand</b>, raised when a button control raises an <b>OnCommand</b> event with 'sort' command.
 * - <b>OnItemCommand</b>, raised when a button control raises an <b>OnCommand</b> event.
 * - <b>OnItemCreatedCommand</b>, raised right after an item is created.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: 1.25 $ $Date: 2005/12/17 06:11:28 $
 * @package System.Web.UI.WebControls
 */
class TDataGrid extends TBaseDataList
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

	public function getColumns()
	{
		if(!$this->_columns)
			$this->_columns=new TDataGridColumnCollection;
		return $this->_columns;
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
		return new TTableStyle;
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
	 * @return boolean whether datagrid columns should be automatically generated Defaults to false.
	 */
	public function getAutoGenerateColumns()
	{
		return $this->getViewState('AutoGenerateColumns',false);
	}

	/**
	 * @param boolean whether datagrid columns should be automatically generated
	 */
	public function setAutoGenerateColumns($value)
	{
		$this->setViewState('AutoGenerateColumns',TPropertyValue::ensureBoolean($value),false);
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
	 * @return boolean whether the footer should be displayed Defaults to true.
	 */
	public function getShowFooter()
	{
		return $this->getViewState('ShowFooter',true);
	}

	/**
	 * @param boolean whether the footer should be displayed
	 */
	public function setShowFooter($value)
	{
		$this->setViewState('ShowFooter',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * Handles <b>BubbleEvent</b>.
	 * This method overrides parent's implementation to handle
	 * {@link onItemCommand ItemCommand} event which is bubbled from
	 * {@link TDataGridItem} child controls.
	 * If the event parameter is {@link TDataGridCommandEventParameter} and
	 * the command name is a recognized one, which includes 'select', 'edit',
	 * 'delete', 'update', and 'cancel' (case-insensitive), then a
	 * corresponding command event is also raised (such as {@link onEditCommand EditCommand}).
	 * This method should only be used by control developers.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	protected function onBubbleEvent($sender,$param)
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
				$this->onSortCommand(new TDataGridSortCommandEventParameter($sender,$param->getCommandParameter()));
				return true;
			}
			else if(strcasecmp($command,'page')===0)
			{
				$p=$param->getCommandParameter();
				if(strcasecmp($p,'next'))
					$pageIndex=$this->getCurrentPageIndex()+1;
				else if(strcasecmp($p,'prev'))
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
	 * Raises <b>CancelCommand</b> event.
	 * This method is invoked when a button control raises<b>Command</b> event
	 * with<b>cancel</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onCancelCommand($param)
	{
		$this->raiseEvent('CancelCommand',$this,$param);
	}

	/**
	 * Raises <b>DeleteCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>delete</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onDeleteCommand($param)
	{
		$this->raiseEvent('DeleteCommand',$this,$param);
	}

	/**
	 * Raises <b>EditCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>edit</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onEditCommand($param)
	{
		$this->raiseEvent('EditCommand',$this,$param);
	}

	/**
	 * Raises <b>ItemCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event.
	 * @param TDataGridItemCommandEventParameter event parameter
	 */
	public function onItemCommand($param)
	{
		$this->raiseEvent('ItemCommand',$this,$param);
	}

	/**
	 * Raises <b>SortCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>sort</b> command name.
	 * @param TDataGridSortCommandEventParameter event parameter
	 */
	public function onSortCommand($param)
	{
		$this->raiseEvent('SortCommand',$this,$param);
	}

	/**
	 * Raises <b>UpdateCommand</b> event.
	 * This method is invoked when a button control raises <b>Command</b> event
	 * with <b>update</b> command name.
	 * @param TDataGridCommandEventParameter event parameter
	 */
	public function onUpdateCommand($param)
	{
		$this->raiseEvent('UpdateCommand',$this,$param);
	}

	/**
	 * Raises <b>ItemCreated</b> event.
	 * This method is invoked right after a datagrid item is created and before
	 * added to page hierarchy.
	 * @param TDataGridItemEventParameter event parameter
	 */
	public function onItemCreated($param)
	{
		$this->raiseEvent('ItemCreated',$this,$param);
	}

	/**
	 * Raises <b>ItemDataBound</b> event.
	 * This method is invoked for each datagrid item after it performs
	 * databinding.
	 * @param TDataGridItemEventParameter event parameter
	 */
	public function onItemDataBound($param)
	{
		$this->raiseEvent('ItemDataBound',$this,$param);
	}

	/**
	 * Raises <b>PageIndexChanged</b> event.
	 * This method is invoked when current page is changed.
	 * @param TDataGridPageChangedEventParameter event parameter
	 */
	public function onPageIndexChanged($param)
	{
		$this->raiseEvent('PageIndexChanged',$this,$param);
	}
}
/**
 * TDataGridItemEventParameter class
 *
 * TDataGridItemEventParameter encapsulates the parameter data for
 * {@link TDataGrid::onItemCreated ItemCreated} event of {@link TDataGrid} controls.
 * The {@link getItem Item} property indicates the DataList item related with the event.
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
	 * @param TDataGridItem DataList item related with the corresponding event
	 */
	public function __construct(TDataGridItem $item)
	{
		$this->_item=$item;
	}

	/**
	 * @return TDataGridItem DataList item related with the corresponding event
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
 * The {@link getItem Item} property indicates the DataList item related with the event.
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
	 * @param TDataGridItem DataList item responsible for the event
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
 * is given by {@link getItemType ItemType} property. Property {@link getDataSetIndex DataSetIndex}
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
	 * @var integer index of the data item in the Items collection of DataList
	 */
	private $_itemIndex='';
	/**
	 * @var integer index of the item from the bound data source
	 */
	private $_dataSetIndex=0;
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
	 * @param integer zero-based index of the item in the item collection of DataList
	 * @param string item type, can be 'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager'.
	 */
	public function __construct($itemIndex,$dataSetIndex,$itemType)
	{
		$this->_itemIndex=$itemIndex;
		$this->_dataSetIndex=$dataSetIndex;
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
	 * @return integer zero-based index of the item in the item collection of DataList
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * @return integer the index of the datagrid item from the bound data source
	 */
	public function getDataSetIndex()
	{
		return $this->_dataSetIndex;
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
	protected function onBubbleEvent($sender,$param)
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
	 * Returns true only when the item to be added is a {@link TDataGridItem}.
	 * This method is invoked before adding an item to the list.
	 * If it returns true, the item will be added to the list, otherwise not.
	 * @param mixed item to be added
	 * @return boolean whether the item can be added to the list
	 */
	protected function canAddItem($item)
	{
		return ($item instanceof TDataGridItem);
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
	 * Returns true only when the item to be added is a {@link TDataGridItem}.
	 * This method is invoked before adding an item to the list.
	 * If it returns true, the item will be added to the list, otherwise not.
	 * @param mixed item to be added
	 * @return boolean whether the item can be added to the list
	 */
	protected function canAddItem($item)
	{
		return ($item instanceof TDataGridColumn);
	}
}


class TDataGridPagerStyle extends TTableItemStyle
{
}

?>