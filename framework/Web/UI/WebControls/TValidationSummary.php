<?php
/**
 * TValidationSummary class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TValidationSummary class
 *
 * TValidationSummary displays a summary of validation errors inline on a Web page,
 * in a message box, or both. By default, a validation summary will collect
 * {@link TBaseValidator::getErrorMessage ErrorMessage} of all failed validators
 * on the page. If {@link getValidationGroup ValidationGroup} is not
 * empty, only those validators who belong to the group will show their error messages
 * in the summary.
 *
 * The summary can be displayed as a list, as a bulleted list, or as a single
 * paragraph based on the {@link setDisplayMode DisplayMode} property.
 * The messages shown can be prefixed with {@link setHeaderText HeaderText}.
 *
 * The summary can be displayed on the Web page and in a message box by setting
 * the {@link setShowSummary ShowSummary} and {@link setShowMessageBox ShowMessageBox}
 * properties, respectively. Note, the latter is only effective when
 * {@link setEnableClientScript EnableClientScript} is true.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TValidationSummary extends \Prado\Web\UI\WebControls\TWebControl
{
	/**
	 * @var TClientSideValidationSummaryOptions validation client side options.
	 */
	private $_clientSide;

	/**
	 * Constructor.
	 * This method sets the foreground color to red.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setForeColor('red');
	}

	/**
	 * @return TValidationSummaryDisplayStyle the style of displaying the error messages. Defaults to TValidationSummaryDisplayStyle::Fixed.
	 */
	public function getDisplay()
	{
		return $this->getViewState('Display', TValidationSummaryDisplayStyle::Fixed);
	}

	/**
	 * @param TValidationSummaryDisplayStyle $value the style of displaying the error messages
	 */
	public function setDisplay($value)
	{
		$this->setViewState('Display', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TValidationSummaryDisplayStyle'), TValidationSummaryDisplayStyle::Fixed);
	}

	/**
	 * @return string the header text displayed at the top of the summary
	 */
	public function getHeaderText()
	{
		return $this->getViewState('HeaderText', '');
	}

	/**
	 * Sets the header text to be displayed at the top of the summary
	 * @param string $value the header text
	 */
	public function setHeaderText($value)
	{
		$this->setViewState('HeaderText', $value, '');
	}

	/**
	 * @return TValidationSummaryDisplayMode the mode of displaying error messages. Defaults to TValidationSummaryDisplayMode::BulletList.
	 */
	public function getDisplayMode()
	{
		return $this->getViewState('DisplayMode', TValidationSummaryDisplayMode::BulletList);
	}

	/**
	 * @param TValidationSummaryDisplayMode $value the mode of displaying error messages
	 */
	public function setDisplayMode($value)
	{
		$this->setViewState('DisplayMode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TValidationSummaryDisplayMode'), TValidationSummaryDisplayMode::BulletList);
	}

	/**
	 * @return bool whether the TValidationSummary component updates itself using client-side script. Defaults to true.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript', true);
	}

	/**
	 * @param bool $value whether the TValidationSummary component updates itself using client-side script.
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the validation summary is displayed in a message box. Defaults to false.
	 */
	public function getShowMessageBox()
	{
		return $this->getViewState('ShowMessageBox', false);
	}

	/**
	 * @param bool $value whether the validation summary is displayed in a message box.
	 */
	public function setShowMessageBox($value)
	{
		$this->setViewState('ShowMessageBox', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether the validation summary is displayed inline. Defaults to true.
	 */
	public function getShowSummary()
	{
		return $this->getViewState('ShowSummary', true);
	}

	/**
	 * @param bool $value whether the validation summary is displayed inline.
	 */
	public function setShowSummary($value)
	{
		$this->setViewState('ShowSummary', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether scroll summary into viewport or not. Defaults to true.
	 */
	public function getScrollToSummary()
	{
		return $this->getViewState('ScrollToSummary', true);
	}

	/**
	 * @param bool $value whether scroll summary into viewport or not.
	 */
	public function setScrollToSummary($value)
	{
		$this->setViewState('ScrollToSummary', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the validation summary should be anchored. Defaults to false.
	 */
	public function getShowAnchor()
	{
		return $this->getViewState('ShowAnchor', false);
	}

	/**
	 * @param bool $value whether the validation summary should be anchored.
	 */
	public function setShowAnchor($value)
	{
		$this->setViewState('ShowAnchor', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Gets the auto-update for this summary.
	 * @return bool automatic client-side summary updates. Defaults to true.
	 */
	public function getAutoUpdate()
	{
		return $this->getViewState('AutoUpdate', true);
	}

	/**
	 * Sets the summary to auto-update on the client-side
	 * @param bool $value true for automatic summary updates.
	 */
	public function setAutoUpdate($value)
	{
		$this->setViewState('AutoUpdate', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the group which this validator belongs to
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup', '');
	}

	/**
	 * @param string $value the group which this validator belongs to
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup', $value, '');
	}

	protected function addAttributesToRender($writer)
	{
		$display = $this->getDisplay();
		$visible = $this->getEnabled(true) && $this->getValidationFailed();
		if (!$visible) {
			if ($display === TValidationSummaryDisplayStyle::None || $display === TValidationSummaryDisplayStyle::Dynamic) {
				$writer->addStyleAttribute('display', 'none');
			} else {
				$writer->addStyleAttribute('visibility', 'hidden');
			}
		}
		$writer->addAttribute('id', $this->getClientID());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Render the javascript for validation summary.
	 */
	protected function renderJsSummary()
	{
		if (!$this->getEnabled(true) || !$this->getEnableClientScript()) {
			return;
		}
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('validator');

		//need to register the validation manager is validation summary is alone.
		$formID = $this->getPage()->getForm()->getClientID();
		$scriptKey = "TBaseValidator:$formID";
		if ($this->getEnableClientScript() && !$cs->isEndScriptRegistered($scriptKey)) {
			$manager['FormID'] = $formID;
			$options = TJavaScript::encode($manager);
			$cs->registerPradoScript('validator');
			$cs->registerEndScript($scriptKey, "new Prado.ValidationManager({$options});");
		}


		$options = TJavaScript::encode($this->getClientScriptOptions());
		$script = "new Prado.WebUI.TValidationSummary({$options});";
		$cs->registerEndScript($this->getClientID(), $script);
	}

	/**
	 * Get a list of options for the client-side javascript validation summary.
	 * @return array list of options for the summary
	 */
	protected function getClientScriptOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['FormID'] = $this->getPage()->getForm()->getClientID();
		if ($this->getShowMessageBox()) {
			$options['ShowMessageBox'] = true;
		}
		if (!$this->getShowSummary()) {
			$options['ShowSummary'] = false;
		}

		$options['ScrollToSummary'] = $this->getScrollToSummary();
		$options['HeaderText'] = $this->getHeaderText();
		$options['DisplayMode'] = $this->getDisplayMode();

		$options['Refresh'] = $this->getAutoUpdate();
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['Display'] = $this->getDisplay();

		if ($this->_clientSide !== null) {
			$options = array_merge($options, $this->_clientSide->getOptions()->toArray());
		}

		return $options;
	}

	/**
	 * @return TClientSideValidationSummaryOptions client-side validation summary
	 * event options.
	 */
	public function getClientSide()
	{
		if ($this->_clientSide === null) {
			$this->_clientSide = $this->createClientScript();
		}
		return $this->_clientSide;
	}

	/**
	 * @return TClientSideValidationSummaryOptions javascript validation summary
	 * event options.
	 */
	protected function createClientScript()
	{
		return new TClientSideValidationSummaryOptions;
	}

	/**
	 * Get the list of validation error messages.
	 * @return array list of validator error messages.
	 */
	protected function getErrorMessages()
	{
		$validators = $this->getPage()->getValidators($this->getValidationGroup());
		$messages = [];
		foreach ($validators as $validator) {
			if (!$validator->getIsValid() && ($msg = $validator->getErrorMessage()) !== '') {
				//$messages[] = $validator->getAnchoredMessage($msg);
				$messages[] = $msg;
			}
		}
		return $messages;
	}

	/**
	 * Checked if at least one validator failed.
	 * @return bool wether validation failed
	 */
	protected function getValidationFailed()
	{
		$validators = $this->getPage()->getValidators($this->getValidationGroup());
		foreach ($validators as $validator) {
			if (!$validator->getIsValid()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Overrides parent implementation by rendering TValidationSummary-specific presentation.
	 * @param mixed $writer
	 * @return string the rendering result
	 */
	public function renderContents($writer)
	{
		$this->renderJsSummary();
		if ($this->getShowSummary()) {
//		    $this->setStyle('display:block');
			switch ($this->getDisplayMode()) {
				case TValidationSummaryDisplayMode::SimpleList:
					$this->renderList($writer);
					break;
				case TValidationSummaryDisplayMode::SingleParagraph:
					$this->renderSingleParagraph($writer);
					break;
				case TValidationSummaryDisplayMode::BulletList:
					$this->renderBulletList($writer);
					break;
				case TValidationSummaryDisplayMode::HeaderOnly:
					$this->renderHeaderOnly($writer);
					break;
			}
		}
	}

	/**
	 * Render the validation summary as a simple list.
	 * @param string $writer the header text
	 * @return string summary list
	 */
	protected function renderList($writer)
	{
		$header = $this->getHeaderText();
		$messages = $this->getErrorMessages();
		$content = '';
		if (strlen($header)) {
			$content .= $header . "<br/>\n";
		}
		foreach ($messages as $message) {
			$content .= "$message<br/>\n";
		}
		$writer->write($content);
	}

	/**
	 * Render the validation summary as a paragraph.
	 * @param string $writer the header text
	 * @return string summary paragraph
	 */
	protected function renderSingleParagraph($writer)
	{
		$header = $this->getHeaderText();
		$messages = $this->getErrorMessages();
		$content = $header;
		foreach ($messages as $message) {
			$content .= ' ' . $message;
		}
		$writer->write($content);
	}

	/**
	 * Render the validation summary as a bullet list.
	 * @param string $writer the header text
	 * @return string summary bullet list
	 */
	protected function renderBulletList($writer)
	{
		$header = $this->getHeaderText();
		$messages = $this->getErrorMessages();
		$content = $header;
		if (count($messages) > 0) {
			$content .= "<ul>\n";
			foreach ($messages as $message) {
				$content .= '<li>' . $message . "</li>\n";
			}
			$content .= "</ul>\n";
		}
		$writer->write($content);
	}

	/**
	 * Render the validation summary header text only.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderHeaderOnly($writer)
	{
		$writer->write($this->getHeaderText());
	}
}
