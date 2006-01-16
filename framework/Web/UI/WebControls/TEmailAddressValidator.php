<?php
/**
 * TEmailAddressValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Using TRegularExpressionValidator class
 */
Prado::using('System.Web.UI.WebControls.TRegularExpressionValidator');

/**
 * TEmailAddressValidator class
 *
 * TEmailAddressValidator validates whether the value of an associated
 * input component is a valid email address. It will check MX record
 * if checkdnsrr() is implemented.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TEmailAddressValidator extends TRegularExpressionValidator
{
	const EMAIL_REGEXP="\\w+([-+.]\\w+)*@\\w+([-.]\\w+)*\\.\\w+([-.]\\w+)*";

	public function getRegularExpression()
	{
		$regex=parent::getRegularExpression();
		return $regex===''?self::EMAIL_REGEXP:$regex;
	}

	public function evaluateIsValid()
	{
		$valid=parent::evaluateIsValid();
		if($valid && function_exists('checkdnsrr'))
		{
			if(($value=$this->getValidationValue($this->getValidationTarget()))!=='')
			{
				if(($pos=strpos($value,'@'))!==false)
				{
					$domain=substr($value,$pos+1);
					return $domain===''?false:checkdnsrr($domain,'MX');
				}
				else
					return false;
			}
		}
		return $valid;
	}
}

?>