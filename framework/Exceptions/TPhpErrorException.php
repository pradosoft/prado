<?php
/**
 * Exception classes file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Exceptions
 */

namespace Prado\Exceptions;

/**
 * TPhpErrorException class
 *
 * TPhpErrorException represents an exception caused by a PHP error.
 * This exception is mainly thrown within a PHP error handler.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Exceptions
 * @since 3.0
 */
class TPhpErrorException extends TSystemException
{
	/**
	 * Constructor.
	 * @param int $errno error number
	 * @param string $errstr error string
	 * @param string $errfile error file
	 * @param int $errline error line number
	 */
	public function __construct($errno, $errstr, $errfile, $errline)
	{
		static $errorTypes = [
			E_ERROR => "Error",
			E_WARNING => "Warning",
			E_PARSE => "Parsing Error",
			E_NOTICE => "Notice",
			E_CORE_ERROR => "Core Error",
			E_CORE_WARNING => "Core Warning",
			E_COMPILE_ERROR => "Compile Error",
			E_COMPILE_WARNING => "Compile Warning",
			E_USER_ERROR => "User Error",
			E_USER_WARNING => "User Warning",
			E_USER_NOTICE => "User Notice",
			E_STRICT => "Runtime Notice"
		];
		$errorType = $errorTypes[$errno] ?? 'Unknown Error';
		parent::__construct("[$errorType] $errstr (@line $errline in file $errfile).");
	}

	/**
	 * Returns if error is one of fatal type.
	 *
	 * @param array $error $error error got from error_get_last()
	 * @return bool if error is one of fatal type
	 */
	public static function isFatalError($error)
	{
		return isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING]);
	}
}
