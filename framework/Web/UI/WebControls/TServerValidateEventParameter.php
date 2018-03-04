<?php
/**
 * TCustomValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TServerValidateEventParameter class
 *
 * TServerValidateEventParameter encapsulates the parameter data for
 * <b>OnServerValidate</b> event of TCustomValidator components.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TServerValidateEventParameter extends \Prado\TEventParameter
{
	/**
	 * the value to be validated
	 * @var string
	 */
	private $_value = '';
	/**
	 * whether the value is valid
	 * @var boolean
	 */
	private $_isValid = true;

	/**
	 * Constructor.
	 * @param string property value to be validated
	 * @param boolean whether the value is valid
	 */
	public function __construct($value, $isValid)
	{
		$this->_value = $value;
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
	 * @param boolean $value whether the value is valid
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}
}
