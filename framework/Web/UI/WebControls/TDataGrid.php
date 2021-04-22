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

use Prado\Collections\TList;
use Prado\Collections\TPagedDataSource;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\UI\ITemplate;

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
 * Each datagrid item has a {@link TDataGridItem::getItemType type}
 * which tells the position and state of the item in the datalist. An item in the header
 * of the repeater is of type Header. A body item may be of either
 * Item, AlternatingItem, SelectedItem or EditItem, depending whether the item
 * index is odd or even, whether it is being selected or edited.
 *
 * A datagrid is specified with a list of columns. Each column specifies how the corresponding
 * table column will be displayed. For example, the header/footer text of that column,
 * the cells in that column, and so on. The following column types are currently
 * provided by the framework,
 * - {@link TBoundColumn}, associated with a specific field in datasource and displays the corresponding data.
 * - {@link TEditCommandColumn}, displaying edit/update/cancel command buttons
 * - {@link TButtonColumn}, displaying generic command buttons that may be bound to specific field in datasource.
 * - {@link TDropDownListColumn}, displaying a dropdown list when the item is in edit state
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
 * Note, the data bound to the datagrid are reset to null after databinding.
 * There are several ways to access the data associated with a datagrid row:
 * - Access the data in {@link onItemDataBound OnItemDataBound} event
 * - Use {@link getDataKeys DataKeys} to obtain the data key associated with
 * the specified datagrid row and use the key to fetch the corresponding data
 * from some persistent storage such as DB.
 * - Save the data in viewstate and get it back during postbacks.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 * @method TTableStyle getStyle()
 */
class TDataGrid extends TBaseDataList implements \Prado\Web\UI\INamingContainer
{
	/**
	 * Command name that TDataGrid understands.
	 */
	const CMD_SELECT = 'Select';
	const CMD_EDIT = 'Edit';
	const CMD_UPDATE = 'Update';
	const CMD_DELETE = 'Delete';
	const CMD_CANCEL = 'Cancel';
	const CMD_SORT = 'Sort';
	const CMD_PAGE = 'Page';
	const CMD_PAGE_NEXT = 'Next';
	const CMD_PAGE_PREV = 'Previous';
	const CMD_PAGE_FIRST = 'First';
	const CMD_PAGE_LAST = 'Last';

	/**
	 * @var TDataGridColumnCollection manually created column collection
	 */
	private $_columns;
	/**
	 * @var TDataGridColumnCollection automatically created column collection
	 */
	private $_autoColumns;
	/**
	 * @var TList all columns including both manually and automatically created columns
	 */
	private $_allColumns;
	/**
	 * @var TDataGridItemCollection datagrid item collection
	 */
	private $_items;
	/**
	 * @var TDataGridItem header item
	 */
	private $_header;
	/**
	 * @var TDataGridItem footer item
	 */
	private $_footer;
	/**
	 * @var TPagedDataSource paged data source object
	 */
	private $_pagedDataSource;
	private $_topPager;
	private $_bottomPager;
	/**
	 * @var ITemplate template used when empty data is bounded
	 */
	private $_emptyTemplate;
	/**
	 * @var bool whether empty template is effective
	 */
	private $_useEmptyTemplate = false;

	/**
	 * @return string tag name (table) of the datagrid
	 */
	protected function getTagName()
	{
		return 'table';
	}

	/**
	 * @return string Name of the class used in AutoGenerateColumns mode
	 */
	protected function getAutoGenerateColumnName()
	{
		return 'TBoundColumn';
	}

	/**
	 * Adds objects parsed in template to datagrid.
	 * Datagrid columns are added into {@link getColumns Columns} collection.
	 * @param mixed $object object parsed in template
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TDataGridColumn) {
			$this->getColumns()->add($object);
		} else {
			parent::addParsedObject($object);
		}  // this is needed by EmptyTemplate
	}

	/**
	 * @return TDataGridColumnCollection manually specified datagrid columns
	 */
	public function getColumns()
	{
		if (!$this->_columns) {
			$this->_columns = new TDataGridColumnCollection($this);
		}
		return $this->_columns;
	}

	/**
	 * @return TDataGridColumnCollection automatically generated datagrid columns
	 */
	public function getAutoColumns()
	{
		if (!$this->_autoColumns) {
			$this->_autoColumns = new TDataGridColumnCollection($this);
		}
		return $this->_autoColumns;
	}

	/**
	 * @return TDataGridItemCollection datagrid item collection
	 */
	public function getItems()
	{
		if (!$this->_items) {
			$this->_items = new TDataGridItemCollection;
		}
		return $this->_items;
	}

	/**
	 * @return int number of items
	 */
	public function getItemCount()
	{
		return $this->_items ? $this->_items->getCount() : 0;
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableStyle} to be used by datagrid.
	 * @return TTableStyle control style to be used
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
	 * @param string $value the URL of the background image for the datagrid
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
		if (($style = $this->getViewState('ItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('ItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for each alternating item
	 */
	public function getAlternatingItemStyle()
	{
		if (($style = $this->getViewState('AlternatingItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('AlternatingItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for selected item
	 */
	public function getSelectedItemStyle()
	{
		if (($style = $this->getViewState('SelectedItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('SelectedItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for edit item
	 */
	public function getEditItemStyle()
	{
		if (($style = $this->getViewState('EditItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('EditItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for header
	 */
	public function getHeaderStyle()
	{
		if (($style = $this->getViewState('HeaderStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('HeaderStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for footer
	 */
	public function getFooterStyle()
	{
		if (($style = $this->getViewState('FooterStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('FooterStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TDataGridPagerStyle the style for pager
	 */
	public function getPagerStyle()
	{
		if (($style = $this->getViewState('PagerStyle', null)) === null) {
			$style = new TDataGridPagerStyle;
			$this->setViewState('PagerStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TStyle the style for thead element, if any
	 * @since 3.1.1
	 */
	public function getTableHeadStyle()
	{
		if (($style = $this->getViewState('TableHeadStyle', null)) === null) {
			$style = new TStyle;
			$this->setViewState('TableHeadStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TStyle the style for tbody element, if any
	 * @since 3.1.1
	 */
	public function getTableBodyStyle()
	{
		if (($style = $this->getViewState('TableBodyStyle', null)) === null) {
			$style = new TStyle;
			$this->setViewState('TableBodyStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return TStyle the style for tfoot element, if any
	 * @since 3.1.1
	 */
	public function getTableFootStyle()
	{
		if (($style = $this->getViewState('TableFootStyle', null)) === null) {
			$style = new TStyle;
			$this->setViewState('TableFootStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return string caption for the datagrid
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption', '');
	}

	/**
	 * @param string $value caption for the datagrid
	 */
	public function setCaption($value)
	{
		$this->setViewState('Caption', $value, '');
	}

	/**
	 * @return TTableCaptionAlign datagrid caption alignment. Defaults to TTableCaptionAlign::NotSet.
	 */
	public function getCaptionAlign()
	{
		return $this->getViewState('CaptionAlign', TTableCaptionAlign::NotSet);
	}

	/**
	 * @param TTableCaptionAlign $value datagrid caption alignment. Valid values include
	 */
	public function setCaptionAlign($value)
	{
		$this->setViewState('CaptionAlign', TPropertyValue::ensureEnum($value, 'TPrado\\Web\\UI\\WebControls\\TableCaptionAlign'), TTableCaptionAlign::NotSet);
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
	 * @return TDataGridPager the pager displayed at the top of datagrid. It could be null if paging is disabled.
	 */
	public function getTopPager()
	{
		return $this->_topPager;
	}

	/**
	 * @return TDataGridPager the pager displayed at the bottom of datagrid. It could be null if paging is disabled.
	 */
	public function getBottomPager()
	{
		return $this->_bottomPager;
	}

	/**
	 * @return TDataGridItem the selected item, null if no item is selected.
	 */
	public function getSelectedItem()
	{
		$index = $this->getSelectedItemIndex();
		$items = $this->getItems();
		if ($index >= 0 && $index < $items->getCount()) {
			return $items->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * @return int the zero-based index of the selected item in {@link getItems Items}.
	 * A value -1 means no item selected.
	 */
	public function getSelectedItemIndex()
	{
		return $this->getViewState('SelectedItemIndex', -1);
	}

	/**
	 * Selects an item by its index in {@link getItems Items}.
	 * Previously selected item will be un-selected.
	 * If the item to be selected is already in edit mode, it will remain in edit mode.
	 * If the index is less than 0, any existing selection will be cleared up.
	 * @param int $value the selected item index
	 */
	public function setSelectedItemIndex($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		if (($current = $this->getSelectedItemIndex()) !== $value) {
			$this->setViewState('SelectedItemIndex', $value, -1);
			$items = $this->getItems();
			$itemCount = $items->getCount();
			if ($current >= 0 && $current < $itemCount) {
				$item = $items->itemAt($current);
				if ($item->getItemType() !== TListItemType::EditItem) {
					$item->setItemType($current % 2 ? TListItemType::AlternatingItem : TListItemType::Item);
				}
			}
			if ($value >= 0 && $value < $itemCount) {
				$item = $items->itemAt($value);
				if ($item->getItemType() !== TListItemType::EditItem) {
					$item->setItemType(TListItemType::SelectedItem);
				}
			}
		}
	}

	/**
	 * @return TDataGridItem the edit item
	 */
	public function getEditItem()
	{
		$index = $this->getEditItemIndex();
		$items = $this->getItems();
		if ($index >= 0 && $index < $items->getCount()) {
			return $items->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * @return int the zero-based index of the edit item in {@link getItems Items}.
	 * A value -1 means no item is in edit mode.
	 */
	public function getEditItemIndex()
	{
		return $this->getViewState('EditItemIndex', -1);
	}

	/**
	 * Edits an item by its index in {@link getItems Items}.
	 * Previously editting item will change to normal item state.
	 * If the index is less than 0, any existing edit item will be cleared up.
	 * @param int $value the edit item index
	 */
	public function setEditItemIndex($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		if (($current = $this->getEditItemIndex()) !== $value) {
			$this->setViewState('EditItemIndex', $value, -1);
			$items = $this->getItems();
			$itemCount = $items->getCount();
			if ($current >= 0 && $current < $itemCount) {
				$items->itemAt($current)->setItemType($current % 2 ? TListItemType::AlternatingItem : TListItemType::Item);
			}
			if ($value >= 0 && $value < $itemCount) {
				$items->itemAt($value)->setItemType(TListItemType::EditItem);
			}
		}
	}

	/**
	 * @return bool whether sorting is enabled. Defaults to false.
	 */
	public function getAllowSorting()
	{
		return $this->getViewState('AllowSorting', false);
	}

	/**
	 * @param bool $value whether sorting is enabled
	 */
	public function setAllowSorting($value)
	{
		$this->setViewState('AllowSorting', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether datagrid columns should be automatically generated. Defaults to true.
	 */
	public function getAutoGenerateColumns()
	{
		return $this->getViewState('AutoGenerateColumns', true);
	}

	/**
	 * @param bool $value whether datagrid columns should be automatically generated
	 */
	public function setAutoGenerateColumns($value)
	{
		$this->setViewState('AutoGenerateColumns', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the header should be displayed. Defaults to true.
	 */
	public function getShowHeader()
	{
		return $this->getViewState('ShowHeader', true);
	}

	/**
	 * @param bool $value whether the header should be displayed
	 */
	public function setShowHeader($value)
	{
		$this->setViewState('ShowHeader', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the footer should be displayed. Defaults to false.
	 */
	public function getShowFooter()
	{
		return $this->getViewState('ShowFooter', false);
	}

	/**
	 * @param bool $value whether the footer should be displayed
	 */
	public function setShowFooter($value)
	{
		$this->setViewState('ShowFooter', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the template applied when no data is bound to the datagrid
	 */
	public function getEmptyTemplate()
	{
		return $this->_emptyTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the template applied when no data is bound to the datagrid
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setEmptyTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_emptyTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datagrid_template_required', 'EmptyTemplate');
		}
	}

	/**
	 * This method overrides parent's implementation to handle
	 * {@link onItemCommand OnItemCommand} event which is bubbled from
	 * {@link TDataGridItem} child controls.
	 * If the event parameter is {@link TDataGridCommandEventParameter} and
	 * the command name is a recognized one, which includes 'select', 'edit',
	 * 'delete', 'update', and 'cancel' (case-insensitive), then a
	 * corresponding command event is also raised (such as {@link onEditCommand OnEditCommand}).
	 * This method should only be used by control developers.
	 * @param \Prado\Web\UI\TControl $sender the sender of the event
	 * @param \Prado\TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof TDataGridCommandEventParameter) {
			$this->onItemCommand($param);
			$command = $param->getCommandName();
			if (strcasecmp($command, self::CMD_SELECT) === 0) {
				$this->setSelectedItemIndex($param->getItem()->getItemIndex());
				$this->onSelectedIndexChanged($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_EDIT) === 0) {
				$this->onEditCommand($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_DELETE) === 0) {
				$this->onDeleteCommand($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_UPDATE) === 0) {
				$this->onUpdateCommand($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_CANCEL) === 0) {
				$this->onCancelCommand($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_SORT) === 0) {
				$this->onSortCommand(new TDataGridSortCommandEventParameter($sender, $param));
				return true;
			} elseif (strcasecmp($command, self::CMD_PAGE) === 0) {
				$p = $param->getCommandParameter();
				if (strcasecmp($p, self::CMD_PAGE_NEXT) === 0) {
					$pageIndex = $this->getCurrentPageIndex() + 1;
				} elseif (strcasecmp($p, self::CMD_PAGE_PREV) === 0) {
					$pageIndex = $this->getCurrentPageIndex() - 1;
				} elseif (strcasecmp($p, self::CMD_PAGE_FIRST) === 0) {
					$pageIndex = 0;
				} elseif (strcasecmp($p, self::CMD_PAGE_LAST) === 0) {
					$pageIndex = $this->getPageCount() - 1;
				} else {
					$pageIndex = TPropertyValue::ensureInteger($p) - 1;
				}
				$this->onPageIndexChanged(new TDataGridPageChangedEventParameter($sender, $pageIndex));
				return true;
			}
		}
		return false;
	}

	/**
	 * Raises <b>OnCancelCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>cancel</b> command name.
	 * @param TDataGridCommandEventParameter $param event parameter
	 */
	public function onCancelCommand($param)
	{
		$this->raiseEvent('OnCancelCommand', $this, $param);
	}

	/**
	 * Raises <b>OnDeleteCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>delete</b> command name.
	 * @param TDataGridCommandEventParameter $param event parameter
	 */
	public function onDeleteCommand($param)
	{
		$this->raiseEvent('OnDeleteCommand', $this, $param);
	}

	/**
	 * Raises <b>OnEditCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>edit</b> command name.
	 * @param TDataGridCommandEventParameter $param event parameter
	 */
	public function onEditCommand($param)
	{
		$this->raiseEvent('OnEditCommand', $this, $param);
	}

	/**
	 * Raises <b>OnItemCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event.
	 * @param TDataGridCommandEventParameter $param event parameter
	 */
	public function onItemCommand($param)
	{
		$this->raiseEvent('OnItemCommand', $this, $param);
	}

	/**
	 * Raises <b>OnSortCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>sort</b> command name.
	 * @param TDataGridSortCommandEventParameter $param event parameter
	 */
	public function onSortCommand($param)
	{
		$this->raiseEvent('OnSortCommand', $this, $param);
	}

	/**
	 * Raises <b>OnUpdateCommand</b> event.
	 * This method is invoked when a button control raises <b>OnCommand</b> event
	 * with <b>update</b> command name.
	 * @param TDataGridCommandEventParameter $param event parameter
	 */
	public function onUpdateCommand($param)
	{
		$this->raiseEvent('OnUpdateCommand', $this, $param);
	}

	/**
	 * Raises <b>OnItemCreated</b> event.
	 * This method is invoked right after a datagrid item is created and before
	 * added to page hierarchy.
	 * @param TDataGridItemEventParameter $param event parameter
	 */
	public function onItemCreated($param)
	{
		$this->raiseEvent('OnItemCreated', $this, $param);
	}

	/**
	 * Raises <b>OnPagerCreated</b> event.
	 * This method is invoked right after a datagrid pager is created and before
	 * added to page hierarchy.
	 * @param TDataGridPagerEventParameter $param event parameter
	 */
	public function onPagerCreated($param)
	{
		$this->raiseEvent('OnPagerCreated', $this, $param);
	}

	/**
	 * Raises <b>OnItemDataBound</b> event.
	 * This method is invoked for each datagrid item after it performs
	 * databinding.
	 * @param TDataGridItemEventParameter $param event parameter
	 */
	public function onItemDataBound($param)
	{
		$this->raiseEvent('OnItemDataBound', $this, $param);
	}

	/**
	 * Raises <b>OnPageIndexChanged</b> event.
	 * This method is invoked when current page is changed.
	 * @param TDataGridPageChangedEventParameter $param event parameter
	 */
	public function onPageIndexChanged($param)
	{
		$this->raiseEvent('OnPageIndexChanged', $this, $param);
	}

	/**
	 * Saves item count in viewstate.
	 * This method is invoked right before control state is to be saved.
	 */
	public function saveState()
	{
		parent::saveState();
		if (!$this->getEnableViewState(true)) {
			return;
		}
		if ($this->_items) {
			$this->setViewState('ItemCount', $this->_items->getCount(), 0);
		} else {
			$this->clearViewState('ItemCount');
		}
		if ($this->_autoColumns) {
			$state = [];
			foreach ($this->_autoColumns as $column) {
				$state[] = $column->saveState();
			}
			$this->setViewState('AutoColumns', $state, []);
		} else {
			$this->clearViewState('AutoColumns');
		}
		if ($this->_columns) {
			$state = [];
			foreach ($this->_columns as $column) {
				$state[] = $column->saveState();
			}
			$this->setViewState('Columns', $state, []);
		} else {
			$this->clearViewState('Columns');
		}
	}

	/**
	 * Loads item count information from viewstate.
	 * This method is invoked right after control state is loaded.
	 */
	public function loadState()
	{
		parent::loadState();
		if (!$this->getEnableViewState(true)) {
			return;
		}
		if (!$this->getIsDataBound()) {
			$state = $this->getViewState('AutoColumns', []);
			if (!empty($state)) {
				$this->_autoColumns = new TDataGridColumnCollection($this);
				foreach ($state as $st) {
					$columnClassName = $this->getAutoGenerateColumnName();
					$column = new $columnClassName;
					$column->loadState($st);
					$this->_autoColumns->add($column);
				}
			} else {
				$this->_autoColumns = null;
			}
			$state = $this->getViewState('Columns', []);
			if ($this->_columns && $this->_columns->getCount() === count($state)) {
				$i = 0;
				foreach ($this->_columns as $column) {
					$column->loadState($state[$i]);
					$i++;
				}
			}
			$this->restoreGridFromViewState();
		}
	}

	/**
	 * Clears up all items in the datagrid.
	 */
	public function reset()
	{
		$this->getControls()->clear();
		$this->getItems()->clear();
		$this->_header = null;
		$this->_footer = null;
		$this->_topPager = null;
		$this->_bottomPager = null;
		$this->_useEmptyTemplate = false;
	}

	/**
	 * Restores datagrid content from viewstate.
	 */
	protected function restoreGridFromViewState()
	{
		$this->reset();

		$allowPaging = $this->getAllowPaging();

		$itemCount = $this->getViewState('ItemCount', 0);
		$dsIndex = $this->getViewState('DataSourceIndex', 0);

		$columns = new TList($this->getColumns());
		$columns->mergeWith($this->_autoColumns);
		$this->_allColumns = $columns;

		$items = $this->getItems();

		if ($columns->getCount()) {
			foreach ($columns as $column) {
				$column->initialize();
			}
			$selectedIndex = $this->getSelectedItemIndex();
			$editIndex = $this->getEditItemIndex();
			for ($index = 0; $index < $itemCount; ++$index) {
				if ($index === 0) {
					if ($allowPaging) {
						$this->_topPager = $this->createPager();
					}
					$this->_header = $this->createItemInternal(-1, -1, TListItemType::Header, false, null, $columns);
				}
				if ($index === $editIndex) {
					$itemType = TListItemType::EditItem;
				} elseif ($index === $selectedIndex) {
					$itemType = TListItemType::SelectedItem;
				} elseif ($index % 2) {
					$itemType = TListItemType::AlternatingItem;
				} else {
					$itemType = TListItemType::Item;
				}
				$items->add($this->createItemInternal($index, $dsIndex, $itemType, false, null, $columns));
				$dsIndex++;
			}
			if ($index > 0) {
				$this->_footer = $this->createItemInternal(-1, -1, TListItemType::Footer, false, null, $columns);
				if ($allowPaging) {
					$this->_bottomPager = $this->createPager();
				}
			}
		}
		if (!$dsIndex && $this->_emptyTemplate !== null) {
			$this->_useEmptyTemplate = true;
			$this->_emptyTemplate->instantiateIn($this);
		}
	}

	/**
	 * Performs databinding to populate datagrid items from data source.
	 * This method is invoked by {@link dataBind()}.
	 * You may override this function to provide your own way of data population.
	 * @param \Traversable $data the bound data
	 */
	protected function performDataBinding($data)
	{
		$this->reset();
		$keys = $this->getDataKeys();
		$keys->clear();
		$keyField = $this->getDataKeyField();

		// get all columns
		if ($this->getAutoGenerateColumns()) {
			$columns = new TList($this->getColumns());
			$autoColumns = $this->createAutoColumns($data);
			$columns->mergeWith($autoColumns);
		} else {
			$columns = $this->getColumns();
		}
		$this->_allColumns = $columns;

		$items = $this->getItems();

		$index = 0;
		$allowPaging = $this->getAllowPaging() && ($data instanceof TPagedDataSource);
		$dsIndex = $allowPaging ? $data->getFirstIndexInPage() : 0;
		$this->setViewState('DataSourceIndex', $dsIndex, 0);
		if ($columns->getCount()) {
			foreach ($columns as $column) {
				$column->initialize();
			}

			$selectedIndex = $this->getSelectedItemIndex();
			$editIndex = $this->getEditItemIndex();
			foreach ($data as $key => $row) {
				if ($keyField !== '') {
					$keys->add($this->getDataFieldValue($row, $keyField));
				} else {
					$keys->add($key);
				}
				if ($index === 0) {
					if ($allowPaging) {
						$this->_topPager = $this->createPager();
					}
					$this->_header = $this->createItemInternal(-1, -1, TListItemType::Header, true, null, $columns);
				}
				if ($index === $editIndex) {
					$itemType = TListItemType::EditItem;
				} elseif ($index === $selectedIndex) {
					$itemType = TListItemType::SelectedItem;
				} elseif ($index % 2) {
					$itemType = TListItemType::AlternatingItem;
				} else {
					$itemType = TListItemType::Item;
				}
				$items->add($this->createItemInternal($index, $dsIndex, $itemType, true, $row, $columns));
				$index++;
				$dsIndex++;
			}
			if ($index > 0) {
				$this->_footer = $this->createItemInternal(-1, -1, TListItemType::Footer, true, null, $columns);
				if ($allowPaging) {
					$this->_bottomPager = $this->createPager();
				}
			}
		}
		$this->setViewState('ItemCount', $index, 0);
		if (!$dsIndex && $this->_emptyTemplate !== null) {
			$this->_useEmptyTemplate = true;
			$this->_emptyTemplate->instantiateIn($this);
			$this->dataBindChildren();
		}
	}

	/**
	 * Merges consecutive cells who have the same text.
	 * @since 3.1.1
	 */
	private function groupCells()
	{
		if (($columns = $this->_allColumns) === null) {
			return;
		}
		$items = $this->getItems();
		foreach ($columns as $id => $column) {
			if (!$column->getEnableCellGrouping()) {
				continue;
			}
			$prevCell = null;
			$prevCellText = null;
			foreach ($items as $item) {
				$itemType = $item->getItemType();
				$cell = $item->getCells()->itemAt($id);
				if (!$cell->getVisible()) {
					continue;
				}
				if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem) {
					if (($cellText = $this->getCellText($cell)) === '') {
						$prevCell = null;
						$prevCellText = null;
						continue;
					}
					if ($prevCell === null || $prevCellText !== $cellText) {
						$prevCell = $cell;
						$prevCellText = $cellText;
					} else {
						if (($rowSpan = $prevCell->getRowSpan()) === 0) {
							$rowSpan = 1;
						}
						$prevCell->setRowSpan($rowSpan + 1);
						$cell->setVisible(false);
					}
				}
			}
		}
	}

	private function getCellText($cell)
	{
		if (($data = $cell->getText()) === '' && $cell->getHasControls()) {
			$controls = $cell->getControls();
			foreach ($controls as $control) {
				if ($control instanceof \Prado\IDataRenderer) {
					return $control->getData();
				}
			}
		}
		return $data;
	}

	/**
	 * Creates a datagrid item instance based on the item type and index.
	 * @param int $itemIndex zero-based item index
	 * @param mixed $dataSourceIndex
	 * @param TListItemType $itemType item type
	 * @return TDataGridItem created data list item
	 */
	protected function createItem($itemIndex, $dataSourceIndex, $itemType)
	{
		return new TDataGridItem($itemIndex, $dataSourceIndex, $itemType);
	}

	private function createItemInternal($itemIndex, $dataSourceIndex, $itemType, $dataBind, $dataItem, $columns)
	{
		$item = $this->createItem($itemIndex, $dataSourceIndex, $itemType);
		$this->initializeItem($item, $columns);
		$param = new TDataGridItemEventParameter($item);
		if ($dataBind) {
			$item->setData($dataItem);
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			$item->dataBind();
			$this->onItemDataBound($param);
		} else {
			$this->onItemCreated($param);
			$this->getControls()->add($item);
		}
		return $item;
	}

	/**
	 * Initializes a datagrid item and cells inside it
	 * @param TDataGrid $item datagrid item to be initialized
	 * @param TDataGridColumnCollection $columns datagrid columns to be used to initialize the cells in the item
	 */
	protected function initializeItem($item, $columns)
	{
		$cells = $item->getCells();
		$itemType = $item->getItemType();
		$index = 0;
		foreach ($columns as $column) {
			if ($itemType === TListItemType::Header) {
				$cell = new TTableHeaderCell;
			} else {
				$cell = new TTableCell;
			}
			if (($id = $column->getID()) !== '') {
				$item->registerObject($id, $cell);
			}
			$cells->add($cell);
			$column->initializeCell($cell, $index, $itemType);
			$index++;
		}
	}

	protected function createPager()
	{
		$pager = new TDataGridPager($this);
		$this->buildPager($pager);
		$this->onPagerCreated(new TDataGridPagerEventParameter($pager));
		$this->getControls()->add($pager);
		return $pager;
	}

	/**
	 * Builds the pager content based on pager style.
	 * @param TDataGridPager $pager the container for the pager
	 */
	protected function buildPager($pager)
	{
		switch ($this->getPagerStyle()->getMode()) {
			case TDataGridPagerMode::NextPrev:
				$this->buildNextPrevPager($pager);
				break;
			case TDataGridPagerMode::Numeric:
				$this->buildNumericPager($pager);
				break;
		}
	}

	/**
	 * Creates a pager button.
	 * Depending on the button type, a TLinkButton or a TButton may be created.
	 * If it is enabled (clickable), its command name and parameter will also be set.
	 * Derived classes may override this method to create additional types of buttons, such as TImageButton.
	 * @param mixed $pager the container pager instance of TActiveDatagridPager
	 * @param string $buttonType button type, either LinkButton or PushButton
	 * @param bool $enabled whether the button should be enabled
	 * @param string $text caption of the button
	 * @param string $commandName CommandName corresponding to the OnCommand event of the button
	 * @param string $commandParameter CommandParameter corresponding to the OnCommand event of the button
	 * @return mixed the button instance
	 */
	protected function createPagerButton($pager, $buttonType, $enabled, $text, $commandName, $commandParameter)
	{
		if ($buttonType === TDataGridPagerButtonType::LinkButton) {
			if ($enabled) {
				$button = new TLinkButton;
			} else {
				$button = new TLabel;
				$button->setText($text);
				return $button;
			}
		} else {
			$button = new TButton;
			if (!$enabled) {
				$button->setEnabled(false);
			}
		}
		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCommandParameter($commandParameter);
		$button->setCausesValidation(false);
		return $button;
	}

	/**
	 * Builds a next-prev pager
	 * @param TDataGridPager $pager the container for the pager
	 */
	protected function buildNextPrevPager($pager)
	{
		$style = $this->getPagerStyle();
		$buttonType = $style->getButtonType();
		$controls = $pager->getControls();
		$currentPageIndex = $this->getCurrentPageIndex();
		if ($currentPageIndex === 0) {
			if (($text = $style->getFirstPageText()) !== '') {
				$label = $this->createPagerButton($pager, $buttonType, false, $text, '', '');
				$controls->add($label);
				$controls->add("\n");
			}

			$label = $this->createPagerButton($pager, $buttonType, false, $style->getPrevPageText(), '', '');
			$controls->add($label);
		} else {
			if (($text = $style->getFirstPageText()) !== '') {
				$button = $this->createPagerButton($pager, $buttonType, true, $text, self::CMD_PAGE, self::CMD_PAGE_FIRST);
				$controls->add($button);
				$controls->add("\n");
			}

			$button = $this->createPagerButton($pager, $buttonType, true, $style->getPrevPageText(), self::CMD_PAGE, self::CMD_PAGE_PREV);
			$controls->add($button);
		}
		$controls->add("\n");
		if ($currentPageIndex === $this->getPageCount() - 1) {
			$label = $this->createPagerButton($pager, $buttonType, false, $style->getNextPageText(), '', '');
			$controls->add($label);
			if (($text = $style->getLastPageText()) !== '') {
				$controls->add("\n");
				$label = $this->createPagerButton($pager, $buttonType, false, $text, '', '');
				$controls->add($label);
			}
		} else {
			$button = $this->createPagerButton($pager, $buttonType, true, $style->getNextPageText(), self::CMD_PAGE, self::CMD_PAGE_NEXT);
			$controls->add($button);
			if (($text = $style->getLastPageText()) !== '') {
				$controls->add("\n");
				$button = $this->createPagerButton($pager, $buttonType, true, $text, self::CMD_PAGE, self::CMD_PAGE_LAST);
				$controls->add($button);
			}
		}
	}

	/**
	 * Builds a numeric pager
	 * @param TDataGridPager $pager the container for the pager
	 */
	protected function buildNumericPager($pager)
	{
		$style = $this->getPagerStyle();
		$buttonType = $style->getButtonType();
		$controls = $pager->getControls();
		$pageCount = $this->getPageCount();
		$pageIndex = $this->getCurrentPageIndex() + 1;
		$maxButtonCount = $style->getPageButtonCount();
		$buttonCount = $maxButtonCount > $pageCount ? $pageCount : $maxButtonCount;
		$startPageIndex = 1;
		$endPageIndex = $buttonCount;
		if ($pageIndex > $endPageIndex) {
			$startPageIndex = ((int) (($pageIndex - 1) / $maxButtonCount)) * $maxButtonCount + 1;
			if (($endPageIndex = $startPageIndex + $maxButtonCount - 1) > $pageCount) {
				$endPageIndex = $pageCount;
			}
			if ($endPageIndex - $startPageIndex + 1 < $maxButtonCount) {
				if (($startPageIndex = $endPageIndex - $maxButtonCount + 1) < 1) {
					$startPageIndex = 1;
				}
			}
		}

		if ($startPageIndex > 1) {
			if (($text = $style->getFirstPageText()) !== '') {
				$button = $this->createPagerButton($pager, $buttonType, true, $text, self::CMD_PAGE, self::CMD_PAGE_FIRST);
				$controls->add($button);
				$controls->add("\n");
			}
			$prevPageIndex = $startPageIndex - 1;
			$button = $this->createPagerButton($pager, $buttonType, true, $style->getPrevPageText(), self::CMD_PAGE, "$prevPageIndex");
			$controls->add($button);
			$controls->add("\n");
		}

		for ($i = $startPageIndex; $i <= $endPageIndex; ++$i) {
			if ($i === $pageIndex) {
				$label = $this->createPagerButton($pager, $buttonType, false, "$i", '', '');
				$controls->add($label);
			} else {
				$button = $this->createPagerButton($pager, $buttonType, true, "$i", self::CMD_PAGE, "$i");
				$controls->add($button);
			}
			if ($i < $endPageIndex) {
				$controls->add("\n");
			}
		}

		if ($pageCount > $endPageIndex) {
			$controls->add("\n");
			$nextPageIndex = $endPageIndex + 1;
			$button = $this->createPagerButton($pager, $buttonType, true, $style->getNextPageText(), self::CMD_PAGE, "$nextPageIndex");
			$controls->add($button);
			if (($text = $style->getLastPageText()) !== '') {
				$controls->add("\n");
				$button = $this->createPagerButton($pager, $buttonType, true, $text, self::CMD_PAGE, self::CMD_PAGE_LAST);
				$controls->add($button);
			}
		}
	}

	/**
	 * Automatically generates datagrid columns based on datasource schema
	 * @param \Traversable $dataSource data source bound to the datagrid
	 * @return TDataGridColumnCollection
	 */
	protected function createAutoColumns($dataSource)
	{
		if (!$dataSource) {
			return null;
		}
		$autoColumns = $this->getAutoColumns();
		$autoColumns->clear();
		$columnClassName = $this->getAutoGenerateColumnName();
		foreach ($dataSource as $row) {
			foreach ($row as $key => $value) {
				$column = new $columnClassName;
				if (is_string($key)) {
					$column->setHeaderText($key);
					$column->setDataField($key);
					$column->setSortExpression($key);
					$autoColumns->add($column);
				} else {
					$column->setHeaderText(TListItemType::Item);
					$column->setDataField($key);
					$column->setSortExpression(TListItemType::Item);
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
		$itemStyle = $this->getViewState('ItemStyle', null);

		$alternatingItemStyle = $this->getViewState('AlternatingItemStyle', null);
		if ($itemStyle !== null) {
			if ($alternatingItemStyle === null) {
				$alternatingItemStyle = $itemStyle;
			} else {
				$alternatingItemStyle->mergeWith($itemStyle);
			}
		}

		$selectedItemStyle = $this->getViewState('SelectedItemStyle', null);

		$editItemStyle = $this->getViewState('EditItemStyle', null);
		if ($selectedItemStyle !== null) {
			if ($editItemStyle === null) {
				$editItemStyle = $selectedItemStyle;
			} else {
				$editItemStyle->mergeWith($selectedItemStyle);
			}
		}

		$headerStyle = $this->getViewState('HeaderStyle', null);
		$footerStyle = $this->getViewState('FooterStyle', null);
		$pagerStyle = $this->getViewState('PagerStyle', null);
		$separatorStyle = $this->getViewState('SeparatorStyle', null);

		foreach ($this->getControls() as $index => $item) {
			if (!($item instanceof TDataGridItem) && !($item instanceof TDataGridPager)) {
				continue;
			}
			$itemType = $item->getItemType();
			switch ($itemType) {
				case TListItemType::Header:
					if ($headerStyle) {
						$item->getStyle()->mergeWith($headerStyle);
					}
					if (!$this->getShowHeader()) {
						$item->setVisible(false);
					}
					break;
				case TListItemType::Footer:
					if ($footerStyle) {
						$item->getStyle()->mergeWith($footerStyle);
					}
					if (!$this->getShowFooter()) {
						$item->setVisible(false);
					}
					break;
				case TListItemType::Separator:
					if ($separatorStyle) {
						$item->getStyle()->mergeWith($separatorStyle);
					}
					break;
				case TListItemType::Item:
					if ($itemStyle) {
						$item->getStyle()->mergeWith($itemStyle);
					}
					break;
				case TListItemType::AlternatingItem:
					if ($alternatingItemStyle) {
						$item->getStyle()->mergeWith($alternatingItemStyle);
					}
					break;
				case TListItemType::SelectedItem:
					if ($selectedItemStyle) {
						$item->getStyle()->mergeWith($selectedItemStyle);
					}
					if ($index % 2 == 1) {
						if ($itemStyle) {
							$item->getStyle()->mergeWith($itemStyle);
						}
					} else {
						if ($alternatingItemStyle) {
							$item->getStyle()->mergeWith($alternatingItemStyle);
						}
					}
					break;
				case TListItemType::EditItem:
					if ($editItemStyle) {
						$item->getStyle()->mergeWith($editItemStyle);
					}
					if ($index % 2 == 1) {
						if ($itemStyle) {
							$item->getStyle()->mergeWith($itemStyle);
						}
					} else {
						if ($alternatingItemStyle) {
							$item->getStyle()->mergeWith($alternatingItemStyle);
						}
					}
					break;
				case TListItemType::Pager:
					if ($pagerStyle) {
						$item->getStyle()->mergeWith($pagerStyle);
						if ($index === 0) {
							if ($pagerStyle->getPosition() === TDataGridPagerPosition::Bottom || !$pagerStyle->getVisible()) {
								$item->setVisible(false);
							}
						} else {
							if ($pagerStyle->getPosition() === TDataGridPagerPosition::Top || !$pagerStyle->getVisible()) {
								$item->setVisible(false);
							}
						}
					}
					break;
				default:
					break;
			}
			if ($this->_columns && $itemType !== TListItemType::Pager) {
				$n = $this->_columns->getCount();
				$cells = $item->getCells();
				for ($i = 0; $i < $n; ++$i) {
					$cell = $cells->itemAt($i);
					$column = $this->_columns->itemAt($i);
					if (!$column->getVisible()) {
						$cell->setVisible(false);
					} else {
						if ($itemType === TListItemType::Header) {
							$style = $column->getHeaderStyle(false);
						} elseif ($itemType === TListItemType::Footer) {
							$style = $column->getFooterStyle(false);
						} else {
							$style = $column->getItemStyle(false);
						}
						if ($style !== null) {
							$cell->getStyle()->mergeWith($style);
						}
					}
				}
			}
		}
	}

	/**
	 * Renders the openning tag for the datagrid control which will render table caption if present.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		parent::renderBeginTag($writer);
		if (($caption = $this->getCaption()) !== '') {
			if (($align = $this->getCaptionAlign()) !== TTableCaptionAlign::NotSet) {
				$writer->addStyleAttribute('caption-side', strtolower($align));
			}
			$writer->renderBeginTag('caption');
			$writer->write($caption);
			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the datagrid.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getHasControls()) {
			$this->groupCells();
			if ($this->_useEmptyTemplate) {
				$control = new TWebControl;
				$control->setID($this->getClientID());
				$control->copyBaseAttributes($this);
				if ($this->getHasStyle()) {
					$control->getStyle()->copyFrom($this->getStyle());
				}
				$control->renderBeginTag($writer);
				$this->renderContents($writer);
				$control->renderEndTag($writer);
			} elseif ($this->getViewState('ItemCount', 0) > 0) {
				$this->applyItemStyles();
				if ($this->_topPager) {
					$this->_topPager->renderControl($writer);
					$writer->writeLine();
				}
				$this->renderTable($writer);
				if ($this->_bottomPager) {
					$writer->writeLine();
					$this->_bottomPager->renderControl($writer);
				}
			}
		}
	}

	/**
	 * Renders the tabular data.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer
	 */
	protected function renderTable($writer)
	{
		$this->renderBeginTag($writer);
		if ($this->_header && $this->_header->getVisible()) {
			$writer->writeLine();
			if ($style = $this->getViewState('TableHeadStyle', null)) {
				$style->addAttributesToRender($writer);
			}
			$writer->renderBeginTag('thead');
			$this->_header->render($writer);
			$writer->renderEndTag();
		}
		$writer->writeLine();
		if ($style = $this->getViewState('TableBodyStyle', null)) {
			$style->addAttributesToRender($writer);
		}
		$writer->renderBeginTag('tbody');
		foreach ($this->getItems() as $item) {
			$item->renderControl($writer);
		}
		$writer->renderEndTag();

		if ($this->_footer && $this->_footer->getVisible()) {
			$writer->writeLine();
			if ($style = $this->getViewState('TableFootStyle', null)) {
				$style->addAttributesToRender($writer);
			}
			$writer->renderBeginTag('tfoot');
			$this->_footer->render($writer);
			$writer->renderEndTag();
		}

		$writer->writeLine();
		$this->renderEndTag($writer);
	}
}
