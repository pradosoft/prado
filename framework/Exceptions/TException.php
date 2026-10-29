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
 * TException translates an error code into a descriptive error message.
 * Additional parameters may be passed together with the error code so
 * that the message contains more detailed information.
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
 * The error code is looked up in the files of {@see getErrorMessageFiles()}:
 * the English master "messages.txt" under "Prado\Exceptions\messages\" from
 * {@see getErrorMessageFile()}, then every file registered with
 * {@see addMessageFile()}. A later file overrides an earlier definition. A code
 * defined in no file is the message itself.
 *
 * Framework messages are English. {@see THttpException} adds the framework's
 * "messages-xx.txt" of the client language, "xx" from
 * {@see \Prado\Prado::getPreferredLanguage()}, for the messages shown to the site
 * visitor. Translations of the developer-facing messages are the
 * `pradosoft/prado-dev-errormessages` package, registered through its Composer
 * `extra.prado.error-messages` entry.
 *
 * The Message Files can have blank lines, and both '#' and ';' comments.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TException extends \Exception
{
	private $_errorCode = '';
	/** @var string[] the message arguments as strings, in placeholder order. */
	private array $_errorArguments = [];
	private static $_messageFiles = [];
	protected static $_messageCache = [];

	/**
	 * Constructor.
	 * @param int|string $errorCode The Optional PHP Exception Error Code.  In old style
	 *   Exceptions this is a string error Message Code.  If this is a string, then the
	 *   $errorCode acts as and $errorMessage becomes a parameter in $args.
	 * @param string $errorMessage The error message code when $errorCode is an integer.
	 *   This can be a string that is listed in a message file. If so, the listed
	 *   message is the error message.
	 * @param array $args These are used to replace placeholders ({0}, {1}, {2}, etc.)
	 *   in the message except the last argument.  If the last argument is a Throwable
	 *   it is treated as the $previous Exception for exception chaining.
	 */
	public function __construct($errorCode, $errorMessage = null, ...$args)
	{
		if (!is_int($errorCode)) {
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
		if ($n > 0 && ($args[$n - 1] instanceof Throwable)) {
			$previous = array_pop($args);
			$n--;
		}
		$tokens = [];
		for ($i = 0; $i < $n; ++$i) {
			$this->_errorArguments[$i] = TPropertyValue::ensureString($args[$i]);
			$tokens['{' . $i . '}'] = $this->_errorArguments[$i];
		}
		parent::__construct(strtr($errorMessage, $tokens), $errorCode, $previous);
	}

	/**
	 * Registers a message file read after the file of {@see getErrorMessageFile()}. Its
	 * definitions override the framework messages and the files registered before it.
	 * When a "-xx" variant of the file exists for the client language of
	 * {@see \Prado\Prado::getPreferredLanguage()}, "messages-fr.txt" for "messages.txt",
	 * the variant is registered in its place.
	 * @param string $file the extra message file
	 * @since 4.2.0
	 */
	public static function addMessageFile($file)
	{
		if (preg_match('/^(.*)(?:-(.{2,5}))?\.(.{2,4})$/', $file, $matching)) {
			$lang = Prado::getPreferredLanguage();
			$msgFile = $matching[1] . '-' . $lang . '.' . $matching[3];
			if (is_file($msgFile)) {
				$file = $msgFile;
			}
		}
		TException::$_messageFiles[] = $file;
	}

	/**
	 * Translates an error code into an error message through the files of
	 * {@see getErrorMessageFiles()}; a later file overrides an earlier definition.
	 * Each file is parsed once per request, an unreadable file as no entries. Allows
	 * for '#' and ';' comments.
	 * @param ?string $key error code that is passed in the exception constructor.
	 * @return string the translated error message, or the error code when no file defines it.
	 */
	protected function translateErrorMessage($key)
	{
		if (empty($key)) {
			return '';
		}
		$value = $key;

		// Cache messages
		foreach ($this->getErrorMessageFiles() as $msgFile) {
			if (!isset(self::$_messageCache[$msgFile])) {
				self::$_messageCache[$msgFile] = [];
				if (($entries = @file($msgFile)) !== false) {
					foreach ($entries as $entry) {
						$entry = trim($entry);

						// Skip empty lines and comments starting with # or ;
						if ($entry === '' || strncmp($entry, '#', 1) === 0 || strncmp($entry, ';', 1) === 0) {
							continue;
						}

						if (strpos($entry, '=') !== false) {
							[$code, $message] = explode('=', $entry, 2);
							self::$_messageCache[$msgFile][trim($code)] = trim($message);
						}
					}
				}
			}
			$value = self::$_messageCache[$msgFile][$key] ?? $value;
		}
		return $value;
	}

	/**
	 * Returns the message files to read, in order of increasing precedence: the file of
	 * {@see getErrorMessageFile()}, then the files of {@see addMessageFile()} in
	 * registration order. A key defined in a later file overrides the earlier definition.
	 * @return string[] the message file paths.
	 * @since 4.4.0
	 */
	protected function getErrorMessageFiles(): array
	{
		return [$this->getErrorMessageFile(), ...self::$_messageFiles];
	}

	/**
	 * @return string path to the English master message file of the framework.
	 */
	protected function getErrorMessageFile()
	{
		return Prado::getFrameworkPath() . '/Exceptions/messages/messages.txt';
	}

	/**
	 * @return string error code
	 */
	public function getErrorCode()
	{
		return $this->_errorCode;
	}

	/**
	 * Returns the message arguments that filled the `{0}`, `{1}`, ... placeholders, as
	 * strings in placeholder order. A translator rebuilds the message from
	 * {@see getErrorCode()} and these arguments.
	 * @return string[] the message arguments.
	 * @since 4.4.0
	 */
	public function getErrorArguments(): array
	{
		return $this->_errorArguments;
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
