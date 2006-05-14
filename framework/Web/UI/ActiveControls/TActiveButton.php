<?php
/*
 * Created on 5/05/2006
 */

class TActiveButton extends TButton implements ICallbackEventHandler
{
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
	 * @return TBaseActiveCallbackControl base callback options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getActiveControl();
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
		if($this->getActiveControl()->canUpdateClientSide())
			$this->getPage()->getCallbackClient()->setAttribute($this, 'value', $value);			
	}
	
	/**
	 * Renders the callback control javascript statement.
	 */
	protected function renderClientControlScript($writer)
	{
	}
	
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id',$this->getClientID());
		$this->getActiveControl()->registerCallbackClientScript($this->getPostBackOptions());		
	}
} 

?>