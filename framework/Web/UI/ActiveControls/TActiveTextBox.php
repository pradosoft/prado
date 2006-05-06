<?php
/*
 * Created on 6/05/2006
 */

class TActiveTextBox extends TTextBox implements ICallbackEventHandler
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
		return $this->getIsInitialized()
				&& $this->getPage()->getIsCallback() 
				&& $this->getAllowCallbackUpdate();
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
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. It class raisePostDataChangedEvent
	 * first then raises {@link onCallback OnCallback} event. This method is
	 * mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */		
	public function raiseCallbackEvent($param)
	{
		$this->raisePostDataChangedEvent();
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
	 * Client-side Text property can only be updated after the OnLoad stage.
	 * @param string text content for the textbox
	 */
	public function setText($value)
	{
		parent::setText($value);
		if($this->canUpdateClientSide() && $this->getHasLoadedPostData())
			$this->getPage()->getCallbackClient()->setValue($this, $value);			
	}	
	
	protected function renderClientControlScript($writer)
	{
		$writer->addAttribute('id',$this->getClientID());		
		$cs = $this->getPage()->getClientScript(); 
		$cs->registerCallbackControl(get_class($this),$this->getCallbackOptions());
	}
	
	/**
	 * @return array list of callback options.
	 */
	protected function getCallbackOptions()
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
		return $client->getCallbackReference($object, $this->getPostBackOptions());
	}	
}

?>