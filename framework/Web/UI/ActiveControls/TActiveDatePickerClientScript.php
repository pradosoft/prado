<?php
/**
 * TActiveDatePicker class file
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.ActiveControls
 */

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