<?php

/**
 * TConfirmSubmit class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\THttpUtility;
use Prado\Web\UI\TControl;

/**
 * TConfirmSubmit class
 *
 * TConfirmSubmit asks for confirmation before the controls inside it act: it wraps
 * them and shows a modal `<dialog role="alertdialog">` when the user causes a postback,
 * a callback, or a navigation from them. OK resumes the action, with its validation,
 * and Cancel leaves the page and the control as they were.
 * ```xml
 * <com:TConfirmSubmit ConfirmText="Delete this record?" ConfirmButtonText="Delete">
 *     <com:TLinkButton Text="Delete" OnClick="deleteRecord" />
 * </com:TConfirmSubmit>
 * ```
 *
 * | User action in the wrapper | Confirmed for | On Cancel |
 * |---|---|---|
 * | click | a link, button, image button, or image map area | the click has no effect |
 * | click | a checkbox or radio button with AutoPostBack | the state stays |
 * | change | a list or text box with AutoPostBack | the value returns |
 *
 * Active controls are the same as their postback counterparts. A control without
 * AutoPostBack keeps its plain behavior. The control renders a `<span>` by default;
 * {@see setTagName TagName} changes it.
 *
 * The client control `Prado.WebUI.TConfirmSubmit` intercepts the events, so without
 * script the controls act with no confirmation. {@see TConfirmButton} confirms without
 * script. With {@see setConfirmText ConfirmText} empty, no confirmation is asked.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TConfirmSubmit extends THtmlElement
{
	use TConfirmDialogTrait;

	/**
	 * The client-side class of the control.
	 */
	public const CLIENT_CLASS = 'Prado.WebUI.TConfirmSubmit';

	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'span';
	}

	/**
	 * @return bool whether the control asks for confirmation: ConfirmText is set and
	 *   the control is enabled
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
	 * Renders the wrapper and, when the control asks for confirmation, registers the
	 * client control and renders the dialog. Registering at render time sees the
	 * children a data binding adds.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if (!$this->getHasConfirmation()) {
			parent::render($writer);
			return;
		}
		$this->setEnsureId(true);
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('confirmsubmit');
		$cs->registerPostBackControl(self::CLIENT_CLASS, $this->getClientOptions());
		parent::render($writer);
		$this->renderConfirmDialog($writer);
	}

	/**
	 * @return array the options of the client control: `ID`, `DialogID`, `OkID`, and
	 *   `AutoPostBack`
	 */
	protected function getClientOptions()
	{
		return [
			'ID' => $this->getClientID(),
			'DialogID' => $this->getDialogClientID(),
			'OkID' => $this->getConfirmButtonClientID(),
			'AutoPostBack' => $this->findAutoPostBackClientIDs($this),
		];
	}

	/**
	 * Finds the visible descendants with AutoPostBack on.
	 * @param TControl $parent the control to search
	 * @return string[] the client IDs of the descendants
	 */
	protected function findAutoPostBackClientIDs(TControl $parent)
	{
		$clientIDs = [];
		foreach ($parent->getControls() as $control) {
			if (!($control instanceof TControl) || !$control->getVisible(false)) {
				continue;
			}
			if (method_exists($control, 'getAutoPostBack') && $control->getAutoPostBack()) {
				$clientIDs[] = $control->getClientID();
			}
			array_push($clientIDs, ...$this->findAutoPostBackClientIDs($control));
		}
		return $clientIDs;
	}

	/**
	 * Renders the OK button, which the client control handles.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $formID unused; the button submits nothing itself
	 */
	protected function renderConfirmButton($writer, $formID)
	{
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('id', $this->getConfirmButtonClientID());
		$writer->renderBeginTag('button');
		$writer->write(THttpUtility::htmlEncode($this->getConfirmButtonText()));
		$writer->renderEndTag();
	}
}
