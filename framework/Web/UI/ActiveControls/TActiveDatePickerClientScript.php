<?php
/**
 * TActiveDatePicker class file
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TActiveDatePickerClientScript class.
 *
 * Client-side date picker event {@see setOnDateChanged OnDateChanged}
 * can be modified through the {@see \Prado\Web\UI\ActiveControls\TActiveDatePicker::getClientSide ClientSide}
 * property of a date picker.
 *
 * The <tt>OnDateChanged</tt> event is raise when the date picker's date
 * is changed.
 * The formatted date according to {@see \Prado\Web\UI\WebControls\TDatePicker::getDateFormat DateFormat} is sent
 * as parameter to this event
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 3.2.1
 */
class TActiveDatePickerClientScript extends TCallbackClientSide
{
	/**
	 * Javascript code to execute when the date picker's date is changed.
	 * @param string $javascript javascript code
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
