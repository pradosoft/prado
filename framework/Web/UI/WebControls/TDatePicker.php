<?php
/**
 * TDatePicker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 *
 * TDatePicker class.
 *
 * TDatePicker displays a text box for date input purpose.
 * When the text box receives focus, a calendar will pop up and users can
 * pick up from it a date that will be automatically entered into the text box.
 * The format of the date string displayed in the text box is determined by
 * the <b>DateFormat</b> property. Valid formats are the combination of the 
 * following tokens,
 *
 * <code>
 *  Character Format Pattern (en-US)
 *  -----------------------------------------
 *  d          day digit
 *  dd         padded day digit e.g. 01, 02
 *  M          month digit
 *  MM         padded month digit
 *  MMMM       localized month name, e.g. March, April
 *  yy         2 digit year
 *  yyyy       4 digit year
 *  -----------------------------------------
 * </code>
 *
 * TDatePicker has three <b>Mode</b> to show the date picker popup. 
 * 
 *  # <b>Basic</b> -- Only shows a text input, focusing on the input shows the 
 *                    date picker.
 *  # <b>Button</b> -- Shows a button next to the text input, clicking on the
 *                     button shows the date, button text can be by the 
 *                     <b>ButtonText</b> property
 *  # <b>ImageButton</b> -- Shows an image next to the text input, clicking on 
 *                          the image shows the date picker, image source can be
 *                          change through the <b>ImageUrl</b> property.
 *
 * The <b>CssClass</b> property can be used to override the css class name
 * for the date picker panel. <b>CalendarStyle</b> property sets the packages
 * styles available. E.g. <b>default</b>.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDatePicker extends TTextBox
{
	/**
	 * @return string the format of the date string
	 */
	public function getDateFormat()
	{
		return $this->getViewState('DateFormat','dd-MM-yyyy');
	}

	/**
	 * Sets the format of the date string.
	 * @param string the format of the date string
	 */
	public function setDateFormat($value)
	{
		$this->setViewState('DateFormat',$value,'dd-MM-yyyy');
	}

	/**
	 * @return boolean whether the calendar window should pop up when the control receives focus
	 */
	public function getShowCalendar()
	{
		return $this->getViewState('ShowCalendar',true);
	}

	/**
	 * Sets whether to pop up the calendar window when the control receives focus
	 * @param boolean whether to show the calendar window
	 */
	public function setShowCalendar($value)
	{
		$this->setViewState('ShowCalendar',TPropertyValue::ensureBoolean($value),true);
	}
	
	/**
	 * Gets the current culture.
	 * @return string current culture, e.g. en_AU.
	 */
	public function getCulture()
	{
		return $this->getViewState('Culture', '');
	}

	/**
	 * Sets the culture/language for the date picker.
	 * @param string a culture string, e.g. en_AU.
	 */
	public function setCulture($value)
	{
		$this->setViewState('Culture', $value, '');
	}

	/**
	 * @param string calendar UI mode, "Basic", "Button" or "ImageButton"
	 */
	public function setMode($value)
	{
	   $this->setViewState('Mode', TPropertyValue::ensureEnum($value, 'Basic', 'Button', 'ImageButton'), 'Basic');
	}

	/**
	 * @return string current calendar UI mode.
	 */
	public function getMode()
	{
	   return $this->getViewState('Mode', 'Basic');
	}
	/**
	 * @param string the image url for "Image" UI mode.
	 */
	public function setButtonImageUrl($value)
	{
	   $this->setViewState('ImageUrl', $value, '');
	}

	/**
	 * @return string the image url for "Image" UI mode.
	 */
	public function getButtonImageUrl()
	{
	   return $this->getViewState('ImageUrl', '');
	}

	/**
	 * @param string set the calendar style 
	 */
	public function setCalendarStyle($value)
	{
	   $this->setViewState('CalendarStyle', $value, 'default');
	}

	/**
	 * @return string current calendar styl
	 */
	public function getCalendarStyle()
	{
	   return $this->getViewState('CalendarStyle', 'default');
	}	

	/**
	 * Set the first day of week, with 0 as Sunday, 1 as Monday, etc.
	 * @param integer 0 for Sunday, 1 for Monday, 2 for Tuesday, etc.
	 */
	public function setFirstDayOfWeek($value)
	{
		$this->setViewState('FirstDayOfWeek', TPropertyValue::ensureInteger($value), 1);
	}

	/**
	 * @return integer first day of the week
	 */
	public function getFirstDayOfWeek()
	{
		return $this->getViewState('FirstDayOfWeek', 1);
	}

	/**
	 * @return string text for the date picker button. Default is "...".
	 */
	public function getButtonText()
	{
		return $this->getViewState('ButtonText', '...');
	}

	/**
	 * @param string text for the date picker button
	 */
	public function setButtonText($value)
	{
		$this->setViewState('ButtonText', $value, '...');
	}

	/**
	 * @param integer date picker starting year, default is 2000.
	 */
	public function setFromYear($value)
	{
		$this->setViewState('FromYear', TPropertyValue::ensureInteger($value), intval(@date('Y'))-5);
	}

	/**
	 * @return integer date picker starting year, default is -5 years
	 */
	public function getFromYear()
	{
		return $this->getViewState('FromYear', intval(@date('Y'))-5);
	}

	/**
	 * @param integer date picker ending year, default +10 years
	 */
	public function setUpToYear($value)
	{
		$this-setViewState('UpToYear', TPropertyValue::ensureInteger($value), intval(@date('Y'))+10);
	}

	/**
	 * @return integer date picker ending year, default +10 years
	 */
	public function getUpToYear()
	{
		return $this->getViewState('UpToYear', intval(@date('Y'))+10);
	}

	/**
	 * Get javascript date picker options.
	 * @return array date picker client-side options
	 */
	protected function getDatePickerOptions()
	{
		$options['Format'] = $this->getDateFormat();
		$options['FirstDayOfWeek'] = $this->getFirstDayOfWeek();
		$options['ClassName'] = $this->getCssClass();
		$options['FromYear'] = $this->getFromYear();
		$options['UpToYear'] = $this->getUpToYear();
		if($this->getMode()!=='Basic')
			$options['Trigger'] = $this->getDatePickerButtonID();
	
		$options = array_merge($options, $this->getCulturalOptions());
		return $options;
	}

	/**
	 * Get javascript localization options, e.g. month and weekday names.
	 * @return array localization options.
	 */
	protected function getCulturalOptions()
	{
		$app = $this->getApplication()->getGlobalization();
		$culture = $this->getCulture() == '' ? $app->getCulture() : $this->getCulture();
		if($culture == 'en') return array();
		
		//expensive operations
		Prado::using('System.I18N.core.DateTimeFormatInfo');
		$info = Prado::createComponent('System.I18N.core.CultureInfo', $culture);
		$date = $info->getDateTimeFormat();
		$serializer = new TJavascriptSerializer($date->getMonthNames());
		$options['MonthNames'] = $serializer->toList();
		$serializer = new TJavascriptSerializer($date->getAbbreviatedDayNames());
		$options['ShortWeekDayNames'] = $serializer->toList();
		return $options;
	}

	/**
	 * Publish the date picker Css asset files.
	 */
	protected function OnPreRender($param)
	{
		parent::onPreRender($param);
		$this->publishCalendarStyle();
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by adding
	 * additional date picker button if Mode is "Button" or "ImageButton".
	 * @param THtmlWriter writer
	 */
	protected function render($writer)
	{
		parent::render($writer);
		switch ($this->getMode())
		{
			case 'Button': $this->renderButtonDatePicker($writer); break;
			case 'ImageButton' : $this->renderImageButtonDatePicker($writer); break;
		
		}
	}

	/**
	 * Gets the ID for the date picker trigger button.
	 * @return string unique button ID
	 */
	protected function getDatePickerButtonID()
	{
		return $this->getClientID().'button';
	}

	/**
	 * Adds an additional button such that when clicked it shows the date picker.
	 * @return THtmlWriter writer
	 */
	protected function renderButtonDatePicker($writer)
	{
		$writer->addAttribute('id', $this->getDatePickerButtonID());		
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('class', $this->getCssClass().' TDatePickerButton');
		$writer->addAttribute('value',$this->getButtonText());
		$writer->renderBeginTag("input");
	}

	/**
	 * Adds an additional image button such that when clicked it shows the date picker.
	 * @return THtmlWriter writer
	 */
	 protected function renderImageButtonDatePicker($writer)
	{
		$url = $this->getButtonImageUrl();
		$url = empty($url) ? $this->publishDefaultButtonImage() : $url;
		$writer->addAttribute('id', $this->getDatePickerButtonID());
		$writer->addAttribute('src', $url);
		$writer->addAttribute('class', $this->getCssClass().' TDatePickerImageButton');
		$writer->renderBeginTag('img');
	}

	/**
	 * Publish the default button image asset file.
	 * @return string image file url.
	 */
	protected function publishDefaultButtonImage()
	{
		$cs = $this->getPage()->getClientScript();
		$image = 'System.Web.Javascripts.datepicker.calendar';
		$file =  Prado::getPathOfNamespace($image, '.png');
		return $this->getService()->getAsset($file);
	}

	/**
	 * Publish the calendar style Css asset file.
	 * @return string Css file url.
	 */
	protected function publishCalendarStyle()
	{
		$cs = $this->getPage()->getClientScript();
		$style = 'System.Web.Javascripts.datepicker.'.$this->getCalendarStyle();		
		$cssFile=Prado::getPathOfNamespace($style,'.css');
		$url = $this->getService()->getAsset($cssFile);
		if(!$cs->isStyleSheetFileRegistered($style))
			$cs->registerStyleSheetFile($style, $url);
		return $url;
	}

	/**
	 * Registers the javascript code to initialize the date picker.
	 * Must use "Event.OnLoad" to initialize the date picker when the 
	 * full page is loaded, otherwise IE will throw an error.
	 * @param THtmlWriter writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id',$this->getClientID());
		if($this->getShowCalendar())
		{
			$scripts = $this->getPage()->getClientScript();
			$scripts->registerClientScript("datepicker");
			$serializer = new TJavascriptSerializer($this->getDatePickerOptions());
			$options = $serializer->toJavascript();
			$id = $this->getClientID();
			$code = "Event.OnLoad(function(){ new Prado.WebUI.TDatePicker('$id', $options); });";
			$scripts->registerEndScript("prado:$id", $code);
		}
	}
}

?>