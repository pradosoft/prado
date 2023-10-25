<?php
/**
 * TExitException file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

/**
 * TExitException class
 *
 * Throwing TExitException will interrupt the application and gracefully terminate.
 * The application will exit with the specified {@see getExitCode Exit Code}.
 *
 * This exception is not designed to be caught by any class other than TApplication.
 * If this exception is caught, it may interfere with the graceful termination of
 * the application.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TExitException extends TSystemException
{
	/**
	 * @var int The exit code
	 */
	protected int $_exitCode;

	/**
	 * Constructor.
	 * @param int $exitCode The status exit code.
	 * @param ?string $message The error message.
	 * @param array $args All the additional parameters.
	 */
	public function __construct(int $exitCode = 0, ?string $message = null, ...$args)
	{
		$this->_exitCode = $exitCode;
		parent::__construct($message, ...$args);
	}

	/**
	 * @return int The exit code to end the application process.
	 */
	public function getExitCode(): int
	{
		return $this->_exitCode;
	}
}
