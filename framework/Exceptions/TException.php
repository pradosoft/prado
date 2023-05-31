<?php
/**
 * Exception classes file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

use Prado\Prado;
use Prado\TPropertyValue;

use Throwable;

/**
 * TException class
 *
 * TException is the base class for all PRADO exceptions.
 *
 * TException provides the functionality of translating an error code
 * into a descriptive error message in a language that is preferred
 * by user browser. Additional parameters may be passed together with
 * the error code so that the translated message contains more detailed
 * information.
 *
 * Old style TException only have an error Message Code as follows:
 * ```php
 *   throw new TException('component_error_message_code'[, $param1, $param2, ..., $chainedException]);
 * ```
 * The parameters and $chainedException are optional. $chainedException
 * may be entirely left out.
 *
 * To include an actual integer error Code, new style PRADO Exceptions
 * should be used.  The new style is as follows:
 * ```php
 *   throw new TException($errorCode, 'component_error_message_code'[, $param1, $param2, ..., $chainedException]);
 * ```
 *
 * Please note that the Error Code and Error Message/Message-Code is swapped
 * to the Normal PHP Exceptions (where the $message is the first parameter and $code
 * is the second).  This done to support Error Message Parameters.
 *
 * By default, TException looks for a message file by calling
 * {@see getErrorMessageFile()} method, which uses the "message-xx.txt"
 * file located under "Prado\Exceptions" folder, where "xx" is the
 * code of the user preferred language. If such a file is not found,
 * "message.txt" will be used instead.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TException extends \Exception
{
	private $_errorCode = '';
	private static $_messagefiles = [];
	protected static $_messageCache = [];

	/**
	 * Constructor.
	 * @param int|string $errorCode The Optional PHP Exception Error Code.  In old style
	 *   Exceptions this is a string error Message Code.  If this is a string, then the
	 *   $errorCode acts as and $errorMessage becomes a parameter in $args.
	 * @param string $errorMessage The error message code when $errorCode is an integer.
	 *   This can be a string that is listed in the message file. If so, the message in
	 *   the preferred language will be used as the error message.
	 * @param array $args These are used to replace placeholders ({0}, {1}, {2}, etc.)
	 *   in the message except the last argument.  If the last argument is a Throwable
	 *   it is treated as the $previous Exception for exception chaining.
	 */
	public function __construct($errorCode, $errorMessage = null, ...$args)
	{
		if(!is_int($errorCode)) {
			//assume old code
			if ($errorMessage !== null || !empty($args)) {
				array_unshift($args, $errorMessage);
			}
			$errorMessage = $errorCode;
			$errorCode = 0;
		}
		$this->_errorCode = $errorMessage;
		$errorMessage = $this->translateErrorMessage($errorMessage);
		$n = count($args);
		$previous = null;
		if($n > 0 && ($args[$n - 1] instanceof Throwable)) {
			$previous = array_pop($args);
			$n--;
		}
		$tokens = [];
		for ($i = 0; $i < $n; ++$i) {
			$tokens['{' . $i . '}'] = TPropertyValue::ensureString($args[$i]);
		}
		parent::__construct(strtr($errorMessage, $tokens), $errorCode, $previous);
	}

	/**
	 * Adds to the various files to read when rendering an error
	 * @param string $file the extra message file
	 * @since 4.2.0
	 */
	public static function addMessageFile($file)
	{
		if (preg_match('/^(.*)(-.{2.4})?\.(.{2,4})$/', $file, $matching)) {
			$lang = Prado::getPreferredLanguage();
			$msgFile = $matching[1] . '-' . $lang . '.' . $matching[2];
			if (is_file($msgFile)) {
				$file = $msgFile;
			}
		}
		TException::$_messagefiles[] = $file;
	}

	/**
	 * Translates an error code into an error message.
	 * @param string $key error code that is passed in the exception constructor.
	 * @return string the translated error message
	 */
	protected function translateErrorMessage($key)
	{
		$msgFiles = TException::$_messagefiles;
		$msgFiles[] = $this->getErrorMessageFile();
		$value = $key;

		// Cache messages
		foreach ($msgFiles as $msgFile) {
			if (!isset(self::$_messageCache[$msgFile])) {
				if (($entries = @file($msgFile)) !== false) {
					foreach ($entries as $entry) {
						[$code, $message] = array_merge(explode('=', $entry, 2), ['']);
						self::$_messageCache[$msgFile][trim($code)] = trim($message);
					}
				}
			}
			$value = self::$_messageCache[$msgFile][$key] ?? $value;
		}
		return $value;
	}

	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		$lang = Prado::getPreferredLanguage();
		$msgFile = Prado::getFrameworkPath() . '/Exceptions/messages/messages-' . $lang . '.txt';
		if (!is_file($msgFile)) {
			$msgFile = Prado::getFrameworkPath() . '/Exceptions/messages/messages.txt';
		}
		return $msgFile;
	}

	/**
	 * @return string error code
	 */
	public function getErrorCode()
	{
		return $this->_errorCode;
	}

	/**
	 * @param string $code error code
	 */
	public function setErrorCode($code)
	{
		$this->_errorCode = $code;
	}

	/**
	 * @return string error message
	 */
	public function getErrorMessage()
	{
		return $this->getMessage();
	}

	/**
	 * @param string $message error message
	 */
	protected function setErrorMessage($message)
	{
		$this->message = $message;
	}
}
