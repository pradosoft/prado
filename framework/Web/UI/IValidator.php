<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * IValidator interface
 *
 * If a control wants to validate user input, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface IValidator
{
	/**
	 * Validates certain data.
	 * The implementation of this function should validate certain data
	 * (e.g. data entered into TTextBox control).
	 * @return bool whether the data passes the validation
	 */
	public function validate();
	/**
	 * @return bool whether the previous {@see validate()} is successful.
	 */
	public function getIsValid();
	/**
	 * @param bool $value whether the validator validates successfully
	 */
	public function setIsValid($value);
	/**
	 * @return string error message during last validate
	 */
	public function getErrorMessage();
	/**
	 * @param string $value error message for the validation
	 */
	public function setErrorMessage($value);
}
