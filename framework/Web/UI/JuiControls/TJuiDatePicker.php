<?php
/**
 * TJuiDatePicker class file.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\TPropertyValue;
use Prado\Exceptions\TNotSupportedException;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\TActiveTextBox;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\WebControls\TTextBoxMode;
use Prado\Util\TSimpleDateFormatter;

/**
 * TJuiDatePicker class.
 *
 * TJuiDatePicker is a textbox that provides a date input. When the text box receives focus,
 * a calendar will pop up and users can pick up from it a date that will be automatically
 * entered into the text box. TJuiDatePicker is an extension to {@link TActivePanel} based on
 * jQuery-UI's {@link http://jqueryui.com/dialog/ Dialog} widget.
 *
 * <code>
 * <com:TJuiDatePicker ID="datepicker1" />
 * </code>
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\JuiControls
 * @since 3.3
 */
class TJuiDatePicker extends TActiveTextBox implements INamingContainer, IJuiOptions
{

	/**
	 * The static variable is used to determine if this is the first instance of TJuiDatePicker. If true,
	 * it will register an additional clientscript to set the language specific global default settings.
	 * @var bool true, if this is the first instance of TJuiDatePicker, false otherwise
	 */
	private static $_first = true;

	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TJuiControlAdapter($this));
	}

	/**
	 * @return string the name of the jQueryUI widget method
	 */
	public function getWidget()
	{
		return 'datepicker';
	}

	/**
	 * @return string the clientid of the jQueryUI widget element
	 */
	public function getWidgetID()
	{
		return $this->getClientID();
	}

	/**
	 * Object containing defined javascript options
	 * @return TJuiControlOptions
	 */
	public function getOptions()
	{
		if (($options = $this->getViewState('JuiOptions')) === null) {
			$options = new TJuiControlOptions($this);
			$this->setViewState('JuiOptions', $options);
		}
		return $options;
	}

	/**
	 * Array containing valid javascript options
	 * @return array()
	 */
	public function getValidOptions()
	{
		return ['altField', 'altFormat', 'appendText', 'autoSize', 'buttonImage', 'buttonImageOnly', 'buttonText', 'calculateWeek',
								 'changeMonth', 'changeYear', 'closeText', 'constrainInput', 'currentText', 'dateFormat', 'dayNames', 'dayNamesMin',
						 'dayNamesShort', 'defaultDate', 'duration', 'firstDay', 'gotoCurrent', 'hideIfNoPrevNext', 'isRTL', 'maxDate',
								 'minDate', 'monthNames', 'monthNamesShort', 'navigationAsDateFormat', 'nextText', 'numberOfMonths', 'prevText',
								 'selectOtherMonths', 'shortYearCutoff', 'showAnim', 'showButtonPanel', 'showCurrentAtPos', 'showMonthAfterYear',
						 'showOn', 'showOptions', 'showOtherMonths', 'showWeek', 'stepMonths', 'weekHeader', 'yearRange', 'yearSuffix',
								 'beforeShow', 'beforeShowDay', 'onChangeMonthYear', 'onClose', 'onSelect'];
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return [];
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control. Also registers language specific global
	 * settings for the first used date picker.
	 * @param mixed $writer
	 */
	protected function addAttributesToRender($writer)
	{
		$cs = $this->getPage()->getClientScript();
		if (self::$_first) {
			$culture = $this->getCurrentCulture();
			if ($culture != 'en') {
				$url = $this->getPage()->getClientScript()->getPradoScriptAssetUrl('jquery-ui') . "/ui/i18n/datepicker-{$culture}.js";
				$cs->registerScriptFile(sprintf('%08X', crc32($url)), $url);
			}
			$code = "jQuery(document).ready(function(){jQuery.datepicker.setDefaults(jQuery.datepicker.regional['{$culture}']);});";
			$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
			self::$_first = false;
		}
		parent::addAttributesToRender($writer);
		$options = TJavaScript::encode($this->getOptions()->toArray());
		$code = "jQuery('#" . $this->getWidgetID() . "')." . $this->getWidget() . "(" . $options . ");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}

	/**
	 * @return TTextBoxMode the behavior mode of the underlying {@link TTextBox} component.
	 * Fixed to TTextBoxMode::SingleLine for the TJuiDatePicker.
	 */
	public function getTextMode()
	{
		return TTextBoxMode::SingleLine;
	}

	/**
	 * Setting the behavior mode of the underlying TTextBox component is NOT supported.
	 * @param TTextBoxMode $value the text mode
	 * @throws TNotSupportedException not supported, fixed to TTextBoxMode::SingleLine.
	 */
	public function setTextMode($value)
	{
		throw new TNotSupportedException('juidatepicker_settextmode_unsupported');
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
	 * @return string the current culture, falls back to application if culture is not set.
	 */
	protected function getCurrentCulture()
	{
		$app = $this->getApplication()->getGlobalization(false);
		return $this->getCulture() == '' ?
				($app ? $app->getCulture() : 'en') : $this->getCulture();
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
	 * Returns the timestamp selected by the user.
	 * This method is required by {@link IDataRenderer}.
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
	 * This method is required by {@link IDataRenderer}.
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
	 * Returns the value to be validated.
	 * This methid is required by IValidatable interface.
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
}
