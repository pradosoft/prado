<?php
/**
 * TBaseActiveControl and TBaseActiveCallbackControl class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 */

Prado::using('System.Web.UI.ActiveControls.TCallbackClientSideOptions');

/**
 * TBaseActiveControl class provided additional basic property for every
 * active control. An instance of TBaseActiveControl or its decendent
 * TBaseActiveCallbackControl is created by {@link TActiveControlAdapter::getBaseActiveControl()}
 * method.
 * 
 * The {@link setEnableUpdate EnableUpdate} property determines wether the active
 * control is allowed to update the contents of the client-side when the callback
 * response returns. 
 * 
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TBaseActiveControl extends TComponent
{
	/**
	 * @TMap map of active control options.
	 */
	private $_options;
	/**
	 * @TControl attached control.
	 */
	private $_control;

	/**
	 * Constructor. Attach a base active control to an active control instance.
	 * @param TControl active control
	 */
	public function __construct($control)
	{
		$this->_control = $control;
		$this->_options = new TMap;
	}

	/** 
	 * Sets a named options with a value. Options are used to store and retrive
	 * named values for the base active controls.
	 * @param string option name.
	 * @param mixed new value.
	 * @param mixed default value.
	 * @return mixed options value.
	 */
	protected function setOption($name,$value,$default=null)
	{
		$value = is_null($value) ? $default : $value;
		if(!is_null($value))
			$this->_options->add($name,$value);
	}

	/** 
	 * Gets an option named value. Options are used to store and retrive
	 * named values for the base active controls.
	 * @param string option name.
	 * @param mixed default value.
	 * @return mixed options value.
	 */
	protected function getOption($name,$default=null)
	{
		$item = $this->_options->itemAt($name);
		return is_null($item) ? $default : $item;
	}

	/**
	 * @return TPage the page containing the attached control.
	 */
	protected function getPage()
	{
		return $this->_control->getPage();
	}

	/**
	 * @return TControl the attached control.
	 */
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
	 * @return boolean true to allow fine grain callback updates.
	 */
	public function getEnableUpdate()
	{
		return $this->getOption('EnableUpdate', true);
	}

	/** 
	 * Returns true if callback response is allowed to update the browser contents.
	 * Is is true if the control is initilized, and is a callback request and 
	 * the {@link setEnableUpdate EnabledUpdate} property is true.
	 * @return boolean true if the callback response is allowed update 
	 * client-side contents. 
	 */
	public function canUpdateClientSide()
	{
		return 	$this->getControl()->getIsInitialized()
				&& $this->getPage()->getIsCallback()
				&& $this->getEnableUpdate();
	}
}

/**
 * TBaseActiveCallbackControl is a common set of options and functionality for
 * active controls that can perform callback requests. 
 * 
 * The properties of TBaseActiveCallbackControl can be accessed and changed from
 * each individual active controls' {@link getActiveControl ActiveControl} 
 * property.
 * 
 * The following example to set the validation group property of a TCallback component.
 * <code>
 * 	<com:TCallback ActiveControl.ValidationGroup="group1" ... />
 * </code>
 * 
 * Additional client-side options and events can be set using the 
 * {@link getClientSide ClientSide} property. The following example to show 
 * an alert box when a TCallback component response returns successfully.
 * <code>
 * 	<com:TCallback Active.Control.ClientSide.OnSuccess="alert('ok!')" ... />
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Fri Jun 16 08:40:43 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TBaseActiveCallbackControl extends TBaseActiveControl
{
	/**
	 * Callback client-side options can be set by setting the properties of
	 * the ClientSide property. E.g. <com:TCallback ActiveControl.ClientSide.OnSuccess="..." />
	 * See {@link TCallbackClientSideOptions} for details on the properties of ClientSide.
	 * @return TCallbackClientSideOptions client-side callback options.
	 */
	public function getClientSide()
	{
		if(is_null($client = $this->getOption('ClientSide')))
		{
			$client = $this->createClientSideOptions();
			$this->setOption('ClientSide', $client);
		}
		return $client;
	}
	
	/**
	 * Sets the client side options. Can only be set when client side is null.
	 * @param TCallbackClientSideOptions client side options.
	 */
	public function setClientSide($client)
	{
		if(is_null($this->getOption('ClientSide')))
			$this->setOption('ClientSide', $client);
		else
			throw new TConfigurationException(
				'active_controls_client_side_exists', $this->getControl()->getID());
	}

	/**
	 * @return TCallbackClientSideOptions callback client-side options.
	 */
	protected function createClientSideOptions()
	{
		return new TCallbackClientSideOptions;
	}

	/**
	 * Sets default callback options. Takes the ID of a TCallbackOptions 
	 * component to duplicate the client-side
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
	 * options are duplicated.
	 */
	public function getCallbackOptions()
	{
		return $this->getOption('CallbackOptions', '');
	}

	/** 
	 * Returns an array of default callback client-side options. The default options
	 * are obtained from the client-side options of a TCallbackOptions control with
	 * ID specified by {@link setCallbackOptionsID CallbackOptionsID}. 
	 * @return array list of default callback client-side options. 
	 */
	protected function getDefaultClientSideOptions()
	{
		if(($id=$this->getCallbackOptions())!=='' 
			&& ($control=$this->getControl()->findControl($id))!==null
			&& $control instanceof TCallbackOptions)
				return $control->getClientSide()->getOptions()->toArray();
		else
			return array();
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
		$default = $this->getDefaultClientSideOptions();
		$options = array_merge($default,$this->getClientSide()->getOptions()->toArray());
		$validate = $this->getCausesValidation();
		$options['CausesValidation']= $validate ? '' : false;
		$options['ValidationGroup']=$this->getValidationGroup();
		return $options;
	}

	/**
	 * Registers the callback control javascript code. Client-side options are
	 * merged and passed to the javascript code. This method should be called by
	 * Active component developers wanting to register the javascript to initialize
	 * the active component with additional options offered by the 
	 * {@link getClientSide ClientSide} property.
	 * @param string client side javascript class name.
	 * @param array additional callback options.
	 */
	public function registerCallbackClientScript($class,$options=null)
	{
		$cs = $this->getPage()->getClientScript();
		if(is_array($options))
			$options = array_merge($this->getClientSideOptions(),$options);
		else
			$options = $this->getClientSideOptions();			
		$cs->registerCallbackControl($class, $options);
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