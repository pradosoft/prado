<?php

/**
 * TDialog class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TDialog class
 *
 * TDialog represents the HTML5 `<dialog>` element. The `<dialog>` element represents
 * a dialog or other interactive component, such as a dismissible alert, inspector,
 * or subwindow.
 *
 * | Property | Default | Renders |
 * |---|---|---|
 * | {@see setOpen Open} | false | the dialog shown on load |
 * | {@see setModal Modal} | false | an Open dialog shown modally |
 * | {@see setClosedBy ClosedBy} | Auto | `closedby`: the user actions that close the dialog ({@see TDialogClosedBy}) |
 * | {@see setHeaderText HeaderText} | '' | a heading of {@see setHeaderLevel HeaderLevel} that names the dialog (`aria-labelledby`) |
 * | {@see setShowCloseButton ShowCloseButton} | false | a close button with the accessible name {@see setCloseButtonText CloseButtonText} |
 *
 * | Open | Modal | Rendering |
 * |---|---|---|
 * | false | any | no `open` attribute; the dialog is closed |
 * | true | false | the `open` attribute; the dialog shows non-modally |
 * | true | true | the client ID and an end script that calls `showModal()`; without JavaScript the dialog stays closed |
 *
 * Open is kept in view state. Opening or closing the dialog in the browser does
 * not change it, so a postback handler that opened the dialog sets Open to false
 * to close it.
 *
 * A {@see TButton} with {@see TButton::setCommandFor CommandFor} opens and closes
 * the dialog in the browser without script, inside the page form: `show-modal`
 * opens it modally, and `close` closes it with the button's `value` as its
 * `returnValue`. {@see \Prado\Web\UI\ActiveControls\TActiveDialog} raises server
 * events when the dialog opens or closes.
 *
 * HeaderText and ShowCloseButton render a `dialog-header` block before the body:
 * the `dialog-title` heading and the `dialog-close` button, an invoker that closes
 * the dialog without script. The heading names the dialog unless
 * `Attributes.aria-labelledby` is set. {@see setEncode Encode} HTML-encodes
 * HeaderText. {@see setCssUrl CssUrl} selects the stylesheet of the header block.
 *
 * Template usage:
 * ```html
 * <com:TButton Text="Edit" CommandFor="EditDialog" Command="show-modal" />
 * <com:TDialog ID="EditDialog" ClosedBy="Any" HeaderText="Edit name" ShowCloseButton="true">
 *     <com:TTextBox ID="Name" />
 *     <com:TButton Text="Save" OnClick="saveClicked" />
 *     <com:TButton Text="Cancel" CommandFor="EditDialog" Command="close" />
 * </com:TDialog>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDialog extends TWebControl
{
	use TEncodeTrait;

	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'dialog';
	}

	/**
	 * @return bool whether the dialog is open
	 */
	public function getOpen()
	{
		return $this->getViewState('Open', false);
	}

	/**
	 * Sets whether the dialog is open.
	 * @param bool $value whether the dialog is open
	 */
	public function setOpen($value)
	{
		$this->setViewState('Open', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether an {@see getOpen Open} dialog shows modally
	 */
	public function getModal()
	{
		return $this->getViewState('Modal', false);
	}

	/**
	 * Sets whether an {@see getOpen Open} dialog shows modally. A modal dialog makes
	 * the rest of the page inert and shows above it, such as a dialog that
	 * reopens after its postback fails validation.
	 * @param bool $value whether an Open dialog shows modally
	 */
	public function setModal($value)
	{
		$this->setViewState('Modal', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return TDialogClosedBy the user actions that close the dialog. Defaults to TDialogClosedBy::Auto.
	 */
	public function getClosedBy()
	{
		return $this->getViewState('ClosedBy', TDialogClosedBy::Auto);
	}

	/**
	 * Sets the user actions that close the dialog (`closedby`). Any closes it on a
	 * click outside it (light dismiss) or Esc.
	 * @param TDialogClosedBy $value the user actions that close the dialog
	 */
	public function setClosedBy($value)
	{
		$this->setViewState('ClosedBy', TPropertyValue::ensureEnum($value, TDialogClosedBy::class), TDialogClosedBy::Auto);
	}

	/**
	 * @return string the text of the heading that names the dialog. Defaults to ''.
	 * @since 4.4.0
	 */
	public function getHeaderText()
	{
		return $this->getViewState('HeaderText', '');
	}

	/**
	 * Sets the text of the heading that names the dialog; '' renders no heading.
	 * @param string $value the text of the heading
	 * @since 4.4.0
	 */
	public function setHeaderText($value)
	{
		$this->setViewState('HeaderText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return int the level of the heading, 1 through 6. Defaults to 2.
	 * @since 4.4.0
	 */
	public function getHeaderLevel()
	{
		return $this->getViewState('HeaderLevel', 2);
	}

	/**
	 * Sets the level of the {@see getHeaderText HeaderText} heading.
	 * @param int $value the level of the heading, 1 through 6
	 * @throws TInvalidDataValueException if the level is not 1 through 6
	 * @since 4.4.0
	 */
	public function setHeaderLevel($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1 || $value > 6) {
			throw new TInvalidDataValueException('dialog_headerlevel_invalid', $value);
		}
		$this->setViewState('HeaderLevel', $value, 2);
	}

	/**
	 * @return bool whether the dialog renders a close button. Defaults to false.
	 * @since 4.4.0
	 */
	public function getShowCloseButton()
	{
		return $this->getViewState('ShowCloseButton', false);
	}

	/**
	 * Sets whether the dialog renders a close button in its header block. The
	 * button closes the dialog without script, with an empty `returnValue`.
	 * @param bool $value whether the dialog renders a close button
	 * @since 4.4.0
	 */
	public function setShowCloseButton($value)
	{
		$this->setViewState('ShowCloseButton', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the accessible name of the close button. Defaults to the
	 *   localized "Close".
	 * @since 4.4.0
	 */
	public function getCloseButtonText()
	{
		return $this->getViewState('CloseButtonText', '') ?: Prado::localize('Close');
	}

	/**
	 * Sets the accessible name of the close button; '' restores the localized "Close".
	 * @param string $value the accessible name of the close button
	 * @since 4.4.0
	 */
	public function setCloseButtonText($value)
	{
		$this->setViewState('CloseButtonText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string URL of the stylesheet of the header block. Defaults to 'default',
	 *   the bundled stylesheet. An empty string registers no stylesheet.
	 * @since 4.4.0
	 */
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl', 'default');
	}

	/**
	 * Sets the URL of the stylesheet of the header block. A replacement stylesheet
	 * styles the `dialog-header`, `dialog-title`, and `dialog-close` classes itself.
	 * @param string $value stylesheet URL, 'default' for the bundled stylesheet,
	 *   or empty to register no stylesheet
	 * @since 4.4.0
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * @return bool whether the dialog renders the header block: HeaderText is set
	 *   or ShowCloseButton is true
	 * @since 4.4.0
	 */
	public function getHasHeader()
	{
		return $this->getHeaderText() !== '' || $this->getShowCloseButton();
	}

	/**
	 * @return string the client ID of the {@see getHeaderText HeaderText} heading
	 * @since 4.4.0
	 */
	public function getHeaderClientID()
	{
		return $this->getClientID() . '_header';
	}

	/**
	 * Registers the stylesheet of the header block.
	 * @param mixed $param event parameter
	 * @since 4.4.0
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getHasHeader()) {
			$this->registerStyleSheet();
		}
	}

	/**
	 * Registers the stylesheet of {@see getCssUrl CssUrl}. 'default' publishes the
	 * bundled stylesheet; an empty string registers nothing.
	 * @since 4.4.0
	 */
	protected function registerStyleSheet()
	{
		if (($url = $this->getCssUrl()) === '') {
			return;
		}
		if ($url === 'default') {
			$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'dialog.css', self::class);
		}
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}

	/**
	 * Adds attribute name-value pairs to renderer. A dialog with a header block
	 * renders its client ID, and its heading names it unless
	 * `Attributes.aria-labelledby` is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getHasHeader()) {
			$this->setEnsureId(true);
			if ($this->getHeaderText() !== '') {
				$writer->addAttribute('aria-labelledby', $this->getHeaderClientID());
			}
		}
		parent::addAttributesToRender($writer);
		if (($closedBy = $this->getClosedBy()) !== TDialogClosedBy::Auto) {
			$writer->addAttribute('closedby', strtolower($closedBy));
		}
		if (!$this->getOpen()) {
			return;
		}
		if ($this->getModal()) {
			$writer->addAttribute('id', $this->getClientID());
			$this->registerShowModalScript();
		} else {
			$writer->addAttribute('open', 'open');
		}
	}

	/**
	 * Registers the end script that shows the {@see getOpen Open} dialog modally.
	 * The `open` attribute shows a dialog non-modally, so a Modal dialog renders
	 * without it and the script calls `showModal()`.
	 */
	protected function registerShowModalScript()
	{
		$id = TJavaScript::quoteString($this->getClientID());
		$this->getPage()->getClientScript()->registerEndScript(
			'prado:dialog:' . $this->getClientID(),
			"(function(d) { if (d && !d.open) { d.showModal(); } })(document.getElementById($id));"
		);
	}

	/**
	 * Renders the header block of {@see getHeaderText HeaderText} and the close
	 * button, then the body.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @since 4.4.0
	 */
	public function renderContents($writer)
	{
		if ($this->getHasHeader()) {
			$this->renderHeader($writer);
		}
		parent::renderContents($writer);
	}

	/**
	 * Renders the `dialog-header` block: the `dialog-title` heading when HeaderText
	 * is set and the `dialog-close` button when ShowCloseButton is true.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @since 4.4.0
	 */
	protected function renderHeader($writer)
	{
		$writer->addAttribute('class', 'dialog-header');
		$writer->renderBeginTag('div');
		if (($text = $this->getHeaderText()) !== '') {
			$writer->addAttribute('id', $this->getHeaderClientID());
			$writer->addAttribute('class', 'dialog-title');
			$writer->renderBeginTag('h' . $this->getHeaderLevel());
			$writer->write($this->encodeText($text));
			$writer->renderEndTag();
		}
		if ($this->getShowCloseButton()) {
			$writer->addAttribute('type', 'button');
			$writer->addAttribute('class', 'dialog-close');
			$writer->addAttribute('commandfor', $this->getClientID());
			$writer->addAttribute('command', 'close');
			$writer->addAttribute('aria-label', $this->getCloseButtonText());
			$writer->renderBeginTag('button');
			$writer->write('&times;');
			$writer->renderEndTag();
		}
		$writer->renderEndTag();
	}
}
