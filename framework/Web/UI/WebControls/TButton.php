<?php
/**
 * TButton class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TButton class
 *
 * TButton creates a click button on the page. It is used to submit data to a page.
 * You can create either a <b>submit</b> button or a <b>command</b> button.
 *
 * A <b>command</b> button has a command name (specified by
 * the {@link setCommandName CommandName} property) and and a command parameter
 * (specified by {@link setCommandParameter CommandParameter} property)
 * associated with the button. This allows you to create multiple TLinkButton
 * components on a Web page and programmatically determine which one is clicked
 * with what parameter. You can provide an event handler for
 * {@link onCommand Command} event to programmatically control the actions performed
 * when the command button is clicked. In the event handler, you can determine
 * the {@link setCommandName CommandName} property value and
 * the {@link setCommandParameter CommandParameter} property value
 * through the {@link TCommandParameter::getName Name} and
 * {@link TCommandParameter::getParameter Parameter} properties of the event
 * parameter which is of type {@link TCommandEventParameter}.
 *
 * A <b>submit</b> button does not have a command name associated with the button
 * and clicking on it simply posts the Web page back to the server.
 * By default, a TButton component is a submit button.
 * You can provide an event handler for the {@link onClick Click} event
 * to programmatically control the actions performed when the submit button is clicked.
 *
 * Clicking on button can trigger form validation, if
 * {@link setCausesValidation CausesValidation} is true.
 * And the validation may be restricted within a certain group of validator
 * controls by setting {@link setValidationGroup ValidationGroup} property.
 * If validation is successful, the data will be post back to the same page.
 * You can change the postback target by setting the {@link setPostBackUrl PostBackUrl}
 * property.
 *
 * TButton displays the {@link setText Text} property as the button caption.
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
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$page=$this->getPage();
		$page->ensureRenderInForm($this);
		$writer->addAttribute('type',$this->getUseSubmitBehavior()?'submit':'button');
		if(($uniqueID=$this->getUniqueID())!=='')
			$writer->addAttribute('name',$uniqueID);
		$writer->addAttribute('value',$this->getText());
		if(!$this->getEnabled())   // in this case, parent will not render 'disabled'
			$writer->addAttribute('disabled','disabled');
		parent::addAttributesToRender($writer);
	}

	/**
	 * Registers the postback javascript code.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onPreRender($param)
	{
		if($this->getEnabled(true))
		{
			$scripts = $this->getPage()->getClientScript();
			$options = $this->getPostBackOptions();
			$postback = $scripts->getPostBackEventReference($this, '', $options, false);
			$scripts->registerClientEvent($this, "click", $postback);
		}
		parent::onPreRender($param);
	}

	/**
	 * Returns postback specifications for the button.
	 * This method is used by framework and control developers.
	 * @return TPostBackOptions parameters about how the button defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$option=new TPostBackOptions();
		$group = $this->getValidationGroup();
		$hasValidators = $this->getPage()->getValidators($group)->getCount()>0;
		if($this->getCausesValidation() && $hasValidators)
		{
			$option->setPerformValidation(true);
			$option->setValidationGroup($group);
		}
		if($this->getPostBackUrl()!=='')
			$option->setActionUrl($this->getPostBackUrl());
		$option->setClientSubmit(!$this->getUseSubmitBehavior());
		
		return $option;
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
	 * This method is invoked when the button is clicked.
	 * The method raises 'Click' event to fire up the event handlers.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handler can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onClick($param)
	{
		$this->raiseEvent('Click',$this,$param);
	}

	/**
	 * This method is invoked when the button is clicked.
	 * The method raises 'Command' event to fire up the event handlers.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TCommandEventParameter event parameter to be passed to the event handlers
	 */
	public function onCommand($param)
	{
		$this->raiseEvent('Command',$this,$param);
		$this->raiseBubbleEvent($this,$param);
	}

	/**
	 * Raises the postback event.
	 * This method is required by {@link IPostBackEventHandler} interface.
	 * If {@link getCausesValidation CausesValidation} is true, it will
	 * invoke the page's {@link TPage::validate validate} method first.
	 * It will raise {@link onClick Click} and {@link onCommand Command} events.
	 * This method is mainly used by framework and control developers.
	 * @param TEventParameter the event parameter
	 */
	public function raisePostBackEvent($param)
	{
		if($this->getCausesValidation())
			$this->getPage()->validate($this->getValidationGroup());
		$this->onClick(null);
		$this->onCommand(new TCommandEventParameter($this->getCommandName(),$this->getCommandParameter()));
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
	 * @return string the command name associated with the {@link onCommand Command} event.
	 */
	public function getCommandName()
	{
		return $this->getViewState('CommandName','');
	}

	/**
	 * Sets the command name associated with the {@link onCommand Command} event.
	 * @param string the text caption to be set
	 */
	public function setCommandName($value)
	{
		$this->setViewState('CommandName',$value,'');
	}

	/**
	 * @return string the parameter associated with the {@link onCommand Command} event
	 */
	public function getCommandParameter()
	{
		return $this->getViewState('CommandParameter','');
	}

	/**
	 * Sets the parameter associated with the {@link onCommand Command} event.
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
}

?>