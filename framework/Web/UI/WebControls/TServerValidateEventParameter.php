<?php
/**
 * TCustomValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TServerValidateEventParameter class
 *
 * TServerValidateEventParameter encapsulates the parameter data for
 * <b>OnServerValidate</b> event of TCustomValidator components.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TServerValidateEventParameter extends TEventParameter
{
	/**
	 * the value to be validated
	 * @var string
	 */
	private $_value='';
	/**
	 * whether the value is valid
	 * @var boolean
	 */
	private $_isValid=true;

	/**
	 * Constructor.
	 * @param string property value to be validated
	 * @param boolean whether the value is valid
	 */
	public function __construct($value,$isValid)
	{
		$this->_value=$value;
		$this->setIsValid($isValid);
	}

	/**
	 * @return string value to be validated
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @return boolean whether the value is valid
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}

	/**
	 * @param boolean whether the value is valid
	 */
	public function setIsValid($value)
	{
		$this->_isValid=TPropertyValue::ensureBoolean($value);
	}
}