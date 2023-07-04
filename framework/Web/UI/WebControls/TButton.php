<?php
/**
 * TButton class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TCommandEventParameter;
use Prado\TPropertyValue;

/**
 * TButton class
 *
 * TButton creates a click button on the page. It is mainly used to submit data to a page.
 *
 * TButton raises two server-side events, {@see onClick OnClick} and {@see onCommand OnCommand},
 * when it is clicked on the client-side. The difference between these two events
 * is that the event {@see onCommand OnCommand} is bubbled up to the button's ancestor controls.
 * And within the event parameter for {@see onCommand OnCommand} contains the reference
 * to the {@see setCommandName CommandName} and {@see setCommandParameter CommandParameter}
 * property values that are set for the button object. This allows you to create multiple TButton
 * components on a Web page and programmatically determine which one is clicked
 * with what parameter.
 *
 * Clicking on button can also trigger form validation, if
 * {@see setCausesValidation CausesValidation} is true.
 * The validation may be restricted within a certain group of validator
 * controls by setting {@see setValidationGroup ValidationGroup} property.
 * If validation is successful, the data will be post back to the same page.
 *
 * TButton displays the {@see setText Text} property as the button caption.
 *
 * TButton by default renders an input tag; the {@see setButtonTag ButtonTag}
 * property can be used to render a button tag (introduced in html5).
 *
 * TButton can be one of three {@see setButtonType ButtonType}: Submit, Button and Reset.
 * By default, it is a Submit button and the form submission uses the browser's
 * default submission capability. If it is Button or Reset, postback may occur
 * if one of the following conditions is met:
 * - an event handler is attached to {@see onClick OnClick} event;
 * - an event handler is attached to {@see onCommand OnCommand} event;
 * - the button is in a non-empty validation group.
 * In addition, clicking on a Reset button will clear up all input fields
 * if the button does not cause a postback.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TButton extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\Web\UI\IPostBackEventHandler, \Prado\Web\UI\IButtonControl, \Prado\IDataRenderer
{
	/**
	 * @return string tag name of the button
	 */
	protected function getTagName()
	{
		return strtolower($this->getButtonTag());
	}

	/**
	 * @return TButtonTag the tag name of the button. Defaults to TButtonType::Input.
	 */
	public function getButtonTag()
	{
		return $this->getViewState('ButtonTag', TButtonTag::Input);
	}

	/**
	 * @param TButtonTag $value the tag name of the button.
	 */
	public function setButtonTag($value)
	{
		$this->setViewState('ButtonTag', TPropertyValue::ensureEnum($value, TButtonTag::class), TButtonTag::Input);
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
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$page = $this->getPage();
		$page->ensureRenderInForm($this);
		$writer->addAttribute('type', strtolower($this->getButtonType()));
		if (($uniqueID = $this->getUniqueID()) !== '') {
			$writer->addAttribute('name', $uniqueID);
		}
		if ($this->getButtonTag() === TButtonTag::Button) {
			$this->addParsedObject($this->getText());
		} else {
			$writer->addAttribute('value', $this->getText());
		}
		if ($this->getEnabled(true)) {
			if ($this->getEnableClientScript() && $this->needPostBackScript()) {
				$this->renderClientControlScript($writer);
			}
		} elseif ($this->getEnabled()) { // in this case, parent will not render 'disabled'
			$writer->addAttribute('disabled', 'disabled');
		}

		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the client-script code.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		$this->getPage()->getClientScript()->registerPostBackControl($this->getClientClassName(), $this->getPostBackOptions());
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TButton';
	}

	/**
	 * @return bool whether to perform validation if the button is clicked
	 */
	protected function canCauseValidation()
	{
		if ($this->getCausesValidation()) {
			$group = $this->getValidationGroup();
			return $this->getPage()->getValidators($group)->getCount() > 0;
		} else {
			return false;
		}
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
	 * @return bool whether the button needs javascript to do postback
	 */
	protected function needPostBackScript()
	{
		return $this->canCauseValidation() || ($this->getButtonType() !== TButtonType::Submit &&
			($this->hasEventHandler('OnClick') || $this->hasEventHandler('OnCommand')))
			|| $this->getIsDefaultButton();
	}

	/**
	 * Returns postback specifications for the button.
	 * This method is used by framework and control developers.
	 * @return array parameters about how the button defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['EventTarget'] = $this->getUniqueID();
		$options['ValidationGroup'] = $this->getValidationGroup();

		return $options;
	}

	/**
	 * Renders the body content enclosed between the control tag.
	 * This overrides the parent implementation with nothing to be rendered for input tags,
	 * button tags are rendered normally.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if ($this->getButtonTag() === TButtonTag::Button) {
			parent::renderContents($writer);
		}
	}

	/**
	 * This method is invoked when the button is clicked.
	 * The method raises 'OnClick' event to fire up the event handlers.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handler can be invoked.
	 * @param \Prado\TEventParameter $param event parameter to be passed to the event handlers
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

	/**
	 * Raises the postback event.
	 * This method is required by {@see \Prado\Web\UI\IPostBackEventHandler} interface.
	 * If {@see getCausesValidation CausesValidation} is true, it will
	 * invoke the page's {@see \Prado\Web\UI\TPage::validate validate} method first.
	 * It will raise {@see onClick OnClick} and {@see onCommand OnCommand} events.
	 * This method is mainly used by framework and control developers.
	 * @param \Prado\TEventParameter $param the event parameter
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
	 * @return string caption of the button
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value caption of the button
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value, '');
	}

	/**
	 * Returns the caption of the button.
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see getText()}.
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
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see setText()}.
	 * @param string $value caption of the button
	 * @see setText
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setText($value);
	}

	/**
	 * @return bool whether postback event trigger by this button will cause input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation', true);
	}

	/**
	 * @param bool $value whether postback event trigger by this button will cause input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the command name associated with the {@see onCommand OnCommand} event.
	 */
	public function getCommandName()
	{
		return $this->getViewState('CommandName', '');
	}

	/**
	 * @param string $value the command name associated with the {@see onCommand OnCommand} event.
	 */
	public function setCommandName($value)
	{
		$this->setViewState('CommandName', $value, '');
	}

	/**
	 * @return string the parameter associated with the {@see onCommand OnCommand} event
	 */
	public function getCommandParameter()
	{
		return $this->getViewState('CommandParameter', '');
	}

	/**
	 * @param string $value the parameter associated with the {@see onCommand OnCommand} event.
	 */
	public function setCommandParameter($value)
	{
		$this->setViewState('CommandParameter', $value, '');
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
	 * @return TButtonType the type of the button. Defaults to TButtonType::Submit.
	 */
	public function getButtonType()
	{
		return $this->getViewState('ButtonType', TButtonType::Submit);
	}

	/**
	 * @param TButtonType $value the type of the button.
	 */
	public function setButtonType($value)
	{
		$this->setViewState('ButtonType', TPropertyValue::ensureEnum($value, TButtonType::class), TButtonType::Submit);
	}
}
