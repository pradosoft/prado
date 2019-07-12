<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * \Prado\Web\UI\IValidatable interface
 *
 * If a control wants to be validated by a validator, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
interface IValidatable
{
	/**
	 * @return mixed the value of the property to be validated.
	 */
	public function getValidationPropertyValue();
	/**
	 * @return bool wether this control's validators validated successfully (must default to true)
	 */
	public function getIsValid();
	/**
	 * @param mixed $value
	 * @return bool wether this control's validators validated successfully
	 */
	public function setIsValid($value);
}
