<?php
/**
 * TActiveCheckBox class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TCheckBox;

/**
 * TActiveCheckBox class.
 *
 * The active control counter part to checkbox. The {@see setAutoPostBack AutoPostBack}
 * property is set to true by default. Thus, when the checkbox is clicked a
 * {@see onCallback OnCallback} event is raise after {@see OnCheckedChanged} event.
 *
 * The {@see setText Text} and {@see setChecked Checked} properties can be
 * changed during a callback.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveCheckBox extends TCheckBox implements IActiveControl, ICallbackEventHandler
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
		$this->setAutoPostBack(true);
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
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * Raises the callback event. This method is required by {@see
	 * ICallbackEventHandler} interface.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
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
	 * {@see setEnableUpdate EnableUpdate} property is set to true.
	 * @param string $value caption of the button
	 */
	public function setText($value)
	{
		if (parent::getText() === $value) {
			return;
		}

		parent::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->update(
				$this->getDefaultLabelID(),
				$value
			);
		}
	}

	/**
	 * Sets a value indicating whether the checkbox is to be checked or not.
	 * Updates checkbox checked state on the client-side if the
	 * {@see setEnableUpdate EnableUpdate} property is set to true.
	 * @param bool $value whether the checkbox is to be checked or not.
	 */
	public function setChecked($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		if (parent::getChecked() === $value) {
			return;
		}

		parent::setChecked($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->check($this, $value);
		}
	}

	/**
	 * Override parent implementation, no javascript is rendered here instead
	 * the javascript required for active control is registered in {@see addAttributesToRender}.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 *
	 * Since 3.1.4, the javascript code is not rendered if {@see setAutoPostBack AutoPostBack} is false
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for the rendering purpose
	 * @param string $clientID checkbox id
	 * @param string $onclick onclick js
	 */
	protected function renderInputTag($writer, $clientID, $onclick)
	{
		parent::renderInputTag($writer, $clientID, $onclick);
		if ($this->getAutoPostBack()) {
			$this->getActiveControl()->registerCallbackClientScript(
				$this->getClientClassName(),
				$this->getPostBackOptions()
			);
		}
	}

	/**
	 * @return string corresponding javascript class name for this TActiveCheckBox.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveCheckBox';
	}

	/**
	 * Overrides parent implementation to ensure label has ID.
	 * @return \Prado\Collections\TMap list of attributes to be rendered for label beside the checkbox
	 */
	public function getLabelAttributes()
	{
		$attributes = parent::getLabelAttributes();
		$attributes['id'] = $this->getDefaultLabelID();
		return $attributes;
	}

	/**
	 * Renders a label beside the checkbox.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for the rendering purpose
	 * @param string $clientID checkbox id
	 * @param string $text label text
	 */
	protected function renderLabel($writer, $clientID, $text)
	{
		$writer->addAttribute('id', $this->getDefaultLabelID());
		parent::renderLabel($writer, $clientID, $text);
	}

	/**
	 * @return string checkbox label ID;
	 */
	protected function getDefaultLabelID()
	{
		if ($attributes = $this->getViewState('LabelAttributes', null)) {
			return TCheckBox::getLabelAttributes()->itemAt('id');
		} else {
			return $this->getClientID() . '_label';
		}
	}
}
