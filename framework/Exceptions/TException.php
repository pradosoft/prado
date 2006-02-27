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
 *     TApplicationException
 *     TSystemException
 *         TInvalidDataValueException
 *         TInvalidDataTypeException
 *         TInvalidDataFormatException
 *         TInvalidOperationException
 *         TConfigurationException
 *         TPhpErrorException
 *         TSecurityException
 *         TIOException
 *         TDBException
 *         THttpException
 *		   TNotSupportedException
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Exceptions
 * @since 3.0
 */
class TException extends Exception
{
	private $_errorCode='';

	/**
	 * Constructor.
	 * @param string error message. This can be a string that is listed
	 * in the message file. If so, the message in the preferred language
	 * will be used as the error message. Any rest parameters will be used
	 * to replace placeholders ({0}, {1}, {2}, etc.) in the message.
	 */
	public function __construct($errorMessage)
	{
		$this->_errorCode=$errorMessage;
		$errorMessage=$this->translateErrorMessage($errorMessage);
		$args=func_get_args();
		array_shift($args);
		$n=count($args);
		$tokens=array();
		for($i=0;$i<$n;++$i)
			$tokens['{'.$i.'}']=TPropertyValue::ensureString($args[$i]);
		parent::__construct(strtr($errorMessage,$tokens));
	}

	protected function translateErrorMessage($key)
	{
		$lang=Prado::getPreferredLanguage();
		$msgFile=Prado::getFrameworkPath().'/Exceptions/messages-'.$lang.'.txt';
		if(!is_file($msgFile))
			$msgFile=Prado::getFrameworkPath().'/Exceptions/messages.txt';
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

	public function setErrorCode($code)
	{
		$this->_errorCode=$code;
	}

	public function getErrorMessage()
	{
		return $this->getMessage();
	}

	protected function setErrorMessage($message)
	{
		$this->message=$message;
	}
}

class TSystemException extends TException
{
}

class TApplicationException extends TException
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

class TIOException extends TSystemException
{
}

class TDBException extends TSystemException
{
}

class TSecurityException extends TSystemException
{
}

class TNotSupportedException extends TSystemException
{
}

class TPhpErrorException extends TSystemException
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


class THttpException extends TSystemException
{
	private $_statusCode;

	public function __construct($statusCode,$errorMessage)
	{
		$this->_statusCode=$statusCode;
		$this->setErrorCode($errorMessage);
		$errorMessage=$this->translateErrorMessage($errorMessage);
		$args=func_get_args();
		array_shift($args);
		array_shift($args);
		$n=count($args);
		$tokens=array();
		for($i=0;$i<$n;++$i)
			$tokens['{'.$i.'}']=TPropertyValue::ensureString($args[$i]);
		parent::__construct(strtr($errorMessage,$tokens));
	}

	public function getStatusCode()
	{
		return $this->_statusCode;
	}
}

?>