<?php
/*
 * Created on 5/05/2006
 */

class TActiveButton extends TButton implements ICallbackEventHandler
{
	/**
	 * @var TCallbackClientSideOptions client-side options.
	 */
	private $_clientSide;

	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}
	
	/**
	 * @param boolean true to allow fine grain callback updates.
	 */
	public function setAllowCallbackUpdate($value)
	{
		$this->setViewState('CallbackUpdate', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return true to allow fine grain callback updates.
	 */
	public function getAllowCallbackUpdate()
	{
		return $this->getViewState('CallbackUpdate', true);
	}

	/**
	 * @return true if can update changes on the client-side during callback.
	 */
	protected function canUpdateClientSide()
	{
		return $this->getIsInitialized() && $this->getAllowCallbackUpdate();
	}

	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. If {@link getCausesValidation
	 * CausesValidation} is true, it will invoke the page's {@link TPage::
	 * validate validate} method first. It will raise {@link onCallback
	 * OnCallback} event and then the {@link onClick OnClick} event. This method
	 * is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */	
 	public function raiseCallbackEvent($param)
	{
		$this->raisePostBackEvent($param);
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
	 * @param string caption of the button
	 */
	public function setText($value)
	{
		parent::setText($value);
		if($this->canUpdateClientSide())
			$this->getPage()->getCallbackClient()->setAttribute($this, 'value', $value);			
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
		if(($id=$this->getCallbackOptions())!=='' && ($control=$this->findControl($id))!==null)
		{
			if($control instanceof TCallbackOptions)
				return clone($control->getClientSide());
		}
		return new TCallbackClientSideOptions;		
	}
	
	/**
	 * Sets the ID of a TCallbackOptions component to duplicate the client-side
	 * options for this control. The {@link getClientSide ClientSide}
	 * subproperties has precendent over the CallbackOptions property.
	 * @param string ID of a TCallbackOptions control from which ClientSide
	 * options are cloned.
	 */
	public function setCallbackOptions($value)
	{
		$this->setViewState('CallbackOptions', $value,'');		
	}
	
	/**
	 * @return string ID of a TCallbackOptions control from which ClientSide
	 * options are cloned.
	 */
	public function getCallbackOptions()
	{
		return $this->getViewState('CallbackOptions','');
	}
	
	/**
	 * Renders the callback control javascript statement.
	 */
	protected function renderClientControlScript($writer)
	{
		$writer->addAttribute('id',$this->getClientID());		
		$cs = $this->getPage()->getClientScript(); 
		$cs->registerCallbackControl(get_class($this),$this->getCallbackOptions());		
	}
	
	/**
	 * @return array list of callback options.
	 */
	protected function getCallbackClientSideOptions()
	{
		return array_merge($this->getPostBackOptions(), 
			$this->getClientSide()->getOptions()->toArray());
	}
			
	/**
	 * Returns the javascript statement to invoke a callback request for this
	 * control. Additional options for callback can be set via subproperties of
	 * {@link getClientSide ClientSide} property. E.g. ClientSide.OnSuccess="..."
	 * @param TControl callback handler control, use current object if null.
	 * @return string javascript statement to invoke a callback.
	 */
	public function getCallbackReference($control=null)
	{
		$client = $this->getPage()->getClientScript(); 
		$object = is_null($control) ? $this : $control;
		return $client->getCallbackReference($object, $this->getCallbackClientSideOptions());
	}
} 

?>