<?php

/**
 * TCronDaemonEventParameter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\TEventParameter;
use Prado\TPropertyValue;

/**
 * TCronDaemonEventParameter class.
 *
 * TCronDaemonEventParameter is the parameter of the {@see \Prado\Util\Cron\TCronModule} daemon
 * events {@see \Prado\Util\Cron\TCronModule::onDaemonStart onDaemonStart},
 * {@see \Prado\Util\Cron\TCronModule::onDaemonIdle onDaemonIdle}, and
 * {@see \Prado\Util\Cron\TCronModule::onDaemonEnd onDaemonEnd}.
 *
 * | Property | Set for | Meaning |
 * |---|---|---|
 * | `LockName` | all | the lease lock name |
 * | `Watching` | all | whether the wake transport is watched on the loop |
 * | `Tasks` | idle, end | the tasks run in the sweep just finished (idle), or in all (end) |
 * | `Elapsed` | idle, end | the seconds since the daemon started |
 * | `Wait` | idle | the seconds the daemon is about to wait; a handler shortens or lengthens it |
 * | `Reason` | end | `handoff`, `lifetime`, `stop`, `signal`, or `error` |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronDaemonEventParameter extends TEventParameter
{
	/** The daemon ended because {@see \Prado\Util\Cron\TCronModule::getDaemonLifetime DaemonLifetime} passed. */
	public const REASON_LIFETIME = 'lifetime';

	/** The daemon ended because {@see \Prado\Util\Cron\TCronModule::stopDaemon} was called. */
	public const REASON_STOP = 'stop';

	/** The daemon ended on SIGTERM or SIGINT. */
	public const REASON_SIGNAL = 'signal';

	/** The daemon ended by an exception leaving the loop. */
	public const REASON_ERROR = 'error';

	/** The retiring daemon handed the lease to the next tick's process, which waited for it. */
	public const REASON_HANDOFF = 'handoff';

	/** @var string the lease lock name */
	private string $_lockName = '';

	/** @var bool whether the wake transport is watched on the loop */
	private bool $_watching = false;

	/** @var int the tasks run */
	private int $_tasks = 0;

	/** @var float the seconds since the daemon started */
	private float $_elapsed = 0.0;

	/** @var float the seconds the daemon is about to wait */
	private float $_wait = 0.0;

	/** @var ?string why the daemon ended */
	private ?string $_reason = null;

	/**
	 * @return string the lease lock name
	 */
	public function getLockName(): string
	{
		return $this->_lockName;
	}

	/**
	 * @param string $value the lease lock name
	 * @return static the current object
	 */
	public function setLockName($value): static
	{
		$this->_lockName = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return bool whether the wake transport is watched on the loop
	 */
	public function getWatching(): bool
	{
		return $this->_watching;
	}

	/**
	 * @param bool $value whether the wake transport is watched on the loop
	 * @return static the current object
	 */
	public function setWatching($value): static
	{
		$this->_watching = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return int the tasks run in the sweep just finished, or in the daemon's life for `onDaemonEnd`
	 */
	public function getTasks(): int
	{
		return $this->_tasks;
	}

	/**
	 * @param int $value the tasks run
	 * @return static the current object
	 */
	public function setTasks($value): static
	{
		$this->_tasks = TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * @return float the seconds since the daemon started
	 */
	public function getElapsed(): float
	{
		return $this->_elapsed;
	}

	/**
	 * @param float $value the seconds since the daemon started
	 * @return static the current object
	 */
	public function setElapsed($value): static
	{
		$this->_elapsed = TPropertyValue::ensureFloat($value);
		return $this;
	}

	/**
	 * @return float the seconds the daemon is about to wait
	 */
	public function getWait(): float
	{
		return $this->_wait;
	}

	/**
	 * @param float $value the seconds the daemon is about to wait; a handler of `onDaemonIdle` changes the wait
	 * @return static the current object
	 */
	public function setWait($value): static
	{
		$this->_wait = max(0.0, TPropertyValue::ensureFloat($value));
		return $this;
	}

	/**
	 * @return ?string why the daemon ended, one of the `REASON_*` constants; null before it ends
	 */
	public function getReason(): ?string
	{
		return $this->_reason;
	}

	/**
	 * @param ?string $value why the daemon ended
	 * @return static the current object
	 */
	public function setReason($value): static
	{
		$this->_reason = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}
}
