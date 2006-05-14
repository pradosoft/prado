<?php
/*
 * Created on 13/05/2006
 */
class TBaseActiveControl extends TComponent
{
	private $_options;
	private $_control;

	public function __construct($control)
	{
		$this->_control = $control;
		$this->_options = new TMap;
	}
	
	protected function setOption($name,$value,$default=null)
	{
		$value = is_null($value) ? $default : $value;
		if(!is_null($value))
			$this->_options->add($name,$value);
	}
	
	protected function getOption($name,$default=null)
	{
		$item = $this->_options->itemAt($name);
		return is_null($item) ? $default : $item;
	}
		
	protected function getPage()
	{
		return $this->_control->getPage();
	}
	
	protected function getControl()
	{
		return $this->_control;
	}
		
	/**
	 * @param boolean true to allow fine grain callback updates.
	 */
	public function setEnableUpdate($value)
	{
		$this->setOption('EnableUpdate', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return true to allow fine grain callback updates.
	 */
	public function getEnableUpdate()
	{
		return $this->getOption('EnableUpdate', true);
	}
	
	public function canUpdateClientSide()
	{
		return 	$this->getControl()->getIsInitialized() 
				&& $this->getPage()->getIsCallback() 
				&& $this->getEnableUpdate();
	}
}


class TBaseActiveCallbackControl extends TBaseActiveControl
{ 
	/**
	 * Callback client-side options can be set by setting the properties of
	 * the ClientSide property. E.g. <com:TCallback ClientSide.OnSuccess="..." />
	 * See {@link TCallbackClientSideOptions} for details on the properties of
	 * ClientSide.
	 * @return TCallbackClientSideOptions client-side callback options.
	 */
	public function getClientSide()
	{
		if(is_null($client = $this->getOption('ClientSide')))
		{
			$client = $this->createClientSide();
			$this->setOption('ClientSide', $client);
		}
		return $client;
	}
	
	/**
	 * @return TCallbackClientSideOptions callback client-side options.
	 */
	protected function createClientSide()
	{
		if(($id=$this->getCallbackOptions())!=='' 
			&& ($control=$this->getControl()->findControl($id))!==null)
		{
			if($control instanceof TCallbackOptions)
				return $control->getClientSide();
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
		$this->setOption('CallbackOptions', $value, '');		
	}
	
	/**
	 * @return string ID of a TCallbackOptions control from which ClientSide
	 * options are cloned.
	 */
	public function getCallbackOptions()
	{
		return $this->getOption('CallbackOptions', '');
	}

	/**
	 * @return boolean whether callback event trigger by this button will cause
	 * input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getOption('CausesValidation',true);
	}

	/**
	 * @param boolean whether callback event trigger by this button will cause
	 * input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setOption('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}	
	
	/**
	 * @return string the group of validators which the button causes validation
	 * upon callback
	 */
	public function getValidationGroup()
	{
		return $this->getOption('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the button causes validation
	 * upon callback
	 */
	public function setValidationGroup($value)
	{
		$this->getOption('ValidationGroup',$value,'');
	}

	/**
	 * @return boolean whether to perform validation if the callback is
	 * requested.
	 */
	public function canCauseValidation()
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
	 * @return array list of callback javascript options.
	 */
	protected function getClientSideOptions()
	{
		$options = $this->getClientSide()->getOptions()->toArray();
		$validate = $this->getCausesValidation();
		$options['CausesValidation']= $validate ? '' : false;
		$options['ValidationGroup']=$this->getValidationGroup();
		return $options;
	}
	
	public function registerCallbackClientScript($options=null)
	{
		$cs = $this->getPage()->getClientScript(); 
		if(is_array($options))
			$options = array_merge($this->getClientSideOptions(),$options);
		else
			$options = $this->getClientSideOptions();			
		$cs->registerCallbackControl(get_class($this->getControl()), $options);
	}

	/**
	 * Returns the javascript statement to invoke a callback request for this
	 * control. Additional options for callback can be set via subproperties of
	 * {@link getClientSide ClientSide} property. E.g. ClientSide.
	 * OnSuccess="..."
	 * @return string javascript statement to invoke a callback.
	 */
	public function getJavascript()
	{
		$client = $this->getPage()->getClientScript(); 
		return $client->getCallbackReference($this->getControl(),$this->getClientSideOptions());
	}
}

?>