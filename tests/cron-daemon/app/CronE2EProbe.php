<?php

/**
 * CronE2EProbe class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\TModule;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Util\Cron\Wake\TCronWakeSocket;

/**
 * CronE2EProbe records the cron daemon's start and end, through the global `fx` events, and
 * each run of a {@see CronE2ETask}, as JSON lines in {@see setLogFile LogFile}.  It offers the
 * `probe` task, so `cron/add <name> probe <schedule>` adds a {@see CronE2ETask}.
 */
class CronE2EProbe extends TModule
{
	/** @var string the JSON-lines log file */
	private string $_logFile = '';

	/**
	 * @return string the JSON-lines log file
	 */
	public function getLogFile(): string
	{
		return $this->_logFile;
	}

	/**
	 * @param string $value the JSON-lines log file
	 */
	public function setLogFile($value)
	{
		$this->_logFile = (string) $value;
	}

	/**
	 * Appends an entry with the process ID and time.
	 * @param array $entry the entry fields
	 */
	public function record(array $entry): void
	{
		$entry += ['pid' => getmypid(), 't' => microtime(true)];
		file_put_contents($this->getLogFile(), json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
	}

	/**
	 * Records the daemon start with its wake transport.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronDaemonEventParameter $param the daemon event parameter
	 */
	public function fxCronDaemonStart($sender, $param)
	{
		$wake = $sender->getWakeTransport();
		$this->record([
			'e' => 'start',
			'wake' => $wake === null ? null : $wake::class,
			'transport' => $wake instanceof TCronWakeSocket ? $wake->getTransport() : null,
			'watching' => $param->getWatching(),
			'php' => PHP_VERSION,
			'os' => PHP_OS_FAMILY,
		]);
	}

	/**
	 * Records the daemon end with its reason.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronDaemonEventParameter $param the daemon event parameter
	 */
	public function fxCronDaemonEnd($sender, $param)
	{
		$this->record(['e' => 'end', 'reason' => $param->getReason()]);
	}

	/**
	 * Records the start of a sweep.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronSweepEventParameter $param the sweep
	 */
	public function fxCronSweepStart($sender, $param)
	{
		$this->record(['e' => 'sweep', 'count' => $param->getCount()]);
	}

	/**
	 * Records the start of a task run.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronTaskEventParameter $param the run
	 */
	public function fxCronTaskStart($sender, $param)
	{
		$this->record(['e' => 'tstart', 'task' => $param->getTask()->getName(), 'chain' => $param->getChain(), 'manual' => $param->getManual(), 'trigger' => $param->getTrigger()]);
	}

	/**
	 * Records the end of a task run with its result.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronTaskEventParameter $param the run
	 */
	public function fxCronTaskEnd($sender, $param)
	{
		$this->record(['e' => 'tend', 'task' => $param->getTask()->getName(), 'result' => $param->getResult(), 'attempt' => $param->getAttempt(), 'duration' => $param->getDuration()]);
	}

	/**
	 * Records a retried attempt.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronTaskEventParameter $param the attempt
	 */
	public function fxCronTaskRetry($sender, $param)
	{
		$this->record(['e' => 'tretry', 'task' => $param->getTask()->getName(), 'attempt' => $param->getAttempt()]);
	}

	/**
	 * Records an overdue run.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronTaskEventParameter $param the run
	 */
	public function fxCronTaskOverdue($sender, $param)
	{
		$this->record(['e' => 'toverdue', 'task' => $param->getTask()->getName(), 'duration' => $param->getDuration()]);
	}

	/**
	 * Records a skipped trigger with its reason.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronTaskEventParameter $param the trigger
	 */
	public function fxCronTaskSkipped($sender, $param)
	{
		$this->record(['e' => 'tskip', 'task' => $param->getTask()->getName(), 'reason' => $param->getReason()]);
	}

	/**
	 * Delivers the pings to the host `probe.invalid` itself, as a webhook module would, and records them.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param \Prado\Util\Cron\TCronTaskEventParameter $param the ping
	 */
	public function fxCronTaskPing($sender, $param)
	{
		if (parse_url((string) $param->getUrl(), PHP_URL_HOST) === 'probe.invalid') {
			$param->setHandled(true);
			$this->record(['e' => 'tping', 'task' => $param->getTask()->getName(), 'ping' => $param->getPing(), 'result' => $param->getResult()]);
		}
	}

	/**
	 * Offers the `probe` task to `cron/add`.
	 * @param \Prado\Util\Cron\TCronModule $sender the cron module
	 * @param mixed $param unused
	 * @return TCronTaskInfo the task information
	 */
	public function fxGetCronTaskInfos($sender, $param)
	{
		return new TCronTaskInfo('probe', 'Application.CronE2ETask', $this->getID(), 'E2E probe', 'Records its run.');
	}
}
