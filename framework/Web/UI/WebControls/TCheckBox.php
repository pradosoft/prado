<?php
/**
 * TCheckBox class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TCheckBox class
 *
 * TCheckBox creates a check box on the page.
 * You can specify the caption to display beside the check box by setting
 * the <b>Text</b> property.  The caption can appear either on the right
 * or left of the check box, which is determined by the <b>TextAlign</b>
 * property.
 *
 * To determine whether the TCheckBox component is checked,
 * test the <b>Checked</b> property. The <b>OnCheckedChanged</b> event
 * is raised when the <b>Checked</b> state of the TCheckBox component changes
 * between posts to the server. You can provide an event handler for
 * the <b>OnCheckedChanged</b> event to  to programmatically
 * control the actions performed when the state of the TCheckBox component changes
 * between posts to the server.
 *
 * Note, <b>Text</b> will be HTML encoded before it is displayed in the TCheckBox component.
 * If you don't want it to be so, set <b>EncodeText</b> to false.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>Text</b>, string, kept in viewstate
 *   <br>Gets or sets the text caption displayed in the TCheckBox component.
 * - <b>EncodeText</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets the value indicating whether Text should be HTML-encoded when rendering.
 * - <b>TextAlign</b>, Left|Right, default=Right, kept in viewstate
 *   <br>Gets or sets the alignment of the text label associated with the TCheckBox component.
 * - <b>Checked</b>, boolean, default=false, kept in viewstate
 *   <br>Gets or sets a value indicating whether the TCheckBox component is checked.
 * - <b>AutoPostBack</b>, boolean, default=false, kept in viewstate
 *   <br>Gets or sets a value indicating whether the TCheckBox automatically posts back to the server when clicked.
 *
 * Events
 * - <b>OnCheckedChanged</b> Occurs when the value of the <b>Checked</b> property changes between posts to the server.
 *
 * Examples
 * - On a page template file, insert the following line to create a TCheckBox component,
 * <code>
 *   <com:TCheckBox Text="Agree" OnCheckedChanged="checkAgree" />
 * </code>
 * The checkbox will show "Agree" text on its right side. If the user makes any change
 * to the <b>Checked</b> state, the checkAgree() method of the page class will be invoked automatically.
 *
 * TFont encapsulates the CSS style fields related with font settings.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TCheckBox extends TWebControl implements IPostBackDataHandler, IValidatable
{
	public static $TEXT_ALIGN=array('Left','Right');

	protected function getTagName()
	{
		return 'input';
	}

	protected function addAttributesToRender($writer)
	{
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the control has been changed
	 */
	public function loadPostData($key,$values)
	{
		$checked=$this->getChecked();
		if(isset($values[$key])!=$checked)
		{
			$this->setChecked(!$checked);
			return true;
		}
		else
			return false;
	}


	/**
	 * Raises postdata changed event.
	 * This method calls {@link onCheckedChanged} method.
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
		$this->onCheckedChanged(new TEventParameter);
	}

	/**
	 * This method is invoked when the value of the <b>Checked</b> property changes between posts to the server.
	 * The method raises 'CheckedChanged' event to fire up the event delegates.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onCheckedChanged($param)
	{
		$this->raiseEvent('CheckedChanged',$this,$param);
	}

	/**
	 * Returns the value of the property that needs validation.
	 * @return mixed the property value to be validated
	 */
	public function getValidationPropertyValue()
	{
		return $this->getChecked();
	}

	/**
	 * @return string the text caption of the checkbox
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text caption of the checkbox.
	 * @param string the text caption to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	/**
	 * @return string the alignment of the text caption
	 */
	public function getTextAlign()
	{
		return $this->getViewState('TextAlign','Right');
	}

	/**
	 * Sets the text alignment of the checkbox
	 * @param string either 'Left' or 'Right'
	 */
	public function setTextAlign($value)
	{
		$this->setViewState('TextAlign',TPropertyValue::ensureEnum($value,self::$TEXT_ALIGN),'Right');
	}

	/**
	 * @return boolean whether the checkbox is checked
	 */
	public function getChecked()
	{
		return $this->getViewState('Checked',false);
	}

	/**
	 * Sets a value indicating whether the checkbox is to be checked or not.
	 * @param boolean whether the checkbox is to be checked or not.
	 */
	public function setChecked($value)
	{
		$this->setViewState('Checked',$value,false);
	}

	/**
	 * @return boolean whether clicking on the checkbox will post the page.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack',false);
	}

	/**
	 * Sets a value indicating whether clicking on the checkbox will post the page.
	 * @param boolean whether clicking on the checkbox will post the page.
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack',$value,false);
	}

	/**
	 * @return boolean whether postback event trigger by this checkbox will cause input validation, default is true.
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * Sets the value indicating whether postback event trigger by this checkbox will cause input validation.
	 * @param boolean whether postback event trigger by this checkbox will cause input validation.
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the group of validators which the checkbox causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the checkbox causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * Returns the attributes to be rendered.
	 * This method overrides the parent's implementation.
	 * @return ArrayObject attributes to be rendered
	 */
	protected function getAttributesToRender()
	{
		$attributes=parent::getAttributesToRender();
		if(isset($attributes['id'])) unset($attributes['id']);
		if(isset($attributes['accesskey'])) unset($attributes['accesskey']);
		if(isset($attributes['tabindex'])) unset($attributes['tabindex']);
		return $attributes;
	}

	/**
	 * Renders the body content of the control.
	 * This method overrides the parent's implementation.
	 * @return string the rendering result.
	 */
	protected function renderBody()
	{
		$name=$this->getUniqueID();
		$disabled=!$this->isEnabled();
		$id=$this->getClientID();

		$input="<input id=\"$id\" type=\"checkbox\" name=\"$name\"";
		if($this->isChecked())
			$input.=" checked=\"checked\"";
		if($disabled)
			$input.=" disabled=\"disabled\"";
		if($this->isAutoPostBack())
		{
			$page=$this->getPage();
			$script=$page->getPostBackClientEvent($this,'');
			$input.=" onclick=\"javascript:$script\"";
		}
		$accessKey=$this->getAccessKey();
		if(strlen($accessKey))
			$input.=" accesskey=\"$accessKey\"";
		$tabIndex=$this->getTabIndex();
		if(!empty($tabIndex))
			$input.=" tabindex=\"$tabIndex\"";
		$input.='/>';
		$text=$this->isEncodeText()?pradoEncodeData($this->getText()):$this->getText();
		if(strlen($text))
		{
			$label="<label for=\"$name\">$text</label>";
			if($this->getTextAlign()=='Left')
				$input="{$label}{$input}";
			else
				$input.=$label;
		}
		return $input;
	}

	protected function renderControl($writer)
	{
		$this->addAttributesToRender($writer);
		$page=$this->getPage();
		$page->ensureRenderInForm($this);
		$needSpan=true;
		if($this->getHasStyle())
		{
			$this->getStyle()->addAttributesToRender($writer);
			$needSpan=true;
		}
		if(!$this->getEnabled(true))
		{
			$writer->addAttribute('disabled','disabled');
			$needSpan=true;
		}
		if(($tooltip=$this->getToolTip())!=='')
		{
			$writer->addAttribute('title',$tooltip);
			$needSpan=true;
		}
		$onclick=null;
		if($this->getHasAttributes())
		{
			$attributes=$this->getAttributes();
			$value=$attributes->remove('value');
			$onclick=$attributes->remove('onclick');
			if($attributes->getCount())
			{
				foreach($attributes as $name=>$value)
					$writer->addAttribute($name,$value);
			}
			$needSpan=true;
			if($value!==null)
				$attributes->add('value',$value);
		}
		if($needSpan)
			$writer->renderBeginTag('span');
		$clientID=$this->getClientID();
		if(($text=$this->getText())!=='')
		{
			if($this->getTextAlign()==='Left')
			{
				$this->renderLabel($writer,$text,$clientID);
				$this->renderInputTag($writer,$clientID,$onclick);
			}
			else
			{
				$this->renderInputTag($writer,$clientID,$onclick);
				$this->renderLabel($writer,$text,$clientID);
			}
		}
		else
			$this->renderInputTag($writer,$clientID,$onclick);
		if($needSpan)
			$writer->renderEndTag();
	}

	private function renderLabel($writer,$text,$clientID)
	{
		$writer->addAttribute('for',$clientID);
		// todo: custom label attributes rendering
		$writer->renderBeginTag('label');
		$writer->write($text);
		$writer->renderEndTag();
	}

	protected function renderInputTag($writer,$clientID,$onclick)
	{
		if($clientID!=='')
			$writer->addAttribute('id',$clientID);
		$writer->addAttribute('type','checkbox');
		if(($uniqueID=$this->getUniqueID())!=='')
			$writer->addAttribute('name',$uniqueID);
		//todo: render value attribute here
		if($this->getChecked())
			$writer->addAttribute('checked','checked');
		if(!$this->getEnabled(true))
			$writer->addAttribute('disabled','disabled');
		$page=$this->getPage();
		if($this->getAutoPostBack() && $page->getClientSupportsJavaScript())
		{
			$option=new TPostBackOptions($this);
			if($this->getCausesValidation() && $page->getValidators($this->getValidationGroup())->getCount())
			{
				$option->PerformValidation=true;
				$option->ValidationGroup=$this->getValidationGroup;
			}
			if($page->getForm())
				$option->AutoPostBack=true;
			if(!empty($onclick))
				$onclick=rtrim($onclick,';').';';
			$onclick.=$page->getClientScript()->getPostBackEventReference($this,'',$option,false);
		}
		if(!empty($onclick))
			$writer->addAttribute('onclick','javascript:'.$onclick);
		if(($accesskey=$this->getAccessKey())!=='')
			$writer->addAttribute('accesskey',$accesskey);
		if(($tabindex=$this->getTabIndex())>0)
			$writer->addAttribute('tabindex',$tabindex);
		//todo: render input attributes
		$writer->renderBeginTag('input');
		$writer->renderEndTag();
	}

	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getPage()->registerRequiresPostBack($this);
	}

	/*
protected internal override void OnPreRender(EventArgs e)
{
      base.OnPreRender(e);
      bool flag1 = this.AutoPostBack;
      if ((this.Page != null) && base.IsEnabled)
      {
            this.Page.RegisterRequiresPostBack(this);
            if (flag1)
            {
                  this.Page.RegisterPostBackScript();
                  this.Page.RegisterFocusScript();
                  if (this.CausesValidation && (this.Page.GetValidators(this.ValidationGroup).Count > 0))
                  {
                        this.Page.RegisterWebFormsScript();
                  }
            }
      }
      if (!this.SaveCheckedViewState(flag1))
      {
            this.ViewState.SetItemDirty("Checked", false);
            if ((this.Page != null) && base.IsEnabled)
            {
                  this.Page.RegisterEnabledControl(this);
            }
      }
}
*/
}

?>