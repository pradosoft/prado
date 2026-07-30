<?php

/**
 * TActiveDetails class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TDetails;

/**
 * TActiveDetails class.
 *
 * TActiveDetails is the active control counterpart to {@see TDetails}. When the
 * user opens or closes the `<details>` disclosure widget in the browser, a callback
 * request is initiated and the corresponding server-side event is raised.
 *
 * Two server-side events are available:
 * - <b>OnOpen</b> — raised when the user opens the details widget. The
 *   {@see getOpen Open} property is updated to `true` before the event fires.
 * - <b>OnClose</b> — raised when the user closes the details widget. The
 *   {@see getOpen Open} property is updated to `false` before the event fires.
 *
 * The {@see getOpen Open} property can also be changed server-side during a
 * callback and the client will be updated automatically when
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl::setEnableUpdate
 * ActiveControl.EnableUpdate} is `true` (the default).
 *
 * Additional client-side callback options (e.g. `OnSuccess`, `OnFailure`) are
 * available through the {@see getClientSide ActiveControl.ClientSide} sub-property.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveDetails extends TDetails implements IActiveControl, ICallbackEventHandler
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
	 * the user opens the details widget.
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function onOpen($param)
	{
		$this->raiseEvent('OnOpen', $this, $param);
	}

	/**
	 * Raises the OnClose event. Override or attach a handler to respond when
	 * the user closes the details widget.
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function onClose($param)
	{
		$this->raiseEvent('OnClose', $this, $param);
	}

	/**
	 * Opens or closes the details widget on the client-side when the Open
	 * property changes during a callback. The client-side helper toggles the
	 * `<details>` element without echoing an open/close callback back to the
	 * server.
	 * @param bool $value whether the details widget is open
	 */
	public function setOpen($value)
	{
		if (parent::getOpen() === \Prado\TPropertyValue::ensureBoolean($value)) {
			return;
		}
		parent::setOpen($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->callClientFunction(
				'Prado.WebUI.TActiveDetails.setOpen',
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
		return 'Prado.WebUI.TActiveDetails';
	}
}
