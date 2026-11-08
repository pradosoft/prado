<?php

/**
 * TConfirmButton class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\THttpUtility;

/**
 * TConfirmButton class
 *
 * TConfirmButton is a {@see TButton} that asks for confirmation in a modal dialog
 * before it posts back. It replaces `onclick="return confirm('...')"` with an
 * accessible `<dialog role="alertdialog">` that works without script.
 * {@see TConfirmSubmit} confirms any control inside it, with script.
 *
 * With {@see setConfirmText ConfirmText} set, the button renders as an invoker
 * (`commandfor`, `command="show-modal"`) of its confirmation dialog. The dialog
 * holds a heading, the message, a Cancel button that closes it, and an OK button
 * that submits the page form as the TConfirmButton: OnClick and OnCommand are
 * raised, and validation runs, as for a TButton. Cancel takes the focus when the
 * dialog opens. The button keeps CssClass, Style, and the other attributes of the
 * control; ButtonTag and ButtonType do not apply.
 *
 * | Property | Default | Renders |
 * |---|---|---|
 * | {@see setConfirmText ConfirmText} | '' | the message; '' renders a plain TButton |
 * | {@see setConfirmHeaderText ConfirmHeaderText} | localized "Confirm" | the heading that names the dialog |
 * | {@see setConfirmButtonText ConfirmButtonText} | localized "OK" | the text of the button that posts back |
 * | {@see setCancelButtonText CancelButtonText} | localized "Cancel" | the text of the button that closes the dialog |
 * | {@see setCssUrl CssUrl} | default | the stylesheet of the dialog, the {@see TDialog} stylesheet |
 *
 * Inside the page form, the dialog renders after `</form>`, as a {@see TTeleport}
 * does, so it does not break the paragraph or table around the button. Its OK
 * button names the page form with the `form` attribute. When client validation
 * fails, the dialog closes so the error messages show.
 * ```xml
 * <com:TConfirmButton Text="Delete" ConfirmText="Delete this record?"
 *     ConfirmButtonText="Delete" OnClick="deleteRecord" />
 * ```
 *
 * {@see setEncode Encode} HTML-encodes ConfirmText and ConfirmHeaderText. The
 * button and dialog button texts are always HTML-encoded.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TConfirmButton extends TButton
{
	use TConfirmDialogTrait;

	/**
	 * @return bool whether the button asks for confirmation: ConfirmText is set and
	 *   the button is enabled
	 */
	public function getHasConfirmation()
	{
		return $this->getConfirmText() !== '' && $this->getEnabled(true);
	}

	/**
	 * Registers the stylesheet of the dialog.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getHasConfirmation()) {
			$this->registerDialogStyleSheet();
		}
	}

	/**
	 * Renders the button. A button that asks for confirmation renders as the invoker
	 * of its dialog, and the dialog after the page form, or in place outside it and
	 * in a callback.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if (!$this->getHasConfirmation()) {
			parent::render($writer);
			return;
		}
		$this->renderInvoker($writer);
		$this->renderConfirmDialog($writer);
	}

	/**
	 * Renders the visible button: an invoker that opens the dialog modally, with
	 * the attributes of the control.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderInvoker($writer)
	{
		$this->getPage()->ensureRenderInForm($this);
		$this->setEnsureId(true);
		TWebControl::addAttributesToRender($writer);
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('aria-haspopup', 'dialog');
		$writer->addAttribute('commandfor', $this->getDialogClientID());
		$writer->addAttribute('command', 'show-modal');
		$writer->renderBeginTag('button');
		$writer->write(THttpUtility::htmlEncode($this->getText()));
		$writer->renderEndTag();
	}

	/**
	 * Renders the OK button, which submits the form as this control and runs its
	 * client validation.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $formID the client ID of the form the button submits, or ''
	 */
	protected function renderConfirmButton($writer, $formID)
	{
		$writer->addAttribute('type', 'submit');
		$writer->addAttribute('id', $this->getConfirmButtonClientID());
		$writer->addAttribute('name', $this->getUniqueID());
		$writer->addAttribute('value', $this->getText());
		if ($formID !== '') {
			$writer->addAttribute('form', $formID);
		}
		$this->addFormSubmitterAttributesToRender($writer);
		if ($this->getEnableClientScript() && $this->needPostBackScript()) {
			$this->getPage()->getClientScript()->registerPostBackControl($this->getClientClassName(), $this->getPostBackOptions());
		}
		$writer->renderBeginTag('button');
		$writer->write(THttpUtility::htmlEncode($this->getConfirmButtonText()));
		$writer->renderEndTag();
	}

	/**
	 * @return array the postback options of the OK button, which the client control handles
	 */
	protected function getPostBackOptions()
	{
		$options = parent::getPostBackOptions();
		if ($this->getHasConfirmation()) {
			$options['ID'] = $this->getConfirmButtonClientID();
		}
		return $options;
	}

	/**
	 * @return string the client class, which closes the dialog when validation fails
	 */
	protected function getClientClassName()
	{
		return $this->getHasConfirmation() ? 'Prado.WebUI.TConfirmButton' : parent::getClientClassName();
	}
}
