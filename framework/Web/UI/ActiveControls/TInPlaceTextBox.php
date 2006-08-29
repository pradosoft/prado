<?php

class TInPlaceTextBox extends TLabel implements
	IActiveControl, ICallbackEventHandler, IPostBackDataHandler, IValidatable
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
	 * @return TBaseActiveControl basic active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	public function setEditTriggerControlID($value)
	{
		$this->setViewState('EditTriggerControlID', $value);
	}

	public function getEditTriggerControlID()
	{
		return $this->getViewState('EditTriggerControlID');
	}

	/**
	 * @return string edit trigger control client ID.
	 */
	protected function getExternalControlID()
	{
		$extID = $this->getEditTriggerControlID();
		if(is_null($extID)) return '';
		if(($control = $this->findControl($extID))!==null)
			return $control->getClientID();
		return $extID;
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 * @throws TInvalidDataValueException if associated control cannot be found using the ID
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getLabelClientID());
		$this->renderClientControlScript($writer);
	}

	protected function getLabelClientID()
	{
		return $this->getClientID().'__label';
	}

	/**
	 * On callback response, the inner HTMl of the label is updated.
	 * @param string the text value of the label
	 */
	public function setText($value)
	{
		parent::setText($value);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->getPage()->getCallbackClient()->update(
				$this->getLabelClientID(), $value);
	}

	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */
 	public function raiseCallbackEvent($param)
	{
		$this->onCallback($param);
	}

	public function setTextBoxCssClass($value)
	{
		$this->setViewState('TextBoxCssClass', $value);
	}

	public function getTextBoxCssClass()
	{
		return $this->getViewState('TextBoxCssClass');
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
		$action = $param->getParameter();
		if(is_array($action) && $action[0] === '__InlineEditor_loadExternalText__')
		{
			$parameter = new TCallbackEventParameter($this->getResponse(), $action[1]);
			$this->onLoadingText($parameter);
		}
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * @return array callback options.
	 */
	protected function getTextBoxOptions()
	{
		$options['ID'] = $this->getLabelClientID();
		$options['TextBoxID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['TextMode'] = $this->getTextMode();
		$options['ExternalControl'] = $this->getExternalControlID();
		$options['TextBoxCssClass'] = $this->getTextBoxCssClass();
		if($this->hasEventHandler('OnLoadingText'))
			$options['LoadTextOnEdit'] = true;
		return $options;
	}

	/**
	 * @return string the behavior mode (SingleLine or MultiLine) of the TextBox component. Defaults to SingleLine.
	 */
	public function getTextMode()
	{
		return $this->getViewState('TextMode','SingleLine');
	}

	/**
	 * Sets the behavior mode (SingleLine or MultiLine) of the TextBox component.
	 * @param string the text mode
	 * @throws TInvalidDataValueException if the input value is not a valid text mode.
	 */
	public function setTextMode($value)
	{
		$this->setViewState('TextMode',TPropertyValue::ensureEnum($value,array('SingleLine','MultiLine')),'SingleLine');
	}
		/**
	 * Returns the value to be validated.
	 * This methid is required by IValidatable interface.
	 * @return mixed the value of the property to be validated.
	 */
	public function getValidationPropertyValue()
	{
		return $this->getText();
	}

	/**
	 * @return boolean whether postback event trigger by this text box will cause input validation, default is true.
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * @param boolean whether postback event trigger by this text box will cause input validation.
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}


	/**
	 * @return string the group of validators which the text box causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the text box causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the component has been changed
	 */
	public function loadPostData($key,$values)
	{
		$value=$values[$key];
		if($this->getText()!==$value)
		{
			$enabled = $this->getActiveControl()->getEnableUpdate();
			$this->getActiveControl()->setEnableUpdate(false);
			$this->setText($value);
			$this->getActiveControl()->setEnableUpdate($enabled);
			return true;
		}
		else
			return false;
	}

	public function raisePostDataChangedEvent()
	{
		$this->onTextChanged(null);
	}

	public function onLoadingText($param)
	{
		$this->raiseEvent('OnLoadingText',$this,$param);
	}

	/**
	 * Raises <b>OnTextChanged</b> event.
	 * This method is invoked when the value of the {@link getText Text}
	 * property changes on postback.
	 * If you override this method, be sure to call the parent implementation to ensure
	 * the invocation of the attached event handlers.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onTextChanged($param)
	{
		$this->raiseEvent('OnTextChanged',$this,$param);
	}

	/**
	 * Registers the javascript code for initializing the active control.
	 */
	protected function renderClientControlScript($writer)
	{
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(), $this->getTextBoxOptions());
	}

	/**
	 * @return string corresponding javascript class name for this TActiveLabelTextBox.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TInPlaceTextBox';
	}
}

?>