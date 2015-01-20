<?php
/**
 * TDatePicker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TDatePickerClientScript class.
 *
 * Client-side date picker event {@link setOnDateChanged OnDateChanged}
 * can be modified through the {@link TDatePicker::getClientSide ClientSide}
 * property of a date picker.
 *
 * The <tt>OnDateChanged</tt> event is raise when the date picker's date
 * is changed.
 * The formatted date according to {@link TDatePicker::getDateFormat DateFormat} is sent
 * as parameter to this event
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
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