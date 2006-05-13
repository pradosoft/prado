<?php
/**
 * TCallback class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 */

/**
 * TCallback component class.
 *
 * The TCallback provides a basic callback handler that can be invoke from the
 * client side by running the javascript code obtained from the
 * {@link getCallbackReference CallbackReference} property. The event {@link
 * onCallback OnCallback} is raise when a callback is requested by the TCallback
 * component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TCallback extends TControl implements ICallbackEventHandler
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
	protected function getCallbackClientSideOptions()
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