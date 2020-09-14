<?php
/**
 * TActiveButton class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Prado;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TButtonTag;

/**
 * TActiveButton is the active control counter part to TButton.
 *
 * When a TActiveButton is clicked, rather than a normal post back request a
 * callback request is initiated.
 *
 * The {@link onCallback OnCallback} event is raised during a callback request
 * and it is raise <b>after</b> the {@link onClick OnClick} event.
 *
 * When the {@link TBaseActiveCallbackControl::setEnableUpdate ActiveControl.EnableUpdate}
 * property is true, changing the {@link setText Text} property during callback request
 * will update the button's caption upon callback response completion.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TActiveButton extends TButton implements ICallbackEventHandler, IActiveControl
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
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
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		return $this->getAdapter()->getBaseActiveControl()->getClientSide();
	}

	/**
	 * Raises the callback event. This method is required by
	 * {@link ICallbackEventHandler} interface. If {@link getCausesValidation CausesValidation}
	 * is true, it will invoke the page's {@link TPage::validate validate}
	 * method first. It will raise {@link onClick OnClick} event first
	 * and then the {@link onCallback OnCallback} event.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$this->raisePostBackEvent($param);
		$this->onCallback($param);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * Updates the button text on the client-side if the
	 * {@link setEnableUpdate EnableUpdate} property is set to true.
	 * @param string $value caption of the button
	 */
	public function setText($value)
	{
		if (parent::getText() === $value) {
			return;
		}

		parent::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			if ($this->getButtonTag() === TButtonTag::Button) {
				$this->getPage()->getCallbackClient()->update($this, $value);
			} else {
				$this->getPage()->getCallbackClient()->setAttribute($this, 'value', $value);
			}
		}
	}

	/**
	 * Override parent implementation, no javascript is rendered here instead
	 * the javascript required for active control is registered in {@link addAttributesToRender}.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 * @param mixed $writer
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
	 * @return string corresponding javascript class name for this TActiveButton.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveButton';
	}
}
