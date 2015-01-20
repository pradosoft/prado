<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

/**
 * IPostBackDataHandler interface
 *
 * If a control wants to load post data, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
interface IPostBackDataHandler
{
	/**
	 * Loads user input data.
	 * The implementation of this function can use $values[$key] to get the user input
	 * data that are meant for the particular control.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the control has been changed
	 */
	public function loadPostData($key,$values);
	/**
	 * Raises postdata changed event.
	 * The implementation of this function should raise appropriate event(s) (e.g. OnTextChanged)
	 * indicating the control data is changed.
	 */
	public function raisePostDataChangedEvent();
	/**
	 * @return boolean whether postback causes the data change. Defaults to false for non-postback state.
	 */
	public function getDataChanged();
}