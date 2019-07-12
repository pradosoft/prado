<?php
/**
 * TActiveDatePicker class file
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TDatePickerInputMode;

/**
 * TActiveDatePicker class
 *
 * The active control counter part to date picker control.
 * When the date selection is changed, the {@link onCallback OnCallback} event is
 * raised.
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1.3
 */
class TActiveDatePicker extends TDatePicker implements ICallbackEventHandler, IActiveControl
{

	/**
	 * @return bool a value indicating whether an automatic postback to the server
	 * will occur whenever the user modifies the text in the TActiveDatePicker control and
	 * then tabs out of the component. Defaults to true.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack', true);
	}

	/**
	 * Sets the value indicating if postback automatically.
	 * An automatic postback to the server will occur whenever the user
	 * modifies the text in the TActiveDatePicker control and then tabs out of the component.
	 * @param bool $value the value indicating if postback automatically
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Get javascript date picker options.
	 * @return array date picker client-side options
	 */
	protected function getDatePickerOptions()
	{
		$options = parent::getDatePickerOptions();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['EventTarget'] = $this->getUniqueID();
		$options['ShowCalendar'] = $this->getShowCalendar();
		$options['AutoPostBack'] = $this->getAutoPostBack();
		return $options;
	}

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
	 * Client-side Text property can only be updated after the OnLoad stage.
	 * @param string $value text content for the textbox
	 */
	public function setText($value)
	{
		if (parent::getText() === $value) {
			return;
		}

		parent::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide() && $this->getHasLoadedPostData()) {
			$cb = $this->getPage()->getCallbackClient();
			$cb->setValue($this, $value);
			if ($this->getInputMode() == TDatePickerInputMode::DropDownList) {
				$dt = new \DateTime;
				$dt->setTimeStamp($this->getTimeStampFromText());
				$id = $this->getClientID();
				$cb->select($id . TControl::CLIENT_ID_SEPARATOR . 'day', 'Value', $dt->format('j'), 'select');
				$cb->select($id . TControl::CLIENT_ID_SEPARATOR . 'month', 'Value', $dt->format('n') - 1, 'select');
				$cb->select($id . TControl::CLIENT_ID_SEPARATOR . 'year', 'Value', $dt->format('Y'), 'select');
			}
		}
	}

	/**
	 * Raises the callback event. This method is required by {@link
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
	 * Registers the javascript code to initialize the date picker.
	 */

	protected function registerCalendarClientScriptPre()
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript("activedatepicker");
	}

	protected function renderClientControlScript($writer)
	{
		$cs = $this->getPage()->getClientScript();
		if (!$cs->isEndScriptRegistered('TDatePicker.spacer')) {
			$spacer = $this->getAssetUrl('spacer.gif');
			$code = "Prado.WebUI.TDatePicker.spacer = '$spacer';";
			$cs->registerEndScript('TDatePicker.spacer', $code);
		}

		$options = TJavaScript::encode($this->getDatePickerOptions());
		$code = "new Prado.WebUI.TActiveDatePicker($options);";
		$cs->registerEndScript("prado:" . $this->getClientID(), $code);
	}

	/**
	 * @return TActiveDatePickerClientScript javascript validator event options.
	 */
	protected function createClientScript()
	{
		return new TActiveDatePickerClientScript;
	}
}
