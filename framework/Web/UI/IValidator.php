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
 * IValidator interface
 *
 * If a control wants to validate user input, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
interface IValidator
{
	/**
	 * Validates certain data.
	 * The implementation of this function should validate certain data
	 * (e.g. data entered into TTextBox control).
	 * @return boolean whether the data passes the validation
	 */
	public function validate();
	/**
	 * @return boolean whether the previous {@link validate()} is successful.
	 */
	public function getIsValid();
	/**
	 * @param boolean whether the validator validates successfully
	 */
	public function setIsValid($value);
	/**
	 * @return string error message during last validate
	 */
	public function getErrorMessage();
	/**
	 * @param string error message for the validation
	 */
	public function setErrorMessage($value);
}