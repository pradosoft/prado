<?php
/**
 * TDatePicker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TTextBox class
 */
Prado::using('System.Web.UI.WebControls.TTextBox');

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
 *                          change through the <b>ButtonImageUrl</b> property.
 *
 * The <b>CssClass</b> property can be used to override the css class name
 * for the date picker panel. <b>CalendarStyle</b> property sets the packages
 * styles available. E.g. <b>default</b>.
 *
 * The <b>InputMode</b> property can be set to "TextBox" or "DropDownList" with
 * default as "TextBox".
 * In <tt>DropDownList</tt> mode, in addition to the popup date picker, three
 * drop down list (day, month and year) are presented to select the date .
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDatePicker extends TTextBox
{
	/**
	 * @var TDatePickerClientScript validator client-script options.
	 */
	private $_clientScript;
		/**
	 * AutoPostBack is not supported.
	 */
	public function setAutoPostBack($value)
	{
		throw new TNotSupportedException('tdatepicker_autopostback_unsupported',
			get_class($this));
	}

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
	 * @param TDatePickerInputMode input method of date values
	 */
	public function setInputMode($value)
	{
		$this->setViewState('InputMode', TPropertyValue::ensureEnum($value, 'TDatePickerInputMode'), TDatePickerInputMode::TextBox);
	}

	/**
	 * @return TDatePickerInputMode input method of date values. Defaults to TDatePickerInputMode::TextBox.
	 */
	public function getInputMode()
	{
		return $this->getViewState('InputMode', TDatePickerInputMode::TextBox);
	}

	/**
	 * @param TDatePickerMode calendar UI mode
	 */
	public function setMode($value)
	{
	   $this->setViewState('Mode', TPropertyValue::ensureEnum($value, 'TDatePickerMode'), TDatePickerMode::Basic);
	}

	/**
	 * @return TDatePickerMode current calendar UI mode.
	 */
	public function getMode()
	{
	   return $this->getViewState('Mode', TDatePickerMode::Basic);
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
	 * @return string current calendar style
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
		$this->setViewState('UpToYear', TPropertyValue::ensureInteger($value), intval(@date('Y'))+10);
	}

	/**
	 * @return integer date picker ending year, default +10 years
	 */
	public function getUpToYear()
	{
		return $this->getViewState('UpToYear', intval(@date('Y'))+10);
	}

	/**
	 * @return integer current selected date from the date picker as timestamp, NULL if timestamp is not set previously.
	 */
	public function getTimeStamp()
	{
		if(trim($this->getText())==='')
			return null;
		else
			return $this->getTimeStampFromText();
	}

	/**
	 * Sets the date for the date picker using timestamp.
	 * @param float time stamp for the date picker
	 */
	public function setTimeStamp($value)
	{
		if($value===null || (is_string($value) && trim($value)===''))
			$this->setText('');
		else
		{
			$date = TPropertyValue::ensureFloat($value);
			$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter',$this->getDateFormat());
			$this->setText($formatter->format($date));
		}
	}

	/**
	 * @return string the date string.
	 */
	public function getDate()
	{
		return $this->getText();
	}

	/**
	 * @param string date string
	 */
	public function setDate($value)
	{
		$this->setText($value);
	}

	/**
	 * Gets the TDatePickerClientScript to set the TDatePicker event handlers.
	 *
	 * The date picker on the client-side supports the following events.
	 * # <tt>OnDateChanged</tt> -- raised when the date is changed.
	 *
	 * You can attach custom javascript code to each of these events
	 *
	 * @return TDatePickerClientScript javascript validator event options.
	 */
	public function getClientSide()
	{
		if(is_null($this->_clientScript))
			$this->_clientScript = $this->createClientScript();
		return $this->_clientScript;
	}

	/**
	 * @return TDatePickerClientScript javascript validator event options.
	 */
	protected function createClientScript()
	{
		return new TDatePickerClientScript;
	}

	/**
	 * Returns the value to be validated.
	 * This methid is required by IValidatable interface.
	 * @return integer the interger timestamp if valid, otherwise the original text.
	 */
	public function getValidationPropertyValue()
	{
		if(($text = $this->getText()) === '')
			return '';
		$date = $this->getTimeStamp();
		return $date == null ? $text : $date;
	}

	/**
	 * Publish the date picker Css asset files.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->publishCalendarStyle();
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by adding
	 * additional date picker button if Mode is Button or ImageButton.
	 * @param THtmlWriter writer
	 */
	public function render($writer)
	{
		if($this->getInputMode() == TDatePickerInputMode::TextBox)
		{
			parent::render($writer);
			$this->renderDatePickerButtons($writer);
		}
		else
		{
			$this->renderDropDownListCalendar($writer);
			if($this->hasDayPattern())
			{
				$this->registerCalendarClientScript();
				$this->renderDatePickerButtons($writer);
			}
		}
	}

	/**
	 * Renders the date picker popup buttons.
	 */
	protected function renderDatePickerButtons($writer)
	{
		if($this->getShowCalendar() && $this->getEnabled(true))
		{
			switch ($this->getMode())
			{
				case TDatePickerMode::Button: $this->renderButtonDatePicker($writer); break;
				case TDatePickerMode::ImageButton : $this->renderImageButtonDatePicker($writer); break;
			}
		}
	}

	/**
	 * Loads user input data. Override parent implementation, when InputMode
	 * is DropDownList call getDateFromPostData to get date data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the component has been changed
	 */
	public function loadPostData($key,$values)
	{
		if($this->getInputMode() == TDatePickerInputMode::TextBox)
			return parent::loadPostData($key, $values);
		$value = $this->getDateFromPostData($key, $values);
		if(!$this->getReadOnly() && $this->getText()!==$value)
		{
			$this->setText($value);
			return true;
		}
		else
			return false;
	}

	/**
	 * Loads date from drop down list data.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return array the date selected
	 */
	protected function getDateFromPostData($key, $values)
	{
		$date = @getdate();

		if(isset($values[$key.'$day']))
			$day = intval($values[$key.'$day']);
		else
			$day = 1;

		if(isset($values[$key.'$month']))
			$month = intval($values[$key.'$month']) + 1;
		else
			$month = $date['mon'];

		if(isset($values[$key.'$year']))
			$year = intval($values[$key.'$year']);
		else
			$year = $date['year'];

		$s = Prado::createComponent('System.Util.TDateTimeStamp');
		$date = $s->getTimeStamp(0, 0, 0, $month, $day, $year);
		//$date = @mktime(0, 0, 0, $month, $day, $year);

		$pattern = $this->getDateFormat();
		$pattern = str_replace(array('MMMM', 'MMM'), array('MM','MM'), $pattern);
		$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter', $pattern);
		return $formatter->format($date);
	}

	/**
	 * Get javascript date picker options.
	 * @return array date picker client-side options
	 */
	protected function getDatePickerOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['InputMode'] = $this->getInputMode();
		$options['Format'] = $this->getDateFormat();
		$options['FirstDayOfWeek'] = $this->getFirstDayOfWeek();
		if(($cssClass=$this->getCssClass())!=='')
			$options['ClassName'] = $cssClass;
		$options['CalendarStyle'] = $this->getCalendarStyle();
		$options['FromYear'] = $this->getFromYear();
		$options['UpToYear'] = $this->getUpToYear();
		if($this->getMode()!==TDatePickerMode::Basic)
			$options['Trigger'] = $this->getDatePickerButtonID();

		$options = array_merge($options, $this->getCulturalOptions());
		if(!is_null($this->_clientScript))
			$options = array_merge($options,
				$this->_clientScript->getOptions()->toArray());
		return $options;
	}

	/**
	 * Get javascript localization options, e.g. month and weekday names.
	 * @return array localization options.
	 */
	protected function getCulturalOptions()
	{
		if($this->getCurrentCulture() == 'en')
			return array();

		$date = $this->getLocalizedCalendarInfo();
		$options['MonthNames'] = TJavaScript::encode($date->getMonthNames(),false);
		$options['AbbreviatedMonthNames'] = TJavaScript::encode($date->getAbbreviatedMonthNames(),false);
		$options['ShortWeekDayNames'] = TJavaScript::encode($date->getAbbreviatedDayNames(),false);

		return $options;
	}

	/**
	 * @return string the current culture, falls back to application if culture is not set.
	 */
	protected function getCurrentCulture()
	{
		$app = $this->getApplication()->getGlobalization(false);
		return $this->getCulture() == '' ?
				($app ? $app->getCulture() : 'en') : $this->getCulture();
	}

	/**
	 * @return DateTimeFormatInfo date time format information for the current culture.
	 */
	protected function getLocalizedCalendarInfo()
	{
		//expensive operations
		$culture = $this->getCurrentCulture();
		Prado::using('System.I18N.core.DateTimeFormatInfo');
		$info = Prado::createComponent('System.I18N.core.CultureInfo', $culture);
		return $info->getDateTimeFormat();
	}

	/**
	 * Renders the drop down list date picker.
	 */
	protected function renderDropDownListCalendar($writer)
	{
		if($this->getMode() == TDatePickerMode::Basic)
			$this->setMode(TDatePickerMode::ImageButton);
		parent::addAttributesToRender($writer);
		$writer->removeAttribute('name');
		$writer->removeAttribute('type');
		$writer->addAttribute('id', $this->getClientID());

		if(strlen($class = $this->getCssClass()) > 0)
			$writer->addAttribute('class', $class);
		$writer->renderBeginTag('span');

		$s = Prado::createComponent('System.Util.TDateTimeStamp');
		$date = $s->getDate($this->getTimeStampFromText());
		//$date = @getdate($this->getTimeStampFromText());

		$this->renderCalendarSelections($writer, $date);

		//render a hidden input field
		$writer->addAttribute('name', $this->getUniqueID());
		$writer->addAttribute('type', 'hidden');
		$writer->addAttribute('value', $this->getText());
		$writer->renderBeginTag('input');

		$writer->renderEndTag();
		$writer->renderEndTag();
	}

	protected function hasDayPattern()
	{
		$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter',
						$this->getDateFormat());
		return !is_null($formatter->getDayPattern());
	}

	/**
	 * Renders the calendar drop down list depending on the DateFormat pattern.
	 * @param THtmlWriter the Html writer to render the drop down lists.
	 * @param array the current selected date
	 */
	protected function renderCalendarSelections($writer, $date)
	{
		$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter',
						$this->getDateFormat());

		foreach($formatter->getDayMonthYearOrdering() as $type)
		{
			if($type == 'day')
				$this->renderCalendarDayOptions($writer,$date['mday']);
			elseif($type == 'month')
				$this->renderCalendarMonthOptions($writer,$date['mon']);
			elseif($type == 'year')
				$this->renderCalendarYearOptions($writer,$date['year']);
		}
	}

	/**
	 * Gets the date from the text input using TSimpleDateFormatter
	 * @return integer current selected date timestamp
	 */
	protected function getTimeStampFromText()
	{
		$pattern = $this->getDateFormat();
		$pattern = str_replace(array('MMMM', 'MMM'), array('MM','MM'), $pattern);
		$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter',$pattern);
		return $formatter->parse($this->getText());
	}

	/**
	 * Renders a drop down lists.
	 * @param THtmlWriter the writer used for the rendering purpose
	 * @param array list of selection options
	 * @param mixed selected key.
	 */
	private function renderDropDownListOptions($writer,$options,$selected=null)
	{
		foreach($options as $k => $v)
		{
			$writer->addAttribute('value', $k);
			if($k == $selected)
				$writer->addAttribute('selected', 'selected');
			$writer->renderBeginTag('option');
			$writer->write($v);
			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the day drop down list options.
	 * @param THtmlWriter the writer used for the rendering purpose
	 * @param mixed selected day.
	 */
	protected function renderCalendarDayOptions($writer, $selected=null)
	{
		$days = $this->getDropDownDayOptions();
		$writer->addAttribute('id', $this->getClientID().'_day');
		$writer->addAttribute('name', $this->getUniqueID().'$day');
		$writer->addAttribute('class', 'datepicker_day_options');
		if($this->getReadOnly() || !$this->getEnabled(true))
			$writer->addAttribute('disabled', 'disabled');
		$writer->renderBeginTag('select');
		$this->renderDropDownListOptions($writer, $days, $selected);
		$writer->renderEndTag();
	}

	/**
	 * @return array list of day options for a drop down list.
	 */
	protected function getDropDownDayOptions()
	{
		$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter',
						$this->getDateFormat());
		$days = array();
		$requiresPadding = $formatter->getDayPattern() === 'dd';
		for($i=1;$i<=31;$i++)
		{
			$days[$i] = $requiresPadding ? str_pad($i, 2, '0', STR_PAD_LEFT) : $i;
		}
		return $days;
	}

	/**
	 * Renders the month drop down list options.
	 * @param THtmlWriter the writer used for the rendering purpose
	 * @param mixed selected month.
	 */
	protected function renderCalendarMonthOptions($writer, $selected=null)
	{
		$info = $this->getLocalizedCalendarInfo();
		$writer->addAttribute('id', $this->getClientID().'_month');
		$writer->addAttribute('name', $this->getUniqueID().'$month');
		$writer->addAttribute('class', 'datepicker_month_options');
		if($this->getReadOnly() || !$this->getEnabled(true))
			$writer->addAttribute('disabled', 'disabled');
		$writer->renderBeginTag('select');
		$this->renderDropDownListOptions($writer,
					$this->getLocalizedMonthNames($info), $selected-1);
		$writer->renderEndTag();
	}

	/**
	 * Returns the localized month names that depends on the month format pattern.
	 * "MMMM" will return the month names, "MM" or "MMM" return abbr. month names
	 * and "M" return month digits.
	 * @param DateTimeFormatInfo localized date format information.
	 * @return array localized month names.
	 */
	protected function getLocalizedMonthNames($info)
	{
		$formatter = Prado::createComponent('System.Util.TSimpleDateFormatter',
						$this->getDateFormat());
		switch($formatter->getMonthPattern())
		{
			case 'MMM': return $info->getAbbreviatedMonthNames();
			case 'MM':
				$array = array();
				for($i=1;$i<=12;$i++)
						$array[$i-1] = $i < 10 ? '0'.$i : $i;
				return $array;
			case 'M':
				$array = array(); for($i=1;$i<=12;$i++) $array[$i-1] = $i;
				return $array;
			default :	return $info->getMonthNames();
		}
	}

	/**
	 * Renders the year drop down list options.
	 * @param THtmlWriter the writer used for the rendering purpose
	 * @param mixed selected year.
	 */
	protected function renderCalendarYearOptions($writer, $selected=null)
	{
		$years = array();
		for($i = $this->getFromYear(); $i <= $this->getUpToYear(); $i++)
			$years[$i] = $i;
		$writer->addAttribute('id', $this->getClientID().'_year');
		$writer->addAttribute('name', $this->getUniqueID().'$year');
		if($this->getReadOnly() || !$this->getEnabled(true))
			$writer->addAttribute('disabled', 'disabled');
		$writer->renderBeginTag('select');
		$writer->addAttribute('class', 'datepicker_year_options');
		$this->renderDropDownListOptions($writer, $years, $selected);
		$writer->renderEndTag();
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
		$writer->renderEndTag();
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
		$writer->addAttribute('alt', ' ');
		$writer->addAttribute('class', $this->getCssClass().' TDatePickerImageButton');
		$writer->renderBeginTag('img');
		$writer->renderEndTag();
	}

	/**
	 * Publish the default button image asset file.
	 * @return string image file url.
	 */
	protected function publishDefaultButtonImage()
	{
		$image = 'System.Web.Javascripts.datepicker.calendar';
		if(($file =  Prado::getPathOfNamespace($image, '.png'))!==null)
			return $this->publishFilePath($file);
		else
			throw new TConfigurationException('datepicker_defaultbuttonimage_invalid',$image);
	}

	/**
	 * Publish the calendar style Css asset file.
	 * @return string Css file url.
	 */
	protected function publishCalendarStyle()
	{
		$cs = $this->getPage()->getClientScript();
		$style = 'System.Web.Javascripts.datepicker.'.$this->getCalendarStyle();
		if(($cssFile=Prado::getPathOfNamespace($style,'.css'))!==null)
		{
			$url = $this->publishFilePath($cssFile);
			if(!$cs->isStyleSheetFileRegistered($style))
				$cs->registerStyleSheetFile($style, $url);
			return $url;
		}
		else
			throw new TConfigurationException('datepicker_calendarstyle_invalid',$style);
	}

	/**
	 * Publish the spacer.gif for IE iframe source.
	 * @return string the URL for the spacer.gif.
	 */
	protected function publishIFrameSpacer()
	{
		$cs = $this->getPage()->getClientScript();
		$spacer = 'System.Web.Javascripts.datepicker.spacer';
		if(($file = Prado::getPathOfNamespace($spacer,'.gif')) != null)
			return $this->publishFilePath($file);
	}

	/**
	 * Add the client id to the input textbox, and register the client scripts.
	 * @param THtmlWriter writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id',$this->getClientID());
		$this->registerCalendarClientScript();
	}


	/**
	 * Registers the javascript code to initialize the date picker.
	 */
	protected function registerCalendarClientScript()
	{
		if($this->getShowCalendar())
		{
			$cs = $this->getPage()->getClientScript();
			$cs->registerPradoScript("datepicker");

			if(!$cs->isEndScriptRegistered('TDatePicker.spacer'))
			{
				$spacer = $this->publishIFrameSpacer();
				$code = "Prado.WebUI.TDatePicker.spacer = '$spacer';";
				$cs->registerEndScript('TDatePicker.spacer', $code);
			}

			$options = TJavaScript::encode($this->getDatePickerOptions());
			$code = "new Prado.WebUI.TDatePicker($options);";
			$cs->registerEndScript("prado:".$this->getClientID(), $code);
		}
	}
}

/**
 * TDatePickerClientScript class.
 *
 * Client-side date picker event {@link setOnDateChanged OnDateChanged}
 * can be modified through the {@link TDatePicker:: getClientSide ClientSide}
 * property of a date picker.
 *
 * The <tt>OnDateChanged</tt> event is raise when the date picker's date
 * is changed.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TDatePickerClientScript extends TClientSideOptions
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


/**
 * TDatePickerInputMode class.
 * TDatePickerInputMode defines the enumerable type for the possible datepicker input methods.
 *
 * The following enumerable values are defined:
 * - TextBox: text boxes are used to input date values
 * - DropDownList: dropdown lists are used to pick up date values
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TDatePickerInputMode extends TEnumerable
{
	const TextBox='TextBox';
	const DropDownList='DropDownList';
}

/**
 * TDatePickerMode class.
 * TDatePickerMode defines the enumerable type for the possible UI mode
 * that a {@link TDatePicker} control can take.
 *
 * The following enumerable values are defined:
 * - Basic: Only shows a text input, focusing on the input shows the date picker
 * - Button: Shows a button next to the text input, clicking on the button shows the date, button text can be by the
 * - ImageButton: Shows an image next to the text input, clicking on the image shows the date picker,
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TDatePickerMode extends TEnumerable
{
	const Basic='Basic';
	const Button='Button';
	const ImageButton='ImageButton';
}

?>