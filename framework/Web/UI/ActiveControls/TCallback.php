<?php

/*
 * Created on 25/04/2006
 */

class TCallback extends TWebControl implements ICallbackEventHandler
{	
	/**
	 * @var TCallbackClientSideOptions client-side options.
	 */
	private $_clientSide;
		
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, call this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}
	
	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'div';
	}	
	
	/**
	 * @return boolean whether callback event trigger by this button will cause
	 * input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * @param boolean whether callback event trigger by this button will cause
	 * input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}	
	
	/**
	 * @return string the group of validators which the button causes validation
	 * upon callback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the button causes validation
	 * upon callback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}
	
	/**
	 * Callback client-side options can be set by setting the properties of
	 * the ClientSide property. E.g. <com:TCallback ClientSide.OnSuccess="..." />
	 * See {@link TCallbackClientSideOptions} for details on the properties of
	 * ClientSide.
	 * @return TCallbackClientSideOptions client-side callback options.
	 */
	public function getClientSide()
	{
		if(is_null($this->_clientSide))
			$this->_clientSide = $this->createClientSideOptions();
		return $this->_clientSide;
	}
	
	/**
	 * @return TCallbackClientSideOptions callback client-side options.
	 */
	protected function createClientSideOptions()
	{
		return new TCallbackClientSideOptions;
	}
	
	/**
	 * @return boolean whether to perform validation if the callback is
	 * requested.
	 */
	protected function canCauseValidation()
	{
		if($this->getCausesValidation())
		{
			$group=$this->getValidationGroup();
			return $this->getPage()->getValidators($group)->getCount()>0;
		}
		else
			return false;
	}	

	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. If {@link getCausesValidation
	 * CausesValidation} is true, it will invoke the page's {@link TPage::
	 * validate validate} method first. It will raise {@link onCallback
	 * OnCallback} event. This method is mainly used by framework and control
	 * developers.
	 * @param TCallbackEventParameter the event parameter
	 */	
	public function raiseCallbackEvent($param)
	{
		if($this->getCausesValidation())
			$this->getPage()->validate($this->getValidationGroup());
		$this->onCallback($param);
	}
		
	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter event parameter to be passed to the event handlers
	 */		
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}
	
	/**
	 * @return array list of callback javascript options.
	 */
	protected function getCallbackOptions()
	{
		$validate = $this->getCausesValidation();
		$options['CausesValidation']= $validate ? '' : false;
		$options['ValidationGroup']=$this->getValidationGroup();
		return $options;
	}
		
	/**
	 * Returns the javascript statement to invoke a callback request for this
	 * control. Additional options for callback can be set via subproperties of
	 * {@link getClientSide ClientSide} property. E.g. ClientSide.OnSuccess="..."
	 * @return string javascript statement to invoke a callback.
	 */
	public function getCallbackReference()
	{
		$client = $this->getPage()->getClientScript(); 
		return $client->getCallbackReference($this, $this->getCallbackOptions());
	}
	
	public function render($writer)
	{
		parent::render($writer);
		if($this->getPage()->getIsCallback())
			$this->getPage()->getCallbackClient()->replace($this, $writer);
	}	
} 

?>
