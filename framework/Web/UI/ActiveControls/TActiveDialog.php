<?php

/**
 * TActiveDialog class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TDialog;

/**
 * TActiveDialog class.
 *
 * TActiveDialog is the active control counterpart to {@see TDialog}. When the
 * user opens or closes the `<dialog>` element in the browser (via the native
 * `close` event, or via the `open` attribute being toggled by other scripts),
 * a callback request is initiated and the corresponding server-side event fires.
 *
 * Two server-side events are available:
 * - <b>OnOpen</b> — raised when the dialog is opened. The {@see getOpen Open}
 *   property is updated to `true` and {@see getReturnValue ReturnValue} to ''
 *   before the event fires.
 * - <b>OnClose</b> — raised when the dialog is closed. The {@see getOpen Open}
 *   property is updated to `false` and {@see getReturnValue ReturnValue} to the
 *   dialog's `returnValue` before the event fires.
 *
 * A {@see \Prado\Web\UI\WebControls\TButton} with `Command="close"` closes the
 * dialog with its `value` as the `returnValue`, so an OnClose handler reads which
 * button closed it. The client resets `returnValue` to '' when the dialog opens,
 * so a close by Esc or light dismiss reports ''.
 *
 * The {@see getOpen Open} property can also be changed server-side during a
 * callback; the client will be updated automatically when
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl::setEnableUpdate
 * ActiveControl.EnableUpdate} is `true` (the default).
 *
 * To open the dialog from the server, call `$dialog->setOpen(true)` inside a
 * callback handler. With {@see setModal Modal} true, it opens modally, both on
 * page load and in a callback; set Modal before Open. A button with
 * `Command="show-modal"` opens it modally from the client.
 *
 * Additional client-side callback options (e.g. `OnSuccess`, `OnFailure`) are
 * available through the {@see getClientSide ActiveControl.ClientSide} sub-property.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter<\Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl> getAdapter()
 */
class TActiveDialog extends TDialog implements IActiveControl, ICallbackEventHandler
{
	/**
	 * Creates a new callback control and sets the adapter to TActiveControlAdapter.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return TCallbackClientSide client-side request options.
	 */
	public function getClientSide()
	{
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * @return string the `returnValue` of the dialog's last close, or '' while it is open
	 */
	public function getReturnValue()
	{
		return $this->getViewState('ReturnValue', '');
	}

	/**
	 * Raises the callback event. Called by the framework when a callback request
	 * targets this control. The {@see getCallbackParameter CallbackParameter} of
	 * `$param` is `'open'` when the dialog opened, and `['close', returnValue]` or
	 * `'close'` when it closed.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$parameter = $param->getCallbackParameter();
		$action = is_array($parameter) ? ($parameter[0] ?? null) : $parameter;
		if ($action === 'open') {
			parent::setOpen(true);
			$this->setViewState('ReturnValue', '', '');
			$this->onOpen($param);
		} elseif ($action === 'close') {
			parent::setOpen(false);
			$returnValue = is_array($parameter) && is_scalar($parameter[1] ?? null) ? (string) $parameter[1] : '';
			$this->setViewState('ReturnValue', $returnValue, '');
			$this->onClose($param);
		}
	}

	/**
	 * Raises the OnOpen event. Override or attach a handler to respond when
	 * the dialog is opened.
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function onOpen($param)
	{
		$this->raiseEvent('OnOpen', $this, $param);
	}

	/**
	 * Raises the OnClose event. Override or attach a handler to respond when
	 * the dialog is closed.
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function onClose($param)
	{
		$this->raiseEvent('OnClose', $this, $param);
	}

	/**
	 * Opens or closes the dialog on the client-side when the Open property
	 * changes during a callback. The client-side helper opens or closes the
	 * `<dialog>` element without echoing an open/close callback back to the
	 * server. Opening resets {@see getReturnValue ReturnValue} to '' and shows
	 * the dialog modally when {@see getModal Modal} is true.
	 * @param bool $value whether the dialog is open
	 */
	public function setOpen($value)
	{
		if (parent::getOpen() === \Prado\TPropertyValue::ensureBoolean($value)) {
			return;
		}
		parent::setOpen($value);
		if ($this->getOpen()) {
			$this->setViewState('ReturnValue', '', '');
		}
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->callClientFunction(
				'Prado.WebUI.TActiveDialog.setOpen',
				[$this, $this->getOpen(), $this->getModal()]
			);
		}
	}

	/**
	 * Registers nothing: the client control shows an Open, Modal dialog modally
	 * when it initializes, through the `ShowModal` option, so the open is not
	 * echoed to the server.
	 */
	protected function registerShowModalScript()
	{
	}

	/**
	 * Ensures the client ID is rendered and registers the callback client script.
	 * @param \Prado\Web\UI\THtmlWriter $writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getPostBackOptions()
		);
	}

	/**
	 * @return array postback options passed to the client-side JS class.
	 */
	protected function getPostBackOptions()
	{
		return [
			'ID' => $this->getClientID(),
			'EventTarget' => $this->getUniqueID(),
			'ShowModal' => $this->getOpen() && $this->getModal(),
		];
	}

	/**
	 * @return string the client-side JavaScript class name.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveDialog';
	}
}
