<?php
/**
 * TLinkButton class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

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
 * {@link onCommand OnCommand} event to programmatically control the actions performed
 * when the command button is clicked. In the event handler, you can determine
 * the {@link setCommandName CommandName} property value and
 * the {@link setCommandParameter CommandParameter} property value
 * through the {@link TCommandParameter::getName Name} and
 * {@link TCommandParameter::getParameter Parameter} properties of the event
 * parameter which is of type {@link \Prado\Web\UI\TCommandEventParameter}.
 *
 * A <b>submit</b> button does not have a command name associated with the button
 * and clicking on it simply posts the Web page back to the server.
 * By default, a TLinkButton component is a submit button.
 * You can provide an event handler for the {@link onClick OnClick} event
 * to programmatically control the actions performed when the submit button is clicked.
 *
 * Clicking on button can trigger form validation, if
 * {@link setCausesValidation CausesValidation} is true.
 * And the validation may be restricted within a certain group of validator
 * controls by setting {@link setValidationGroup ValidationGroup} property.
 * If validation is successful, the data will be post back to the same page.
 *
 * TLinkButton will display the {@link setText Text} property value
 * as the hyperlink text. If {@link setText Text} is empty, the body content
 * of TLinkButton will be displayed. Therefore, you can use TLinkButton
 * as an image button by enclosing an &lt;img&gt; tag as the body of TLinkButton.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TLinkButton extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\Web\UI\IPostBackEventHandler, \Prado\Web\UI\IButtonControl, \Prado\IDataRenderer
{
	/**
	 * @return string tag name of the button
	 */
	protected function getTagName()
	{
		return 'a';
	}

	/**
	 * @return bool whether to render javascript.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript', true);
	}

	/**
	 * @param bool $value whether to render javascript.
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$page = $this->getPage();
		$page->ensureRenderInForm($this);

		$writer->addAttribute('id', $this->getClientID());

		// We call parent implementation here because some attributes
		// may be overwritten in the following
		parent::addAttributesToRender($writer);

		if ($this->getEnabled(true) && $this->getEnableClientScript()) {
			$this->renderLinkButtonHref($writer);
			$this->renderClientControlScript($writer);
		} elseif ($this->getEnabled()) { // in this case, parent will not render 'disabled'
			$writer->addAttribute('disabled', 'disabled');
		}
	}

	/**
	 * Renders the client-script code.
	 * @param THtmlWriter $writer renderer
	 */
	protected function renderClientControlScript($writer)
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPostBackControl($this->getClientClassName(), $this->getPostBackOptions());
	}

	/**
	 * @param bool $value set by a panel to register this button as the default button for the panel.
	 */
	public function setIsDefaultButton($value)
	{
		$this->setViewState('IsDefaultButton', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool true if this button is registered as a default button for a panel.
	 */
	public function getIsDefaultButton()
	{
		return $this->getViewState('IsDefaultButton', false);
	}

	/**
	 * Renders the Href for link button.
	 * @param THtmlWriter $writer renderer
	 */
	protected function renderLinkButtonHref($writer)
	{
		//create unique no-op url references
		$nop = "javascript:;//" . $this->getClientID();
		$writer->addAttribute('href', $nop);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TLinkButton';
	}

	/**
	 * Returns postback specifications for the button.
	 * This method is used by framework and control developers.
	 * @return array parameters about how the button defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['StopEvent'] = true;

		return $options;
	}

	/**
	 * Renders the body content enclosed between the control tag.
	 * If {@link getText Text} is not empty, it will be rendered. Otherwise,
	 * the body content enclosed in the control tag will be rendered.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getText()) === '') {
			parent::renderContents($writer);
		} else {
			$writer->write($text);
		}
	}

	/**
	 * @return string the text caption of the button
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value the text caption to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value, '');
	}

	/**
	 * Returns the caption of the button.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getText()}.
	 * @return string caption of the button.
	 * @see getText
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->getText();
	}

	/**
	 * Sets the caption of the button.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setText()}.
	 * @param string $value caption of the button
	 * @see setText
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setText($value);
	}

	/**
	 * @return string the command name associated with the {@link onCommand OnCommand} event.
	 */
	public function getCommandName()
	{
		return $this->getViewState('CommandName', '');
	}

	/**
	 * @param string $value the command name associated with the {@link onCommand OnCommand} event.
	 */
	public function setCommandName($value)
	{
		$this->setViewState('CommandName', $value, '');
	}

	/**
	 * @return string the parameter associated with the {@link onCommand OnCommand} event
	 */
	public function getCommandParameter()
	{
		return $this->getViewState('CommandParameter', '');
	}

	/**
	 * @param string $value the parameter associated with the {@link onCommand OnCommand} event.
	 */
	public function setCommandParameter($value)
	{
		$this->setViewState('CommandParameter', $value, '');
	}

	/**
	 * @return bool whether postback event trigger by this button will cause input validation
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation', true);
	}

	/**
	 * Sets the value indicating whether postback event trigger by this button will cause input validation.
	 * @param string $value the text caption to be set
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the group of validators which the button causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup', '');
	}

	/**
	 * @param string $value the group of validators which the button causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup', $value, '');
	}

	/**
	 * Raises the postback event.
	 * This method is required by {@link IPostBackEventHandler} interface.
	 * If {@link getCausesValidation CausesValidation} is true, it will
	 * invoke the page's {@link TPage::validate validate} method first.
	 * It will raise {@link onClick OnClick} and {@link onCommand OnCommand} events.
	 * This method is mainly used by framework and control developers.
	 * @param TEventParameter $param the event parameter
	 */
	public function raisePostBackEvent($param)
	{
		if ($this->getCausesValidation()) {
			$this->getPage()->validate($this->getValidationGroup());
		}
		$this->onClick(null);
		$this->onCommand(new \Prado\Web\UI\TCommandEventParameter($this->getCommandName(), $this->getCommandParameter()));
	}

	/**
	 * This method is invoked when the button is clicked.
	 * The method raises 'OnClick' event to fire up the event handlers.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handler can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onClick($param)
	{
		$this->raiseEvent('OnClick', $this, $param);
	}

	/**
	 * This method is invoked when the button is clicked.
	 * The method raises 'OnCommand' event to fire up the event handlers.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param \Prado\Web\UI\TCommandEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCommand($param)
	{
		$this->raiseEvent('OnCommand', $this, $param);
		$this->raiseBubbleEvent($this, $param);
	}
}
