<?php
/**
 * TButton class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TButton class
 *
 * TButton creates a click button on the page.
 *
 * You can create either a <b>submit</b> button or a <b>client</b> button by setting
 * <b>UseSubmitBehavior</b> property. Set <b>Text</b> property to specify the button's caption.
 * Upon clicking on the button, on the server side two events are raised by the button:
 * <b>OnClick</b> and <b>OnCommand</b>. You can attach event handlers to these events
 * to respond to the button click action. For <b>OnCommand</b> event, you can associate
 * it with a command name and parameter by setting <b>CommandName</b> and <b>CommandParameter</b>
 * properties, respectively. They are passed as the event parameter to the <b>OnCommand</b>
 * event handler (see {@link TCommandEventParameter}).
 *
 * Clicking on button can trigger form validation, if <b>CausesValidation</b> is true.
 * And the validation may be restricted within a certain group of validator controls by
 * setting <b>ValidationGroup</b> property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TButton extends TWebControl implements IPostBackEventHandler
{
	/**
	 * @return string tag name of the button
	 */
	protected function getTagName()
	{
		return 'input';
	}

	/**
	 * Processes an object that is created during parsing template.
	 * This overrides the parent implementation by forbidding any body components.
	 * @param mixed the newly created object in template
	 * @throws TInvalidOperationException if a component is found within body
	 */
	public function addParsedObject($object)
	{
		if(!is_string($object))
			throw new TInvalidOperationException('body_contents_not_allowed',get_class($this).':'.$this->getUniqueID());
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$page=$this->getPage();
		$page->ensureRenderInForm($this);
		if($this->getUseSubmitBehavior())
			$writer->addAttribute('type','submit');
		else
			$writer->addAttribute('type','button');
		if(($uniqueID=$this->getUniqueID())!=='')
			$writer->addAttribute('name',$uniqueID);
		$writer->addAttribute('value',$this->getText());

		$onclick='';
		if($this->getEnabled(true))
		{
			$onclick=$this->getOnClientClick();
			if($onclick!=='')
				$onclick=rtrim($onclick,';').';';
			$onclick.=$page->getClientScript()->getPostBackEventReference($this,'',$this->getPostBackOptions(),false);
		}
		else if($this->getEnabled())   // in this case, parent will not render 'disabled'
			$writer->addAttribute('disabled','disabled');
		if($onclick!=='')
			$writer->addAttribute('onclick','javascript:'.$onclick);
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the body content enclosed between the control tag.
	 * This overrides the parent implementation with nothing to be rendered.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function renderContents($writer)
	{
	}

	/**
	 * OnClick event raiser.
	 * This method raises OnClick event.
	 * Be sure to invoke the parent implementation if this method is overriden.
	 * @param TEventParameter the event parameter
	 */
	protected function onClick($param)
	{
		$this->raiseEvent('Click',$this,$param);
	}

	/**
	 * OnCommand event raiser.
	 * This method raises OnCommand event.
	 * Be sure to invoke the parent implementation if this method is overriden.
	 * @param TCommandEventParameter the event parameter
	 */
	protected function onCommand($param)
	{
		$this->raiseEvent('Command',$this,$param);
		$this->raiseBubbleEvent($this,$param);
	}

	/**
	 * Raises the postback event.
	 * This method is required by IPostBackEventHandler interface.
	 * If <b>CausesValidation</b> is true, it will invokes the page's {@validate}
	 * method first.
	 * It will raise <b>OnClick</b> and <b>OnCommand</b> events.
	 * This method is mainly used by framework and control developers.
	 * @param TEventParameter the event parameter
	 */
	public function raisePostBackEvent($param)
	{
		if($this->getCausesValidation())
			$this->getPage()->validate($this->getValidationGroup());
		$this->onClick(new TEventParameter);
		$this->onCommand(new TCommandEventParameter($this->getCommandName(),$this->getCommandParameter()));
	}

	/**
	 * Returns postback specifications for the button.
	 * This method is used by framework and control developers.
	 * @return TPostBackOptions parameters about how the button defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$options=new TPostBackOptions($this);
		$options->ClientSubmit=false;
		$page=$this->getPage();
		if($this->getCausesValidation() && $page->getValidators($this->getValidationGroup())->getCount()>0)
		{
			$options->PerformValidation=true;
			$options->ValidationGroup=$this->getValidationGroup();
		}
		if($this->getPostBackUrl()!=='')
			$options->ActionUrl=$this->getPostBackUrl();
		$options->ClientSubmit=!$this->getUseSubmitBehavior();
		return $options;
	}

	/**
	 * @return string caption of the button
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * @param string caption of the button
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	/**
	 * @return boolean whether postback event trigger by this button will cause input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * @param boolean whether postback event trigger by this button will cause input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the command name associated with the <b>OnCommand</b> event.
	 */
	public function getCommandName()
	{
		return $this->getViewState('CommandName','');
	}

	/**
	 * Sets the command name associated with the <b>OnCommand</b> event.
	 * @param string the text caption to be set
	 */
	public function setCommandName($value)
	{
		$this->setViewState('CommandName',$value,'');
	}

	/**
	 * @return string the parameter associated with the <b>OnCommand</b> event
	 */
	public function getCommandParameter()
	{
		return $this->getViewState('CommandParameter','');
	}

	/**
	 * Sets the parameter associated with the <b>OnCommand</b> event.
	 * @param string the text caption to be set
	 */
	public function setCommandParameter($value)
	{
		$this->setViewState('CommandParameter',$value,'');
	}

	/**
	 * @return boolean whether to use the button as a submit button, default is true.
	 */
	public function getUseSubmitBehavior()
	{
		return $this->getViewState('UseSubmitBehavior',true);
	}

	/**
	 * @param boolean whether to use the button as a submit button
	 */
	public function setUseSubmitBehavior($value)
	{
		$this->setViewState('UseSubmitBehavior',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the group of validators which the button causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the button causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * @return string the URL of the page to post to when the button is clicked, default is empty meaning post to the current page itself
	 */
	public function getPostBackUrl()
	{
		return $this->getViewState('PostBackUrl','');
	}

	/**
	 * @param string the URL of the page to post to from the current page when the button is clicked, empty if post to the current page itself
	 */
	public function setPostBackUrl($value)
	{
		$this->setViewState('PostBackUrl',$value,'');
	}

	/**
	 * @return string the javascript to be executed when the button is clicked
	 */
	public function getOnClientClick()
	{
		return $this->getViewState('ClientClick','');
	}

	/**
	 * @param string the javascript to be executed when the button is clicked. Do not prefix it with "javascript:".
	 */
	public function setOnClientClick($value)
	{
		$this->setViewState('ClientClick',$value,'');
	}
}

?>