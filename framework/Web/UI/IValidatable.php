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
 * IValidatable interface
 *
 * If a control wants to be validated by a validator, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
interface IValidatable
{
	/**
	 * @return mixed the value of the property to be validated.
	 */
	public function getValidationPropertyValue();
	/**
	 * @return boolean wether this control's validators validated successfully (must default to true)
	 */
	public function getIsValid();
	/**
	 * @return boolean wether this control's validators validated successfully
	 */
	public function setIsValid($value);
}