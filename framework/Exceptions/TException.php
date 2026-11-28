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
 * file located under "Prado\Exceptions\messages\" folder, where "xx" is
 * the code of the user preferred language. If such a file is not found,
 * "message.txt" will be used instead.
 *
 * A message defined in a file is an ICU MessageFormat pattern, as in 4.4.
 * {@see formatErrorMessage()} formats it with `\MessageFormatter` in the language of
 * the defining file ({@see getMessageFileLanguage()}), so a message can use `plural`,
 * `select`, and `number` arguments:
 * ```
 * cache_items_expired = {0, plural, one{# item} other{# items}} expired in ''{1}''.
 * ```
 * ICU quotes literal text with apostrophes. An apostrophe before or after a brace is
 * doubled, so `''{0}''` renders the argument in single quotes. An apostrophe elsewhere,
 * as in "can't", is literal. A pattern written before 4.3.3 that quotes a placeholder
 * in single apostrophes, `'{0}'`, has its placeholders replaced as before; 4.4 renders
 * it as the literal text `{0}`.
 *
 * The Message Files can have blank lines, and both '#' and ';' comments.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TException extends \Exception
{
	private $_errorCode = '';
	private static $_messageFiles = [];
	protected static $_messageCache = [];

	/**
	 * Constructor.
	 * @param int|string $errorCode The Optional PHP Exception Error Code.  In old style
	 *   Exceptions this is a string error Message Code.  If this is a string, then the
	 *   $errorCode acts as and $errorMessage becomes a parameter in $args.
	 * @param string $errorMessage The error message code when $errorCode is an integer.
	 *   This can be a string that is listed in the message file. If so, the message in
	 *   the preferred language will be used as the error message.
	 * @param array $args These are the arguments of the placeholders ({0}, {1}, {2}, etc.)
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
		$n = count($args);
		$previous = null;
		if ($n > 0 && ($args[$n - 1] instanceof Throwable)) {
			$previous = array_pop($args);
			$n--;
		}
		$arguments = [];
		for ($i = 0; $i < $n; ++$i) {
			$arguments[$i] = TPropertyValue::ensureString($args[$i]);
		}
		parent::__construct($this->formatErrorMessage($errorMessage, $arguments), $errorCode, $previous);
	}

	/**
	 * Formats the message of an error code with its arguments.
	 *
	 * | Message source | Formatting |
	 * |---|---|
	 * | defined in a message file | ICU MessageFormat in the language of {@see getMessageFileLanguage()} |
	 * | defined in no file | `{0}`, `{1}`, ... replaced with the arguments |
	 * | a pattern ICU cannot parse | `{0}`, `{1}`, ... replaced with the arguments |
	 * | a pattern that quotes a placeholder in single apostrophes, `'{0}'` | `{0}`, `{1}`, ... replaced with the arguments, as before 4.3.3 |
	 * @param ?string $key the error code.
	 * @param string[] $args the message arguments, in placeholder order.
	 * @return string the error message.
	 * @since 4.3.3
	 */
	protected function formatErrorMessage(?string $key, array $args): string
	{
		$message = $this->translateErrorMessage($key);
		$file = $this->findErrorMessageFile($key);
		if ($file !== null && !self::isPlainMessagePattern($message)) {
			try {
				$formatted = \MessageFormatter::formatMessage($this->getMessageFileLanguage($file), $message, $args);
			} catch (\IntlException) {
				$formatted = false;
			}
			if ($formatted !== false) {
				return $formatted;
			}
		}
		$tokens = [];
		foreach ($args as $i => $arg) {
			$tokens['{' . $i . '}'] = $arg;
		}
		return strtr($message, $tokens);
	}

	/**
	 * Returns whether a message pattern is written for plain placeholder replacement, as
	 * message files were before 4.3.3: it quotes a placeholder in single apostrophes,
	 * `'{0}'`, which ICU renders as the literal text `{0}`. The ICU form doubles them,
	 * `''{0}''`.
	 * @param string $message the message pattern.
	 * @return bool whether the placeholders of the pattern are replaced without ICU.
	 * @since 4.3.3
	 */
	protected static function isPlainMessagePattern(string $message): bool
	{
		return preg_match("/(?<!')'\\{\\d+\\}'(?!')/", $message) === 1;
	}

	/**
	 * Returns the language of a message file: "xx" of a "-xx.txt" or "-xx_YY.txt" name,
	 * as "messages-fr.txt" from {@see getErrorMessageFile()}, and "en" for any other name.
	 * @param string $file the message file path.
	 * @return string the language code that formats the file's messages.
	 * @since 4.3.3
	 */
	protected function getMessageFileLanguage(string $file): string
	{
		if (preg_match('/-([a-z]{2,3})(?:[_-][a-z0-9]+)?\.\w+$/i', basename($file), $matches)) {
			return strtolower($matches[1]);
		}
		return 'en';
	}

	/**
	 * Adds to the various files to read when rendering an error
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
	 * @param ?string $key error code that is passed in the exception constructor.
	 * @return string the translated error message, or the error code when no file defines it.
	 */
	protected function translateErrorMessage($key)
	{
		if (empty($key)) {
			return '';
		}
		$file = $this->findErrorMessageFile($key);
		return $file === null ? $key : self::readMessageFile($file)[$key];
	}

	/**
	 * Returns the message files to read, in order of increasing precedence: the files of
	 * {@see addMessageFile()} in registration order, then the file of
	 * {@see getErrorMessageFile()}. A key defined in a later file overrides the earlier definition.
	 * @return string[] the message file paths.
	 * @since 4.3.3
	 */
	protected function getErrorMessageFiles(): array
	{
		return [...self::$_messageFiles, $this->getErrorMessageFile()];
	}

	/**
	 * Returns the file of {@see getErrorMessageFiles()} with the highest precedence that
	 * defines an error code.
	 * @param ?string $key the error code.
	 * @return ?string the message file path, or null when no file defines the code.
	 * @since 4.3.3
	 */
	protected function findErrorMessageFile(?string $key): ?string
	{
		if ($key === null || $key === '') {
			return null;
		}
		foreach (array_reverse($this->getErrorMessageFiles()) as $msgFile) {
			if (isset(self::readMessageFile($msgFile)[$key])) {
				return $msgFile;
			}
		}
		return null;
	}

	/**
	 * Parses a message file into its entries. Each file is parsed once per request, an
	 * unreadable file as no entries. Allows for '#' and ';' comments.
	 * @param string $msgFile the message file path.
	 * @return array<string, string> error code => message.
	 */
	private static function readMessageFile(string $msgFile): array
	{
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
		return self::$_messageCache[$msgFile];
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
