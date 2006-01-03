<?php
/**
 * TLinkButton class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TLinkButton class
 *
 * TLinkButton creates a hyperlink style button on the page.
 * TLinkButton has the same appearance as a hyperlink. However, it is mainly
 * used to submit data to a page. Like {@link TButton}, you can create either
 * a <b>submit</b> button or a <b>command</b> button.
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
 * By default, a TLinkButton component is a submit button.
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
 * TLinkButton will display the {@link setText Text} property value
 * as the hyperlink text. If {@link setText Text} is empty, the body content
 * of TLinkButton will be displayed. Therefore, you can use TLinkButton
 * as an image button by enclosing an &lt;img&gt; tag as the body of TLinkButton.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TLinkButton extends TWebControl implements IPostBackEventHandler
{
	/**
	 * @return string tag name of the button
	 */
	protected function getTagName()
	{
		return 'a';
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

		$writer->addAttribute('id',$this->getClientID());

		// We call parent implementation here because some attributes
		// may be overwritten in the following
		parent::addAttributesToRender($writer);

		if($this->getEnabled(true))
		{
			$url = $this->getPostBackUrl();
			//create unique no-op url references
			$nop = "javascript:;//".$this->getClientID();
			$writer->addAttribute('href', $url ? $url : $nop);

			$scripts = $this->getPage()->getClientScript();
			$options = $this->getPostBackOptions();
			$postback = $scripts->getPostBackEventReference($this, '', $options, false);
			$code = "{$postback}; Event.stop(e);";
			$scripts->registerClientEvent($this, "click", $code);
		}
		else if($this->getEnabled()) // in this case, parent will not render 'disabled'
			$writer->addAttribute('disabled','disabled');
	}

	/**
	 * Returns postback specifications for the button.
	 * This method is used by framework and control developers.
	 * @return TPostBackOptions parameters about how the button defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$flag=false;

		$option=new TPostBackOptions();
		$group = $this->getValidationGroup();
		$hasValidators = $this->getPage()->getValidators($group)->getCount()>0;
		if($this->getCausesValidation() && $hasValidators)
		{
			$flag=true;
			$options->setPerformValidation(true);
			$options->setValidationGroup($this->getValidationGroup());
		}
		if($this->getPostBackUrl()!=='')
		{
			$flag=true;
			$options->setActionUrl($this->getPostBackUrl());
		}
		return $flag?$options:null;
	}

	/**
	 * Renders the body content enclosed between the control tag.
	 * If {@link getText Text} is not empty, it will be rendered. Otherwise,
	 * the body content enclosed in the control tag will be rendered.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function renderContents($writer)
	{
		if(($text=$this->getText())==='')
			parent::renderContents($writer);
		else
			$writer->write($text);
	}

	/**
	 * @return string the text caption of the button
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text caption of the button.
	 * @param string the text caption to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
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
	 * @return boolean whether postback event trigger by this button will cause input validation
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * Sets the value indicating whether postback event trigger by this button will cause input validation.
	 * @param string the text caption to be set
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',$value,true);
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
}

?>