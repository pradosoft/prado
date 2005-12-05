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
 * TLinkButton has the same appearance as a hyperlink. However, it is only
 * used to submit data to the same page. If you want to link to another Web page
 * when the component is clicked, consider using the THyperLink component.
 * Like TButton, you can create either a <b>submit</b> button or a <b>command</b> button.
 *
 * A <b>command</b> button has a command name (specified by the <b>CommandName</b> property)
 * and a command parameter (specified by <b>CommandParameter</b> property)
 * associated with the button. This allows you to create multiple TLinkButton components
 * on a Web page and programmatically determine which one is clicked with what parameter.
 * You can provide an event handler for the <b>OnCommand</b> event to programmatically control
 * the actions performed when the command button is clicked.
 * In the event handler, you can also determine
 * the <b>CommandName</b> property value and the <b>CommandParameter</b> property value
 * through <b>name</b> and <b>parameter</b> of the event parameter which is of
 * type <b>TCommandEventParameter</b>.
 *
 * A <b>submit</b> button does not have a command name associated with the button
 * and clicking on it simply posts the Web page back to the server.
 * By default, a TLinkButton component is a submit button.
 * You can provide an event handler for the <b>OnClick</b> event to programmatically
 * control the actions performed when the submit button is clicked.
 *
 * TLinkButton will display the <b>Text</b> property value as the hyperlink text. If <b>Text</b>
 * is empty, the body content of TLinkButton will be displayed.
 * Therefore, you can use TLinkButton as an image button by enclosing an 'img' tag
 * as the body of TLinkButton.
 *
 * Note, <b>Text</b> will be HTML encoded before it is displayed in the TLinkButton component.
 * If you don't want it to be so, set <b>EncodeText</b> to false.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>Text</b>, string, kept in viewstate
 *   <br>Gets or sets the text caption displayed in the TLinkButton component.
 * - <b>EncodeText</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets the value indicating whether Text should be HTML-encoded when rendering.
 * - <b>CausesValidation</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets a value indicating whether validation is performed when the TLinkButton component is clicked.
 * - <b>CommandName</b>, string, kept in viewstate
 *   <br>Gets or sets the command name associated with the TLinkButton component that is passed to
 *   the <b>OnCommand</b> event.
 * - <b>CommandParameter</b>, string, kept in viewstate
 *   <br>Gets or sets an optional parameter passed to the <b>OnCommand</b> event along with
 *   the associated <b>CommandName</b>.
 *
 * Events
 * - <b>OnClick</b> Occurs when the TLinkButton component is clicked.
 * - <b>OnCommand</b> Occurs when the TLinkButton component is clicked.
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
		$onclick=$this->hasAttribute('onclick')?$this->getAttributes()->remove('onclick'):'';
		$onclick=THttpUtility::trimJavaScriptString($onclick).THttpUtility::trimJavaScriptString($this->getOnClientClick());
		if(!empty($onclick))
			$writer->addAttribute('onclick',$onclick);

		// We call parent implementation here because some attributes
		// may be overwritten in the following
		parent::addAttributesToRender($writer);

		if($this->getEnabled(true))
		{
			if(($options=$this->getPostBackOptions())===null)
				$href='javascript:void(0)';
			else
				$href=$page->getClientScript()->getPostBackEventReference($this,'',$options,true);
			$writer->addAttribute('href',$href);
		}
		else if($this->getEnabled())   // in this case, parent will not render 'disabled'
			$writer->addAttribute('disabled','disabled');
	}

	/**
	 * Returns postback specifications for the button.
	 * This method is used by framework and control developers.
	 * @return TPostBackOptions parameters about how the button defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$options=new TPostBackOptions();
		$options->ClientSubmit=true;
		$page=$this->getPage();
		if($this->getCausesValidation() && $page->getValidators($this->getValidationGroup())->getCount()>0)
		{
			$options->PerformValidation=true;
			$options->ValidationGroup=$this->getValidationGroup();
		}
		if($this->getPostBackUrl()!=='')
			$options->ActionUrl=THttpUtility::quoteJavaScriptString($this->getPostBackUrl());
		return $options;
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
	 * @return string the javascript to be executed when the button is clicked
	 */
	public function getOnClientClick()
	{
		return $this->getViewState('OnClientClick','');
	}

	/**
	 * @param string the javascript to be executed when the button is clicked. Do not prefix it with "javascript:".
	 */
	public function setOnClientClick($value)
	{
		$this->setViewState('OnClientClick',$value,'');
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
	 * Raises postback event.
	 * The implementation of this function should raise appropriate event(s) (e.g. OnClick, OnCommand)
	 * indicating the component is responsible for the postback event.
	 * This method is primarily used by framework developers.
	 * @param string the parameter associated with the postback event
	 */
	public function raisePostBackEvent($param)
	{
		$this->onClick(new TEventParameter);
		$cmdParam=new TCommandEventParameter;
		$cmdParam->name=$this->getCommandName();
		$cmdParam->parameter=$this->getCommandParameter();
		$this->onCommand($cmdParam);
	}

	/**
	 * This method is invoked when the component is clicked.
	 * The method raises 'OnClick' event to fire up the event delegates.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onClick($param)
	{
		$this->raiseEvent('Click',$this,$param);
	}

	/**
	 * This method is invoked when the component is clicked.
	 * The method raises 'OnCommand' event to fire up the event delegates.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TCommandEventParameter event parameter to be passed to the event handlers
	 */
	public function onCommand($param)
	{
		$this->raiseEvent('Command',$this,$param);
		$this->raiseBubbleEvent($this,$param);
	}

	/**
	 * This overrides the parent implementation by rendering more TLinkButton-specific attributes.
	 * @return ArrayObject the attributes to be rendered
	 */
	protected function getAttributesToRender()
	{
		$attr=parent::getAttributesToRender();
		if($this->isEnabled())
		{
			$page=$this->getPage();
			$postBack=$page->getPostBackClientEvent($this,'');
			if($this->causesValidation() && $this->Page->isEndScriptRegistered('TValidator'))
			{
				$group = $this->getValidationGroup();
				$group = strlen($group) ? ",'".$group."'" : '';
				$script = "Prado.Validation.AddTarget('{$this->ClientID}' {$group});";
				$this->Page->registerEndScript($this->ClientID.'target', $script);
			}

			$attr['href']="javascript:{$postBack}";
		}
		return $attr;
	}

	/**
	 * This overrides the parent implementation by rendering either <b>Text</b> or the body contents.
	 * @return string the rendering result
	 */
	protected function renderBody()
	{
		$text=$this->isEncodeText()?pradoEncodeData($this->getText()):$this->getText();
		return strlen($text)?$text:parent::renderBody();
	}
}

?>