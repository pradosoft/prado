<?php
/**
 * Exception classes file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Exceptions
 */

/**
 * TException class
 *
 * TException is the base class for all PRADO exceptions.
 * TException
 *     TSystemException
 *         TNullReferenceException
 *         TIndexOutOfRangeException
 *         TArithmeticException
 *         TInvalidValueException
 *         TInvalidTypeException
 *         TInvalidFormatException
 *         TInvalidOperationException
 *         TConfigurationException
 *         TSecurityException
 *         TIOException
 *         TDBException
 *         THttpException
 *		   TNotSupportedException
 *     TApplicationException
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Exceptions
 * @since 3.0
 */
class TException extends Exception
{
	private $_errorCode='';

	public function __construct($errorCode)
	{
		$this->_errorCode=$errorCode;
		$args=func_get_args();
		$args[0]=$this->translateErrorCode($errorCode);
		$str=call_user_func_array('sprintf',$args);
		parent::__construct($str);
	}

	protected function translateErrorCode($key)
	{
		$languages=Prado::getUserLanguages();
		$msgFile=dirname(__FILE__).'/messages.'.$languages[0];
		if(!is_file($msgFile))
			$msgFile=dirname(__FILE__).'/messages.en';
		if(($entries=@file($msgFile))===false)
			return $key;
		else
		{
			foreach($entries as $entry)
			{
				@list($code,$message)=explode('=',$entry,2);
				if(trim($code)===$key)
					return trim($message);
			}
			return $key;
		}
	}

	public function getErrorCode()
	{
		return $this->_errorCode;
	}

	public function setErrorCode($errorCode)
	{
		$this->_errorCode=$errorCode;
	}

	public function getErrorMessage()
	{
		return $this->getMessage();
	}

	public function setErrorMessage($msg)
	{
		$this->message=$msg;
	}
}

class TSystemException extends TException
{
}

class TApplicationException extends TException
{
}

class TNullReferenceException extends TSystemException
{
}

class TIndexOutOfRangeException extends TSystemException
{
}

class TArithmeticException extends TSystemException
{
}

class TInvalidOperationException extends TSystemException
{
}

class TInvalidDataTypeException extends TSystemException
{
}

class TInvalidDataValueException extends TSystemException
{
}

class TInvalidDataFormatException extends TSystemException
{
}

class TConfigurationException extends TSystemException
{
}

class TIOException extends TException
{
}

class TDBException extends TException
{
}

class TSecurityException extends TException
{
}

class THttpException extends TException
{
}

class TNotSupportedException extends TException
{
}

class TPhpErrorException extends TException
{
	public function __construct($errno,$errstr,$errfile,$errline)
	{
		static $errorTypes=array(
			E_ERROR           => "Error",
			E_WARNING         => "Warning",
			E_PARSE           => "Parsing Error",
			E_NOTICE          => "Notice",
			E_CORE_ERROR      => "Core Error",
			E_CORE_WARNING    => "Core Warning",
			E_COMPILE_ERROR   => "Compile Error",
			E_COMPILE_WARNING => "Compile Warning",
			E_USER_ERROR      => "User Error",
			E_USER_WARNING    => "User Warning",
			E_USER_NOTICE     => "User Notice",
			E_STRICT          => "Runtime Notice"
		);
		$errorType=isset($errorTypes[$errno])?$errorTypes[$errno]:'Unknown Error';
		parent::__construct("[$errorType] $errstr (@line $errline in file $errfile).");
	}
}

?>