<?php

/**
 * TCronTaskEventParameter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\TEventParameter;
use Prado\TPropertyValue;

/**
 * TCronTaskEventParameter class.
 *
 * TCronTaskEventParameter is the parameter of the {@see \Prado\Util\Cron\TCronModule} task events:
 * {@see \Prado\Util\Cron\TCronModule::onTaskStart onTaskStart},
 * {@see \Prado\Util\Cron\TCronModule::onTaskEnd onTaskEnd},
 * {@see \Prado\Util\Cron\TCronModule::onTaskFailure onTaskFailure},
 * {@see \Prado\Util\Cron\TCronModule::onTaskRetry onTaskRetry},
 * {@see \Prado\Util\Cron\TCronModule::onTaskSkipped onTaskSkipped}, and
 * {@see \Prado\Util\Cron\TCronModule::onTaskPing onTaskPing}.  A started run ends with `onTaskEnd`
 * (and `onTaskFailure` when it failed) or with `onTaskSkipped` when a start handler cancels it, another
 * process claimed it, or a previous run holds its run lock; a trigger skipped before the start raises
 * only `onTaskSkipped`.
 *
 * | Property | Set for | Meaning |
 * |---|---|---|
 * | `Task` | all | the {@see \Prado\Util\Cron\TCronTask} |
 * | `Manual` | all | whether the run was asked for (`cron/now`) or chained instead of triggered |
 * | `Chain` | all | the names of the tasks that ran before this one in a chain |
 * | `Trigger` | triggered runs | the trigger the run serves; a missed one when caught up |
 * | `Cancel` | start | a handler sets it to skip the run |
 * | `Reason` | skipped | a `REASON_*` constant |
 * | `Result` | end, failure, retry, ping after the run | a `TCronModule::RESULT_*` constant |
 * | `Duration` | end, failure, ping after the run | the seconds the run took |
 * | `Exception` | end, failure, retry | the exception a task in the cron process threw |
 * | `Attempt` | end, failure, retry | the attempt, from 1 |
 * | `Ping` | ping | a `TCronTask::PING_*` constant |
 * | `Url` | ping | the URL to ping |
 * | `Handled` | ping | a handler sets it when it delivered the ping |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronTaskEventParameter extends TEventParameter
{
	/** The run lock is held by another run of the task. */
	public const REASON_RUNNING = 'running';

	/** The run would start later than the task's StartingDeadline. */
	public const REASON_DEADLINE = 'deadline';

	/** An `onTaskStart` handler cancelled the run. */
	public const REASON_CANCELLED = 'cancelled';

	/** Another process claimed the run. */
	public const REASON_CLAIMED = 'claimed';

	/** The task's user lacks the permission to run it. */
	public const REASON_DENIED = 'denied';

	/** The task is paused. */
	public const REASON_PAUSED = 'paused';

	/** The time is outside the task's Between, or inside its UnlessBetween. */
	public const REASON_WINDOW = 'window';

	/** A behavior's `dyShouldRunTask` declined the run. */
	public const REASON_CONDITION = 'condition';

	/** @var TCronTask the task */
	private TCronTask $_task;

	/** @var bool whether the run was asked for instead of triggered */
	private bool $_manual = false;

	/** @var bool whether a start handler cancels the run */
	private bool $_cancel = false;

	/** @var ?string why the run was skipped */
	private ?string $_reason = null;

	/** @var ?string the result of the run */
	private ?string $_result = null;

	/** @var ?float the seconds the run took */
	private ?float $_duration = null;

	/** @var ?\Throwable the exception the task threw */
	private ?\Throwable $_exception = null;

	/** @var ?string the point of the run being pinged */
	private ?string $_ping = null;

	/** @var ?string the URL to ping */
	private ?string $_url = null;

	/** @var bool whether a handler delivered the ping */
	private bool $_handled = false;

	/** @var int the attempt of the run, from 1 */
	private int $_attempt = 1;

	/** @var ?int the trigger the run serves */
	private ?int $_trigger = null;

	/** @var string[] the names of the tasks that ran before this one in a chain */
	private array $_chain = [];

	/**
	 * @param TCronTask $task the task
	 * @param bool $manual whether the run was asked for instead of triggered
	 */
	public function __construct(TCronTask $task, bool $manual = false)
	{
		$this->_task = $task;
		$this->_manual = $manual;
		parent::__construct();
	}

	/**
	 * @return TCronTask the task
	 */
	public function getTask(): TCronTask
	{
		return $this->_task;
	}

	/**
	 * @return bool whether the run was asked for (`cron/now`) instead of triggered
	 */
	public function getManual(): bool
	{
		return $this->_manual;
	}

	/**
	 * @return bool whether an `onTaskStart` handler cancels the run
	 */
	public function getCancel(): bool
	{
		return $this->_cancel;
	}

	/**
	 * @param bool|string $value whether to skip the run; an `onTaskStart` handler sets it
	 * @return static the current object
	 */
	public function setCancel($value): static
	{
		$this->_cancel = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return ?string why the run was skipped, a `REASON_*` constant
	 */
	public function getReason(): ?string
	{
		return $this->_reason;
	}

	/**
	 * @param ?string $value why the run was skipped, a `REASON_*` constant
	 * @return static the current object
	 */
	public function setReason(?string $value): static
	{
		$this->_reason = $value;
		return $this;
	}

	/**
	 * @return ?string the result of the run, a `TCronModule::RESULT_*` constant
	 */
	public function getResult(): ?string
	{
		return $this->_result;
	}

	/**
	 * @param ?string $value the result of the run
	 * @return static the current object
	 */
	public function setResult(?string $value): static
	{
		$this->_result = $value;
		return $this;
	}

	/**
	 * @return ?float the seconds the run took
	 */
	public function getDuration(): ?float
	{
		return $this->_duration;
	}

	/**
	 * @param ?float $value the seconds the run took
	 * @return static the current object
	 */
	public function setDuration(?float $value): static
	{
		$this->_duration = $value;
		return $this;
	}

	/**
	 * @return ?\Throwable the exception a task in the cron process threw; null for a Process task's child
	 */
	public function getException(): ?\Throwable
	{
		return $this->_exception;
	}

	/**
	 * @param ?\Throwable $value the exception the task threw
	 * @return static the current object
	 */
	public function setException(?\Throwable $value): static
	{
		$this->_exception = $value;
		return $this;
	}

	/**
	 * @return string[] the names of the tasks that ran before this one in a chain, first to last; empty for a run not chained
	 */
	public function getChain(): array
	{
		return $this->_chain;
	}

	/**
	 * @param string[] $value the names of the tasks that ran before this one in a chain
	 * @return static the current object
	 */
	public function setChain(array $value): static
	{
		$this->_chain = $value;
		return $this;
	}

	/**
	 * @return ?int the trigger the run serves, after the task's Jitter; a missed trigger for a caught-up run
	 *   ({@see \Prado\Util\Cron\TCronTask::getCatchUp() CatchUp}); null for a manual run
	 */
	public function getTrigger(): ?int
	{
		return $this->_trigger;
	}

	/**
	 * @param ?int $value the trigger the run serves
	 * @return static the current object
	 */
	public function setTrigger(?int $value): static
	{
		$this->_trigger = $value;
		return $this;
	}

	/**
	 * @return int the attempt of the run, from 1; above 1 after a retry
	 */
	public function getAttempt(): int
	{
		return $this->_attempt;
	}

	/**
	 * @param int $value the attempt of the run
	 * @return static the current object
	 */
	public function setAttempt(int $value): static
	{
		$this->_attempt = $value;
		return $this;
	}

	/**
	 * @return ?string the point of the run being pinged, a `TCronTask::PING_*` constant
	 */
	public function getPing(): ?string
	{
		return $this->_ping;
	}

	/**
	 * @param ?string $value the point of the run being pinged
	 * @return static the current object
	 */
	public function setPing(?string $value): static
	{
		$this->_ping = $value;
		return $this;
	}

	/**
	 * @return ?string the URL to ping
	 */
	public function getUrl(): ?string
	{
		return $this->_url;
	}

	/**
	 * @param ?string $value the URL to ping
	 * @return static the current object
	 */
	public function setUrl(?string $value): static
	{
		$this->_url = $value;
		return $this;
	}

	/**
	 * @return bool whether an `onTaskPing` handler delivered the ping, so the module does not send it
	 */
	public function getHandled(): bool
	{
		return $this->_handled;
	}

	/**
	 * @param bool|string $value whether a handler delivered the ping
	 * @return static the current object
	 */
	public function setHandled($value): static
	{
		$this->_handled = TPropertyValue::ensureBoolean($value);
		return $this;
	}
}
