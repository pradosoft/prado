<?php
/**
 * TScaffoldListView class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold
 */

namespace Prado\Data\ActiveRecord\Scaffold;

/**
 * Load the scaffold base class.
 */
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\IItemDataRenderer;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TPager;
use Prado\Web\UI\WebControls\TRepeater;
use Prado\Web\UI\WebControls\TRepeaterCommandEventParameter;

/**
 * TScaffoldListView displays a list of Active Records.
 *
 * The {@link getHeader Header} property is a TRepeater displaying the
 * Active Record property/field names. The {@link getSort Sort} property
 * is a drop down list displaying the combination of properties and its possible
 * ordering. The {@link getPager Pager} property is a TPager control displaying
 * the links and/or buttons that navigate to different pages in the Active Record data.
 * The {@link getList List} property is a TRepeater that renders a row of
 * Active Record data.
 *
 * Custom rendering of the each Active Record can be achieved by specifying
 * the ItemTemplate or AlternatingItemTemplate property of the main {@linnk getList List}
 * repeater.
 *
 * The TScaffoldListView will listen for two command events named "delete" and
 * "edit". A "delete" command will delete a the record for the row where the
 * "delete" command is originates. An "edit" command will push
 * the record data to be edited by a TScaffoldEditView with ID specified by the
 * {@link setEditViewID EditViewID}.
 *
 * Additional {@link setSearchCondition SearchCondition} and
 * {@link setSearchParameters SearchParameters} (takes array values) can be
 * specified to customize the records to be shown. The {@link setSearchCondition SearchCondition}
 * will be used as the Condition property of TActiveRecordCriteria, and similarly
 * the {@link setSearchParameters SearchParameters} will be the corresponding
 * Parameters property of TActiveRecordCriteria.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord\Scaffold
 * @since 3.1
 */
class TScaffoldListView extends TScaffoldBase
{
	private $_sort;
	private $_header;
	private $_list;

	/**
	 * Initialize the sort drop down list and the column names repeater.
	 */
	protected function initializeSort()
	{
		$table = $this->getTableInfo();
		$sorts = ['Sort By', str_repeat('-', 15)];
		$headers = [];
		foreach ($table->getColumns() as $name => $colum) {
			$fname = ucwords(str_replace('_', ' ', $name));
			$sorts[$name . ' ASC'] = $fname . ' Ascending';
			$sorts[$name . ' DESC'] = $fname . ' Descending';
			$headers[] = $fname;
		}
		$this->_sort->setDataSource($sorts);
		$this->_sort->dataBind();
		$this->_header->setDataSource($headers);
		$this->_header->dataBind();
	}

	/**
	 * Loads and display the data.
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if (!$this->getPage()->getIsPostBack() || $this->getViewState('CurrentClass') != $this->getRecordClass()) {
			$this->initializeSort();
			$this->setViewState('CurrentClass', $this->getRecordClass());
		}
		$this->loadRecordData();
	}

	/**
	 * Fetch the records and data bind it to the list.
	 */
	protected function loadRecordData()
	{
		$search = new TActiveRecordCriteria($this->getSearchCondition(), $this->getSearchParameters());
		$this->_list->setVirtualItemCount($this->getRecordFinder()->count($search));
		$finder = $this->getRecordFinder();
		$criteria = $this->getRecordCriteria();
		$this->_list->setDataSource($finder->findAll($criteria));
		$this->_list->dataBind();
	}

	/**
	 * @return TActiveRecordCriteria sort/search/paging criteria
	 */
	protected function getRecordCriteria()
	{
		$total = $this->_list->getVirtualItemCount();
		$limit = $this->_list->getPageSize();
		$offset = $this->_list->getCurrentPageIndex() * $limit;
		if ($offset + $limit > $total) {
			$limit = $total - $offset;
		}
		$criteria = new TActiveRecordCriteria($this->getSearchCondition(), $this->getSearchParameters());
		if ($limit > 0) {
			$criteria->setLimit($limit);
			if ($offset <= $total) {
				$criteria->setOffset($offset);
			}
		}
		$order = explode(' ', $this->_sort->getSelectedValue(), 2);
		if (is_array($order) && count($order) === 2) {
			$criteria->setOrdersBy($order);
		}
		return $criteria;
	}

	/**
	 * @param string $value search condition, the SQL string after the WHERE clause.
	 */
	public function setSearchCondition($value)
	{
		$this->setViewState('SearchCondition', $value);
	}

	/**
	 * @return string SQL search condition for list display.
	 */
	public function getSearchCondition()
	{
		return $this->getViewState('SearchCondition');
	}

	/**
	 * @param array $value search parameters
	 */
	public function setSearchParameters($value)
	{
		$this->setViewState('SearchParameters', TPropertyValue::ensureArray($value), []);
	}

	/**
	 * @return array search parameters
	 */
	public function getSearchParameters()
	{
		return $this->getViewState('SearchParameters', []);
	}

	/**
	 * Continue bubbling the "edit" command, "delete" command is handled in this class.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function bubbleEvent($sender, $param)
	{
		switch (strtolower($param->getCommandName())) {
			case 'delete':
				return $this->deleteRecord($sender, $param);
			case 'edit':
				$this->initializeEdit($sender, $param);
		}
		$this->raiseBubbleEvent($this, $param);
		return true;
	}

	/**
	 * Initialize the edit view control form when EditViewID is set.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function initializeEdit($sender, $param)
	{
		if (($ctrl = $this->getEditViewControl()) !== null) {
			if ($param instanceof TRepeaterCommandEventParameter) {
				$pk = $param->getItem()->getCustomData();
				$ctrl->setRecordPk($pk);
				$ctrl->initializeEditForm();
			}
		}
	}

	/**
	 * Deletes an Active Record.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function deleteRecord($sender, $param)
	{
		if ($param instanceof TRepeaterCommandEventParameter) {
			$pk = $param->getItem()->getCustomData();
			$this->getRecordFinder()->deleteByPk($pk);
		}
	}

	/**
	 * Initialize the default display for each Active Record item.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function listItemCreated($sender, $param)
	{
		$item = $param->getItem();
		if ($item instanceof IItemDataRenderer) {
			$type = $item->getItemType();
			if ($type == TListItemType::Item || $type == TListItemType::AlternatingItem) {
				$this->populateField($sender, $param);
			}
		}
	}

	/**
	 * Sets the Record primary key to the current repeater item's CustomData.
	 * Binds the inner repeater with properties of the current Active Record.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function populateField($sender, $param)
	{
		$item = $param->getItem();
		if (($data = $item->getData()) !== null) {
			$item->setCustomData($this->getRecordPkValues($data));
			if (($prop = $item->findControl('_properties')) !== null) {
				$item->_properties->setDataSource($this->getRecordPropertyValues($data));
				$item->_properties->dataBind();
			}
		}
	}

	/**
	 * Updates repeater page index with the pager new index value.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function pageChanged($sender, $param)
	{
		$this->_list->setCurrentPageIndex($param->getNewPageIndex());
	}

	/**
	 * @return TRepeater Repeater control for Active Record instances.
	 */
	public function getList()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_list');
	}

	/**
	 * @return TPager List pager control.
	 */
	public function getPager()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_pager');
	}

	/**
	 * @return TDropDownList Control that displays and controls the record ordering.
	 */
	public function getSort()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_sort');
	}

	/**
	 * @return TRepeater Repeater control for record property names.
	 */
	public function getHeader()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_header');
	}

	/**
	 * @return string TScaffoldEditView control ID for editing selected Active Record.
	 */
	public function getEditViewID()
	{
		return $this->getViewState('EditViewID');
	}

	/**
	 * @param string $value TScaffoldEditView control ID for editing selected Active Record.
	 */
	public function setEditViewID($value)
	{
		$this->setViewState('EditViewID', $value);
	}

	/**
	 * @return TScaffoldEditView control for editing selected Active Record, null if EditViewID is not set.
	 */
	protected function getEditViewControl()
	{
		if (($id = $this->getEditViewID()) !== null) {
			$ctrl = $this->getParent()->findControl($id);
			if ($ctrl === null) {
				throw new TConfigurationException('scaffold_unable_to_find_edit_view', $id);
			}
			return $ctrl;
		}
	}
}
