<?php

abstract class TDataBoundControl extends TWebControl
{
	private $_initialized=false;
	private $_dataSource=null;
	private $_requiresBindToNull=false;
	private $_requiresDataBinding=false;
	private $_prerendered=false;
	private $_currentView=null;
	private $_currentDataSource=null;
	private $_currentViewValid=false;
	private $_currentDataSourceValid=false;

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
	 * @param Traversable|array|string data source object
	 */
	public function setDataSource($value)
	{
		$this->_dataSource=$this->validateDataSource($value);;
		$this->onDataSourceChanged();
	}

	/**
	 * @return string ID path to the data source control. Defaults to empty.
	 */
	public function getDataSourceID()
	{
		return $this->getViewState('DataSourceID','');
	}

	/**
	 * @param string ID path to the data source control. The data source
	 * control must be locatable via {@link TControl::findControl} call.
	 */
	public function setDataSourceID($value)
	{
		$dsid=$this->getViewState('DataSourceID','');
		if($dsid!=='' && $value==='')
			$this->_requiresBindToNull=true;
		$this->setViewState('DataSourceID',$value,'');
		$this->onDataSourceChanged();
	}

	/**
	 * @return boolean if the databound control uses the data source specified
	 * by {@link setDataSourceID}, or it uses the data source object specified
	 * by {@link setDataSource}.
	 */
	protected function getUsingDataSourceID()
	{
		return $this->getDataSourceID()!=='';
	}

	/**
	 * Sets {@link setRequiresDataBinding RequiresDataBinding} as true if the control is initialized.
	 * This method is invoked when either {@link setDataSource} or {@link setDataSourceID} is changed.
	 */
	protected function onDataSourceChanged()
	{
		$this->_currentViewValid=false;
		$this->_currentDataSourceValid=false;
		if($this->getInitialized())
			$this->setRequiresDataBinding(true);
	}

	/**
	 * @return boolean whether the databound control has been initialized.
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
	 * @param boolean a value indicating whether the databound control is initialized.
	 */
	protected function setInitialized($value)
	{
		$this->_initialized=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean if databind has been invoked in the previous page request
	 */
	protected function getIsDataBound()
	{
		return $this->getViewState('IsDataBound',false);
	}

	/**
	 * @param boolean if databind has been invoked in this page request
	 */
	protected function setIsDataBound($value)
	{
		$this->setViewState('IsDataBound',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether a databind call is required (by the data bound control)
	 */
	protected function getRequiresDataBinding()
	{
		return $this->_requiresDataBinding;
	}

	/**
	 * Sets a value indicating whether a databind call is required by the data bound control.
	 * If true and the control has been prerendered while it uses the data source
	 * specified by {@link setDataSourceID}, a databind call will be called by this method.
	 * @param boolean whether a databind call is required.
	 */
	protected function setRequiresDataBinding($value)
	{
		$value=TPropertyValue::ensureBoolean($value);
		if($value && $this->_prerendered)
		{
			$this->_requiresDataBinding=true;
			$this->ensureDataBound();
		}
		else
			$this->_requiresDataBinding=$value;
	}

	/**
	 * Ensures any pending {@link dataBind} is called.
	 * This method calls {@link dataBind} if the data source is specified
	 * by {@link setDataSourceID} or if {@link getRequiresDataBinding RequiresDataBinding}
	 * is true.
	 */
	protected function ensureDataBound()
	{
		if($this->_requiresDataBinding && ($this->getUsingDataSourceID() || $this->_requiresBindToNull))
		{
			$this->dataBind();
			$this->_requiresBindToNull=false;
		}
	}

	/**
	 * Performs databinding.
	 * This method overrides the parent implementation by calling
	 * {@link performSelect} which fetches data from data source and does
	 * the actual binding work.
	 */
	public function dataBind()
	{
		// TODO: databinding should only be raised after data is ready
		// what about property bindings? should they be after data is ready?
		$this->setRequiresDataBinding(false);
		$this->dataBindProperties();
		$view=$this->getDataSourceView();
		$data=$view->select($this->getSelectParameters());
		$this->onDataBinding(null);
		$this->performDataBinding($data);
		$this->setIsDataBound(true);
		$this->onDataBound(null);
	}

	public function dataSourceViewChanged($sender,$param)
	{
		if(!$this->_ignoreDataSourceViewChanged)
			$this->setRequiresDataBinding(true);
	}

	protected function getDataSourceView()
	{
		if(!$this->_currentViewValid)
		{
			if($this->_currentView && $this->_currentViewIsFromDataSourceID)
				$handlers=$this->_currentView->detachEventHandler('DataSourceViewChanged',array($this,'dataSourceViewChanged'));
			$dataSource=$this->determineDataSource();
			if(($view=$dataSource->getView($this->getDataMember()))===null)
				throw new TInvalidDataValueException('databoundcontrol_datamember_invalid',$this->getDataMember());
			if($this->_currentViewIsFromDataSourceID=$this->getUsingDataSourceID())
				$view->attachEventHandler('DataSourceViewChanged',array($this,'dataSourceViewChanged'));
			$this->_currentView=$view;
			$this->_currentViewValid=true;
		}
		return $this->_currentView;
	}

	protected function determineDataSource()
	{
		if(!$this->_currentDataSourceValid)
		{
			$dsid=$this->getDataSourceID();
			if($dsid!=='')
			{
				if(($dataSource=$this->getNamingContainer()->findControl($dsid))===null)
					throw new TInvalidDataValueException('databoundcontrol_datasourceid_inexistent',$dsid);
				else if(!($dataSource instanceof IDataSource))
					throw new TInvalidDataValueException('databoundcontrol_datasourceid_invalid',$dsid);
				else
					$this->_currentDataSource=$dataSource;
			}
			else
			{
				$this->_currentDataSource=new TReadOnlyDataSource($this->getDataSource(),$this->getDataMember());
			}
			$this->_currentDataSourceValid=true;
		}
		return $this->_currentDataSource;
	}

	abstract protected function performDataBinding($data);

	/**
	 * Raises <b>DataBound</b> event.
	 * This method should be invoked after a databind is performed.
	 * It is mainly used by framework and component developers.
	 */
	public function onDataBound($param)
	{
		$this->raiseEvent('DataBound',$this,$param);
	}

	/**
	 * Sets page's <b>PreLoad</b> event handler as {@link onPagePreLoad}.
	 * If viewstate is disabled and the current request is a postback,
	 * {@link setRequiresDataBinding RequiresDataBinding} will be set true.
	 * This method overrides the parent implementation.
	 * @param TEventParameter event parameter
	 */
	protected function onInit($param)
	{
		parent::onInit($param);
		$page=$this->getPage();
		$page->attachEventHandler('PreLoad',array($this,'onPagePreLoad'));
	}

	/**
	 * Sets {@link getInitialized} as true.
	 * This method is invoked when page raises <b>PreLoad</b> event.
	 * @param mixed event sender
	 * @param TEventParameter event parameter
	 */
	protected function onPagePreLoad($sender,$param)
	{
		$this->_initialized=true;
		$isPostBack=$this->getPage()->getIsPostBack();
		if(!$isPostBack || ($isPostBack && (!$this->getEnableViewState(true) || !$this->getIsDataBound())))
			$this->setRequiresDataBinding(true);
	}

	/**
	 * Ensures any pending databind is performed.
	 * This method overrides the parent implementation.
	 * @param TEventParameter event parameter
	 */
	protected function onPreRender($param)
	{
		$this->_prerendered=true;
		$this->ensureDataBound();
		parent::onPreRender($param);
	}

	/**
	 * Validates if the parameter is a valid data source.
	 * If it is a string or an array, it will be converted as a TList object.
	 * @return Traversable the data that is traversable
	 * @throws TInvalidDataTypeException if the data is neither null nor Traversable
	 */
	protected function validateDataSource($value)
	{
		if(is_array($value))
			$value=new TList($value);
		else if($value!==null && !($value instanceof Traversable))
			throw new TInvalidDataTypeException('databoundcontrol_datasource_invalid');
		return $value;
	}

	public function getDataMember()
	{
		return $this->getViewState('DataMember','');
	}

	public function setDataMember($value)
	{
		$this->setViewState('DataMember',$value,'');
	}

	public function getSelectParameters()
	{
		if(!$this->_parameters)
			$this->_parameters=new TDataSourceSelectParameters;
		return $this->_parameters;
	}
}

?>