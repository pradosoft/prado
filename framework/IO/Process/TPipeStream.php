<?php

/**
 * TPipeStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Process;

use Prado\Exceptions\TIOException;
use Prado\Prado;
use Prado\IO\TStream;

/**
 * TPipeStream class
 *
 * Wraps a pipe as a {@see TStream}, either a {@see popen()} command pipe or one of
 * the pipe descriptors of a {@see TProcess}.  Pipes are non-seekable; the seekable
 * capability resolves to false from the stream metadata.
 *
 * A command pipe opened with {@see popen()} is closed with {@see pclose()}, whose
 * return value is the command's exit code, exposed via {@see getExitCode()}.
 * A {@see proc_open()} descriptor pipe is an ordinary stream closed with
 * {@see fclose()}; its owning {@see TProcess} reports the exit code.
 *
 * TPipeStream is a {@see \Psr\Http\Message\StreamInterface}, so a pipe from one
 * process composes with another. Pass {@see TProcess::getStdout()} as a
 * descriptor to {@see TProcess::open()} to wire processes together.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPipeStream extends TStream
{
	/** @var bool Whether this pipe was opened with popen() (closed with pclose()). */
	private bool $_isCommandPipe = false;

	/** @var ?int The exit code captured from pclose(), or null. */
	private ?int $_exitCode = null;

	/**
	 * Opens a command pipe with {@see popen()}.
	 * @param string $command The command to run.
	 * @param string $mode 'r' to read the command's output, 'w' to write its input.
	 * @throws TIOException When the command pipe cannot be opened.
	 * @return self The command pipe.
	 */
	public static function popen(string $command, string $mode): self
	{
		$resource = @popen($command, $mode);
		if ($resource === false) {
			throw new TIOException('pipe_popen_failed', $command, $mode);
		}
		$pipe = Prado::createComponent(self::class);
		$pipe->setIsCommandPipeDirect(true);
		$pipe->attachResource($resource, true);
		return $pipe;
	}

	/**
	 * Closes the pipe.  Command pipes use {@see pclose()} (which blocks until the command
	 * exits) and capture the exit code; descriptor pipes use {@see fclose()}.
	 * @param mixed $resource The pipe resource.
	 * @return bool Whether the close succeeded.
	 */
	protected function closeResource(mixed $resource): bool
	{
		if ($this->getIsCommandPipeDirect()) {
			$code = pclose($resource);
			$this->setExitCodeDirect($code);
			return $code !== -1;
		}
		return fclose($resource);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw command-pipe flag.
	 * @return bool The raw command-pipe flag.
	 */
	protected function getIsCommandPipeDirect(): bool
	{
		return $this->_isCommandPipe;
	}

	/**
	 * Sets the raw command-pipe flag.
	 * @param bool $value The raw command-pipe flag.
	 */
	protected function setIsCommandPipeDirect(bool $value): void
	{
		$this->_isCommandPipe = $value;
	}

	/**
	 * Returns the raw captured exit code.
	 * @return ?int The raw captured exit code.
	 */
	protected function getExitCodeDirect(): ?int
	{
		return $this->_exitCode;
	}

	/**
	 * Sets the raw captured exit code.
	 * @param ?int $value The raw captured exit code.
	 */
	protected function setExitCodeDirect(?int $value): void
	{
		$this->_exitCode = $value;
	}

	/**
	 * Indicates whether this pipe is a popen() command pipe.
	 * @return bool Whether this pipe is a popen() command pipe.
	 */
	public function getIsCommandPipe(): bool
	{
		return $this->getIsCommandPipeDirect();
	}

	/**
	 * Returns the command exit code captured at close (command pipes only).
	 * @return ?int The command exit code captured at close (command pipes only), or null.
	 */
	public function getExitCode(): ?int
	{
		return $this->getExitCodeDirect();
	}

	/**
	 * Excludes the command-pipe flag (meaningless without the handle) from
	 * {@see \Prado\TComponent::__sleep()}.  The captured exit code is a plain int and is
	 * kept so it survives serialization.
	 * @param array &$exprops The properties excluded from serialization.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_isCommandPipe";
	}
}
