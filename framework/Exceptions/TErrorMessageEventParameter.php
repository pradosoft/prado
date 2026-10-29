<?php

/**
 * TErrorMessageEventParameter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

use Prado\TPropertyValue;

/**
 * TErrorMessageEventParameter class
 *
 * TErrorMessageEventParameter is the parameter of the {@see TErrorHandler::onMissingTranslation()}
 * event. The error page shows {@see getMessage() Message}, which starts as the exception
 * message; a handler sets it to the text the visitor should see. The message is the
 * event {@see \Prado\TEventParameter::getParameter() Parameter}, so {@see \Prado\TEventParameter::getParameterChanged() ParameterChanged}
 * reports whether a handler supplied one.
 *
 * {@see getException() Exception}, {@see getErrorCode() ErrorCode}, {@see getErrorArguments() ErrorArguments},
 * and {@see getLanguage() Language} describe the message to translate. The error code and
 * arguments are those of a {@see TException}; another exception has an empty code and no arguments.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TErrorMessageEventParameter extends \Prado\TEventParameter
{
	private \Throwable $_exception;
	private string $_language;

	/**
	 * Constructor.
	 * @param \Throwable $exception the exception whose message the error page shows.
	 * @param string $language the language of the error page.
	 * @param ?string $message the message to start with, default the exception message.
	 */
	public function __construct(\Throwable $exception, string $language, ?string $message = null)
	{
		$this->_exception = $exception;
		$this->_language = $language;
		parent::__construct($message ?? $exception->getMessage());
	}

	/**
	 * @return \Throwable the exception whose message the error page shows.
	 */
	public function getException(): \Throwable
	{
		return $this->_exception;
	}

	/**
	 * @return string the {@see TException::getErrorCode()} of the exception, or '' for another exception.
	 */
	public function getErrorCode(): string
	{
		return $this->_exception instanceof TException ? (string) $this->_exception->getErrorCode() : '';
	}

	/**
	 * @return string[] the {@see TException::getErrorArguments()} of the exception, or [] for another exception.
	 */
	public function getErrorArguments(): array
	{
		return $this->_exception instanceof TException ? $this->_exception->getErrorArguments() : [];
	}

	/**
	 * @return string the language of the error page, from {@see \Prado\Prado::getApplicationLanguage()}.
	 */
	public function getLanguage(): string
	{
		return $this->_language;
	}

	/**
	 * @return string the message the error page shows.
	 */
	public function getMessage(): string
	{
		return (string) TPropertyValue::ensureString($this->getParameter());
	}

	/**
	 * Sets the message the error page shows.
	 * @param string $value the translated message.
	 */
	public function setMessage(string $value): void
	{
		$this->setParameter($value);
	}
}
