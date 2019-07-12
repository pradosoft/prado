<?php
/**
 * TDataBoundControl class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Collections\TPagedDataSource;
use Prado\Data\TDbDataReader;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Collections\TList;
use Prado\Collections\TMap;

/**
 * TDataBoundControl class.
 *
 * TDataBoundControl is the based class for controls that need to populate
 * data from data sources. It provides basic properties and methods that allow
 * the derived controls to associate with data sources and retrieve data from them.
 *
 * TBC....
 *
 * TDataBoundControl is equipped with paging capabilities. By setting
 * {@link setAllowPaging AllowPaging} to true, the input data will be paged
 * and only one page of data is actually populated into the data-bound control.
 * This saves a lot of memory when dealing with larget datasets.
 *
 * To specify the number of data items displayed on each page, set
 * the {@link setPageSize PageSize} property, and to specify which
 * page of data to be displayed, set {@link setCurrentPageIndex CurrentPageIndex}.
 *
 * When the size of the original data is too big to be loaded all in the memory,
 * one can enable custom paging. In custom paging, the total number of data items
 * is specified manually via {@link setVirtualItemCount VirtualItemCount},
 * and the data source only needs to contain the current page of data. To enable
 * custom paging, set {@link setAllowCustomPaging AllowCustomPaging} to true.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
abstract class TDataBoundControl extends \Prado\Web\UI\WebControls\TWebControl
{
	private $_initialized = false;
	private $_dataSource;
	private $_requiresBindToNull = false;
	private $_requiresDataBinding = false;
	private $_prerendered = false;
	private $_currentView;
	private $_currentDataSource;
	private $_currentViewValid = false;
	private $_currentDataSourceValid = false;
	private $_currentViewIsFromDataSourceID = false;
	private $_parameters;
	private $_isDataBound = false;

	/**
	 * @return Traversable data source object, defaults to null.
	 */
	public function getDataSource()
	{
		return $this->_dataSource;
	}

	/**
	 * Sets the data source object associated with the databound control.
	 * The data source must implement Traversable interface.
	 * If an array is given, it will be converted to xxx.
	 * If a string is given, it will be converted to xxx.
	 * @param array|string|Traversable $value data source object
	 */
	public function setDataSource($value)
	{
		$this->_dataSource = $this->validateDataSource($value);
		$this->onDataSourceChanged();
	}

	/**
	 * @return string ID path to the data source control. Defaults to empty.
	 */
	public function getDataSourceID()
	{
		return $this->getViewState('DataSourceID', '');
	}

	/**
	 * @param string $value ID path to the data source control. The data source
	 * control must be locatable via {@link TControl::findControl} call.
	 */
	public function setDataSourceID($value)
	{
		$dsid = $this->getViewState('DataSourceID', '');
		if ($dsid !== '' && $value === '') {
			$this->_requiresBindToNull = true;
		}
		$this->setViewState('DataSourceID', $value, '');
		$this->onDataSourceChanged();
	}

	/**
	 * @return bool if the databound control uses the data source specified
	 * by {@link setDataSourceID}, or it uses the data source object specified
	 * by {@link setDataSource}.
	 */
	protected function getUsingDataSourceID()
	{
		return $this->getDataSourceID() !== '';
	}

	/**
	 * Sets {@link setRequiresDataBinding RequiresDataBinding} as true if the control is initialized.
	 * This method is invoked when either {@link setDataSource} or {@link setDataSourceID} is changed.
	 */
	public function onDataSourceChanged()
	{
		$this->_currentViewValid = false;
		$this->_currentDataSourceValid = false;
		if ($this->getInitialized()) {
			$this->setRequiresDataBinding(true);
		}
	}

	/**
	 * @return bool whether the databound control has been initialized.
	 * By default, the control is initialized after its viewstate has been restored.
	 */
	protected function getInitialized()
	{
		return $this->_initialized;
	}

	/**
	 * Sets a value indicating whether the databound control is initialized.
	 * If initialized, any modification to {@link setDataSource DataSource} or
	 * {@link setDataSourceID DataSourceID} will set {@link setRequiresDataBinding RequiresDataBinding}
	 * as true.
	 * @param bool $value a value indicating whether the databound control is initialized.
	 */
	protected function setInitialized($value)
	{
		$this->_initialized = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether databind has been invoked in the previous page request
	 */
	protected function getIsDataBound()
	{
		return $this->_isDataBound;
	}

	/**
	 * @param bool $value if databind has been invoked in this page request
	 */
	protected function setIsDataBound($value)
	{
		$this->_isDataBound = $value;
	}

	/**
	 * @return bool whether a databind call is required (by the data bound control)
	 */
	protected function getRequiresDataBinding()
	{
		return $this->_requiresDataBinding;
	}

	/**
	 * @return bool whether paging is enabled. Defaults to false.
	 */
	public function getAllowPaging()
	{
		return $this->getViewState('AllowPaging', false);
	}

	/**
	 * @param bool $value whether paging is enabled
	 */
	public function setAllowPaging($value)
	{
		$this->setViewState('AllowPaging', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether the custom paging is enabled. Defaults to false.
	 */
	public function getAllowCustomPaging()
	{
		return $this->getViewState('AllowCustomPaging', false);
	}

	/**
	 * Sets a value indicating whether the custom paging should be enabled.
	 * When the pager is in custom paging mode, the {@link setVirtualItemCount VirtualItemCount}
	 * property is used to determine the paging, and the data items in the
	 * {@link setDataSource DataSource} are considered to be in the current page.
	 * @param bool $value whether the custom paging is enabled
	 */
	public function setAllowCustomPaging($value)
	{
		$this->setViewState('AllowCustomPaging', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return int the zero-based index of the current page. Defaults to 0.
	 */
	public function getCurrentPageIndex()
	{
		return $this->getViewState('CurrentPageIndex', 0);
	}

	/**
	 * @param int $value the zero-based index of the current page
	 * @throws TInvalidDataValueException if the value is less than 0
	 */
	public function setCurrentPageIndex($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = 0;
		}
		$this->setViewState('CurrentPageIndex', $value, 0);
	}

	/**
	 * @return int the number of data items on each page. Defaults to 10.
	 */
	public function getPageSize()
	{
		return $this->getViewState('PageSize', 10);
	}

	/**
	 * @param int $value the number of data items on each page.
	 * @throws TInvalidDataValueException if the value is less than 1
	 */
	public function setPageSize($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 1) {
			throw new TInvalidDataValueException('databoundcontrol_pagesize_invalid', get_class($this));
		}
		$this->setViewState('PageSize', TPropertyValue::ensureInteger($value), 10);
	}

	/**
	 * @return int number of pages of data items available
	 */
	public function getPageCount()
	{
		return $this->getViewState('PageCount', 1);
	}

	/**
	 * @return int virtual number of data items in the data source. Defaults to 0.
	 * @see setAllowCustomPaging
	 */
	public function getVirtualItemCount()
	{
		return $this->getViewState('VirtualItemCount', 0);
	}

	/**
	 * @param int $value virtual number of data items in the data source.
	 * @throws TInvalidDataValueException if the value is less than 0
	 * @see setAllowCustomPaging
	 */
	public function setVirtualItemCount($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			throw new TInvalidDataValueException('databoundcontrol_virtualitemcount_invalid', get_class($this));
		}
		$this->setViewState('VirtualItemCount', $value, 0);
	}

	/**
	 * Sets a value indicating whether a databind call is required by the data bound control.
	 * If true and the control has been prerendered while it uses the data source
	 * specified by {@link setDataSourceID}, a databind call will be called by this method.
	 * @param bool $value whether a databind call is required.
	 */
	protected function setRequiresDataBinding($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		if ($value && $this->_prerendered) {
			$this->_requiresDataBinding = true;
			$this->ensureDataBound();
		} else {
			$this->_requiresDataBinding = $value;
		}
	}

	/**
	 * Ensures any pending {@link dataBind} is called.
	 * This method calls {@link dataBind} if the data source is specified
	 * by {@link setDataSourceID} or if {@link getRequiresDataBinding RequiresDataBinding}
	 * is true.
	 */
	protected function ensureDataBound()
	{
		if ($this->_requiresDataBinding && ($this->getUsingDataSourceID() || $this->_requiresBindToNull)) {
			$this->dataBind();
			$this->_requiresBindToNull = false;
		}
	}

	/**
	 * @return TPagedDataSource creates a paged data source
	 */
	protected function createPagedDataSource()
	{
		$ds = new TPagedDataSource;
		$ds->setCurrentPageIndex($this->getCurrentPageIndex());
		$ds->setPageSize($this->getPageSize());
		$ds->setAllowPaging($this->getAllowPaging());
		$ds->setAllowCustomPaging($this->getAllowCustomPaging());
		$ds->setVirtualItemCount($this->getVirtualItemCount());
		return $ds;
	}

	/**
	 * Performs databinding.
	 * This method overrides the parent implementation by calling
	 * {@link performSelect} which fetches data from data source and does
	 * the actual binding work.
	 */
	public function dataBind()
	{
		$this->setRequiresDataBinding(false);
		$this->dataBindProperties();
		$this->onDataBinding(null);

		if (($view = $this->getDataSourceView()) !== null) {
			$data = $view->select($this->getSelectParameters());
		} else {
			$data = null;
		}

		if ($data instanceof \Traversable) {
			if ($this->getAllowPaging()) {
				$ds = $this->createPagedDataSource();
				$ds->setDataSource($data);
				$this->setViewState('PageCount', $ds->getPageCount());
				if ($ds->getCurrentPageIndex() >= $ds->getPageCount()) {
					$ds->setCurrentPageIndex($ds->getPageCount() - 1);
					$this->setCurrentPageIndex($ds->getCurrentPageIndex());
				}
				$this->performDataBinding($ds);
			} else {
				$this->clearViewState('PageCount');
				$this->performDataBinding($data);
			}
		}
		$this->setIsDataBound(true);
		$this->onDataBound(null);
	}

	public function dataSourceViewChanged($sender, $param)
	{
		if (!$this->_ignoreDataSourceViewChanged) {
			$this->setRequiresDataBinding(true);
		}
	}

	protected function getDataSourceView()
	{
		if (!$this->_currentViewValid) {
			if ($this->_currentView && $this->_currentViewIsFromDataSourceID) {
				$this->_currentView->detachEventHandler('DataSourceViewChanged', [$this, 'dataSourceViewChanged']);
			}
			if (($dataSource = $this->determineDataSource()) !== null) {
				if (($view = $dataSource->getView($this->getDataMember())) === null) {
					throw new TInvalidDataValueException('databoundcontrol_datamember_invalid', $this->getDataMember());
				}
				if ($this->_currentViewIsFromDataSourceID = $this->getUsingDataSourceID()) {
					$view->attachEventHandler('OnDataSourceViewChanged', [$this, 'dataSourceViewChanged']);
				}
				$this->_currentView = $view;
			} else {
				$this->_currentView = null;
			}
			$this->_currentViewValid = true;
		}
		return $this->_currentView;
	}

	protected function determineDataSource()
	{
		if (!$this->_currentDataSourceValid) {
			if (($dsid = $this->getDataSourceID()) !== '') {
				if (($dataSource = $this->getNamingContainer()->findControl($dsid)) === null) {
					throw new TInvalidDataValueException('databoundcontrol_datasourceid_inexistent', $dsid);
				} elseif (!($dataSource instanceof IDataSource)) {
					throw new TInvalidDataValueException('databoundcontrol_datasourceid_invalid', $dsid);
				} else {
					$this->_currentDataSource = $dataSource;
				}
			} elseif (($dataSource = $this->getDataSource()) !== null) {
				$this->_currentDataSource = new TReadOnlyDataSource($dataSource, $this->getDataMember());
			} else {
				$this->_currentDataSource = null;
			}
			$this->_currentDataSourceValid = true;
		}
		return $this->_currentDataSource;
	}

	abstract protected function performDataBinding($data);

	/**
	 * Raises <b>OnDataBound</b> event.
	 * This method should be invoked after a databind is performed.
	 * It is mainly used by framework and component developers.
	 * @param mixed $param
	 */
	public function onDataBound($param)
	{
		$this->raiseEvent('OnDataBound', $this, $param);
	}

	/**
	 * Sets page's <b>OnPreLoad</b> event handler as {@link pagePreLoad}.
	 * If viewstate is disabled and the current request is a postback,
	 * {@link setRequiresDataBinding RequiresDataBinding} will be set true.
	 * This method overrides the parent implementation.
	 * @param TEventParameter $param event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$page = $this->getPage();
		$page->attachEventHandler('OnPreLoad', [$this, 'pagePreLoad']);
	}

	/**
	 * Sets {@link getInitialized} as true.
	 * This method is invoked when page raises <b>PreLoad</b> event.
	 * @param mixed $sender event sender
	 * @param TEventParameter $param event parameter
	 */
	public function pagePreLoad($sender, $param)
	{
		$this->_initialized = true;
		$isPostBack = $this->getPage()->getIsPostBack();
		if (!$isPostBack || ($isPostBack && (!$this->getEnableViewState(true) || !$this->getIsDataBound()))) {
			$this->setRequiresDataBinding(true);
		}
	}

	/**
	 * Ensures any pending databind is performed.
	 * This method overrides the parent implementation.
	 * @param TEventParameter $param event parameter
	 */
	public function onPreRender($param)
	{
		$this->_prerendered = true;
		$this->ensureDataBound();
		parent::onPreRender($param);
	}

	/**
	 * Validates if the parameter is a valid data source.
	 * If it is a string or an array, it will be converted as a TList object.
	 * @param array|string|Traversable $value data source to be validated
	 * @throws TInvalidDataTypeException if the data is neither null nor Traversable
	 * @return Traversable the data that is traversable
	 */
	protected function validateDataSource($value)
	{
		if (is_string($value)) {
			$list = new TList;
			foreach (TPropertyValue::ensureArray($value) as $key => $value) {
				if (is_array($value)) {
					$list->add($value);
				} else {
					$list->add([$value, is_string($key) ? $key : $value]);
				}
			}
			return $list;
		} elseif (is_array($value)) {
			return new TMap($value);
		} elseif ($value instanceof TDbDataReader) {
			// read array from TDbDataReader since it's forward-only stream and can only be traversed once
			return $value->readAll();
		} elseif (($value instanceof \Traversable) || $value === null) {
			return $value;
		} else {
			throw new TInvalidDataTypeException('databoundcontrol_datasource_invalid', get_class($this));
		}
	}

	public function getDataMember()
	{
		return $this->getViewState('DataMember', '');
	}

	public function setDataMember($value)
	{
		$this->setViewState('DataMember', $value, '');
	}

	public function getSelectParameters()
	{
		if (!$this->_parameters) {
			$this->_parameters = new TDataSourceSelectParameters;
		}
		return $this->_parameters;
	}
}
