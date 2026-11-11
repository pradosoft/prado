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
