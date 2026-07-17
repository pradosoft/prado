<?php

/**
 * TResultProcessFork class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Process;

use Prado\Prado;
use Prado\Util\TLogger;

/**
 * TResultProcessFork class.
 *
 * A {@see TProcessFork} whose child returns a result to the parent.  The child body (or an overridden
 * {@see produce()}) returns any serializable value; the fork serializes it over the parent-child
 * channel and the parent reads it back through {@see getResult()} once the child is reaped.
 *
 * The channel is always opened.  The parent collects the serialized result as the channel drains
 * (through {@see wait()} or a registered {@see \Prado\IO\Socket\TSocketReactor}), so a result larger
 * than the pipe buffer cannot deadlock the child.  Only serializable data crosses the channel: a
 * closure, resource, or open handle does not.  An uncaught exception in the body leaves
 * {@see getHasResult()} false and exits the child with {@see EXIT_EXCEPTION}.
 *
 * ```php
 * $fork = TResultProcessFork::fork(fn () => ['rows' => 42, 'ok' => true]);
 * $fork->wait();                 // drains the result and reaps the child
 * $data = $fork->getResult();    // ['rows' => 42, 'ok' => true]
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TResultProcessFork extends TProcessFork
{
	/** The result fork always carries a channel to deliver the result. */
	protected const REQUIRES_CHANNEL = true;

	/** @var string The parent's accumulation of the serialized result. */
	private string $_buffer = '';

	/** @var mixed The result decoded from the child, available after the child is reaped. */
	private mixed $_result = null;

	/** @var bool Whether a result was received and decoded. */
	private bool $_hasResult = false;

	/**
	 * Runs the result-producing body in the child, serializes the value to the channel, and exits.
	 * An uncaught exception is logged and exits with {@see EXIT_EXCEPTION}.
	 */
	protected function execute(): void
	{
		$payload = ['ok' => false, 'result' => null];
		try {
			$payload['result'] = $this->produce();
			$payload['ok'] = true;
		} catch (\Throwable $e) {
			Prado::log((string) $e, TLogger::ERROR, static::class);
		}
		try {
			$this->writeChannel(serialize($payload));
		} catch (\Throwable $e) {
			// The parent is gone; the result cannot be delivered.
		}
		$this->exit($payload['ok'] ? 0 : self::EXIT_EXCEPTION);
	}

	/**
	 * Produces the result in the child.  The default runs the callable passed to {@see fork()}; a
	 * subclass overrides this to produce a result without a callable.
	 * @return mixed The serializable result.
	 */
	protected function produce(): mixed
	{
		return $this->_body !== null ? ($this->_body)($this) : null;
	}

	/**
	 * Accumulates the serialized result as the channel drains.
	 * @param string $bytes The bytes read from the channel.
	 */
	protected function consume(string $bytes): void
	{
		$this->_buffer .= $bytes;
	}

	/**
	 * Decodes the accumulated result at the child's end of stream, releasing the wire buffer so a
	 * large result is not held twice.
	 */
	protected function finishChannel(): void
	{
		if ($this->_buffer === '') {
			return;
		}
		$payload = @unserialize($this->_buffer);
		$this->_buffer = '';
		if (is_array($payload) && ($payload['ok'] ?? false)) {
			$this->_result = $payload['result'] ?? null;
			$this->_hasResult = true;
		}
	}

	/**
	 * Returns the result the child produced, available once the child is reaped.
	 * @return mixed The result, or null when none was received.
	 */
	public function getResult(): mixed
	{
		return $this->_result;
	}

	/**
	 * Returns whether a result was received and decoded.
	 * @return bool Whether the child delivered a result.
	 */
	public function getHasResult(): bool
	{
		return $this->_hasResult;
	}

	/**
	 * Excludes the transient wire buffer from serialization; the decoded result is kept.
	 * @param array $exprops The properties excluded from __sleep.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_buffer";
	}
}
