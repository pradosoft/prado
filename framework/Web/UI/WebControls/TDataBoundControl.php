<?php

abstract class TDataBoundControl extends TWebControl
{
	private $_initialized=false;
	private $_dataSource=null;
	private $_requiresBindToNull=false;
	private $_requiresDataBinding=false;
	private $_throwOnDataPropertyChange=false;
	private $_prerendered=false;

	/**
	 * @return Traversable data source object, defaults to null.
	 */
	public function getDataSource()
	{
		return $this->_dataSource;
	}

	/**
	 * @param Traversable|array|string data source object
	 */
	public function setDataSource($value)
	{
		if($value!==null)
			$this->validateDataSource($value);
		$this->_dataSource=$value;
		$this->onDataPropertyChanged();
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
		$this->onDataPropertyChanged();
	}

	/**
	 * This method is invoked when either {@link setDataSource} or {@link setDataSourceID} is changed.
	 */
	protected function onDataPropertyChanged()
	{
		if($this->_throwOnDataPropertyChanged)
			throw new TInvalidOperationException('databoundcontrol_dataproperty_unchangeable');
		if($this->getInitialized())
			$this->setRequiresDataBinding(true);
	}

	/**
	 * @return boolean whether the databound control has been initialized.
	 */
	protected function getInitialized()
	{
		return $this->_initialized;
	}

	/**
	 * @param boolean a value indicating whether the databound control is initialized.
	 */
	protected function setInitialized($value)
	{
		$this->_initialized=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean if the databound control uses the data source control specified
	 * by {@link setDataSourceID}, or it uses the data source object specified
	 * by {@link setDataSource}.
	 */
	protected function getUsingDataSourceID()
	{
		return $this->getDataSourceID()!=='';
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
		if($value && $this->_prerendered && $this->getUsingDataSourceID())
		{
			$this->_requiresDataBinding=true;
			$this->ensureDataBound();
		}
		else
			$this->_requiresDataBinding=$value;
	}

	/**
	 * Performs databinding.
	 * This method overrides the parent implementation by calling
	 * {@link performSelect} which fetches data from data source and does
	 * the actual binding work.
	 * @param boolean whether to raise DataBind event. This parameter is ignored.
	 */
	public function dataBind($raiseDataBindingEvent=true)
	{
		$this->performSelect();
	}

	/**
	 * Ensures any pending {@link dataBind} is called.
	 * This method calls {@link dataBind} if the data source is specified
	 * by {@link setDataSourceID} or if {@link getRequiresDataBinding RequiresDataBinding}
	 * is true.
	 */
	protected function ensureDataBound()
	{
		try
		{
			$this->_throwOnDataPropertyChange=true;
			if($this->_requiresDataBinding && ($this->getUsingDataSourceID() || $this->_requiresBindToNull))
			{
				$this->dataBind();
				$this->_requiresBindToNull=false;
			}
		}
		catch(Exception $e)
		{
			$this->_throwOnDataPropertyChange=false;
			throw $e;
		}
	}

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
		if(!$this->getEnableViewState(true) && $page->getIsPostBack())
			$this->setRequiresDataBinding(true);
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
	 * @return boolean if the parameter is a valid data source
	 */
	protected function validateDataSource($value)
	{
		if(!is_array($value) && !($value instanceof Traversable))
			throw new TInvalidDataTypeException('databoundcontrol_datasource_invalid');
	}

	/**
	 * @return ???
	 */
	protected function performSelect()
	{
		if(!$this->getUsingDataSourceID())
			$this->onDataBinding(null);
		$view=$this->getDataSourceView();
		$this->setRequiresDataBinding(false);
		$this->setDataBound(true);
		$data=$view->select($this->getSelectParameters());
		if($this->getUsingDataSourceID())
			$this->onDataBinding(null);
		$this->performDataBinding($data);
		$this->onDataBound(null);
	}

	protected function getDataSourceView()
	{
		$source=$this->getDataSourceByID();
		return $source->getView($this->getDataMember());
	}

	protected function performDataBinding($data)
	{
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
			$this->_parameters=$this->createSelectParameters();
		return $this->_parameters;
	}

	protected function createSelectParameters()
	{
		return new TDataSourceSelectParameters;
	}
}

?>