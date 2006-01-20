<?php

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
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

	protected function OnPreRender($param)
	{
		parent::onPreRender($param);
		$this->publishCalendarStyle();
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by replacing
	 * the body content with syntax highlighted result.
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

	protected function getDatePickerButtonID()
	{
		return $this->getClientID().'button';
	}

	protected function renderButtonDatePicker($writer)
	{
		$writer->addAttribute('id', $this->getDatePickerButtonID());		
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('class', $this->getCssClass().' TDatePickerButton');
		$writer->addAttribute('value',$this->getButtonText());
		$writer->renderBeginTag("input");
	}

	protected function renderImageButtonDatePicker($writer)
	{
		$url = $this->getButtonImageUrl();
		$url = empty($url) ? $this->publishDefaultButtonImage() : $url;
		$writer->addAttribute('id', $this->getDatePickerButtonID());
		$writer->addAttribute('src', $url);
		$writer->addAttribute('class', $this->getCssClass().' TDatePickerImageButton');
		$writer->renderBeginTag('img');
	}

	protected function publishDefaultButtonImage()
	{
		$cs = $this->getPage()->getClientScript();
		$image = 'System.Web.Javascripts.datepicker.calendar';
		$file =  Prado::getPathOfNamespace($image, '.png');
		return $this->getService()->getAsset($file);
	}

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