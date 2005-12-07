<?php
/**
 * TTextBox class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TTextBox class
 *
 * TTextBox displays a text box on the Web page for user input.
 * The text displayed in the TTextBox control is determined by the <b>Text</b> property.
 * You can create a <b>SingleLine</b>, a <b>MultiLine</b>, or a <b>Password</b> text box
 * by setting the <b>TextMode</b> property.
 * If the TTextBox control is a multiline text box, the number of rows
 * it displays is determined by the <b>Rows</b> property, and the <b>Wrap</b> property
 * can be used to determine whether to wrap the text in the component.
 *
 * To specify the display width of the text box, in characters, set the <b>Columns</b> property.
 * To prevent the text displayed in the component from being modified,
 * set the <b>ReadOnly</b> property to true. If you want to limit the user input
 * to a specified number of characters, set the <b>MaxLength</b> property. To use AutoComplete
 * feature, set the <b>AutoCompleteType</b> property.
 *
 * If <b>AutoPostBack</b> is set true, updating the text box and then changing the focus out of it
 * will cause postback action. And if <b>CausesValidation</b> is true, validation will also
 * be processed, which can be further restricted within a <b>ValidationGroup</b>.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTextBox extends TWebControl implements IPostBackDataHandler, IValidatable
{
	/**
	 * @return string tag name of the textbox
	 */
	protected function getTagName()
	{
		return ($this->getTextMode()==='MultiLine')?'textarea':'input';
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional textbox specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$page=$this->getPage();
		$page->ensureRenderInForm($this);
		if(($uid=$this->getUniqueID())!=='')
			$writer->addAttribute('name',$uid);
		if(($textMode=$this->getTextMode())==='MultiLine')
		{
			if(($rows=$this->getRows())>0)
				$writer->addAttribute('rows',$rows);
			if(($cols=$this->getColumns())>0)
				$writer->addAttribute('cols',$cols);
			if(!$this->getWrap())
				$writer->addAttribute('wrap','off');
		}
		else
		{
			if($textMode==='SingleLine')
			{
				$writer->addAttribute('type','text');
				if(($text=$this->getText())!=='')
					$writer->addAttribute('value',$text);
				if(($act=$this->getAutoCompleteType())!=='None')
				{
					if($act==='Disabled')
						$writer->addAttribute('autocomplete','off');
					else if($act==='Search')
						$writer->addAttribute('vcard_name','search');
					else if($act==='HomeCountryRegion')
						$writer->addAttribute('vcard_name','HomeCountry');
					else if($act==='BusinessCountryRegion')
						$writer->addAttribute('vcard_name','BusinessCountry');
					else
					{
						if(($pos=strpos($act,'Business'))===0)
							$act='Business'.'.'.substr($act,8);
						else if(($pos=strpos($act,'Home'))===0)
							$act='Home'.'.'.substr($act,4);
						$writer->addAttribute('vcard_name','vCard.'.$act);
					}
				}
			}
			else
			{
				$writer->addAttribute('type','password');
			}
			if(($cols=$this->getColumns())>0)
				$writer->addAttribute('size',$cols);
			if(($maxLength=$this->getMaxLength())>0)
				$writer->addAttribute('maxlength',$maxLength);
		}
		if($this->getReadOnly())
			$writer->addAttribute('readonly','readonly');
		if(!$this->getEnabled(true) && $this->getEnabled())  // in this case parent will not render 'disabled'
			$writer->addAttribute('disabled','disabled');
		if($this->getAutoPostBack() && $page->getClientSupportsJavaScript())
		{
			$onchange='';
			$onkeypress='if (WebForm_TextBoxKeyHandler() == false) return false;';
			if($this->getHasAttributes())
			{
				$attributes=$this->getAttributes();
				$onchange=$attributes->itemAt('onchange');
				if($onchange!=='')
					$onchange=rtrim($onchange,';').';';
				$attributes->remove('onchange');
				$onkeypress.=$attributes->itemAt('onkeypress');
				$attributes->remove('onkeypress');
			}

			$option=new TPostBackOptions($this);
			if($this->getCausesValidation())
			{
				$option->PerformValidation=true;
				$option->ValidationGroup=$this->getValidationGroup();
			}
			if($page->getForm())
				$option->AutoPostBack=true;
			$onchange.=$page->getClientScript()->getPostBackEventReference($option);
			$writer->addAttribute('onchange',$onchange);
			$writer->addAttribute('onkeypress',$onkeypress);
		}
		parent::addAttributesToRender($writer);
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
		if(!$this->getReadOnly() && $this->getText()!==$value)
		{
			$this->setText($value);
			return true;
		}
		else
			return false;
	}

 	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		if(($page=$this->getPage()) && $this->getEnabled(true))
		{
			// TODO
			//if($this->getTextMode()==='Password' || ($this->hasEventHandler('TextChanged') && $this->getVisible()))
			//	$page->registerEnabledControl($this);
			if($this->getAutoPostBack())
			{
				$page->registerWebFormsScript();
				$page->registerPostBackScript();
				$page->registerFocusScript();
			}
		}
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
	 * This method is invoked when the value of the <b>Text</b> property changes on postback.
	 * The method raises 'TextChanged' event.
	 * If you override this method, be sure to call the parent implementation to ensure
	 * the invocation of the attached event handlers.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onTextChanged($param)
	{
		$this->raiseEvent('TextChanged',$this,$param);
	}

	/**
	 * Raises postdata changed event.
	 * This method is required by IPostBackDataHandler interface.
	 * It is invoked by the framework when <b>Text</b> property is changed on postback.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		$page=$this->getPage();
		if($this->getAutoPostBack() && !$page->getPostBackEventTarget())
		{
			$page->setPostBackEventTarget($this);
			if($this->getCausesValidation())
				$page->validate($this->getValidationGroup());
		}
		$this->onTextChanged(new TEventParameter);
	}

	/**
	 * Renders the body content of the textbox when it is in MultiLine text mode.
	 * @param THtmlWriter the writer for rendering
	 */
	protected function renderContents($writer)
	{
		if($this->getTextMode()==='MultiLine')
			$writer->write(THttpUtility::htmlEncode($this->getText()));
	}

	/**
	 * @return string the AutoComplete type of the textbox
	 */
	public function getAutoCompleteType()
	{
		return $this->getViewState('AutoCompleteType','None');
	}

	/**
	 * @param string the AutoComplete type of the textbox, default value is 'None'.
	 * Valid values include:
	 * 'BusinessCity','BusinessCountryRegion','BusinessFax','BusinessPhone',
	 * 'BusinessState','BusinessStreetAddress','BusinessUrl','BusinessZipCode',
	 * 'Cellular','Company','Department','Disabled','DisplayName','Email',
	 * 'FirstName','Gender','HomeCity','HomeCountryRegion','HomeFax','Homepage',
	 * 'HomePhone','HomeState','HomeStreetAddress','HomeZipCode','JobTitle',
	 * 'LastName','MiddleName','None','Notes','Office','Pager','Search'
	 * @throws TInvalidDataValueException if the input parameter is not a valid AutoComplete type
	 */
	public function setAutoCompleteType($value)
	{
		$this->setViewState('AutoCompleteType',TPropertyValue::ensureEnum($value,array('BusinessCity','BusinessCountryRegion','BusinessFax','BusinessPhone','BusinessState','BusinessStreetAddress','BusinessUrl','BusinessZipCode','Cellular','Company','Department','Disabled','DisplayName','Email','FirstName','Gender','HomeCity','HomeCountryRegion','HomeFax','Homepage','HomePhone','HomeState','HomeStreetAddress','HomeZipCode','JobTitle','LastName','MiddleName','None','Notes','Office','Pager','Search')),'None');
	}

	/**
	 * @return boolean a value indicating whether an automatic postback to the server
     * will occur whenever the user modifies the text in the TTextBox control and
     * then tabs out of the component.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack',false);
	}

	/**
	 * Sets the value indicating if postback automatically.
	 * An automatic postback to the server will occur whenever the user
	 * modifies the text in the TTextBox control and then tabs out of the component.
	 * @param boolean the value indicating if postback automatically
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether postback event trigger by this text box will cause input validation, default is true.
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * Sets the value indicating whether postback event trigger by this text box will cause input validation.
	 * @param boolean whether postback event trigger by this button will cause input validation.
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return integer the display width of the text box in characters, default is 0 meaning not set.
	 */
	public function getColumns()
	{
		return $this->getViewState('Columns',0);
	}

	/**
	 * Sets the display width of the text box in characters.
	 * @param integer the display width, set it 0 to clear the setting
	 */
	public function setColumns($value)
	{
		$this->setViewState('Columns',TPropertyValue::ensureInteger($value),0);
	}

	/**
	 * @return integer the maximum number of characters allowed in the text box, default is 0 meaning not set.
	 */
	public function getMaxLength()
	{
		return $this->getViewState('MaxLength',0);
	}

	/**
	 * Sets the maximum number of characters allowed in the text box.
	 * @param integer the maximum length,  set it 0 to clear the setting
	 */
	public function setMaxLength($value)
	{
		$this->setViewState('MaxLength',TPropertyValue::ensureInteger($value),0);
	}

	/**
	 * @return boolean whether the textbox is read only, default is false
	 */
	public function getReadOnly()
	{
		return $this->getViewState('ReadOnly',false);
	}

	/**
	 * @param boolean whether the textbox is read only
	 */
	public function setReadOnly($value)
	{
		$this->setViewState('ReadOnly',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return integer the number of rows displayed in a multiline text box, default is 4
	 */
	public function getRows()
	{
		return $this->getViewState('Rows',4);
	}

	/**
	 * Sets the number of rows displayed in a multiline text box.
	 * @param integer the number of rows,  set it 0 to clear the setting
	 */
	public function setRows($value)
	{
		$this->setViewState('Rows',TPropertyValue::ensureInteger($value),4);
	}

	/**
	 * @return string the text content of the TTextBox control.
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text content of the TTextBox control.
	 * @param string the text content
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	/**
	 * @return string the behavior mode (SingleLine, MultiLine, or Password) of the TTextBox component.
	 */
	public function getTextMode()
	{
		return $this->getViewState('TextMode','SingleLine');
	}

	/**
	 * Sets the behavior mode (SingleLine, MultiLine, or Password) of the TTextBox component.
	 * @param string the text mode
	 * @throws TInvalidDataValueException if the input value is not a valid text mode.
	 */
	public function setTextMode($value)
	{
		$this->setViewState('TextMode',TPropertyValue::ensureEnum($value,array('SingleLine','MultiLine','Password')),'SingleLine');
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
	 * @return boolean whether the text content wraps within a multiline text box.
	 */
	public function getWrap()
	{
		return $this->getViewState('Wrap',true);
	}

	/**
	 * Sets the value indicating whether the text content wraps within a multiline text box.
	 * @param boolean whether the text content wraps within a multiline text box.
	 */
	public function setWrap($value)
	{
		$this->setViewState('Wrap',TPropertyValue::ensureBoolean($value),true);
	}
}

?>