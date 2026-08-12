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
 *   property is updated to `true` before the event fires.
 * - <b>OnClose</b> — raised when the dialog is closed. The {@see getOpen Open}
 *   property is updated to `false` before the event fires.
 *
 * The {@see getOpen Open} property can also be changed server-side during a
 * callback; the client will be updated automatically when
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl::setEnableUpdate
 * ActiveControl.EnableUpdate} is `true` (the default).
 *
 * To open the dialog from the server, call `$dialog->setOpen(true)` inside a
 * callback handler. To open it modally from the client side, you can use the
 * `ActiveControl.ClientSide.OnSuccess` script to call
 * `document.getElementById('dialogId').showModal()`.
 *
 * Additional client-side callback options (e.g. `OnSuccess`, `OnFailure`) are
 * available through the {@see getClientSide ActiveControl.ClientSide} sub-property.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
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
	 * Raises the callback event. Called by the framework when a callback request
	 * targets this control. The {@see getCallbackParameter CallbackParameter} of
	 * `$param` is either `'open'` or `'close'`, indicating which transition occurred.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$action = $param->getCallbackParameter();
		if ($action === 'open') {
			parent::setOpen(true);
			$this->onOpen($param);
		} elseif ($action === 'close') {
			parent::setOpen(false);
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
	 * server.
	 * @param bool $value whether the dialog is open
	 */
	public function setOpen($value)
	{
		if (parent::getOpen() === \Prado\TPropertyValue::ensureBoolean($value)) {
			return;
		}
		parent::setOpen($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->callClientFunction(
				'Prado.WebUI.TActiveDialog.setOpen',
				[$this, $this->getOpen()]
			);
		}
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
		return ['ID' => $this->getClientID(), 'EventTarget' => $this->getUniqueID()];
	}

	/**
	 * @return string the client-side JavaScript class name.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveDialog';
	}
}
