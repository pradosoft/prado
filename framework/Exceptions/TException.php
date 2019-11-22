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

use Prado\Prado;
use Prado\TPropertyValue;

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
 * By default, TException looks for a message file by calling
 * {@link getErrorMessageFile()} method, which uses the "message-xx.txt"
 * file located under "Prado\Exceptions" folder, where "xx" is the
 * code of the user preferred language. If such a file is not found,
 * "message.txt" will be used instead.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Exceptions
 * @since 3.0
 */
class TException extends \Exception
{
	private $_errorCode = '';
	protected static $_messageCache = [];

	/**
	 * Constructor.
	 * @param string $errorMessage error message. This can be a string that is listed
	 * in the message file. If so, the message in the preferred language
	 * will be used as the error message. Any rest parameters will be used
	 * to replace placeholders ({0}, {1}, {2}, etc.) in the message.
	 */
	public function __construct($errorMessage)
	{
		$this->_errorCode = $errorMessage;
		$errorMessage = $this->translateErrorMessage($errorMessage);
		$args = func_get_args();
		array_shift($args);
		$n = count($args);
		$tokens = [];
		for ($i = 0; $i < $n; ++$i) {
			$tokens['{' . $i . '}'] = TPropertyValue::ensureString($args[$i]);
		}
		parent::__construct(strtr($errorMessage, $tokens));
	}

	/**
	 * Translates an error code into an error message.
	 * @param string $key error code that is passed in the exception constructor.
	 * @return string the translated error message
	 */
	protected function translateErrorMessage($key)
	{
		$msgFile = $this->getErrorMessageFile();

		// Cache messages
		if (!isset(self::$_messageCache[$msgFile])) {
			if (($entries = @file($msgFile)) !== false) {
				foreach ($entries as $entry) {
					[$code, $message] = array_merge(explode('=', $entry, 2), ['']);
					self::$_messageCache[$msgFile][trim($code)] = trim($message);
				}
			}
		}
		return isset(self::$_messageCache[$msgFile][$key]) ? self::$_messageCache[$msgFile][$key] : $key;
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
