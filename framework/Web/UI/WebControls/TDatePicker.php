<?php
/**
 * TDatePicker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TControl;
use Prado\Util\TSimpleDateFormatter;
use Prado\I18N\core\CultureInfo;

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
 * TDatePicker has four <b>Mode</b> to show the date picker popup.
 *
 *  # <b>Basic</b> -- Only shows a text input, focusing on the input shows the
 *                    date picker. This way you can access the popup using only
 *                    the keyboard. Note that because of this, TAB-bing through
 *                    this control will automatically select the current date if
 *                    no previous date was selected. If you close the popup (eg.
 *                    pressing the ESC key) you'll need to un-focus and re-focus
 *                    the control again for the popup to reappear.
 *  # <b>Clickable</b> -- Only shows a text input, clicking on the input shows the
 *                    date picker. This mode solves the two small problems of the
 *                    Basic mode. It was first introduced in Prado 3.2.
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
 * In <b>DropDownList</b> mode, in addition to the popup date picker, three
 * drop down list (day, month and year) are presented to select the date .
 *
 * The <b>PositionMode</b> property can be set to "Top" or "Bottom" with default
 * as "Bottom". It specifies the position of the calendar popup, relative to the
 * input field.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDatePicker extends TTextBox
{
	/**
	 * Script path relative to the TClientScriptManager::SCRIPT_PATH
	 */
	const SCRIPT_PATH = 'datepicker';

	/**
	 * @var TDatePickerClientScript validator client-script options.
	 */
	private $_clientScript;
	/**
	 * AutoPostBack is not supported.
	 * @param mixed $value
	 */
	public function setAutoPostBack($value)
	{
		throw new TNotSupportedException(
			'tdatepicker_autopostback_unsupported',
			get_class($this)
		);
	}

	/**
	 * @return string the format of the date string
	 */
	public function getDateFormat()
	{
		return $this->getViewState('DateFormat', 'dd-MM-yyyy');
	}

	/**
	 * Sets the format of the date string.
	 * @param string $value the format of the date string
	 */
	public function setDateFormat($value)
	{
		$this->setViewState('DateFormat', $value, 'dd-MM-yyyy');
	}

	/**
	 * @return bool whether the calendar window should pop up when the control receives focus
	 */
	public function getShowCalendar()
	{
		return $this->getViewState('ShowCalendar', true);
	}

	/**
	 * Sets whether to pop up the calendar window when the control receives focus
	 * @param bool $value whether to show the calendar window
	 */
	public function setShowCalendar($value)
	{
		$this->setViewState('ShowCalendar', TPropertyValue::ensureBoolean($value), true);
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
	 * @param string $value a culture string, e.g. en_AU.
	 */
	public function setCulture($value)
	{
		$this->setViewState('Culture', $value, '');
	}

	/**
	 * @param TDatePickerInputMode $value input method of date values
	 */
	public function setInputMode($value)
	{
		$this->setViewState('InputMode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TDatePickerInputMode'), TDatePickerInputMode::TextBox);
	}

	/**
	 * @return TDatePickerInputMode input method of date values. Defaults to TDatePickerInputMode::TextBox.
	 */
	public function getInputMode()
	{
		return $this->getViewState('InputMode', TDatePickerInputMode::TextBox);
	}

	/**
	 * @param TDatePickerMode $value calendar UI mode
	 */
	public function setMode($value)
	{
		$this->setViewState('Mode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TDatePickerMode'), TDatePickerMode::Basic);
	}

	/**
	 * @return TDatePickerMode current calendar UI mode.
	 */
	public function getMode()
	{
		return $this->getViewState('Mode', TDatePickerMode::Basic);
	}
	/**
	 * @param string $value the image url for "Image" UI mode.
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
	 * @param string $value set the calendar style
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
	 * @param int $value 0 for Sunday, 1 for Monday, 2 for Tuesday, etc.
	 */
	public function setFirstDayOfWeek($value)
	{
		$this->setViewState('FirstDayOfWeek', TPropertyValue::ensureInteger($value), 1);
	}

	/**
	 * @return int first day of the week
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
	 * @param string $value text for the date picker button
	 */
	public function setButtonText($value)
	{
		$this->setViewState('ButtonText', $value, '...');
	}

	/**
	 * @param int $value date picker starting year, default is 2000.
	 */
	public function setFromYear($value)
	{
		$this->setViewState('FromYear', TPropertyValue::ensureInteger($value), (int) (@date('Y')) - 5);
	}

	/**
	 * @return int date picker starting year, default is -5 years
	 */
	public function getFromYear()
	{
		return $this->getViewState('FromYear', (int) (@date('Y')) - 5);
	}

	/**
	 * @param int $value date picker ending year, default +10 years
	 */
	public function setUpToYear($value)
	{
		$this->setViewState('UpToYear', TPropertyValue::ensureInteger($value), (int) (@date('Y')) + 10);
	}

	/**
	 * @return int date picker ending year, default +10 years
	 */
	public function getUpToYear()
	{
		return $this->getViewState('UpToYear', (int) (@date('Y')) + 10);
	}

	/**
	 * @param TDatePickerPositionMode $value calendar UI position
	 */
	public function setPositionMode($value)
	{
		$this->setViewState('PositionMode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TDatePickerPositionMode'), TDatePickerPositionMode::Bottom);
	}

	/**
	 * @return TDatePickerPositionMode current calendar UI position.
	 */
	public function getPositionMode()
	{
		return $this->getViewState('PositionMode', TDatePickerPositionMode::Bottom);
	}

	/**
	 * @return int current selected date from the date picker as timestamp, NULL if timestamp is not set previously.
	 */
	public function getTimeStamp()
	{
		if (trim($this->getText()) === '') {
			return null;
		} else {
			return $this->getTimeStampFromText();
		}
	}

	/**
	 * Sets the date for the date picker using timestamp.
	 * @param float $value time stamp for the date picker
	 */
	public function setTimeStamp($value)
	{
		if ($value === null || (is_string($value) && trim($value) === '')) {
			$this->setText('');
		} else {
			$date = TPropertyValue::ensureFloat($value);
			$formatter = new TSimpleDateFormatter($this->getDateFormat());
			$this->setText($formatter->format($date));
		}
	}

	/**
	 * Returns the timestamp selected by the user.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getTimeStamp()}.
	 * @return int the timestamp of the TDatePicker control.
	 * @see getTimeStamp
	 * @since 3.1.2
	 */
	public function getData()
	{
		return $this->getTimeStamp();
	}

	/**
	 * Sets the timestamp represented by this control.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setTimeStamp()}.
	 * @param int $value the timestamp of the TDatePicker control.
	 * @see setTimeStamp
	 * @since 3.1.2
	 */
	public function setData($value)
	{
		$this->setTimeStamp($value);
	}

	/**
	 * @return string the date string.
	 */
	public function getDate()
	{
		return $this->getText();
	}

	/**
	 * @param string $value date string
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
		if ($this->_clientScript === null) {
			$this->_clientScript = $this->createClientScript();
		}
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
	 * This methid is required by \Prado\Web\UI\IValidatable interface.
	 * @return int the interger timestamp if valid, otherwise the original text.
	 */
	public function getValidationPropertyValue()
	{
		if (($text = $this->getText()) === '') {
			return '';
		}
		$date = $this->getTimeStamp();
		return $date == null ? $text : $date;
	}

	/**
	 * Publish the date picker Css asset files.
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->publishCalendarStyle();
		$this->registerCalendarClientScriptPre();
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by adding
	 * additional date picker button if Mode is Button or ImageButton.
	 * @param THtmlWriter $writer writer
	 */
	public function render($writer)
	{
		if ($this->getInputMode() == TDatePickerInputMode::TextBox) {
			parent::render($writer);
			$this->renderDatePickerButtons($writer);
		} else {
			$this->renderDropDownListCalendar($writer);
			if ($this->hasDayPattern()) {
				$this->renderClientControlScript($writer);
				$this->renderDatePickerButtons($writer);
			}
		}
	}

	/**
	 * Renders the date picker popup buttons.
	 * @param mixed $writer
	 */
	protected function renderDatePickerButtons($writer)
	{
		if ($this->getShowCalendar()) {
			switch ($this->getMode()) {
				case TDatePickerMode::Button:
					$this->renderButtonDatePicker($writer);
					break;
				case TDatePickerMode::ImageButton:
					$this->renderImageButtonDatePicker($writer);
					break;
			}
		}
	}

	/**
	 * Loads user input data. Override parent implementation, when InputMode
	 * is DropDownList call getDateFromPostData to get date data.
	 * This method is primarly used by framework developers.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the data of the component has been changed
	 */
	public function loadPostData($key, $values)
	{
		if ($this->getInputMode() == TDatePickerInputMode::TextBox) {
			return parent::loadPostData($key, $values);
		}
		$value = $this->getDateFromPostData($key, $values);
		if (!$this->getReadOnly() && $this->getText() !== $value) {
			$this->setText($value);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Loads date from drop down list data.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return array the date selected
	 */
	protected function getDateFromPostData($key, $values)
	{
		$date = @getdate();

		$pattern = $this->getDateFormat();
		$pattern = str_replace(['MMMM', 'MMM'], ['MM', 'MM'], $pattern);
		$formatter = new TSimpleDateFormatter($pattern);

		$order = $formatter->getDayMonthYearOrdering();

		if (isset($values[$key . '$day'])) {
			$day = (int) ($values[$key . '$day']);
		} elseif (in_array('day', $order)) {
			$day = $date['mday'];
		} else {
			$day = 1;
		}

		if (isset($values[$key . '$month'])) {
			$month = (int) ($values[$key . '$month']) + 1;
		} else {
			$month = $date['mon'];
		}

		if (isset($values[$key . '$year'])) {
			$year = (int) ($values[$key . '$year']);
		} else {
			$year = $date['year'];
		}

		$s = new \DateTime;
		$s->setDate($year, $month, $day);
		$s->setTime(0, 0, 0);
		$date = $s->getTimeStamp();
		//$date = @mktime(0, 0, 0, $month, $day, $year);

		$pattern = $this->getDateFormat();
		$pattern = str_replace(['MMMM', 'MMM'], ['MM', 'MM'], $pattern);

		$formatter = new TSimpleDateFormatter($pattern);
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
		if (($cssClass = $this->getCssClass()) !== '') {
			$options['ClassName'] = $cssClass;
		}
		$options['CalendarStyle'] = $this->getCalendarStyle();
		$options['FromYear'] = $this->getFromYear();
		$options['UpToYear'] = $this->getUpToYear();
		switch ($this->getMode()) {
			case TDatePickerMode::Basic:
				break;
			case TDatePickerMode::Clickable:
				$options['TriggerEvent'] = "click";
				break;
			default:
				$options['Trigger'] = $this->getDatePickerButtonID();
				break;
		}
		$options['PositionMode'] = $this->getPositionMode();

		$options = array_merge($options, $this->getCulturalOptions());
		if ($this->_clientScript !== null) {
			$options = array_merge(
				$options,
				$this->_clientScript->getOptions()->toArray()
			);
		}
		return $options;
	}

	/**
	 * Get javascript localization options, e.g. month and weekday names.
	 * @return array localization options.
	 */
	protected function getCulturalOptions()
	{
		if ($this->getCurrentCulture() == 'en') {
			return [];
		}

		$info = $this->getLocalizedCalendarInfo();
		$options['MonthNames'] = $info->findInfo('calendar/gregorian/monthNames/format/wide');
		$options['AbbreviatedMonthNames'] = $info->findInfo('calendar/gregorian/monthNames/format/abbreviated');
		$options['ShortWeekDayNames'] = $info->findInfo('calendar/gregorian/dayNames/format/abbreviated');

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
		$info = new CultureInfo($culture);
		return $info;
	}

	/**
	 * Renders the drop down list date picker.
	 * @param mixed $writer
	 */
	protected function renderDropDownListCalendar($writer)
	{
		if ($this->getMode() == TDatePickerMode::Basic) {
			$this->setMode(TDatePickerMode::ImageButton);
		}
		parent::addAttributesToRender($writer);
		$writer->removeAttribute('name');
		$writer->removeAttribute('type');
		$writer->addAttribute('id', $this->getClientID());

		if (strlen($class = $this->getCssClass()) > 0) {
			$writer->addAttribute('class', $class);
		}
		$writer->renderBeginTag('span');

		$date = new \DateTime;
		$date->setTimeStamp($this->getTimeStampFromText());
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
		$formatter = new TSimpleDateFormatter($this->getDateFormat());
		return ($formatter->getDayPattern() !== null);
	}

	/**
	 * Renders the calendar drop down list depending on the DateFormat pattern.
	 * @param THtmlWriter $writer the Html writer to render the drop down lists.
	 * @param array $date the current selected date
	 */
	protected function renderCalendarSelections($writer, $date)
	{
		$formatter = new TSimpleDateFormatter($this->getDateFormat());

		foreach ($formatter->getDayMonthYearOrdering() as $type) {
			if ($type == 'day') {
				$this->renderCalendarDayOptions($writer, $date->format('j'));
			} elseif ($type == 'month') {
				$this->renderCalendarMonthOptions($writer, $date->format('n'));
			} elseif ($type == 'year') {
				$this->renderCalendarYearOptions($writer, $date->format('Y'));
			}
		}
	}

	/**
	 * Gets the date from the text input using TSimpleDateFormatter
	 * @return int current selected date timestamp
	 */
	protected function getTimeStampFromText()
	{
		$pattern = $this->getDateFormat();
		$pattern = str_replace(['MMMM', 'MMM'], ['MM', 'MM'], $pattern);
		$formatter = new TSimpleDateFormatter($pattern);
		return $formatter->parse($this->getText());
	}

	/**
	 * Renders a drop down lists.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 * @param array $options list of selection options
	 * @param null|mixed $selected selected key.
	 */
	private function renderDropDownListOptions($writer, $options, $selected = null)
	{
		foreach ($options as $k => $v) {
			$writer->addAttribute('value', $k);
			if ($k == $selected) {
				$writer->addAttribute('selected', 'selected');
			}
			$writer->renderBeginTag('option');
			$writer->write($v);
			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the day drop down list options.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 * @param null|mixed $selected selected day.
	 */
	protected function renderCalendarDayOptions($writer, $selected = null)
	{
		$days = $this->getDropDownDayOptions();
		$writer->addAttribute('id', $this->getClientID() . TControl::CLIENT_ID_SEPARATOR . 'day');
		$writer->addAttribute('name', $this->getUniqueID() . TControl::ID_SEPARATOR . 'day');
		$writer->addAttribute('class', 'datepicker_day_options');
		if ($this->getReadOnly() || !$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}
		$writer->renderBeginTag('select');
		$this->renderDropDownListOptions($writer, $days, $selected);
		$writer->renderEndTag();
	}

	/**
	 * @return array list of day options for a drop down list.
	 */
	protected function getDropDownDayOptions()
	{
		$formatter = new TSimpleDateFormatter($this->getDateFormat());
		$days = [];
		$requiresPadding = $formatter->getDayPattern() === 'dd';
		for ($i = 1; $i <= 31; $i++) {
			$days[$i] = $requiresPadding ? str_pad($i, 2, '0', STR_PAD_LEFT) : $i;
		}
		return $days;
	}

	/**
	 * Renders the month drop down list options.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 * @param null|mixed $selected selected month.
	 */
	protected function renderCalendarMonthOptions($writer, $selected = null)
	{
		$info = $this->getLocalizedCalendarInfo();
		$writer->addAttribute('id', $this->getClientID() . TControl::CLIENT_ID_SEPARATOR . 'month');
		$writer->addAttribute('name', $this->getUniqueID() . TControl::ID_SEPARATOR . 'month');
		$writer->addAttribute('class', 'datepicker_month_options');
		if ($this->getReadOnly() || !$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}
		$writer->renderBeginTag('select');
		$this->renderDropDownListOptions(
			$writer,
			$this->getLocalizedMonthNames($info),
			$selected - 1
		);
		$writer->renderEndTag();
	}

	/**
	 * Returns the localized month names that depends on the month format pattern.
	 * "MMMM" will return the month names, "MM" or "MMM" return abbr. month names
	 * and "M" return month digits.
	 * @param DateTimeFormatInfo $info localized date format information.
	 * @return array localized month names.
	 */
	protected function getLocalizedMonthNames($info)
	{
		$formatter = new TSimpleDateFormatter($this->getDateFormat());
		switch ($formatter->getMonthPattern()) {
			case 'MMM': return $info->findInfo('calendar/gregorian/monthNames/format/abbreviated');
			case 'MM':
				$array = [];
				for ($i = 1; $i <= 12; $i++) {
					$array[$i - 1] = $i < 10 ? '0' . $i : $i;
				}
				return $array;
			case 'M':
				$array = [];
				for ($i = 1; $i <= 12; $i++) {
					$array[$i - 1] = $i;
				}
				return $array;
			default:
				return $info->findInfo('calendar/gregorian/monthNames/format/wide');
		}
	}

	/**
	 * Renders the year drop down list options.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 * @param null|mixed $selected selected year.
	 */
	protected function renderCalendarYearOptions($writer, $selected = null)
	{
		$years = [];
		for ($i = $this->getFromYear(); $i <= $this->getUpToYear(); $i++) {
			$years[$i] = $i;
		}
		$writer->addAttribute('id', $this->getClientID() . TControl::CLIENT_ID_SEPARATOR . 'year');
		$writer->addAttribute('name', $this->getUniqueID() . TControl::ID_SEPARATOR . 'year');
		$writer->addAttribute('class', 'datepicker_year_options');
		if ($this->getReadOnly() || !$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}
		$writer->renderBeginTag('select');
		$this->renderDropDownListOptions($writer, $years, $selected);
		$writer->renderEndTag();
	}

	/**
	 * Gets the ID for the date picker trigger button.
	 * @return string unique button ID
	 */
	protected function getDatePickerButtonID()
	{
		return $this->getClientID() . 'button';
	}

	/**
	 * Adds an additional button such that when clicked it shows the date picker.
	 * @param mixed $writer
	 * @return THtmlWriter writer
	 */
	protected function renderButtonDatePicker($writer)
	{
		$writer->addAttribute('id', $this->getDatePickerButtonID());
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('class', $this->getCssClass() . ' TDatePickerButton');
		$writer->addAttribute('value', $this->getButtonText());
		if (!$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}
		$writer->renderBeginTag("input");
		$writer->renderEndTag();
	}

	/**
	 * Adds an additional image button such that when clicked it shows the date picker.
	 * @param mixed $writer
	 * @return THtmlWriter writer
	 */
	protected function renderImageButtonDatePicker($writer)
	{
		$url = $this->getButtonImageUrl();
		$url = empty($url) ? $this->getAssetUrl('calendar.png') : $url;
		$writer->addAttribute('id', $this->getDatePickerButtonID());
		$writer->addAttribute('src', $url);
		$writer->addAttribute('alt', ' ');
		$writer->addAttribute('class', $this->getCssClass() . ' TDatePickerImageButton');
		if (!$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}
		$writer->addAttribute('type', 'image');
		$writer->addAttribute('onclick', 'return false;');
		$writer->renderBeginTag('input');
		$writer->renderEndTag();
	}

	/**
	 * @param string $file date picker asset file in the self::SCRIPT_PATH directory.
	 * @return string date picker asset url.
	 */
	protected function getAssetUrl($file = '')
	{
		$base = $this->getPage()->getClientScript()->getPradoScriptAssetUrl();
		return $base . '/' . self::SCRIPT_PATH . '/' . $file;
	}

	/**
	 * Publish the calendar style Css asset file.
	 * @return string Css file url.
	 */
	protected function publishCalendarStyle()
	{
		$url = $this->getAssetUrl($this->getCalendarStyle() . '.css');
		$cs = $this->getPage()->getClientScript();
		if (!$cs->isStyleSheetFileRegistered($url)) {
			$cs->registerStyleSheetFile($url, $url);
		}
		return $url;
	}

	/**
	 * Add the client id to the input textbox, and register the client scripts.
	 * @param THtmlWriter $writer writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
	}

	/**
	 * Registers the javascript code to initialize the date picker.
	 */
	protected function registerCalendarClientScriptPre()
	{
		if ($this->getShowCalendar()) {
			$cs = $this->getPage()->getClientScript();
			$cs->registerPradoScript("datepicker");
		}
	}

	protected function renderClientControlScript($writer)
	{
		if ($this->getShowCalendar()) {
			$cs = $this->getPage()->getClientScript();
			if (!$cs->isEndScriptRegistered('TDatePicker.spacer')) {
				$spacer = $this->getAssetUrl('spacer.gif');
				$code = "Prado.WebUI.TDatePicker.spacer = '$spacer';";
				$cs->registerEndScript('TDatePicker.spacer', $code);
			}

			$options = TJavaScript::encode($this->getDatePickerOptions());
			$code = "new Prado.WebUI.TDatePicker($options);";
			$cs->registerEndScript("prado:" . $this->getClientID(), $code);
		}
	}
}
