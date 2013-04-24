<?php
/**
 * TActiveDatePicker class file
 * 
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TActiveDatePicker.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.ActiveControls
 */

/**
 * Load active control adapter.
 */
Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');

/**
 * TActiveDatePicker class
 * 
 * The active control counter part to date picker control.
 * When the date selection is changed, the {@link onCallback OnCallback} event is
 * raised.
 * 
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @version $Id: TActiveDatePicker.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.ActiveControls
 * @since 3.1.3
 */
class TActiveDatePicker extends TDatePicker  implements ICallbackEventHandler, IActiveControl
{
	
	/**
	 * @return boolean a value indicating whether an automatic postback to the server
     * will occur whenever the user modifies the text in the TActiveDatePicker control and
     * then tabs out of the component. Defaults to true.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack',true);
	}

	/**
	 * Sets the value indicating if postback automatically.
	 * An automatic postback to the server will occur whenever the user
	 * modifies the text in the TActiveDatePicker control and then tabs out of the component.
	 * @param boolean the value indicating if postback automatically
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * Get javascript date picker options.
	 * @return array date picker client-side options
	 */
	protected function getDatePickerOptions()
	{
		$options = parent::getDatePickerOptions();
		$options['CausesValidation']=$this->getCausesValidation();
		$options['ValidationGroup']=$this->getValidationGroup();
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
	public function getActiveControl(){
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Client-side Text property can only be updated after the OnLoad stage.
	 * @param string text content for the textbox
	 */
	public function setText($value){
		parent::setText($value);
		if($this->getActiveControl()->canUpdateClientSide() && $this->getHasLoadedPostData()){
			$cb=$this->getPage()->getCallbackClient();
			$cb->setValue($this, $value);
			if ($this->getInputMode()==TDatePickerInputMode::DropDownList)
			{
				$s = Prado::createComponent('System.Util.TDateTimeStamp');
				$date = $s->getDate($this->getTimeStampFromText());
				$id=$this->getClientID();
				$cb->select($id.TControl::CLIENT_ID_SEPARATOR.'day', 'Value', $date['mday'], 'select');
				$cb->select($id.TControl::CLIENT_ID_SEPARATOR.'month', 'Value', $date['mon']-1, 'select');
				$cb->select($id.TControl::CLIENT_ID_SEPARATOR.'year', 'Value', $date['year'], 'select');
				
			}
		}
	}
	
	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. 
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */
 	public function raiseCallbackEvent($param){
		$this->onCallback($param);
	}	
	
	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter event parameter to be passed to the event handlers
	 */
	public function onCallback($param){
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

	protected function registerCalendarClientScriptPost()
	{
		$cs = $this->getPage()->getClientScript();
		if(!$cs->isEndScriptRegistered('TDatePicker.spacer'))
		{
			$spacer = $this->getAssetUrl('spacer.gif');
			$code = "Prado.WebUI.TDatePicker.spacer = '$spacer';";
			$cs->registerEndScript('TDatePicker.spacer', $code);
		}

		$options = TJavaScript::encode($this->getDatePickerOptions());
		$code = "new Prado.WebUI.TActiveDatePicker($options);";
		$cs->registerEndScript("prado:".$this->getClientID(), $code);
	}

	/**
	 * @return TActiveDatePickerClientScript javascript validator event options.
	 */
	protected function createClientScript()
	{
		return new TActiveDatePickerClientScript;
	}
}

/**
 * TActiveDatePickerClientScript class.
 *
 * Client-side date picker event {@link setOnDateChanged OnDateChanged}
 * can be modified through the {@link TActiveDatePicker::getClientSide ClientSide}
 * property of a date picker.
 *
 * The <tt>OnDateChanged</tt> event is raise when the date picker's date
 * is changed.
 * The formatted date according to {@link TDatePicker::getDateFormat DateFormat} is sent
 * as parameter to this event
 * 
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @version $Id: TActiveDatePicker.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.ActiveControls
 * @since 3.2.1
 */
class TActiveDatePickerClientScript extends TCallbackClientSide
{
	/**
	 * Javascript code to execute when the date picker's date is changed.
	 * @param string javascript code
	 */
	public function setOnDateChanged($javascript)
	{
		$this->setFunction('OnDateChanged', $javascript);
	}

	/**
	 * @return string javascript code to execute when the date picker's date is changed.
	 */
	public function getOnDateChanged()
	{
		return $this->getOption('OnDateChanged');
	}
}