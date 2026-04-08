<?php

/**
 * Exception classes file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

/**
 * THttpException class
 *
 * THttpException represents an exception that is caused by invalid operations
 * of end-users. The {@see getStatusCode StatusCode} gives the type of HTTP error.
 * It is used by {@see \Prado\Exceptions\TErrorHandler} to provide different error output to users.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THttpException extends TSystemException
{
	private $_statusCode;

	/**
	 * Constructor.
	 * @param int $statusCode HTTP status code, such as 404, 500, etc.
	 * @param string $errorMessage error message. This can be a string that is listed
	 * in the message file. If so, the message in the preferred language
	 * will be used as the error message. Any rest parameters will be used
	 * to replace placeholders ({0}, {1}, {2}, etc.) in the message.
	 * @param array $args
	 */
	public function __construct($statusCode, $errorMessage, ...$args)
	{
		$this->_statusCode = $statusCode;
		parent::__construct($errorMessage, ...$args);
	}

	/**
	 * @return int HTTP status code, such as 404, 500, etc.
	 */
	public function getStatusCode()
	{
		return $this->_statusCode;
	}
}
